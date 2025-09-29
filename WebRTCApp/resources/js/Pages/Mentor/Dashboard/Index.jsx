import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Dialer from '@/Components/Dialer';
import { Head } from '@inertiajs/react';

export default function Dashboard({ sip_account, password }) {

    console.log(sip_account);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Panel de Usuario
                </h2>
            }
        >
            <Head title="Panel de Usuario" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            { sip_account ? (
                                <div>
                                    <h4>Bienvenido!</h4>
                                    <p>Usuario SIP: {sip_account.sip_user_id}</p>
                                    <Dialer sip_account={sip_account} password={password}/>
                                </div>
                                ) : (
                                <div>
                                    <h4>Bienvenido!</h4>
                                    <p>No tienes una cuenta SIP asignada</p>
                                </div>
                                ) }
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
