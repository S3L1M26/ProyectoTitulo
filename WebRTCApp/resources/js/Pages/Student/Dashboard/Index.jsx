import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Dialer from '@/Components/Dialer';
import ProfileReminderNotification from '@/Components/ProfileReminderNotification';
import { Head } from '@inertiajs/react';

export default function Dashboard({ sip_account, password, mentorSuggestions = [] }) {

    console.log(sip_account);
    console.log('Mentor suggestions:', mentorSuggestions);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Panel de Usuario
                </h2>
            }
        >
            <Head title="Panel de Control" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                    {/* Notificación de perfil incompleto */}
                    <ProfileReminderNotification />
                    
                    {/* Sección de sugerencias de mentores */}
                    {mentorSuggestions.length > 0 && (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                    Mentores Sugeridos para Ti
                                </h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {mentorSuggestions.map((mentorUser) => (
                                        <div key={mentorUser.id} className="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                            <div className="flex items-center justify-between mb-2">
                                                <h4 className="font-medium text-gray-900">{mentorUser.name}</h4>
                                                <div className="flex items-center">
                                                    <span className="text-yellow-400">★</span>
                                                    <span className="text-sm text-gray-600 ml-1">
                                                        {mentorUser.mentor.calificacionPromedio ? Number(mentorUser.mentor.calificacionPromedio).toFixed(1) : '0.0'}/5
                                                    </span>
                                                </div>
                                            </div>
                                            <p className="text-sm text-gray-600 mb-2">
                                                {mentorUser.mentor.años_experiencia} años de experiencia
                                            </p>
                                            <p className="text-sm text-gray-700 mb-3 line-clamp-3">
                                                {mentorUser.mentor.biografia || mentorUser.mentor.experiencia}
                                            </p>
                                            <div className="flex flex-wrap gap-1 mb-3">
                                                {mentorUser.mentor.areas_interes.slice(0, 3).map((area) => (
                                                    <span 
                                                        key={area.id} 
                                                        className="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded"
                                                    >
                                                        {area.nombre}
                                                    </span>
                                                ))}
                                                {mentorUser.mentor.areas_interes.length > 3 && (
                                                    <span className="text-xs text-gray-500">
                                                        +{mentorUser.mentor.areas_interes.length - 3} más
                                                    </span>
                                                )}
                                            </div>
                                            <button className="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition-colors text-sm">
                                                Ver Perfil
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                    
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
