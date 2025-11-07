import React, { useState } from 'react';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import { useForm as useInertiaForm } from '@inertiajs/react';
import { toast } from 'react-toastify';

export default function ConfirmarMentoriaModal({ isOpen, onClose, solicitud }) {
    const [preview, setPreview] = useState(null);
    const [apiError, setApiError] = useState('');
    const [loadingPreview, setLoadingPreview] = useState(false);
    const [validationErrors, setValidationErrors] = useState({});

    // useInertiaForm maneja tanto el estado como el envío
    const { data: formData, setData, post, processing, reset } = useInertiaForm({
        fecha: '',
        hora: '',
        duracion_minutos: 60,
        topic: '',
    });

    // Reset form cuando se abre el modal (solo cuando isOpen cambia de false a true)
    React.useEffect(() => {
        if (isOpen && solicitud) {
            reset();
            setData({
                fecha: '',
                hora: '',
                duracion_minutos: 60,
                topic: `Mentoría con ${solicitud.estudiante?.name || ''}`.trim(),
            });
            setPreview(null);
            setApiError('');
            setValidationErrors({});
        }
    }, [isOpen]); // Removido 'solicitud' de las dependencias para evitar resets continuos

    const handleFieldChange = (field, value) => {
        console.log(`Field changed: ${field} = "${value}"`);
        
        // Actualizar usando setData de useInertiaForm
        setData(field, value);
        console.log('Updated formData:', { ...formData, [field]: value });
        
        // Limpiar error del campo cuando el usuario empieza a escribir
        if (validationErrors[field]) {
            setValidationErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[field];
                return newErrors;
            });
        }
    };

    const onGeneratePreview = async () => {
        // Sin validar campos: la preview es opcional.
        if (!formData.fecha || !formData.hora) return;

        setApiError('');
        setPreview(null);
        setLoadingPreview(true);
        
        try {
            const now = new Date();
            const combined = new Date(`${formData.fecha}T${formData.hora}:00`);
            
            if (isNaN(combined.getTime()) || combined < now) {
                setApiError('Para generar la vista previa, selecciona una fecha/hora futura.');
                setLoadingPreview(false);
                return;
            }

            const res = await window.axios.post(route('api.mentorias.generar-enlace'), {
                fecha: formData.fecha,
                hora: formData.hora,
                duracion_minutos: Number(formData.duracion_minutos),
                topic: formData.topic,
            });
            
            setPreview(res.data || null);
            toast.success('Vista previa del enlace de Zoom generada correctamente');
        } catch (e) {
            const errorMsg = e?.response?.data?.message || 'No se pudo generar el enlace de preview.';
            setApiError(errorMsg);
            toast.error(errorMsg);
        } finally {
            setLoadingPreview(false);
        }
    };

    const validateForm = () => {
        console.log('=== VALIDATING FORM ===');
        console.log('Current formData:', formData);
        
        const errors = {};
        
        if (!formData.fecha) {
            console.log('❌ Fecha is empty:', formData.fecha);
            errors.fecha = 'La fecha es requerida';
        } else {
            console.log('✓ Fecha is valid:', formData.fecha);
        }
        
        if (!formData.hora) {
            console.log('❌ Hora is empty:', formData.hora);
            errors.hora = 'La hora es requerida';
        } else {
            console.log('✓ Hora is valid:', formData.hora);
        }
        
        if (!formData.duracion_minutos || formData.duracion_minutos < 30) {
            console.log('❌ Duracion is invalid:', formData.duracion_minutos);
            errors.duracion_minutos = 'La duración mínima es 30 minutos';
        } else if (formData.duracion_minutos > 180) {
            console.log('❌ Duracion exceeds max:', formData.duracion_minutos);
            errors.duracion_minutos = 'La duración máxima es 180 minutos';
        } else {
            console.log('✓ Duracion is valid:', formData.duracion_minutos);
        }
        
        console.log('Validation errors:', errors);
        console.log('======================');
        
        setValidationErrors(errors);
        return Object.keys(errors).length === 0;
    };

    const onConfirm = (e) => {
        e.preventDefault();
        
        console.log('>>> CONFIRM CLICKED <<<');
        console.log('Form data before validation:', formData);
        
        if (!validateForm()) {
            setApiError('Por favor completa todos los campos requeridos.');
            return;
        }
        
        console.log('✓ Validation passed, submitting...');
        setApiError('');
        
        // ✅ Con useInertiaForm, solo pasamos la ruta - los datos ya están en el form
        post(route('mentorias.confirmar', { solicitud: solicitud.id }), {
            preserveScroll: true,
            onSuccess: () => {
                console.log('✓ SUCCESS - Mentoría confirmada');
                toast.success('¡Mentoría confirmada! Se ha enviado un correo al estudiante con los detalles de la reunión.');
                reset();
                setPreview(null);
                setApiError('');
                setValidationErrors({});
                onClose?.();
            },
            onError: (errs) => {
                console.log('❌ ERROR - Respuesta del servidor:', errs);
                toast.error('No se pudo confirmar la mentoría. Por favor verifica los datos.');
                const newErrors = {};
                if (errs?.fecha) newErrors.fecha = errs.fecha;
                if (errs?.hora) newErrors.hora = errs.hora;
                if (errs?.duracion_minutos) newErrors.duracion_minutos = errs.duracion_minutos;
                setValidationErrors(newErrors);
                setApiError(errs?.message || 'No se pudo confirmar la mentoría.');
            },
        });
    };

    const handleClose = () => {
        reset();
        setPreview(null);
        setApiError('');
        setValidationErrors({});
        onClose?.();
    };

    const minDate = new Date().toISOString().split('T')[0];

    if (!solicitud) return null;

    return (
        <Modal show={isOpen} onClose={handleClose} maxWidth="lg">
            <div className="p-6">
                <div className="flex items-start justify-between mb-4">
                    <h3 className="text-lg font-semibold text-gray-900">Confirmar Mentoría</h3>
                    <button 
                        type="button"
                        onClick={handleClose} 
                        className="text-gray-500 hover:text-gray-700 focus:outline-none"
                    >
                        ✖
                    </button>
                </div>

                <form onSubmit={onConfirm} className="space-y-4">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel htmlFor="fecha" value="Fecha" />
                            <TextInput
                                id="fecha"
                                type="date"
                                min={minDate}
                                value={formData.fecha}
                                onChange={(e) => handleFieldChange('fecha', e.target.value)}
                                className="mt-1 block w-full"
                            />
                            <InputError message={validationErrors.fecha} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="hora" value="Hora" />
                            <TextInput
                                id="hora"
                                type="time"
                                value={formData.hora}
                                onChange={(e) => handleFieldChange('hora', e.target.value)}
                                className="mt-1 block w-full"
                            />
                            <InputError message={validationErrors.hora} className="mt-2" />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel htmlFor="duracion" value="Duración (minutos)" />
                            <TextInput
                                id="duracion"
                                type="number"
                                min="30"
                                max="180"
                                step="15"
                                value={formData.duracion_minutos}
                                onChange={(e) => handleFieldChange('duracion_minutos', e.target.value)}
                                className="mt-1 block w-full"
                            />
                            <InputError message={validationErrors.duracion_minutos} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="topic" value="Tema (opcional)" />
                            <TextInput
                                id="topic"
                                type="text"
                                placeholder="Ej: Revisión de código"
                                value={formData.topic}
                                onChange={(e) => handleFieldChange('topic', e.target.value)}
                                className="mt-1 block w-full"
                            />
                        </div>
                    </div>

                    {/* Preview del enlace Zoom (opcional) */}
                    {preview?.join_url && (
                        <div className="bg-green-50 border border-green-200 rounded-md p-3">
                            <InputLabel htmlFor="join_url" value="Vista previa del enlace Zoom" />
                            <TextInput 
                                id="join_url" 
                                type="text" 
                                className="mt-1 block w-full bg-white" 
                                value={preview.join_url} 
                                readOnly 
                            />
                            <p className="text-xs text-green-700 mt-1">
                                ✓ Enlace generado correctamente. Se guardará al confirmar.
                            </p>
                        </div>
                    )}

                    {apiError && (
                        <div className="bg-red-50 border border-red-200 rounded-md p-3">
                            <p className="text-sm text-red-600">{apiError}</p>
                        </div>
                    )}

                    <div className="flex items-center justify-between gap-3 pt-4 border-t">
                        <SecondaryButton 
                            type="button"
                            onClick={onGeneratePreview} 
                            disabled={loadingPreview || !formData.fecha || !formData.hora}
                        >
                            {loadingPreview ? 'Generando…' : '🔗 Vista previa'}
                        </SecondaryButton>
                        
                        <PrimaryButton 
                            type="submit"
                            disabled={processing}
                        >
                            {processing ? 'Confirmando…' : '✓ Confirmar y notificar'}
                        </PrimaryButton>
                    </div>

                    <p className="text-xs text-gray-500 text-center">
                        La vista previa es opcional. Al confirmar se creará el enlace de Zoom automáticamente.
                    </p>
                </form>
            </div>
        </Modal>
    );
}
