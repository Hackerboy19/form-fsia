<?php
declare(strict_types=1);

/**
 * Allow-list HTML sanitizer for article bodies coming from the rich text editor.
 *
 * Even though only admins can write articles, the HTML is printed as-is on the
 * public site, so anything that can run script (script/style/iframe tags, on*
 * attributes, javascript: URLs) is removed here. Unknown tags are unwrapped
 * (their text is kept); dangerous ones are dropped with their contents.
 */
function sanitize_article_html(string $html): string
{
    if (trim($html) === '') {
        return '';
    }

    $allowed = [
        'p' => [], 'br' => [], 'hr' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [],
        'u' => [], 's' => [], 'blockquote' => [], 'ul' => [], 'ol' => [], 'li' => [],
        'h2' => [], 'h3' => [], 'h4' => [], 'code' => [], 'pre' => [],
        'figure' => [], 'figcaption' => [],
        'a'   => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
    ];
    $dropWithContent = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button',
                        'textarea', 'select', 'svg', 'math', 'template', 'noscript', 'link', 'meta', 'base'];

    $doc = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    // The wrapper div plus the XML encoding hint keep UTF-8 text intact.
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $root = $doc->getElementById('__root');
    if (!$root) {
        return '';
    }

    $walk = function (DOMNode $node) use (&$walk, $allowed, $dropWithContent): void {
        // Iterate over a snapshot: children are removed/unwrapped as we go.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);

            if (in_array($tag, $dropWithContent, true)) {
                $node->removeChild($child);
                continue;
            }

            $walk($child);

            if (!isset($allowed[$tag])) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                if (!in_array($name, $allowed[$tag], true)) {
                    $child->removeAttribute($attr->name);
                    continue;
                }
                if (in_array($name, ['href', 'src'], true)) {
                    $v = trim($attr->value);
                    $ok = $name === 'href'
                        ? (is_safe_url($v) || preg_match('/^(mailto|tel):/i', $v) || str_starts_with($v, '#'))
                        : is_safe_url($v);
                    if (!$ok) {
                        $child->removeAttribute($attr->name);
                    }
                }
                if ($name === 'target' && $attr->value !== '_blank') {
                    $child->removeAttribute('target');
                }
                if (in_array($name, ['width', 'height'], true) && !ctype_digit($attr->value)) {
                    $child->removeAttribute($attr->name);
                }
            }
            if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }
            if ($tag === 'img' && !$child->hasAttribute('src')) {
                $node->removeChild($child);
            }
        }
    };
    $walk($root);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return trim($out);
}
