import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import CreateSipUserForm from '../UserManagement/CreateSipUserForm';
import DeleteUserFormAdmin from '@/Pages/Profile/Partials/DeleteUserFormAdmin';

export default function AdminDashboard({ users, allUsersHaveSip, allUsers }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Crear Usuario SIP
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                        
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <CreateSipUserForm className="mt-8" users={users} allUsersHaveSip={allUsersHaveSip}/>
                    </div>
                    
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <DeleteUserFormAdmin className="mt-8" allUsers={allUsers} />
                    </div>
                        
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
