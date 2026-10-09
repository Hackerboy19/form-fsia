import { useEffect, useRef } from 'react';

/** Native <dialog> modal: focus trapping, Esc and backdrop come for free. */
export default function Modal({ open, title, onClose, children, footer, width = 'max-w-2xl' }) {
  const ref = useRef(null);

  useEffect(() => {
    const d = ref.current;
    if (!d) return;
    if (open && !d.open) d.showModal();
    if (!open && d.open) d.close();
  }, [open]);

  return (
    <dialog
      ref={ref}
      onClose={onClose}
      onClick={(e) => e.target === ref.current && onClose()}
      className={`w-[calc(100%-2rem)] ${width} rounded-2xl p-0 shadow-2xl backdrop:bg-navy/70 backdrop:backdrop-blur-sm`}
    >
      {open && (
        <div className="flex max-h-[90vh] flex-col">
          <header className="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 className="font-display text-lg font-bold">{title}</h2>
            <button type="button" onClick={onClose} className="rounded-lg px-2 text-2xl leading-none text-slate-400 hover:text-navy" aria-label="Close">×</button>
          </header>
          <div className="overflow-y-auto px-6 py-5">{children}</div>
          {footer && <footer className="flex justify-end gap-2 border-t border-slate-100 bg-cream px-6 py-4">{footer}</footer>}
        </div>
      )}
    </dialog>
  );
}
