import { useState, useEffect } from 'react';

export default function FieldValidation({ value, rules = [], className = '' }) {
    const [isValid, setIsValid] = useState(null);
    const [validationMessage, setValidationMessage] = useState('');

    useEffect(() => {
        validateField();
    }, [value]);

    const validateField = () => {
        if (!value) {
            setIsValid(null);
            setValidationMessage('');
            return;
        }

        for (const rule of rules) {
            const result = rule(value);
            if (!result.valid) {
                setIsValid(false);
                setValidationMessage(result.message);
                return;
            }
        }

        setIsValid(true);
        setValidationMessage('');
    };

    if (isValid === null) return null;

    return (
        <div className={`flex items-center text-sm mt-1 ${className}`}>
            {isValid ? (
                <div className="flex items-center text-green-600">
                    <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                    </svg>
                    <span>Válido</span>
                </div>
            ) : (
                <div className="flex items-center text-red-600">
                    <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                    </svg>
                    <span>{validationMessage}</span>
                </div>
            )}
        </div>
    );
}

// Reglas de validación comunes
export const validationRules = {
    minLength: (min) => (value) => ({
        valid: value.length >= min,
        message: `Debe tener al menos ${min} caracteres`
    }),
    
    maxLength: (max) => (value) => ({
        valid: value.length <= max,
        message: `No debe exceder ${max} caracteres`
    }),
    
    minWords: (min) => (value) => {
        const words = value.trim().split(/\s+/).filter(word => word.length > 0);
        return {
            valid: words.length >= min,
            message: `Debe tener al menos ${min} palabras`
        };
    },
    
    required: (value) => ({
        valid: value && value.trim().length > 0,
        message: 'Este campo es requerido'
    }),
    
    minArrayLength: (min) => (value) => ({
        valid: Array.isArray(value) && value.length >= min,
        message: `Debe seleccionar al menos ${min} opción${min > 1 ? 'es' : ''}`
    })
};