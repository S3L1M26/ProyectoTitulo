import { useForm, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import PrimaryButton from '@/Components/PrimaryButton';

export default function VerifyEmail({ status, auto_send = false }) {
    const { post, processing } = useForm({});
    const [hasBeenSent, setHasBeenSent] = useState(false);
    const [isFirstLoad, setIsFirstLoad] = useState(true);
    const [countdown, setCountdown] = useState(0);

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
            setCountdown(60); // Iniciar countdown de 60 segundos
        } else if (status === 'verification-rate-limited') {
            setCountdown(60); // Countdown cuando está rate-limited
        }
        setIsFirstLoad(false);
    }, [auto_send, isFirstLoad, status]);

    // Countdown timer
    useEffect(() => {
        if (countdown > 0) {
            const timer = setTimeout(() => {
                setCountdown(countdown - 1);
            }, 1000);
            return () => clearTimeout(timer);
        }
    }, [countdown]);

    const submit = (e) => {
        e.preventDefault();
        if (countdown > 0) return; // Prevenir envío si hay countdown activo
        
        post(route('verification.send'), {
            onSuccess: () => {
                setHasBeenSent(true);
                setCountdown(60); // Reiniciar countdown
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
                disabled={processing || countdown > 0}
                onClick={submit}
                type="button"
            >
                {countdown > 0 
                    ? `Espera ${countdown}s para reenviar`
                    : hasBeenSent || status === 'verification-link-sent' 
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
                        <p className="text-green-600">✓ Se ha enviado un nuevo enlace de verificación a tu correo electrónico.</p>
                        {auto_send && <p className="text-xs text-green-600">Correo enviado automáticamente</p>}
                        {countdown > 0 && (
                            <p className="text-xs text-gray-500">Podrás solicitar otro en {countdown} segundos</p>
                        )}
                    </div>
                ) : status === 'verification-rate-limited' ? (
                    <div className="space-y-2">
                        <p className="text-orange-600">⏱️ Por favor espera para solicitar otro correo de verificación.</p>
                        {countdown > 0 && (
                            <p className="text-sm font-medium text-orange-700">Disponible en {countdown} segundos</p>
                        )}
                        <p className="text-xs text-gray-500">Esto previene el spam y protege tu cuenta.</p>
                    </div>
                ) : hasBeenSent ? (
                    <div className="space-y-2">
                        <p>Se ha enviado un enlace de verificación a tu correo electrónico.</p>
                        {countdown > 0 && (
                            <p className="text-xs text-gray-500">Podrás solicitar otro en {countdown} segundos</p>
                        )}
                    </div>
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
