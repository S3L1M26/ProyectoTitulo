import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage, router } from '@inertiajs/react';
import { useState, lazy, Suspense, memo, useEffect } from 'react';
import MentoriaCard from '@/Components/MentoriaCard';
import ReviewModal from '@/Components/ReviewModal';

// OPTIMIZACIÓN: Lazy loading de componentes pesados
const ProfileReminderNotification = lazy(() => import('@/Components/ProfileReminderNotification'));
const MentorDetailModal = lazy(() => import('@/Components/MentorDetailModal'));
const MentorSuggestions = lazy(() => import('@/Components/MentorSuggestions'));

const Dashboard = memo(function Dashboard({ 
    mentorSuggestions = [], 
    aprendiz, 
    solicitudesPendientes = [],
    mentoriasConfirmadas = [],
    mentoriasHistorial = []
}) {
    const { flash } = usePage().props;
    const [selectedMentor, setSelectedMentor] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isReviewModalOpen, setIsReviewModalOpen] = useState(false);
    const [showMentorias, setShowMentorias] = useState(false); // Lazy mount
    const [vistaMentorias, setVistaMentorias] = useState('confirmadas'); // 'confirmadas' o 'historial'
    const [isReviewMode, setIsReviewMode] = useState(false); // Para modo reseña desde tarjeta

    // Expandir automáticamente si hay mentorías confirmadas
    useEffect(() => {
        if (mentoriasConfirmadas.length > 0) {
            setShowMentorias(true);
        }
    }, [mentoriasConfirmadas.length]);

    // Polling para actualizar mentorías confirmadas cada 30 segundos
    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({ only: ['mentoriasConfirmadas', 'solicitudesPendientes'] });
        }, 30000); // 30 segundos

        return () => clearInterval(interval);
    }, []);

    // Mostrar mensaje de éxito si existe
    useEffect(() => {
        if (flash?.success) {
            const timer = setTimeout(() => {
                alert(flash.success);
            }, 100);
            return () => clearTimeout(timer);
        }
    }, [flash]);

    const openMentorModal = (mentor) => {
        setSelectedMentor(mentor);
        setIsReviewMode(false);
        setIsModalOpen(true);
    };

    const openReviewModal = (mentor) => {
        setSelectedMentor(mentor);
        setIsReviewModalOpen(true);
    };

    const closeMentorModal = () => {
        setIsModalOpen(false);
        setSelectedMentor(null);
        setIsReviewMode(false);
    };

    const closeReviewModal = () => {
        setIsReviewModalOpen(false);
        setSelectedMentor(null);
    };

    // Estado de carga para props lazy de Inertia
    const isLoadingSuggestions = mentorSuggestions === undefined || mentorSuggestions === null;

    // Verificar si requiere verificación de certificado (cuando no está cargando)
    const requiresVerification = !isLoadingSuggestions && mentorSuggestions?.requires_verification === true;
    const mentorList = requiresVerification ? [] : (Array.isArray(mentorSuggestions) ? mentorSuggestions : []);

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
                            <p className="textgray-600">Aquí podrás encontrar mentores que te ayuden en tu orientación profesional.</p>
                        </div>
                    </div>
                    {/* Sección de sugerencias de mentores */}
                    {isLoadingSuggestions ? (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="animate-pulse space-y-3">
                                    <div className="h-6 bg-gray-200 rounded w-1/3"></div>
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        {Array.from({ length: 3 }).map((_, i) => (
                                            <div key={i} className="border rounded-lg p-4">
                                                <div className="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
                                                <div className="h-3 bg-gray-200 rounded w-1/3 mb-2"></div>
                                                <div className="h-16 bg-gray-200 rounded"></div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <Suspense fallback={
                            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <div className="animate-pulse space-y-4">
                                        <div className="h-8 bg-gray-200 rounded w-1/3"></div>
                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            {[1, 2, 3].map((i) => (
                                                <div key={i} className="border rounded-lg p-4 space-y-3">
                                                    <div className="h-4 bg-gray-200 rounded"></div>
                                                    <div className="h-4 bg-gray-200 rounded w-2/3"></div>
                                                    <div className="h-20 bg-gray-200 rounded"></div>
                                                    <div className="h-10 bg-gray-200 rounded"></div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        }>
                            <MentorSuggestions
                                mentorList={mentorList}
                                onOpenMentorModal={openMentorModal}
                                onOpenReviewModal={openReviewModal}
                                isLoadingSuggestions={false}
                                requiresVerification={requiresVerification}
                                mentorSuggestions={mentorSuggestions}
                            />
                        </Suspense>
                    )}

                    {/* Mis Mentorías - Tabs y contenido */}
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <div className="flex items-center gap-3">
                                    <h3 className="text-lg font-semibold text-gray-900">Mis Mentorías</h3>
                                    {vistaMentorias === 'confirmadas' && mentoriasConfirmadas.length > 0 && (
                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {mentoriasConfirmadas.length}
                                        </span>
                                    )}
                                </div>
                                <button 
                                    onClick={() => setShowMentorias((v) => !v)} 
                                    className="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium text-sm transition-colors"
                                >
                                    {showMentorias ? (
                                        <>
                                            <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 15l7-7 7 7" />
                                            </svg>
                                            Ocultar
                                        </>
                                    ) : (
                                        <>
                                            <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                            </svg>
                                            Mostrar
                                        </>
                                    )}
                                </button>
                            </div>
                            {showMentorias && (
                                <>
                                    {/* Tabs */}
                                    <div className="flex gap-4 mb-6 border-b border-gray-200">
                                        <button 
                                            className={`pb-2 px-1 font-semibold transition-colors ${vistaMentorias === 'confirmadas' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'}`}
                                            onClick={() => setVistaMentorias('confirmadas')}
                                        >
                                            Confirmadas
                                        </button>
                                        <button 
                                            className={`pb-2 px-1 font-semibold transition-colors ${vistaMentorias === 'historial' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'}`}
                                            onClick={() => setVistaMentorias('historial')}
                                        >
                                            Historial
                                        </button>
                                    </div>

                                    {/* Contenido de Confirmadas */}
                                    {vistaMentorias === 'confirmadas' && (
                                        mentoriasConfirmadas.length === 0 ? (
                                            <div className="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                                                <div className="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                    <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <p className="text-gray-500 font-medium mb-2">Aún no tienes mentorías confirmadas</p>
                                                <p className="text-sm text-gray-400">Cuando un mentor confirme una de tus solicitudes, aparecerá aquí.</p>
                                            </div>
                                        ) : (
                                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                {mentoriasConfirmadas.map((m) => (
                                                    <MentoriaCard key={m.id} mentoria={m} userRole="aprendiz" />
                                                ))}
                                            </div>
                                        )
                                    )}

                                    {/* Contenido de Historial */}
                                    {vistaMentorias === 'historial' && (
                                        mentoriasHistorial.length === 0 ? (
                                            <div className="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                                                <div className="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                    <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <p className="text-gray-500 font-medium mb-2">No hay mentorías en el historial</p>
                                                <p className="text-sm text-gray-400">Las mentorías completadas y canceladas aparecerán aquí.</p>
                                            </div>
                                        ) : (
                                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                {mentoriasHistorial.map((m) => (
                                                    <MentoriaCard key={m.id} mentoria={m} userRole="aprendiz" isHistorial={true} />
                                                ))}
                                            </div>
                                        )
                                    )}
                                </>
                            )}
                        </div>
                    </div>

                </div>
            </div>

            {/* Modal de detalles del mentor - LAZY LOADED */}
            {isModalOpen && (
                <Suspense fallback={<div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center"><div className="animate-spin rounded-full h-12 w-12 border-b-2 border-white"></div></div>}>
                    <MentorDetailModal
                        isOpen={isModalOpen}
                        onClose={closeMentorModal}
                        mentor={selectedMentor}
                        aprendiz={aprendiz}
                        solicitudesPendientes={solicitudesPendientes}
                    />
                </Suspense>
            )}

            {/* Modal de reseña - Direct rendering (not lazy since it's fast) */}
            {selectedMentor && (
                <ReviewModal
                    isOpen={isReviewModalOpen}
                    onClose={closeReviewModal}
                    mentor={selectedMentor}
                    canReview={selectedMentor?.mentor?.can_review}
                    userReview={selectedMentor?.mentor?.user_review}
                />
            )}
        </AuthenticatedLayout>
    );
});

export default Dashboard;
