import { createPortal } from 'react-dom';

export default function Dialog({ open, title, onClose, children }) {
    if (!open || typeof document === 'undefined') {
        return null;
    }

    return createPortal(
        <div className="fixed inset-0 z-50 flex items-center justify-center px-4">
            <button type="button" className="absolute inset-0 z-0 bg-black/40" aria-label="Close" onClick={onClose} />
            <div className="relative z-10 max-h-[80vh] w-full max-w-md overflow-y-auto rounded-xl border border-line bg-white p-5">
                <div className="mb-4 flex items-center justify-between gap-3">
                    <h2 className="text-base font-semibold">{title}</h2>
                    <button type="button" onClick={onClose} className="text-xs font-semibold text-muted">
                        Close
                    </button>
                </div>
                {children}
            </div>
        </div>,
        document.body,
    );
}
