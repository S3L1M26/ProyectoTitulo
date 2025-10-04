import { usePage } from '@inertiajs/react';

export default function ProfileProgress({ className = '' }) {
    const { auth } = usePage().props;
    const user = auth.user;

    // Calcular progreso del perfil para ambos roles
    const calculateProgress = () => {
        if (user.role === 'student') {
            return calculateStudentProgress();
        } else if (user.role === 'mentor') {
            return calculateMentorProgress();
        }
        
        return { percentage: 100, missingFields: [] };
    };

    const calculateStudentProgress = () => {
        let completedFields = 0;
        let totalFields = 3;
        const missingFields = [];
        const aprendiz = user.aprendiz;

        if (aprendiz?.semestre && aprendiz.semestre > 0) {
            completedFields++;
        } else {
            missingFields.push('Semestre');
        }

        if (aprendiz?.areas_interes && Array.isArray(aprendiz.areas_interes) && aprendiz.areas_interes.length > 0) {
            completedFields++;
        } else {
            missingFields.push('Áreas de interés');
        }

        if (aprendiz?.objetivos && typeof aprendiz.objetivos === 'string' && aprendiz.objetivos.trim().length > 0) {
            completedFields++;
        } else {
            missingFields.push('Objetivos personales');
        }

        const percentage = Math.round((completedFields / totalFields) * 100);
        return { percentage, missingFields, completedFields, totalFields };
    };

    const calculateMentorProgress = () => {
        let completedFields = 0;
        let totalFields = 4;
        const missingFields = [];
        const mentor = user.mentor;

        if (mentor?.experiencia && mentor.experiencia.trim().length >= 50) {
            completedFields++;
        } else {
            missingFields.push('Experiencia profesional detallada');
        }

        if (mentor?.biografia && mentor.biografia.trim().length >= 100) {
            completedFields++;
        } else {
            missingFields.push('Biografía personal');
        }

        if (mentor?.años_experiencia && mentor.años_experiencia > 0) {
            completedFields++;
        } else {
            missingFields.push('Años de experiencia');
        }

        if (mentor?.areas_interes && Array.isArray(mentor.areas_interes) && mentor.areas_interes.length > 0) {
            completedFields++;
        } else {
            missingFields.push('Áreas de especialidad');
        }

        const percentage = Math.round((completedFields / totalFields) * 100);
        return { percentage, missingFields, completedFields, totalFields };
    };

    const { percentage, missingFields, completedFields, totalFields } = calculateProgress();

    // Badge simple y elegante
    return (
        <div className={`${className}`}>
            {percentage === 100 ? (
                // Perfil completo - Badge verde
                <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div className="flex items-center">
                        <div className="flex-shrink-0">
                            <svg className="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                            </svg>
                        </div>
                        <div className="ml-3">
                            <h3 className="text-sm font-medium text-green-800">
                                ✅ Perfil completo
                            </h3>
                            <p className="text-sm text-green-700">
                                {user.role === 'student' 
                                    ? 'Tu perfil está listo para recibir recomendaciones de mentores' 
                                    : 'Tu perfil está visible para estudiantes interesados'
                                }
                            </p>
                        </div>
                    </div>
                </div>
            ) : (
                // Perfil incompleto - Badge amarillo con CTA
                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <svg className="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="ml-3">
                                <h3 className="text-sm font-medium text-yellow-800">
                                    ⚠️ Perfil incompleto ({percentage}%)
                                </h3>
                                <p className="text-sm text-yellow-700">
                                    {user.role === 'student' 
                                        ? 'Completa los formularios abajo para mejores recomendaciones' 
                                        : 'Completa los formularios abajo para atraer más estudiantes'
                                    }
                                </p>
                            </div>
                        </div>
                        <div className="ml-4">
                            <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                {missingFields.length} campo{missingFields.length > 1 ? 's' : ''} pendiente{missingFields.length > 1 ? 's' : ''}
                            </span>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}