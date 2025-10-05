import { Link, usePage } from '@inertiajs/react';

export default function ProfileReminderNotification({ className = '' }) {
    const { auth, profile_completeness } = usePage().props;
    const user = auth.user;

    // Solo mostrar para estudiantes y mentores
    if (user.role !== 'student' && user.role !== 'mentor') return null;

    // Usar los datos calculados por el backend con ponderaciones correctas
    const getProfileCompletenessData = () => {
        // Usar datos calculados por el middleware/Inertia (más confiable)
        if (profile_completeness) {
            return profile_completeness;
        }

        // Fallback: calcular usando las mismas ponderaciones que el backend
        if (user.role === 'student') {
            return calculateStudentCompleteness();
        } else if (user.role === 'mentor') {
            return calculateMentorCompleteness();
        }

        return { percentage: 100, missing_fields: [], completed_fields: [] };
    };

    // Cálculo de estudiante con ponderaciones correctas
    const calculateStudentCompleteness = () => {
        const weights = {
            'areas_interes': 40,  // Más importante para emparejamiento
            'semestre': 35,       // Importante para nivel académico
            'objetivos': 25       // Importante pero menos crítico
        };

        let totalScore = 0;
        const missingFields = [];
        const completedFields = [];
        const aprendiz = user.aprendiz;

        // Verificar semestre
        if (aprendiz?.semestre && aprendiz.semestre > 0) {
            completedFields.push('semestre');
            totalScore += weights.semestre;
        } else {
            missingFields.push('Semestre');
        }

        // Verificar áreas de interés
        if (aprendiz?.areas_interes && aprendiz.areas_interes.length > 0) {
            completedFields.push('areas_interes');
            totalScore += weights.areas_interes;
        } else {
            missingFields.push('Áreas de interés');
        }

        // Verificar objetivos
        if (aprendiz?.objetivos && aprendiz.objetivos.trim() !== '') {
            completedFields.push('objetivos');
            totalScore += weights.objetivos;
        } else {
            missingFields.push('Objetivos personales');
        }

        return {
            percentage: totalScore,
            missing_fields: missingFields,
            completed_fields: completedFields,
            weights: weights
        };
    };

    // Cálculo de mentor con ponderaciones correctas
    const calculateMentorCompleteness = () => {
        const weights = {
            'experiencia': 30,        // Muy importante para credibilidad
            'areas_interes': 25,      // Crítico para matching
            'biografia': 20,          // Importante para confianza
            'años_experiencia': 15,   // Complementario
            'disponibilidad': 10      // Menos crítico
        };

        let totalScore = 0;
        const missingFields = [];
        const completedFields = [];
        const mentor = user.mentor;

        if (!mentor) {
            return {
                percentage: 0,
                missing_fields: ['Experiencia profesional', 'Biografía', 'Años de experiencia', 'Disponibilidad', 'Áreas de especialidad'],
                completed_fields: [],
                weights: weights
            };
        }

        // Verificar experiencia (peso 30%)
        if (mentor.experiencia && mentor.experiencia.trim().length >= 50) {
            completedFields.push('experiencia');
            totalScore += weights.experiencia;
        } else {
            missingFields.push('Experiencia profesional detallada');
        }

        // Verificar áreas de especialidad (peso 25%)
        if (mentor.areas_interes && mentor.areas_interes.length > 0) {
            completedFields.push('areas_interes');
            totalScore += weights.areas_interes;
        } else {
            missingFields.push('Áreas de especialidad');
        }

        // Verificar biografía (peso 20%)
        if (mentor.biografia && mentor.biografia.trim().length >= 100) {
            completedFields.push('biografia');
            totalScore += weights.biografia;
        } else {
            missingFields.push('Biografía personal');
        }

        // Verificar años de experiencia (peso 15%)
        if (mentor.años_experiencia && mentor.años_experiencia > 0) {
            completedFields.push('años_experiencia');
            totalScore += weights.años_experiencia;
        } else {
            missingFields.push('Años de experiencia');
        }

        // Verificar disponibilidad (peso 10%)
        if (mentor.disponibilidad && mentor.disponibilidad.trim().length > 0) {
            completedFields.push('disponibilidad');
            totalScore += weights.disponibilidad;
        } else {
            missingFields.push('Disponibilidad');
        }

        return {
            percentage: totalScore,
            missing_fields: missingFields,
            completed_fields: completedFields,
            weights: weights
        };
    };

    const profileData = getProfileCompletenessData();
    const completeness = profileData.percentage;

    // Función helper para mapear nombres de campos con claves de ponderación
    const getFieldKey = (fieldName) => {
        const fieldMapping = {
            // Estudiante
            'Semestre': 'semestre',
            'Áreas de interés': 'areas_interes',
            'Objetivos personales': 'objetivos',
            
            // Mentor
            'Experiencia profesional detallada': 'experiencia',
            'Experiencia profesional': 'experiencia',
            'Áreas de especialidad': 'areas_interes',
            'Biografía personal': 'biografia',
            'Biografía': 'biografia',
            'Años de experiencia': 'años_experiencia',
            'Disponibilidad': 'disponibilidad'
        };
        
        return fieldMapping[fieldName] || '';
    };

    // Solo mostrar si completitud < 80%
    if (completeness >= 80) return null;

    return (
        <div className={`bg-yellow-50 border-l-4 border-yellow-400 p-4 ${className}`}>
            <div className="flex">
                <div className="flex-shrink-0">
                    <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                    </svg>
                </div>
                <div className="ml-3">
                    <h3 className="text-sm font-medium text-yellow-800">
                        ¡Tu perfil está incompleto!
                    </h3>
                    <div className="mt-2 text-sm text-yellow-700">
                        <p>
                            Tu perfil está completo al <strong>{completeness}%</strong>. 
                            {user.role === 'student' 
                                ? ' Completa tu información para recibir mejores recomendaciones de mentores.'
                                : ' Completa tu información para atraer más estudiantes.'
                            }
                        </p>
                        
                        {/* Mostrar campos faltantes con sus respectivos pesos */}
                        {profileData.missing_fields && profileData.missing_fields.length > 0 && (
                            <div className="mt-3">
                                <p className="text-xs font-medium text-yellow-800 mb-2">
                                    Campos pendientes por completar:
                                </p>
                                <ul className="text-xs space-y-1">
                                    {profileData.missing_fields.map((field, index) => {
                                        // Obtener el peso del campo
                                        let weight = 0;
                                        if (profileData.weights) {
                                            const fieldKey = getFieldKey(field);
                                            weight = profileData.weights[fieldKey] || 0;
                                        }
                                        
                                        return (
                                            <li key={index} className="flex justify-between items-center">
                                                <span>• {field}</span>
                                                {weight > 0 && (
                                                    <span className="ml-2 px-2 py-0.5 bg-yellow-200 text-yellow-800 rounded-full text-xs font-medium">
                                                        {weight}%
                                                    </span>
                                                )}
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                        )}
                    </div>
                    <div className="mt-4">
                        <div className="-mx-2 -my-1.5 flex">
                            <Link
                                href={route('profile.edit')}
                                className="bg-yellow-50 px-2 py-1.5 rounded-md text-sm font-medium text-yellow-800 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-yellow-50 focus:ring-yellow-600"
                            >
                                Completar perfil
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}