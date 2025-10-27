import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import DeleteUserFormAdmin from '@/Pages/Profile/Partials/DeleteUserFormAdmin';

export default function AdminDashboard({ allUsers }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Panel de Administración
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                        
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <div className="text-center">
                            <h3 className="text-lg font-medium text-gray-800 mb-4">Panel de Administración</h3>
                            <p className="text-gray-600">Funcionalidades de administración disponibles próximamente.</p>
                        </div>
                    </div>
                    
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <DeleteUserFormAdmin className="mt-8" allUsers={allUsers} />
                    </div>
                        
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
