import { useForm, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import PrimaryButton from '@/Components/PrimaryButton';

export default function VerifyEmail({ status, auto_send = false }) {
    const { post, processing } = useForm({});
    const [hasBeenSent, setHasBeenSent] = useState(false);
    const [isFirstLoad, setIsFirstLoad] = useState(true);

    // Envío automático al cargar el componente por primera vez
    useEffect(() => {
        if (auto_send && isFirstLoad && !status) {
            post(route('verification.send'), {
                onSuccess: () => {
                    setHasBeenSent(true);
                    setIsFirstLoad(false);
                }
            });
        } else if (status === 'verification-link-sent') {
            setHasBeenSent(true);
        }
        setIsFirstLoad(false);
    }, [auto_send, isFirstLoad, status]);

    const submit = (e) => {
        e.preventDefault();
        post(route('verification.send'), {
            onSuccess: () => {
                setHasBeenSent(true);
            }
        });
    };

    const headerMsg = (
        <h2 className="text-lg font-semibold text-gray-900 mb-2 text-center">
            Verifica tu correo electrónico
        </h2>
    );

    const footerElements = (
        <div className="flex flex-col items-center gap-4">
            <PrimaryButton
                disabled={processing}
                onClick={submit}
                type="button"
            >
                {hasBeenSent || status === 'verification-link-sent' 
                    ? 'Reenviar correo de verificación' 
                    : 'Enviar correo de verificación'
                }
            </PrimaryButton>
            <Link
                href={route('logout')}
                method="post"
                as="button"
                className="text-sm text-gray-600 underline hover:text-gray-900"
            >
                Cerrar sesión
            </Link>
        </div>
    );

    return (
        <GuestLayout headerMsg={headerMsg} footerElements={footerElements}>
            <div className="text-sm text-gray-700 text-center">
                {status === 'verification-link-sent' ? (
                    <div className="space-y-2">
                        <p>Se ha enviado un nuevo enlace de verificación a tu correo electrónico.</p>
                        {auto_send && <p className="text-xs text-green-600">✓ Correo enviado automáticamente</p>}
                    </div>
                ) : hasBeenSent ? (
                    <p>Se ha enviado un enlace de verificación a tu correo electrónico.</p>
                ) : (
                    <div className="space-y-2">
                        <p>Por favor, verifica tu correo electrónico antes de continuar.</p>
                        {processing && auto_send && (
                            <p className="text-xs text-blue-600">Enviando correo automáticamente...</p>
                        )}
                    </div>
                )}
            </div>
        </GuestLayout>
    );
}
