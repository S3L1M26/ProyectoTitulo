import { Link, usePage } from '@inertiajs/react';

export default function ProfileReminderNotification({ className = '' }) {
    const { auth } = usePage().props;
    const user = auth.user;

    // Solo mostrar para estudiantes y mentores
    if (user.role !== 'student' && user.role !== 'mentor') return null;

    // Usar datos de sesión calculados por el middleware (más confiable)
    // Fallback a cálculo simple si no hay datos de sesión
    const getCompletenessFromSession = () => {
        // En un entorno real, esto vendría de window.Laravel.session o similar
        // Por ahora, usamos un cálculo simplificado
        if (user.role === 'student') {
            const aprendiz = user.aprendiz;
            return aprendiz?.semestre && aprendiz?.areas_interes?.length > 0 && aprendiz?.objetivos ? 100 : 50;
        } else {
            const mentor = user.mentor;
            return mentor?.experiencia && mentor?.biografia && mentor?.años_experiencia && mentor?.areas_interes?.length > 0 ? 100 : 60;
        }
    };

    const completeness = getCompletenessFromSession();

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
                            Tu perfil está completo al {completeness}%. 
                            {user.role === 'student' 
                                ? ' Completa tu información para recibir mejores recomendaciones de mentores.'
                                : ' Completa tu información para atraer más estudiantes.'
                            }
                        </p>
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