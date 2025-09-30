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
                    <InputLabel htmlFor="areas_interes" value="Áreas de Interés" />
                    
                    {loadingAreas ? (
                        <div className="mt-2 text-sm text-gray-500 flex items-center">
                            <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Cargando áreas de interés...
                        </div>
                    ) : (
                        <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            {areasInteres.map((area) => (
                                <label 
                                    key={area.id} 
                                    className="flex items-center p-2 rounded-md hover:bg-gray-50 transition-colors cursor-pointer"
                                    title={area.descripcion || `Área de ${area.nombre}`}
                                >
                                    <input
                                        type="checkbox"
                                        value={area.id}
                                        checked={data.areas_interes.includes(area.id)}
                                        onChange={() => handleAreaInteresChange(area.id)}
                                        className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span className="ml-2 text-sm text-gray-700 flex items-center">
                                        {area.nombre}
                                        <svg 
                                            className="w-3 h-3 text-gray-400 ml-1" 
                                            fill="currentColor" 
                                            viewBox="0 0 20 20"
                                        >
                                            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clipRule="evenodd" />
                                        </svg>
                                    </span>
                                </label>
                            ))}
                        </div>
                    )}

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
                    <PrimaryButton disabled={processing} className="relative">
                        {processing && (
                            <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        )}
                        {processing ? 'Guardando...' : 'Guardar Perfil'}
                    </PrimaryButton>

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