import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";

export default function Edit({ user }) {
    return (
        <AuthenticatedLayout 
            header={ 
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Editar Usuario
                </h2>
            }
        >

            <Head title="Editar Usuario" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">           
                        <div className="text-center">
                            <h3 className="text-lg font-medium text-gray-800 mb-4">Administración de Usuario</h3>
                            <p className="text-gray-600">La funcionalidad de administración de usuarios será implementada próximamente.</p>
                            <p className="text-sm text-gray-500 mt-2">Usuario: {user.name} ({user.email})</p>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}