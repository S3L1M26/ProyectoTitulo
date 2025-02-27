import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import EditSipUserForm from "../UserManagement/EditSipUserForm";
import { Head } from "@inertiajs/react";

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
                    <EditSipUserForm className="mt-8" user={user} sipUser={sipUser} ps_aor={ps_aor} ps_endpoint={ps_endpoint}/>              
                </div>
            </div>
        </AuthenticatedLayout>
    );
}