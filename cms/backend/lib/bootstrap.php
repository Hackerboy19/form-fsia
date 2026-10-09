<?php
declare(strict_types=1);

/*
 * Included first by every file in api/. Sets JSON + security headers, answers
 * CORS preflights, turns uncaught errors into JSON, and provides the request /
 * validation helpers the endpoints share.
 */

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

set_exception_handler(function (Throwable $e): void {
    error_log('[fsia-cms] ' . $e);
    $body = ['error' => 'Server error'];
    if (!empty(cms_config()['debug'])) {
        $body['detail'] = $e->getMessage();
    }
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode($body);
});

/** Thrown by the helpers below; caught in respond() and turned into a 4xx. */
final class ApiError extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 400, public readonly array $fields = [])
    {
        parent::__construct($message);
    }
}

// --- CORS ------------------------------------------------------------------

(function (): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '' && in_array($origin, cms_config()['allowed_origins'] ?? [], true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Admin-Token, X-HTTP-Method-Override');
        header('Access-Control-Max-Age: 600');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
})();

// --- Request ---------------------------------------------------------------

/**
 * The effective method. Some shared hosts block PUT/DELETE, so a POST may carry
 * the real method in X-HTTP-Method-Override.
 */
function request_method(): string
{
    $m = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($m === 'POST') {
        $o = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '');
        if (in_array($o, ['PUT', 'DELETE'], true)) {
            return $o;
        }
    }
    return $m;
}

/** Decoded JSON request body; must be an object. */
function json_body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if (strlen($raw) > 2 * 1024 * 1024) {
        throw new ApiError('Request body too large', 413);
    }
    $data = json_decode($raw === '' ? '{}' : $raw, true);
    if (!is_array($data) || array_is_list($data) && $data !== []) {
        throw new ApiError('Body must be a JSON object');
    }
    return $data;
}

function query_param(string $key): ?string
{
    $v = $_GET[$key] ?? null;
    return is_string($v) ? trim($v) : null;
}

function query_id(): int
{
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) {
        throw new ApiError('A positive integer ?id= is required');
    }
    return $id;
}

// --- Auth ------------------------------------------------------------------

function provided_token(): string
{
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(\S+)$/i', $h, $m)) {
        return $m[1];
    }
    // Some Apache/CGI setups drop Authorization; accept a dedicated header too.
    return (string) ($_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '');
}

function is_admin(): bool
{
    $expected = (string) (cms_config()['admin_token'] ?? '');
    $given = provided_token();
    // A short or placeholder token would make the whole API writable; refuse it.
    if (strlen($expected) < 32 || str_starts_with($expected, 'replace-with')) {
        return false;
    }
    return $given !== '' && hash_equals($expected, $given);
}

function require_admin(): void
{
    if (!is_admin()) {
        usleep(300000); // slows down token guessing
        throw new ApiError('Admin token missing or invalid', 401);
    }
}

// --- Responses -------------------------------------------------------------

function send_json(mixed $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}

/**
 * Runs an endpoint's handler and maps ApiError to a JSON error response.
 * Handlers return [data, status] or just data.
 */
function respond(callable $handler): void
{
    try {
        $result = $handler();
        [$data, $status] = is_array($result) && array_key_exists('__status', $result)
            ? [$result['data'], $result['__status']]
            : [$result, 200];
        send_json($data, $status);
    } catch (ApiError $e) {
        $body = ['error' => $e->getMessage()];
        if ($e->fields) {
            $body['fields'] = $e->fields;
        }
        send_json($body, $e->status);
    }
}

function created(mixed $data): array
{
    return ['__status' => 201, 'data' => $data];
}

// --- Validation ------------------------------------------------------------

/** Collects field errors so one response can report all of them. */
final class Validator
{
    private array $errors = [];

    public function __construct(private array $data) {}

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function string(string $key, int $max, bool $required = false, string $default = ''): string
    {
        $v = $this->data[$key] ?? $default;
        if (!is_string($v) && !is_int($v) && !is_float($v)) {
            $this->errors[$key] = 'Must be text';
            return $default;
        }
        $v = trim((string) $v);
        if ($required && $v === '') {
            $this->errors[$key] = 'Required';
        } elseif (mb_strlen($v) > $max) {
            $this->errors[$key] = "At most $max characters";
        }
        return $v;
    }

    /** Empty, an absolute http(s) URL, or a root-relative path like /uploads/x.jpg. */
    public function url(string $key, int $max = 512, bool $required = false): string
    {
        $v = $this->string($key, $max, $required);
        if ($v !== '' && !isset($this->errors[$key]) && !is_safe_url($v)) {
            $this->errors[$key] = 'Must be an http(s) URL or a path starting with /';
        }
        return $v;
    }

    public function enum(string $key, array $allowed, ?string $default = null): string
    {
        $v = $this->data[$key] ?? $default;
        if (!is_string($v) || !in_array($v, $allowed, true)) {
            $this->errors[$key] = 'Must be one of: ' . implode(', ', $allowed);
            return (string) $default;
        }
        return $v;
    }

    public function int(string $key, int $default = 0, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int
    {
        $v = $this->data[$key] ?? $default;
        $i = filter_var($v, FILTER_VALIDATE_INT);
        if ($i === false || $i < $min || $i > $max) {
            $this->errors[$key] = "Must be a whole number between $min and $max";
            return $default;
        }
        return $i;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $v = $this->data[$key] ?? $default;
        $b = filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($b === null) {
            $this->errors[$key] = 'Must be true or false';
            return $default;
        }
        return $b;
    }

    public function date(string $key, bool $required = false): ?string
    {
        $v = $this->string($key, 10, $required);
        if ($v === '') {
            return null;
        }
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $v);
        if (!$d || $d->format('Y-m-d') !== $v) {
            $this->errors[$key] = 'Must be a date like 2026-10-09';
            return null;
        }
        return $v;
    }

    public function addError(string $key, string $message): void
    {
        $this->errors[$key] = $message;
    }

    public function check(): void
    {
        if ($this->errors) {
            throw new ApiError('Validation failed', 422, $this->errors);
        }
    }
}

function is_safe_url(string $url): bool
{
    if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
        return !preg_match('/[\s<>"]/', $url);
    }
    return (bool) filter_var($url, FILTER_VALIDATE_URL)
        && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
}

/** Page slug from ?page=, limited to the pages listed in config. */
function managed_page(?string $slug): string
{
    if ($slug === null || $slug === '') {
        throw new ApiError('?page= is required');
    }
    if (!in_array($slug, cms_config()['pages'], true)) {
        throw new ApiError('Unknown page. Managed pages: ' . implode(', ', cms_config()['pages']), 404);
    }
    return $slug;
}
