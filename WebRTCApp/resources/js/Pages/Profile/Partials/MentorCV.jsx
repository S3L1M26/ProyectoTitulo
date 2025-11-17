import { useForm, router } from '@inertiajs/react';
import { useState, useEffect, useRef, memo } from 'react';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

const MentorCV = memo(function MentorCV({ cv, cvVerified, className = '' }) {
    const [uploading, setUploading] = useState(false);
    const [uploadProgress, setUploadProgress] = useState(0);
    const [isPolling, setIsPolling] = useState(false);
    const pollingIntervalRef = useRef(null);
    const fileInputRef = useRef(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        cv: null,
        is_public: true,
    });

    // Auto-polling cuando el estado es 'pending'
    useEffect(() => {
        if (cv?.status === 'pending' && !isPolling) {
            startPolling();
        }
        
        return () => stopPolling();
    }, [cv?.status]);

    const startPolling = () => {
        if (pollingIntervalRef.current) return;
        
        setIsPolling(true);
        pollingIntervalRef.current = setInterval(() => {
            // OPCIÓN SIMPLE: Reload completo de la página (más rápido y confiable que partial reload)
            // Similar a un refresh manual del navegador, pero preservando scroll
            router.reload({ 
                preserveScroll: true,
                // No usar 'only' - recargar todo para asegurar sincronización
            });
        }, 3000); // Cada 3 segundos
    };

    const stopPolling = () => {
        if (pollingIntervalRef.current) {
            clearInterval(pollingIntervalRef.current);
            pollingIntervalRef.current = null;
            setIsPolling(false);
        }
    };

    // Detener polling si el estado cambia de pending
    useEffect(() => {
        if (cv?.status && cv.status !== 'pending') {
            stopPolling();
        }
    }, [cv?.status]);

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            // Validar tamaño (10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('El archivo no debe superar los 10MB');
                e.target.value = '';
                return;
            }
            
            // Validar tipo
            if (file.type !== 'application/pdf') {
                alert('Solo se permiten archivos PDF');
                e.target.value = '';
                return;
            }
            
            setData('cv', file);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        if (!data.cv) {
            alert('Por favor selecciona un archivo PDF');
            return;
        }

        setUploading(true);
        setUploadProgress(0);

        // Simular progreso durante el upload
        const progressInterval = setInterval(() => {
            setUploadProgress(prev => {
                if (prev >= 90) {
                    clearInterval(progressInterval);
                    return 90;
                }
                return prev + 10;
            });
        }, 200);

        post(route('mentor.cv.upload'), {
            preserveScroll: true,
            onSuccess: () => {
                clearInterval(progressInterval);
                setUploadProgress(100);
                setTimeout(() => {
                    setUploading(false);
                    setUploadProgress(0);
                    reset();
                    if (fileInputRef.current) fileInputRef.current.value = '';
                    startPolling();
                }, 500);
            },
            onError: () => {
                clearInterval(progressInterval);
                setUploading(false);
                setUploadProgress(0);
            },
        });
    };

    const handleToggleVisibility = () => {
        // TODO: Implementar endpoint para cambiar visibilidad
        router.post(route('mentor.cv.toggle-visibility'), {
            is_public: !cv.is_public,
        }, {
            preserveScroll: true,
        });
    };

    const getStatusBadge = () => {
        if (!cv) {
            return (
                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700">
                    <svg className="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    No subido
                </span>
            );
        }

        const badges = {
            pending: (
                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 animate-pulse">
                    <svg className="animate-spin w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Procesando...
                </span>
            ),
            approved: (
                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    <svg className="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ✓ CV Verificado
                </span>
            ),
            rejected: (
                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                    <svg className="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ✗ CV Rechazado
                </span>
            ),
            invalid: (
                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                    <svg className="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    ⚠ Archivo No Válido
                </span>
            ),
        };

        return badges[cv.status] || badges.invalid;
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">
                    Curriculum Vitae (CV)
                </h2>
                <p className="mt-1 text-sm text-gray-600">
                    Sube tu CV para verificar tu experiencia profesional y ofrecer mentorías.
                </p>
            </header>

            <div className="mt-6 space-y-6">
                {/* Banner de advertencia si CV no verificado */}
                {!cvVerified && (
                    <div className="p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                        <div className="flex">
                            <div className="flex-shrink-0">
                                <svg className="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div className="ml-3">
                                <p className="text-sm font-medium text-yellow-800">Requerido para ofrecer mentorías</p>
                                <p className="mt-1 text-sm text-yellow-700">
                                    Debes verificar tu CV antes de poder activar tu disponibilidad como mentor.
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Estado del CV */}
                <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p className="text-sm font-medium text-gray-700">Estado actual:</p>
                        <div className="mt-2">{getStatusBadge()}</div>
                        {cv?.created_at && (
                            <p className="mt-1 text-xs text-gray-500">
                                Subido {new Date(cv.created_at).toLocaleDateString('es-ES')}
                            </p>
                        )}
                    </div>
                    
                    {cv?.keyword_score !== null && cv?.keyword_score !== undefined && (
                        <div className="text-right">
                            <p className="text-sm font-medium text-gray-700">Puntuación:</p>
                            <p className="text-2xl font-bold text-gray-900">{cv.keyword_score}/170</p>
                            <p className="text-xs text-gray-500">Mínimo: 50</p>
                        </div>
                    )}
                </div>

                {/* Mensaje de rechazo */}
                {cv?.status === 'rejected' && cv?.rejection_reason && (
                    <div className="p-4 bg-red-50 border-l-4 border-red-400 rounded">
                        <div className="flex">
                            <div className="flex-shrink-0">
                                <svg className="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div className="ml-3">
                                <p className="text-sm font-medium text-red-800">Razón del rechazo:</p>
                                <p className="mt-1 text-sm text-red-700">{cv.rejection_reason}</p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Mensaje de procesamiento */}
                {cv?.status === 'pending' && (
                    <div className="p-4 bg-blue-50 border-l-4 border-blue-400 rounded">
                        <div className="flex">
                            <div className="flex-shrink-0">
                                <svg className="animate-spin h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <div className="ml-3">
                                <p className="text-sm text-blue-700">
                                    Tu CV está siendo procesado y validado. Esto puede tomar unos minutos. La página se actualizará automáticamente.
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Formulario de upload */}
                {(!cv || cv.status === 'rejected' || cv.status === 'invalid') && (
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <InputLabel htmlFor="cv" value="Seleccionar CV (PDF, máx. 10MB)" />
                            <input
                                ref={fileInputRef}
                                id="cv"
                                type="file"
                                accept=".pdf"
                                onChange={handleFileChange}
                                disabled={uploading || processing}
                                className="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            />
                            {errors.cv && (
                                <p className="mt-2 text-sm text-red-600">{errors.cv}</p>
                            )}
                        </div>

                        {/* Toggle de visibilidad pública */}
                        <div className="flex items-center">
                            <input
                                id="is_public"
                                type="checkbox"
                                checked={data.is_public}
                                onChange={(e) => setData('is_public', e.target.checked)}
                                disabled={uploading || processing}
                                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50"
                            />
                            <label htmlFor="is_public" className="ml-2 block text-sm text-gray-700">
                                Hacer mi CV visible públicamente (estudiantes podrán verlo)
                            </label>
                        </div>

                        {/* Progress bar */}
                        {uploading && (
                            <div className="space-y-2">
                                <div className="flex justify-between text-sm text-gray-600">
                                    <span>Subiendo...</span>
                                    <span>{uploadProgress}%</span>
                                </div>
                                <div className="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                    <div 
                                        className="bg-blue-600 h-2 rounded-full transition-all duration-300 ease-out"
                                        style={{ width: `${uploadProgress}%` }}
                                    ></div>
                                </div>
                            </div>
                        )}

                        <div className="flex items-center gap-4">
                            <PrimaryButton disabled={uploading || processing || !data.cv}>
                                {uploading ? 'Subiendo...' : (cv ? 'Reenviar CV' : 'Subir CV')}
                            </PrimaryButton>

                            {data.cv && !uploading && (
                                <SecondaryButton
                                    type="button"
                                    onClick={() => {
                                        reset();
                                        if (fileInputRef.current) fileInputRef.current.value = '';
                                    }}
                                >
                                    Cancelar
                                </SecondaryButton>
                            )}
                        </div>
                    </form>
                )}

                {/* Información del CV aprobado */}
                {cv?.status === 'approved' && (
                    <div className="space-y-4">
                        <div className="p-4 bg-green-50 border-l-4 border-green-400 rounded">
                            <div className="flex items-start">
                                <div className="flex-shrink-0">
                                    <svg className="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div className="ml-3 flex-1">
                                    <p className="text-sm font-medium text-green-800">
                                        ¡Tu CV ha sido verificado exitosamente!
                                    </p>
                                    <p className="mt-1 text-sm text-green-700">
                                        Ahora puedes activar tu disponibilidad para ofrecer mentorías.
                                    </p>
                                    {cv?.processed_at && (
                                        <p className="mt-1 text-xs text-green-600">
                                            Verificado {new Date(cv.processed_at).toLocaleDateString('es-ES')}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Toggle de visibilidad pública */}
                        <div className="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg">
                            <div className="flex-1">
                                <p className="text-sm font-medium text-gray-900">Visibilidad del CV</p>
                                <p className="text-xs text-gray-500 mt-1">
                                    {cv.is_public 
                                        ? 'Tu CV es visible para los estudiantes' 
                                        : 'Tu CV está oculto (solo tú puedes verlo)'}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={handleToggleVisibility}
                                className={`${
                                    cv.is_public ? 'bg-blue-600' : 'bg-gray-200'
                                } relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2`}
                            >
                                <span
                                    className={`${
                                        cv.is_public ? 'translate-x-5' : 'translate-x-0'
                                    } pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out`}
                                />
                            </button>
                        </div>

                        {/* Link para ver CV */}
                        {cv.is_public && (
                            <a
                                href={route('mentor.cv.show', { mentor: cv.user_id })}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                            >
                                <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Vista Previa de CV
                            </a>
                        )}
                    </div>
                )}
            </div>
        </section>
    );
});

export default MentorCV;
