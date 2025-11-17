import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { useForm, usePage, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import axios from 'axios';

export default function UpdateMentorProfile({ className = '' }) {
    const { auth, errors: pageErrors, cvVerified = false } = usePage().props;
    const user = auth.user;
    const mentor = user.mentor || {};

    const [areasInteres, setAreasInteres] = useState([]);
    const [loadingAreas, setLoadingAreas] = useState(true);
    const [showPreview, setShowPreview] = useState(false);
    const [isAvailable, setIsAvailable] = useState(false);
    const [freshCalificacion, setFreshCalificacion] = useState(mentor.calificacionPromedio || 0);
    const [freshDisponibilidad, setFreshDisponibilidad] = useState(mentor.disponible_ahora || false);
    const [localCvVerified, setLocalCvVerified] = useState(cvVerified);

    // Sincronizar cvVerified cuando cambia en las props
    useEffect(() => {
        setLocalCvVerified(cvVerified);
    }, [cvVerified]);

    const { data, setData, patch, errors, processing, recentlySuccessful, clearErrors } = useForm({
        experiencia: mentor.experiencia || '',
        biografia: mentor.biografia || '',
        años_experiencia: mentor.años_experiencia || '',
        disponibilidad: mentor.disponibilidad || '',
        disponibilidad_detalle: mentor.disponibilidad_detalle || '',
        areas_especialidad: mentor.areas_interes ? mentor.areas_interes.map(area => area.id) : []
    });

    // Cargar disponibilidad fresca del servidor (igual que calificación)
    useEffect(() => {
        const fetchFreshDisponibilidad = async () => {
            try {
                const response = await axios.get('/api/mentor/disponibilidad');
                setFreshDisponibilidad(response.data.disponible_ahora || false);
            } catch (error) {
                console.error('Error cargando disponibilidad:', error);
                setFreshDisponibilidad(mentor.disponible_ahora || false);
            }
        };
        
        fetchFreshDisponibilidad();
    }, [mentor.id]);

    // Cargar calificación fresca del servidor (sin caché)
    useEffect(() => {
        const fetchFreshCalificacion = async () => {
            try {
                const response = await axios.get('/api/mentor/calificacion');
                setFreshCalificacion(response.data.calificacionPromedio || 0);
            } catch (error) {
                console.error('Error cargando calificación:', error);
                setFreshCalificacion(mentor.calificacionPromedio || 0);
            }
        };
        
        fetchFreshCalificacion();
    }, [mentor.id]);

    // Cargar áreas de interés disponibles
    useEffect(() => {
        const fetchAreasInteres = async () => {
            try {
                const response = await fetch('/profile/areas-interes');
                const areas = await response.json();
                setAreasInteres(areas);
            } catch (error) {
                console.error('Error al cargar áreas de interés:', error);
            } finally {
                setLoadingAreas(false);
            }
        };

        fetchAreasInteres();
    }, []);

    // Verificar disponibilidad actual - usa freshDisponibilidad como fuente de verdad
    useEffect(() => {
        setIsAvailable(freshDisponibilidad === true || freshDisponibilidad === 1);
    }, [freshDisponibilidad]);

    // Función para verificar si todos los campos requeridos están completos
    const isProfileComplete = () => {
        return (
            data.experiencia.trim().length >= 50 &&
            data.biografia.trim().length >= 100 &&
            data.años_experiencia > 0 &&
            data.areas_especialidad.length > 0 &&
            data.disponibilidad.trim().length > 0 &&
            localCvVerified // Usar estado local que se sincroniza con props
        );
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        clearErrors();
        
        patch(route('profile.update-mentor'), {
            preserveScroll: true,
            onSuccess: () => {
                // La disponibilidad se maneja por separado
            }
        });
    };

    const toggleAvailability = async () => {
        console.log('🔘🔘🔘 TOGGLE CLICKED!');
        console.log('  isAvailable (before toggle):', isAvailable);
        console.log('  mentor.id:', mentor.id);
        console.log('  Will send: disponible =', !isAvailable);
        
        try {
            console.log('📤 Calling router.post()...');
            
            const result = router.post(
                '/profile/mentor/toggle-disponibilidad', 
                { disponible: !isAvailable },
                {
                    preserveScroll: true,
                    onBefore: () => {
                        console.log('🟡 [onBefore] Request about to be sent');
                        return true;
                    },
                    onStart: () => {
                        console.log('🟡 [onStart] Request started');
                    },
                    onProgress: (progress) => {
                        console.log('🟡 [onProgress]', progress);
                    },
                    onSuccess: (page) => {
                        console.log('✅ [onSuccess] Response received!');
                        console.log('  Status:', 'success');
                        console.log('  Page props:', page.props);
                        
                        // Actualizar la disponibilidad fresca inmediatamente (antes de reload)
                        setFreshDisponibilidad(!freshDisponibilidad);
                        console.log('✅ Updated freshDisponibilidad to:', !freshDisponibilidad);
                        
                        console.log('🔄 Reloading page in 1s...');
                        
                        setTimeout(() => {
                            console.log('🔄 Executing router.reload()...');
                            router.reload();
                        }, 1000);
                    },
                    onError: (errors) => {
                        console.error('❌ [onError] Request failed!');
                        console.error('  Errors object:', errors);
                        Object.entries(errors).forEach(([key, value]) => {
                            console.error(`    ${key}: ${value}`);
                        });
                    },
                    onFinish: () => {
                        console.log('🏁 [onFinish] Request completed');
                    }
                }
            );
            
            console.log('📤 router.post() called, result:', result);
            
        } catch (err) {
            console.error('❌ Exception in toggleAvailability:', err);
        }
    };

    const handleToggleAvailabilityClick = () => {
        console.log('🖱️ Toggle button clicked');
        toggleAvailability();
    };

    const handleAreaToggle = (areaId) => {
        const currentAreas = data.areas_especialidad;
        if (currentAreas.includes(areaId)) {
            setData('areas_especialidad', currentAreas.filter(id => id !== areaId));
        } else if (currentAreas.length < 5) {
            setData('areas_especialidad', [...currentAreas, areaId]);
        }
    };

    const calculateWordCount = (text) => {
        return text.trim() === '' ? 0 : text.trim().split(/\s+/).length;
    };

    const truncateText = (text, maxLength = 150) => {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    };

    const renderMentorPreview = () => (
        <div className="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-lg p-6 border border-blue-200 max-w-full overflow-hidden">
            <div className="flex items-start space-x-4 min-w-0">
                <div className="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-xl flex-shrink-0">
                    {user.name.charAt(0).toUpperCase()}
                </div>
                <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-3 mb-2">
                        <div className="flex-1 min-w-0">
                            <h3 className="text-xl font-bold text-gray-900 break-words leading-tight">{user.name}</h3>
                        </div>
                        <div className={`px-3 py-1 rounded-full text-sm font-medium whitespace-nowrap flex-shrink-0 ${
                            isAvailable 
                                ? 'bg-green-100 text-green-800' 
                                : 'bg-gray-100 text-gray-600'
                        }`}>
                            {isAvailable ? '🟢 Disponible' : '⏸️ No disponible'}
                        </div>
                    </div>
                    
                    <div className="flex items-center justify-between mt-1">
                        <p className="text-gray-600 text-sm">
                            {data.años_experiencia} {data.años_experiencia === 1 ? 'año' : 'años'} de experiencia
                        </p>
                        <div className="flex items-center">
                            <span className="text-yellow-400 mr-1">★</span>
                            <span className="text-sm font-medium text-gray-700">
                                {freshCalificacion ? Number(freshCalificacion).toFixed(1) : '0.0'}/5
                            </span>
                        </div>
                    </div>

                    {data.areas_especialidad.length > 0 && (
                        <div className="mt-3">
                            <div className="flex flex-wrap gap-2">
                                {data.areas_especialidad.map(areaId => {
                                    const area = areasInteres.find(a => a.id === areaId);
                                    return area ? (
                                        <span key={areaId} className="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-md break-words">
                                            {area.nombre}
                                        </span>
                                    ) : null;
                                })}
                            </div>
                        </div>
                    )}

                    {/* Biografía */}
                    {data.biografia && (
                        <div className="mt-4">
                            <h4 className="font-semibold text-gray-900 mb-2">Sobre mí</h4>
                            <div className="text-gray-700 text-sm leading-relaxed break-words" 
                                 style={{ 
                                     wordWrap: 'break-word', 
                                     overflowWrap: 'anywhere',
                                     hyphens: 'auto',
                                     maxWidth: '100%'
                                 }}>
                                {truncateText(data.biografia, 200)}
                            </div>
                        </div>
                    )}

                    {/* Experiencia */}
                    {data.experiencia && (
                        <div className="mt-4">
                            <h4 className="font-semibold text-gray-900 mb-2">Experiencia</h4>
                            <div className="text-gray-700 text-sm leading-relaxed break-words" 
                                 style={{ 
                                     wordWrap: 'break-word', 
                                     overflowWrap: 'anywhere',
                                     hyphens: 'auto',
                                     maxWidth: '100%'
                                 }}>
                                {truncateText(data.experiencia, 200)}
                            </div>
                        </div>
                    )}

                    {/* Disponibilidad */}
                    {data.disponibilidad && (
                        <div className="mt-4">
                            <h4 className="font-semibold text-gray-900 mb-2">Disponibilidad</h4>
                            <p className="text-gray-700 text-sm break-words">
                                {data.disponibilidad}
                            </p>
                            {data.disponibilidad_detalle && (
                                <p className="text-gray-600 text-sm mt-1 break-words">
                                    {data.disponibilidad_detalle}
                                </p>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );




    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">Perfil de Mentor</h2>
                <p className="mt-1 text-sm text-gray-600">
                    Completa tu información para atraer estudiantes y ofrecer mentorías efectivas.
                </p>
                
                {/* Indicador de progreso en tiempo real */}
                {(() => {
                    const progress = [
                        { field: 'experiencia', completed: data.experiencia.trim().length >= 50, weight: 25 },
                        { field: 'areas', completed: data.areas_especialidad.length > 0, weight: 20 },
                        { field: 'biografia', completed: data.biografia.trim().length >= 100, weight: 20 },
                        { field: 'años', completed: data.años_experiencia > 0, weight: 15 },
                        { field: 'disponibilidad', completed: data.disponibilidad.trim().length > 0, weight: 10 },
                        { field: 'cv', completed: localCvVerified, weight: 10 }
                    ];
                    const totalProgress = progress.reduce((sum, item) => sum + (item.completed ? item.weight : 0), 0);
                    
                    return (
                        <div className="mt-3 bg-gray-50 rounded-lg p-3">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-sm font-medium text-gray-700">Progreso del perfil</span>
                                <span className="text-sm font-bold text-blue-600">{totalProgress}%</span>
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-2">
                                <div 
                                    className="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                    style={{ width: `${totalProgress}%` }}
                                ></div>
                            </div>
                            {totalProgress < 100 ? (
                                <p className="text-xs text-gray-500 mt-1">
                                    💡 Puedes guardar tu progreso parcial. Completa todo (incluyendo CV verificado) para activar tu disponibilidad como mentor.
                                </p>
                            ) : (
                                <p className="text-xs text-green-600 mt-1">
                                    🎉 ¡Perfil completo! Ya puedes activar tu disponibilidad para recibir estudiantes.
                                </p>
                            )}
                        </div>
                    );
                })()}
            </header>

            {/* Toggle Preview */}
            <div className="mt-6">
                <button
                    type="button"
                    onClick={() => setShowPreview(!showPreview)}
                    className="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors"
                >
                    <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    {showPreview ? 'Ocultar Preview' : 'Ver Preview del Perfil'}
                </button>
            </div>

            {/* Preview del perfil */}
            {showPreview && (
                <div className="mt-6 w-full">
                    <h3 className="text-md font-medium text-gray-900 mb-4">👀 Así te verán los estudiantes:</h3>
                    <div className="w-full max-w-4xl">
                        {renderMentorPreview()}
                    </div>
                </div>
            )}

            <form onSubmit={handleSubmit} className="mt-6 space-y-6">
                {/* Años de Experiencia */}
                <div>
                    <InputLabel htmlFor="años_experiencia" value="Años de Experiencia *" />
                    <TextInput
                        id="años_experiencia"
                        type="number"
                        min="1"
                        max="50"
                        className="mt-1 block w-full"
                        value={data.años_experiencia}
                        onChange={(e) => setData('años_experiencia', parseInt(e.target.value) || '')}
                        placeholder="Ej: 5"
                    />
                    <p className="mt-1 text-xs text-gray-500">
                        Número total de años trabajando en tu área de especialidad
                    </p>
                    <InputError message={errors.años_experiencia} className="mt-2" />
                </div>

                {/* Biografía */}
                <div>
                    <InputLabel htmlFor="biografia" value="Biografía Personal *" />
                    <textarea
                        id="biografia"
                        className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm min-h-[120px]"
                        value={data.biografia}
                        onChange={(e) => setData('biografia', e.target.value)}
                        placeholder="Cuéntanos sobre ti, tu formación, intereses y motivación para ser mentor..."
                        maxLength={1000}
                    />
                    <div className="mt-1 flex justify-between text-xs text-gray-500">
                        <span>Mínimo 100 caracteres para una biografía completa</span>
                        <span className={data.biografia.length < 100 ? 'text-red-500' : 'text-green-600'}>
                            {data.biografia.length}/1000 caracteres
                        </span>
                    </div>
                    <InputError message={errors.biografia} className="mt-2" />
                </div>

                {/* Experiencia Profesional */}
                <div>
                    <InputLabel htmlFor="experiencia" value="Experiencia Profesional *" />
                    <textarea
                        id="experiencia"
                        className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm min-h-[150px]"
                        value={data.experiencia}
                        onChange={(e) => setData('experiencia', e.target.value)}
                        placeholder="Describe tu trayectoria profesional, proyectos destacados, tecnologías dominadas, roles desempeñados..."
                        maxLength={2000}
                    />
                    <div className="mt-1 flex justify-between text-xs text-gray-500">
                        <span>
                            Mínimo 50 caracteres, 10 palabras. 
                            {data.años_experiencia >= 10 && ' Se esperan roles senior o de liderazgo.'}
                        </span>
                        <span className={calculateWordCount(data.experiencia) < 10 ? 'text-red-500' : 'text-green-600'}>
                            {calculateWordCount(data.experiencia)} palabras • {data.experiencia.length}/2000 caracteres
                        </span>
                    </div>
                    <InputError message={errors.experiencia} className="mt-2" />
                </div>

                {/* Áreas de Especialidad */}
                <div>
                    <InputLabel htmlFor="areas_especialidad" value="Áreas de Especialidad *" />
                    <p className="mt-1 text-sm text-gray-600">
                        Selecciona entre 1 y 5 áreas en las que puedes ofrecer mentoría
                    </p>
                    
                    {loadingAreas ? (
                        <div className="mt-2 text-sm text-gray-500 flex items-center">
                            <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Cargando áreas...
                        </div>
                    ) : (
                        <div className="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            {areasInteres.map(area => (
                                <div
                                    key={area.id}
                                    onClick={() => handleAreaToggle(area.id)}
                                    className={`p-3 rounded-lg border-2 cursor-pointer transition-all ${
                                        data.areas_especialidad.includes(area.id)
                                            ? 'border-blue-500 bg-blue-50 text-blue-900'
                                            : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'
                                    } ${data.areas_especialidad.length >= 5 && !data.areas_especialidad.includes(area.id) 
                                        ? 'opacity-50 cursor-not-allowed' 
                                        : ''}`}
                                >
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <h4 className="font-medium text-sm">{area.nombre}</h4>
                                            <p className="text-xs text-gray-500 mt-1">{area.descripcion}</p>
                                        </div>
                                        {data.areas_especialidad.includes(area.id) && (
                                            <svg className="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                            </svg>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                    
                    <div className="mt-2 text-sm text-gray-600">
                        Seleccionadas: {data.areas_especialidad.length}/5
                    </div>
                    <InputError message={errors.areas_especialidad} className="mt-2" />
                </div>

                {/* Disponibilidad */}
                <div>
                    <InputLabel htmlFor="disponibilidad" value="Disponibilidad General *" />
                    <TextInput
                        id="disponibilidad"
                        type="text"
                        className="mt-1 block w-full"
                        value={data.disponibilidad}
                        onChange={(e) => setData('disponibilidad', e.target.value)}
                        placeholder="Ej: Lunes a Viernes 18:00-20:00, Fines de semana mañanas"
                        maxLength={200}
                    />
                    <p className="mt-1 text-xs text-gray-500">
                        Indica tus horarios generales de disponibilidad
                    </p>
                    <InputError message={errors.disponibilidad} className="mt-2" />
                </div>

                {/* Disponibilidad Detalle */}
                <div>
                    <InputLabel htmlFor="disponibilidad_detalle" value="Detalles de Disponibilidad (Opcional)" />
                    <textarea
                        id="disponibilidad_detalle"
                        className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        rows="3"
                        value={data.disponibilidad_detalle}
                        onChange={(e) => setData('disponibilidad_detalle', e.target.value)}
                        placeholder="Información adicional sobre tu disponibilidad, preferencias de contacto, modalidad de mentoría, etc."
                        maxLength={500}
                    />
                    <div className="mt-1 text-xs text-gray-500 text-right">
                        {data.disponibilidad_detalle.length}/500 caracteres
                    </div>
                    <InputError message={errors.disponibilidad_detalle} className="mt-2" />
                </div>

                {/* Botones de acción */}
                <div className="flex items-center gap-4">
                    {/* Validación inteligente para el botón */}
                    {(() => {
                        const hasBasicInfo = data.experiencia.trim().length >= 10 && data.años_experiencia > 0;
                        const isComplete = data.experiencia.trim().length >= 50 && 
                                         data.biografia.trim().length >= 100 && 
                                         data.años_experiencia > 0 && 
                                         data.areas_especialidad.length > 0 &&
                                         data.disponibilidad.trim().length > 0;
                        
                        return (
                            <PrimaryButton 
                                disabled={processing || !hasBasicInfo}
                                className={`${!isComplete ? 'bg-yellow-600 hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-900 focus:ring-yellow-500' : ''}`}
                            >
                                {processing && (
                                    <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                )}
                                {processing ? 'Guardando...' : 
                                 isComplete ? '✅ Guardar Perfil Completo' : 
                                 hasBasicInfo ? '💾 Guardar Progreso' : 'Completa info básica'}
                            </PrimaryButton>
                        );
                    })()}

                    {/* Toggle de disponibilidad - Solo visible si el perfil está completo */}
                    {isProfileComplete() && (
                        <button
                            type="button"
                            onClick={handleToggleAvailabilityClick}
                            className={`px-4 py-2 rounded-md font-medium transition-colors ${
                                isAvailable
                                    ? 'bg-red-100 text-red-700 hover:bg-red-200'
                                    : 'bg-green-100 text-green-700 hover:bg-green-200'
                            }`}
                        >
                            {isAvailable ? '⏸️ Pausar Disponibilidad' : '▶️ Activar Disponibilidad'}
                        </button>
                    )}

                    {/* Mensaje informativo cuando el perfil no está completo */}
                    {!isProfileComplete() && (
                        <div className="p-3 bg-blue-50 border border-blue-200 rounded-md">
                            <div className="flex items-start">
                                <svg className="w-5 h-5 text-blue-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                </svg>
                                <div className="flex-1">
                                    <h4 className="text-sm font-medium text-blue-800">Requisitos para activar disponibilidad</h4>
                                    <ul className="text-sm text-blue-700 mt-2 space-y-1">
                                        <li className={data.experiencia.trim().length >= 50 ? 'line-through opacity-60' : ''}>
                                            • Experiencia profesional (mínimo 50 caracteres)
                                        </li>
                                        <li className={data.biografia.trim().length >= 100 ? 'line-through opacity-60' : ''}>
                                            • Biografía completa (mínimo 100 caracteres)
                                        </li>
                                        <li className={data.años_experiencia > 0 ? 'line-through opacity-60' : ''}>
                                            • Años de experiencia
                                        </li>
                                        <li className={data.areas_especialidad.length > 0 ? 'line-through opacity-60' : ''}>
                                            • Al menos un área de especialidad
                                        </li>
                                        <li className={data.disponibilidad.trim().length > 0 ? 'line-through opacity-60' : ''}>
                                            • Disponibilidad general
                                        </li>
                                        <li className={localCvVerified ? 'line-through opacity-60' : 'font-semibold'}>
                                            {localCvVerified ? '✅ CV verificado' : '⚠️ CV verificado (sube tu CV arriba)'}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    )}

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out duration-300"
                        enterFrom="opacity-0 scale-95"
                        enterTo="opacity-100 scale-100"
                        leave="transition ease-in-out duration-300"
                        leaveFrom="opacity-100 scale-100"
                        leaveTo="opacity-0 scale-95"
                    >
                        <p className="text-sm text-green-600 font-medium">✅ Perfil actualizado</p>
                    </Transition>
                </div>

                {/* Mostrar errores de disponibilidad y CV */}
                {(pageErrors?.disponibilidad || pageErrors?.cv_verification) && (
                    <div className="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                        <div className="flex items-start">
                            <svg className="w-5 h-5 text-red-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                            </svg>
                            <div>
                                {pageErrors?.cv_verification && (
                                    <>
                                        <p className="text-sm font-medium text-red-800">❌ {pageErrors.cv_verification}</p>
                                        <p className="text-sm text-red-700 mt-1">
                                            Por favor, sube tu CV en la sección de arriba y espera a que sea verificado.
                                        </p>
                                    </>
                                )}
                                {pageErrors?.disponibilidad && (
                                    <p className="text-sm text-red-600">❌ {pageErrors.disponibilidad}</p>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </form>
        </section>
    );
}