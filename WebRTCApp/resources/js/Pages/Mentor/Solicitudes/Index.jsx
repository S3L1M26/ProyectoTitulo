import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ProfileReminderNotification from '@/Components/ProfileReminderNotification';
import SolicitudesMentoriaPanel from '@/Components/SolicitudesMentoriaPanel';
import { Head, usePage } from '@inertiajs/react';

export default function Solicitudes({ solicitudes = [], mentorProfile }) {
    const { flash } = usePage().props;
    
    // Contar solicitudes pendientes
    const pendientesCount = solicitudes.filter(s => s.estado === 'pendiente').length;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Solicitudes de Mentoría
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
            <Head title="Solicitudes de Mentoría" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                    {/* Notificación de perfil incompleto */}
                    <ProfileReminderNotification />
                    
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
