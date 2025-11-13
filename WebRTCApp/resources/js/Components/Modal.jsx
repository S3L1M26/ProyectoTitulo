export default function Modal({
    children,
    show = false,
    maxWidth = '2xl',
    closeable = true,
    onClose = () => {},
}) {
    const close = () => {
        if (closeable) {
            onClose();
        }
    };

    const maxWidthClass = {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[maxWidth];

    if (!show) return null;

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            {/* Backdrop */}
            <div 
                className="fixed inset-0 bg-gray-500/75 transition-opacity" 
                aria-hidden="true"
                onClick={close}
            />

            {/* Container centrado */}
            <div className="fixed inset-0 z-10 overflow-y-auto pointer-events-none">
                <div className="flex min-h-full items-center justify-center p-4">
                    {/* Panel del modal */}
                    <div
                        className={`relative pointer-events-auto w-full rounded-lg bg-white text-left shadow-xl sm:my-8 ${maxWidthClass}`}
                        onClick={(e) => e.stopPropagation()}
                    >
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
