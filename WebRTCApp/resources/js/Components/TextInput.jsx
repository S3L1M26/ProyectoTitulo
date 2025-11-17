import { forwardRef, useEffect, useRef } from 'react';

// Componente que forwarde correctamente el ref para react-hook-form
export default forwardRef(function TextInput(
    { type = 'text', className = '', isFocused = false, ...props },
    ref,
) {
    const localRef = useRef(null);

    useEffect(() => {
        if (isFocused && localRef.current) {
            localRef.current.focus();
        }
    }, [isFocused]);

    // Manejar ambas refs: la local (para focus) y la externa (de react-hook-form)
    const handleRef = (element) => {
        // Guardar referencia local
        localRef.current = element;
        
        // Pasar al ref externo (react-hook-form)
        if (ref) {
            if (typeof ref === 'function') {
                ref(element);
            } else {
                ref.current = element;
            }
        }
    };

    return (
        <input
            {...props}
            type={type}
            className={
                'rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ' +
                className
            }
            ref={handleRef}
        />
    );
});
