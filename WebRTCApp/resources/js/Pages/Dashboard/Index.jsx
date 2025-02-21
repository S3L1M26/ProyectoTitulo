import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Dialer from '@/Components/Dialer';
import { Head } from '@inertiajs/react';

export default function Dashboard({ sip_account, ps_auth }) {

    console.log(sip_account);
    console.log(ps_auth);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            { sip_account ? (
                                <Dialer sip_account={sip_account} ps_auth={ps_auth}/>
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
