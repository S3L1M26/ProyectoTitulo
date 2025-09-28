import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import { useRef } from 'react';


export default function ResetSipPassword({ className = '', sipUser }) {
    const passwordInput = useRef();
    
    const {
        data,
        setData,
        errors,
        put,
        reset,
        processing,
        recentlySuccessful,
    } = useForm({
        new_sip_password: '',
        new_sip_password_confirmation: '',
    });

    const updatePassword = (e) => {
        e.preventDefault();

        put(route('admin.users.reset-sip-password', sipUser.sip_user_id), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errors) => {
                if (errors.new_sip_password) {
                    reset('new_sip_password', 'new_sip_password_confirmation');
                    passwordInput.current.focus();
                }
            },
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">
                    Actualizar Contraseña SIP
                </h2>

                <p className="mt-1 text-sm text-gray-600">
                    Asegúrate de que la cuenta SIP use una contraseña larga y aleatoria para mantenerla segura.
                </p>
            </header>

            <form onSubmit={updatePassword} className="mt-6 space-y-6">
            
                <div>
                    <InputLabel htmlFor="new_sip_password" value="Nueva Contraseña SIP" />

                    <TextInput
                        id="new_sip_password"
                        ref={passwordInput}
                        value={data.new_sip_password}
                        onChange={(e) => setData('new_sip_password', e.target.value)}
                        type="password"
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                    />

                    <InputError message={errors.new_sip_password} className="mt-2" />
                </div>

                <div>
                    <InputLabel
                        htmlFor="new_sip_password_confirmation"
                        value="Confirmar Contraseña SIP"
                    />

                    <TextInput
                        id="new_sip_password_confirmation"
                        value={data.new_sip_password_confirmation}
                        onChange={(e) =>
                            setData('new_sip_password_confirmation', e.target.value)
                        }
                        type="password"
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                    />

                    <InputError
                        message={errors.password_confirmation}
                        className="mt-2"
                    />
                </div>

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Guardar</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600">
                            Guardado.
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}