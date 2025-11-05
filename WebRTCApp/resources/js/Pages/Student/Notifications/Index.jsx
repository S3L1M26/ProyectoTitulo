import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import NotificationsList from '@/Components/NotificationsList';

export default function NotificationsPage({ notificaciones = [], contadorNoLeidas = 0 }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Notificaciones
                    </h2>
                    {contadorNoLeidas > 0 && (
                        <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            {contadorNoLeidas} {contadorNoLeidas === 1 ? 'nueva' : 'nuevas'}
                        </span>
                    )}
                </div>
            }
        >
            <Head title="Notificaciones" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <NotificationsList 
                        notificaciones={notificaciones}
                        contadorNoLeidas={contadorNoLeidas}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
