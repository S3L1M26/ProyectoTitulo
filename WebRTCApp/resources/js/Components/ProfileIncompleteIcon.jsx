import { usePage } from '@inertiajs/react';

export default function ProfileIncompleteIcon({ className = '' }) {
    const { auth } = usePage().props;
    const user = auth.user;

    // Para estudiantes y mentores
    if (user.role !== 'student' && user.role !== 'mentor') return null;

    // Verificar si el perfil está incompleto
    const isProfileIncomplete = () => {
        if (user.role === 'student') {
            return isStudentProfileIncomplete();
        } else if (user.role === 'mentor') {
            return isMentorProfileIncomplete();
        }
        return false;
    };

    const isStudentProfileIncomplete = () => {
        const aprendiz = user.aprendiz;
        if (!aprendiz) return true;
        
        const hasSemestre = aprendiz.semestre && aprendiz.semestre > 0;
        const hasAreas = aprendiz.areas_interes && Array.isArray(aprendiz.areas_interes) && aprendiz.areas_interes.length > 0;
        const hasObjetivos = aprendiz.objetivos && typeof aprendiz.objetivos === 'string' && aprendiz.objetivos.trim().length > 0;

        return !hasSemestre || !hasAreas || !hasObjetivos;
    };

    const isMentorProfileIncomplete = () => {
        const mentor = user.mentor;
        if (!mentor) return true;
        
        const hasExperiencia = mentor.experiencia && mentor.experiencia.trim().length >= 50;
        const hasBiografia = mentor.biografia && mentor.biografia.trim().length >= 100;
        const hasAñosExperiencia = mentor.años_experiencia && mentor.años_experiencia > 0;
        const hasAreas = mentor.areas_interes && Array.isArray(mentor.areas_interes) && mentor.areas_interes.length > 0;

        return !hasExperiencia || !hasBiografia || !hasAñosExperiencia || !hasAreas;
    };

    // No mostrar si el perfil está completo
    if (!isProfileIncomplete()) return null;

    return (
        <div className={`relative inline-flex ${className}`}>
            {/* Icono de notificación */}
            <div className="absolute -top-1 -right-1">
                <div className="w-3 h-3 bg-red-500 rounded-full animate-pulse">
                    <div className="absolute inset-0 w-3 h-3 bg-red-500 rounded-full animate-ping"></div>
                </div>
            </div>
            
            {/* Tooltip opcional */}
            <div className="group relative">
                <svg className="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                </svg>
                
                {/* Tooltip */}
                <div className="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 text-xs text-white bg-gray-800 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                    Perfil incompleto
                </div>
            </div>
        </div>
    );
}