import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import EditSipUserForm from "../UserManagement/EditSipUserForm";
import { Head } from "@inertiajs/react";
import ResetUserPassword from "../UserManagement/ResetUserPassword";
import ResetSipPassword from "../UserManagement/ResetSipPassword";

export default function Edit({ user, sipUser, ps_aor, ps_endpoint }) {
    return (
        <AuthenticatedLayout 
            header={ 
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Editar Usuario SIP
                </h2>
            }
        >

            <Head title="Dashboard" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">           
                        <EditSipUserForm className="mt-8" user={user} sipUser={sipUser} ps_aor={ps_aor} ps_endpoint={ps_endpoint}/>              
                    </div>
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <ResetUserPassword className="max-w-xl" user={user} />
                    </div>

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <ResetSipPassword className="max-w-xl" sipUser={sipUser} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}