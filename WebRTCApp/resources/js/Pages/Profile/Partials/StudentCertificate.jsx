import { useForm, router } from '@inertiajs/react';
import { useState, useEffect, useRef, memo } from 'react';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

const StudentCertificate = memo(function StudentCertificate({ certificate, className = '' }) {
    const [uploading, setUploading] = useState(false);
    const [uploadProgress, setUploadProgress] = useState(0);
    const [isPolling, setIsPolling] = useState(false);
    const pollingIntervalRef = useRef(null);
    const fileInputRef = useRef(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        certificate: null,
    });

    // Auto-polling cuando el estado es 'pending'
    useEffect(() => {
        if (certificate?.status === 'pending' && !isPolling) {
            startPolling();
        }
        
        return () => stopPolling();
    }, [certificate?.status]);

    const startPolling = () => {
        if (pollingIntervalRef.current) return; // Ya está polling
        
        setIsPolling(true);
        pollingIntervalRef.current = setInterval(() => {
            // Recargar la página para obtener estado actualizado (Inertia hace esto eficientemente)
            router.reload({ only: ['certificate'], preserveScroll: true });
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
        if (certificate?.status && certificate.status !== 'pending') {
            stopPolling();
        }
    }, [certificate?.status]);

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            // Validar tamaño (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('El archivo no debe superar los 5MB');
                e.target.value = '';
                return;
            }
            
            // Validar tipo
            if (file.type !== 'application/pdf') {
                alert('Solo se permiten archivos PDF');
                e.target.value = '';
                return;
            }
            
            setData('certificate', file);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        if (!data.certificate) {
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

        post(route('student.certificate.upload'), {
            preserveScroll: true,
            onSuccess: () => {
                clearInterval(progressInterval);
                setUploadProgress(100);
                setTimeout(() => {
                    setUploading(false);
                    setUploadProgress(0);
                    reset();
                    if (fileInputRef.current) fileInputRef.current.value = '';
                    startPolling(); // Iniciar polling después del upload
                }, 500);
            },
            onError: () => {
                clearInterval(progressInterval);
                setUploading(false);
                setUploadProgress(0);
            },
        });
    };

    const handleReupload = () => {
        if (fileInputRef.current) {
            fileInputRef.current.click();
        }
    };

    const getStatusBadge = () => {
        if (!certificate) {
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
                    ✓ Certificado Verificado
                </span>
            ),
            rejected: (
                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                    <svg className="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ✗ Certificado Rechazado
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

        return badges[certificate.status] || badges.invalid;
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">
                    Certificado de Alumno Regular
                </h2>
                <p className="mt-1 text-sm text-gray-600">
                    Sube tu certificado de alumno regular para acceder a las sugerencias de mentores.
                </p>
            </header>

            <div className="mt-6 space-y-6">
                {/* Estado del certificado */}
                <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p className="text-sm font-medium text-gray-700">Estado actual:</p>
                        <div className="mt-2">{getStatusBadge()}</div>
                        {certificate?.uploaded_at && (
                            <p className="mt-1 text-xs text-gray-500">
                                Subido {certificate.uploaded_at}
                            </p>
                        )}
                    </div>
                    
                    {certificate?.keyword_score !== null && certificate?.keyword_score !== undefined && (
                        <div className="text-right">
                            <p className="text-sm font-medium text-gray-700">Puntuación:</p>
                            <p className="text-2xl font-bold text-gray-900">{certificate.keyword_score}/100</p>
                        </div>
                    )}
                </div>

                {/* Mensaje de rechazo */}
                {certificate?.status === 'rejected' && certificate?.rejection_reason && (
                    <div className="p-4 bg-red-50 border-l-4 border-red-400 rounded">
                        <div className="flex">
                            <div className="flex-shrink-0">
                                <svg className="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div className="ml-3">
                                <p className="text-sm font-medium text-red-800">Razón del rechazo:</p>
                                <p className="mt-1 text-sm text-red-700">{certificate.rejection_reason}</p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Mensaje de procesamiento */}
                {certificate?.status === 'pending' && (
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
                                    Tu certificado está siendo procesado. Esto puede tomar unos minutos. La página se actualizará automáticamente.
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Formulario de upload */}
                {(!certificate || certificate.status === 'rejected' || certificate.status === 'invalid') && (
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <InputLabel htmlFor="certificate" value="Seleccionar certificado (PDF, máx. 5MB)" />
                            <input
                                ref={fileInputRef}
                                id="certificate"
                                type="file"
                                accept=".pdf"
                                onChange={handleFileChange}
                                disabled={uploading || processing}
                                className="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            />
                            {errors.certificate && (
                                <p className="mt-2 text-sm text-red-600">{errors.certificate}</p>
                            )}
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
                            <PrimaryButton disabled={uploading || processing || !data.certificate}>
                                {uploading ? 'Subiendo...' : (certificate ? 'Reenviar Certificado' : 'Subir Certificado')}
                            </PrimaryButton>

                            {data.certificate && !uploading && (
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

                {/* Información del certificado aprobado */}
                {certificate?.status === 'approved' && (
                    <div className="p-4 bg-green-50 border-l-4 border-green-400 rounded">
                        <div className="flex items-start">
                            <div className="flex-shrink-0">
                                <svg className="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div className="ml-3 flex-1">
                                <p className="text-sm font-medium text-green-800">
                                    ¡Tu certificado ha sido verificado exitosamente!
                                </p>
                                <p className="mt-1 text-sm text-green-700">
                                    Ahora puedes acceder a las sugerencias de mentores en tu dashboard.
                                </p>
                                {certificate?.processed_at && (
                                    <p className="mt-1 text-xs text-green-600">
                                        Verificado {certificate.processed_at}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
});

export default StudentCertificate;
