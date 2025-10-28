import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useState, lazy, Suspense, memo } from 'react';

// OPTIMIZACIÓN: Lazy loading de componentes pesados
const ProfileReminderNotification = lazy(() => import('@/Components/ProfileReminderNotification'));
const MentorDetailModal = lazy(() => import('@/Components/MentorDetailModal'));

const Dashboard = memo(function Dashboard({ mentorSuggestions = [] }) {
    const [selectedMentor, setSelectedMentor] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);

    const openMentorModal = (mentor) => {
        setSelectedMentor(mentor);
        setIsModalOpen(true);
    };

    const closeMentorModal = () => {
        setIsModalOpen(false);
        setSelectedMentor(null);
    };

    // Verificar si requiere verificación de certificado
    const requiresVerification = mentorSuggestions?.requires_verification === true;
    const mentorList = requiresVerification ? [] : (Array.isArray(mentorSuggestions) ? mentorSuggestions : []);

    console.log('Mentor suggestions:', mentorSuggestions);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Panel de Usuario
                </h2>
            }
        >
            <Head title="Panel de Control" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                    {/* Notificación de perfil incompleto - LAZY LOADED */}
                    <Suspense fallback={<div className="animate-pulse h-20 bg-gray-200 rounded"></div>}>
                        <ProfileReminderNotification />
                    </Suspense>
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h4 className="text-lg font-semibold text-gray-800 mb-4">¡Bienvenido a tu panel de estudiante!</h4>
                            <p className="text-gray-600">Aquí podrás encontrar mentores que te ayuden en tu orientación profesional.</p>
                        </div>
                    </div>
                    {/* Sección de sugerencias de mentores */}
                    {requiresVerification ? (
                        /* Mensaje de certificado requerido */
                        <div className="overflow-hidden bg-yellow-50 border-2 border-yellow-200 shadow-sm sm:rounded-lg">
                            <div className="p-8 text-center">
                                <div className="mx-auto w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-6">
                                    <svg className="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 className="text-xl font-semibold text-gray-900 mb-3">
                                    Certificado de Alumno Regular Requerido
                                </h3>
                                <p className="text-gray-700 mb-6 max-w-md mx-auto">
                                    {mentorSuggestions.message || 'Debes verificar tu certificado de alumno regular para ver mentores.'}
                                </p>
                                <a 
                                    href={mentorSuggestions.upload_url || '/profile#certificate'}
                                    className="inline-flex items-center px-6 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors font-medium"
                                >
                                    <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    Subir Certificado
                                </a>
                            </div>
                        </div>
                    ) : mentorList.length > 0 ? (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                    Mentores Sugeridos para Ti
                                </h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {mentorList.map((mentorUser) => (
                                        <div key={mentorUser.id} className="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                            <div className="flex items-center justify-between mb-2">
                                                <h4 className="font-medium text-gray-900">{mentorUser.name}</h4>
                                                <div className="flex items-center">
                                                    <span className="text-yellow-400">★</span>
                                                    <span className="text-sm text-gray-600 ml-1">
                                                        {mentorUser.mentor.calificacionPromedio ? Number(mentorUser.mentor.calificacionPromedio).toFixed(1) : '0.0'}/5
                                                    </span>
                                                </div>
                                            </div>
                                            <p className="text-sm text-gray-600 mb-2">
                                                {mentorUser.mentor.años_experiencia} años de experiencia
                                            </p>
                                            <p className="text-sm text-gray-700 mb-3 line-clamp-3">
                                                {mentorUser.mentor.biografia || mentorUser.mentor.experiencia}
                                            </p>
                                            <div className="flex flex-wrap gap-1 mb-3">
                                                {mentorUser.mentor.areas_interes.slice(0, 3).map((area) => (
                                                    <span 
                                                        key={area.id} 
                                                        className="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded"
                                                    >
                                                        {area.nombre}
                                                    </span>
                                                ))}
                                                {mentorUser.mentor.areas_interes.length > 3 && (
                                                    <span className="text-xs text-gray-500">
                                                        +{mentorUser.mentor.areas_interes.length - 3} más
                                                    </span>
                                                )}
                                            </div>
                                            <button 
                                                onClick={() => openMentorModal(mentorUser)}
                                                className="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition-colors text-sm"
                                            >
                                                Ver Perfil
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="p-8 text-center">
                                <div className="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-6">
                                    <svg className="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <h3 className="text-xl font-semibold text-gray-900 mb-3">
                                    No hay mentores sugeridos aún
                                </h3>
                                <p className="text-gray-600 mb-6 max-w-md mx-auto">
                                    Para recibir sugerencias personalizadas de mentores, asegúrate de completar tu perfil con tus áreas de interés. También verifica que haya mentores disponibles en tus áreas de especialización.
                                </p>
                                <div className="flex flex-col sm:flex-row gap-3 justify-center">
                                    <a 
                                        href="/profile" 
                                        className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                    >
                                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        Completar mi perfil
                                    </a>
                                    <button 
                                        onClick={() => window.location.reload()} 
                                        className="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
                                    >
                                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Actualizar sugerencias
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}
                    
                    
                </div>
            </div>

            {/* Modal de detalles del mentor - LAZY LOADED */}
            {isModalOpen && (
                <Suspense fallback={<div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center"><div className="animate-spin rounded-full h-12 w-12 border-b-2 border-white"></div></div>}>
                    <MentorDetailModal
                        isOpen={isModalOpen}
                        onClose={closeMentorModal}
                        mentor={selectedMentor}
                    />
                </Suspense>
            )}
        </AuthenticatedLayout>
    );
});

export default Dashboard;
