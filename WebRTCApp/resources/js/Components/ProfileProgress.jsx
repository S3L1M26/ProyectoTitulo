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

        if (mentor?.experiencia && mentor.experiencia.trim().length > 0) {
            completedFields++;
        } else {
            missingFields.push('Experiencia profesional');
        }

        if (mentor?.especialidades && mentor.especialidades.trim().length > 0) {
            completedFields++;
        } else {
            missingFields.push('Especialidades');
        }

        if (mentor?.disponibilidad && mentor.disponibilidad.trim().length > 0) {
            completedFields++;
        } else {
            missingFields.push('Disponibilidad');
        }

        if (mentor?.descripcion && mentor.descripcion.trim().length > 0) {
            completedFields++;
        } else {
            missingFields.push('Descripción del perfil');
        }

        const percentage = Math.round((completedFields / totalFields) * 100);
        return { percentage, missingFields, completedFields, totalFields };
    };

    const { percentage, missingFields, completedFields, totalFields } = calculateProgress();

    // No mostrar si el perfil está completo
    if (percentage === 100) return null;

    return (
        <div className={`bg-blue-50 border border-blue-200 rounded-lg p-4 ${className}`}>
            <div className="flex items-center justify-between mb-3">
                <h3 className="text-sm font-medium text-blue-900">
                    Completitud del Perfil
                </h3>
                <span className="text-sm font-semibold text-blue-700">
                    {percentage}%
                </span>
            </div>

            {/* Barra de progreso */}
            <div className="w-full bg-blue-200 rounded-full h-2 mb-3">
                <div 
                    className="bg-blue-600 h-2 rounded-full transition-all duration-300 ease-in-out"
                    style={{ width: `${percentage}%` }}
                ></div>
            </div>

            {/* Campos faltantes */}
            {missingFields.length > 0 && (
                <div className="mb-3">
                    <p className="text-sm text-blue-800 mb-2">
                        Te faltan {missingFields.length} campo{missingFields.length > 1 ? 's' : ''}:
                    </p>
                    <ul className="text-sm text-blue-700 space-y-1">
                        {missingFields.map((field, index) => (
                            <li key={index} className="flex items-center">
                                <svg className="w-3 h-3 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clipRule="evenodd" />
                                </svg>
                                {field}
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            {/* CTA */}
            <div className="text-center">
                <p className="text-xs text-blue-600">
                    {user.role === 'student' 
                        ? 'Completa tu perfil para recibir mejores recomendaciones de mentores'
                        : 'Completa tu perfil para atraer más estudiantes'
                    }
                </p>
            </div>
        </div>
    );
}