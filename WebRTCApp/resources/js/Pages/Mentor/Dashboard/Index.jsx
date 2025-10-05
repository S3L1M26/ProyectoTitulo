import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ProfileReminderNotification from '@/Components/ProfileReminderNotification';
import { Head } from '@inertiajs/react';

export default function Dashboard() {

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Panel de Mentor
                </h2>
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
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
