import { useId } from 'react';

/** Label + input + character counter + server-side error, in one place. */
export function Field({ label, hint, error, max, value = '', type = 'text', rows = 4, recommended, onChange, ...rest }) {
  const id = useId();
  const length = String(value ?? '').length;
  const over = max && length > max;
  const longish = recommended && length > recommended;
  const Tag = type === 'textarea' ? 'textarea' : 'input';

  return (
    <div>
      <div className="flex items-baseline justify-between gap-3">
        <label htmlFor={id} className="label">{label}</label>
        {max ? (
          <span className={`text-[11px] font-semibold ${over ? 'text-red-600' : longish ? 'text-amber-600' : 'text-slate-400'}`}>
            {length}/{recommended || max}
          </span>
        ) : null}
      </div>
      <Tag
        id={id}
        className={`input ${error ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : ''}`}
        value={value ?? ''}
        onChange={(e) => onChange(e.target.value)}
        aria-invalid={Boolean(error)}
        aria-describedby={error || hint ? `${id}-help` : undefined}
        {...(type === 'textarea' ? { rows } : { type })}
        {...rest}
      />
      {(error || hint) && (
        <p id={`${id}-help`} className={`mt-1 text-xs ${error ? 'text-red-600' : 'text-slate-500'}`}>{error || hint}</p>
      )}
    </div>
  );
}

export function SelectField({ label, value, options, onChange, error }) {
  const id = useId();
  return (
    <div>
      <label htmlFor={id} className="label">{label}</label>
      <select id={id} className="input" value={value} onChange={(e) => onChange(e.target.value)}>
        {options.map((o) => (
          <option key={o.value ?? o} value={o.value ?? o}>{o.label ?? o}</option>
        ))}
      </select>
      {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
    </div>
  );
}

/** Accessible switch. */
export function Toggle({ checked, onChange, label, description, disabled }) {
  return (
    <label className={`flex items-start gap-3 ${disabled ? 'opacity-50' : 'cursor-pointer'}`}>
      <button
        type="button"
        role="switch"
        aria-checked={checked}
        disabled={disabled}
        onClick={() => onChange(!checked)}
        className={`relative mt-0.5 inline-flex h-6 w-11 flex-none rounded-full transition ${checked ? 'bg-gold' : 'bg-slate-300'}`}
      >
        <span className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition ${checked ? 'left-[22px]' : 'left-0.5'}`} />
      </button>
      <span>
        <span className="block text-sm font-semibold">{label}</span>
        {description && <span className="block text-xs text-slate-500">{description}</span>}
      </span>
    </label>
  );
}
