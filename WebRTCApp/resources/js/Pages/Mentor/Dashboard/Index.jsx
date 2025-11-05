import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ProfileReminderNotification from '@/Components/ProfileReminderNotification';
import SolicitudesMentoriaPanel from '@/Components/SolicitudesMentoriaPanel';
import { Head, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Dashboard({ solicitudes = [], mentorProfile }) {
    const { flash } = usePage().props;
    
    // Contar solicitudes pendientes
    const pendientesCount = solicitudes.filter(s => s.estado === 'pendiente').length;

    // Mostrar mensaje de éxito si existe
    useEffect(() => {
        if (flash?.success) {
            // Podrías usar un toast library aquí, por ahora usamos alert
            const timer = setTimeout(() => {
                alert(flash.success);
            }, 100);
            return () => clearTimeout(timer);
        }
    }, [flash]);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Panel de Mentor
                    </h2>
                    {pendientesCount > 0 && (
                        <div className="flex items-center bg-red-100 text-red-800 px-4 py-2 rounded-full">
                            <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span className="font-semibold">{pendientesCount} solicitud{pendientesCount !== 1 ? 'es' : ''} pendiente{pendientesCount !== 1 ? 's' : ''}</span>
                        </div>
                    )}
                </div>
            }
        >
            <Head title="Panel de Mentor" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                    {/* Notificación de perfil incompleto */}
                    <ProfileReminderNotification />
                    
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h4 className="text-lg font-semibold text-gray-800 mb-4">¡Bienvenido a tu panel de mentor!</h4>
                            <p className="text-gray-600">Aquí podrás gestionar tus sesiones de mentoría y ayudar a estudiantes en su orientación profesional.</p>
                        </div>
                    </div>

                    {/* Panel de solicitudes de mentoría */}
                    <SolicitudesMentoriaPanel 
                        solicitudes={solicitudes}
                        mentorProfile={mentorProfile}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
