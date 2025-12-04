import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import FieldValidation, { validationRules } from '@/Components/FieldValidation';
import { Transition } from '@headlessui/react';
import { useForm, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';

export default function UpdateAprendizProfile({ className = '' }) {
    const user = usePage().props.auth.user;
    const [areasInteres, setAreasInteres] = useState([]);
    const [loadingAreas, setLoadingAreas] = useState(true);

    // Cargar áreas de interés desde la API
    useEffect(() => {
        const fetchAreasInteres = async () => {
            try {
                const response = await fetch(route('api.areas-interes'));
                const data = await response.json();
                setAreasInteres(data);
            } catch (error) {
                console.error('Error al cargar áreas de interés:', error);
            } finally {
                setLoadingAreas(false);
            }
        };

        fetchAreasInteres();
    }, []);

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            semestre: user.aprendiz?.semestre || '',
            areas_interes: user.aprendiz?.areas_interes?.map(area => area.id) || [],
            objetivos: user.aprendiz?.objetivos || '',
        });

    const submit = (e) => {
        e.preventDefault();
        patch(route('profile.update-aprendiz'));
    };

    const handleAreaInteresChange = (areaId) => {
        const currentAreas = data.areas_interes;
        const isSelected = currentAreas.includes(areaId);
        
        if (isSelected) {
            setData('areas_interes', currentAreas.filter(id => id !== areaId));
        } else {
            setData('areas_interes', [...currentAreas, areaId]);
        }
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">
                    Perfil de Estudiante
                </h2>

                <p className="mt-1 text-sm text-gray-600">
                    Completa tu perfil de estudiante para recibir mejores recomendaciones de mentores.
                </p>
                
                {/* Indicador de progreso en tiempo real */}
                {(() => {
                    const progress = [
                        { field: 'areas', completed: data.areas_interes.length > 0, weight: 40 },
                        { field: 'semestre', completed: data.semestre > 0, weight: 35 },
                        { field: 'objetivos', completed: data.objetivos.trim().length >= 20, weight: 25 }
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
                            {totalProgress < 100 && (
                                <p className="text-xs text-gray-500 mt-1">
                                    💡 Puedes guardar tu progreso parcial, pero necesitas completar todo para recibir mejores recomendaciones
                                </p>
                            )}
                        </div>
                    );
                })()}
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                {/* Semestre */}
                <div>
                    <InputLabel htmlFor="semestre" value="Semestre" />
                    
                    <select
                        id="semestre"
                        name="semestre"
                        value={data.semestre}
                        onChange={(e) => setData('semestre', e.target.value)}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="">Selecciona tu semestre</option>
                        {[...Array(10)].map((_, i) => (
                            <option key={i + 1} value={i + 1}>
                                Semestre {i + 1}
                            </option>
                        ))}
                    </select>

                    <InputError className="mt-2" message={errors.semestre} />
                </div>

                {/* Áreas de Interés */}
                <div>
                    <InputLabel htmlFor="areas_interes" value="Áreas de Interés *" />
                    <p className="mt-1 text-sm text-gray-600">
                        Selecciona las áreas en las que te interesa recibir mentoría
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
                                    className={`p-3 rounded-lg border-2 transition-all ${
                                        data.areas_interes.includes(area.id)
                                            ? 'border-blue-500 bg-blue-50 text-blue-900'
                                            : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'
                                    }`}
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <button
                                            type="button"
                                            onClick={() => handleAreaInteresChange(area.id)}
                                            className="text-left flex-1"
                                        >
                                            <h4 className="font-medium text-sm">{area.nombre}</h4>
                                            <p className="text-xs text-gray-500 mt-1">{area.descripcion}</p>
                                        </button>
                                        <div className="flex items-center gap-2">
                                            {area.roadmap_url && (
                                                <a
                                                    href={area.roadmap_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="px-2 py-1 text-xs rounded-md border bg-gray-50 hover:bg-gray-100 text-gray-700"
                                                    title="Ver ruta en roadmap.sh"
                                                    onClick={(e) => e.stopPropagation()}
                                                >
                                                    Ruta
                                                </a>
                                            )}
                                            {data.areas_interes.includes(area.id) && (
                                                <svg className="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                </svg>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                    
                    <div className="mt-2 text-sm text-gray-600">
                        Seleccionadas: {data.areas_interes.length}
                    </div>

                    <FieldValidation 
                        value={data.areas_interes} 
                        rules={[validationRules.minArrayLength(1)]} 
                    />
                    <InputError className="mt-2" message={errors.areas_interes} />
                </div>

                {/* Objetivos */}
                <div>
                    <InputLabel htmlFor="objetivos" value="Objetivos Personales" />
                    
                    <textarea
                        id="objetivos"
                        name="objetivos"
                        value={data.objetivos}
                        onChange={(e) => setData('objetivos', e.target.value)}
                        rows={4}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                        placeholder="Describe tus objetivos de aprendizaje y lo que esperas lograr con la mentoría..."
                    />
                    
                    <div className="mt-1 text-xs text-gray-500">
                        {data.objetivos.length}/1000 caracteres
                    </div>

                    <FieldValidation 
                        value={data.objetivos} 
                        rules={[
                            validationRules.minWords(5),
                            validationRules.maxLength(1000)
                        ]} 
                    />
                    <InputError className="mt-2" message={errors.objetivos} />
                </div>

                <div className="flex items-center gap-4">
                    {/* Validación inteligente para el botón */}
                    {(() => {
                        const hasBasicInfo = data.semestre > 0;
                        const isComplete = data.semestre > 0 && 
                                         data.areas_interes.length > 0 && 
                                         data.objetivos.trim().length >= 20;
                        
                        return (
                            <PrimaryButton 
                                disabled={processing || !hasBasicInfo} 
                                className={`relative ${!isComplete ? 'bg-yellow-600 hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-900 focus:ring-yellow-500' : ''}`}
                            >
                                {processing && (
                                    <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                )}
                                {processing ? 'Guardando...' : 
                                 isComplete ? '✅ Guardar Perfil Completo' : 
                                 hasBasicInfo ? '💾 Guardar Progreso' : 'Selecciona tu semestre'}
                            </PrimaryButton>
                        );
                    })()}

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out duration-300"
                        enterFrom="opacity-0 scale-95"
                        enterTo="opacity-100 scale-100"
                        leave="transition ease-in-out duration-300"
                        leaveFrom="opacity-100 scale-100"
                        leaveTo="opacity-0 scale-95"
                    >
                        <div className="flex items-center bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-2 rounded-md">
                            <svg className="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                            </svg>
                            Perfil guardado exitosamente
                        </div>
                    </Transition>
                </div>
            </form>
        </section>
    );
}