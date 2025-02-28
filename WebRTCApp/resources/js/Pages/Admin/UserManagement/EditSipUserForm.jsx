import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import Checkbox from '@/Components/Checkbox';
import { Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

export default function EditSipUserForm({ className = '', user, sipUser, ps_aor, ps_endpoint }) {
    const { data, setData, put, errors, processing, recentlySuccessful, reset } = useForm({
        sip_id: sipUser.sip_user_id || '',
        max_contacts: ps_aor.max_contacts || 2,
        qualify_frequency: ps_aor.qualify_frequency || 30,
        codecs: ps_endpoint.allow ? ps_endpoint.allow.split(',') : ['opus', 'ulaw', 'alaw', 'gsm'],
        direct_media: ps_endpoint.direct_media === 'yes', // Convertir 'yes'/'no' a booleano
        mailboxes: ps_endpoint.mailboxes || '',
        user_id: user.id || ''
    });

    const codecOptions = ['opus', 'ulaw', 'alaw', 'gsm', 'g729', 'h263', 'h264'];

    const handleCodecChange = (codec) => {
        const updatedCodecs = data.codecs.includes(codec)
            ? data.codecs.filter(c => c !== codec)
            : [...data.codecs, codec];
            
        setData('codecs', updatedCodecs.sort());
    };

    const submit = (e) => {
        e.preventDefault();
        console.log('Submitting form with data:', data); // Add logging for debugging
        // Convertir codecs de array a cadena separada por comas
        const formattedData = {
            ...data,
            allow: data.codecs.join(','), // Convertir array a string
            direct_media: data.direct_media ? 'yes' : 'no', // Convertir booleano a 'yes'/'no'
        };

        put(route('admin.users.update', user.id), {
            data: formattedData, // Enviar los datos formateados
            onSuccess: () => reset(),
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">
                    Editar Usuario: {user.name} ({user.email})
                </h2>
                <p className="mt-1 text-sm text-gray-600">
                    Actualizar el endpoint SIP y la información del usuario
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                {/* SIP ID */}
                <div>
                    <InputLabel htmlFor="sip_id" value="SIP User ID" />
                    <TextInput
                        id="sip_id"
                        type="number"
                        className="mt-1 block w-full"
                        value={data.sip_id}
                        onChange={(e) => setData('sip_id', e.target.value)}
                        required
                        isFocused
                        disabled
                    />
                    {errors.sip_id && (
                        <InputError message={errors.sip_id} className="mt-2" />
                    )}
                </div>

                {/* Connection Settings */}
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel htmlFor="max_contacts" value="Max Devices" />
                        <TextInput
                            id="max_contacts"
                            type="number"
                            className="mt-1 block w-full"
                            value={data.max_contacts}
                            onChange={(e) => setData('max_contacts', e.target.value)}
                        />
                        <InputError className="mt-2" message={errors.max_contacts} />
                    </div>

                    <div>
                        <InputLabel htmlFor="qualify_frequency" value="Quality Check (seconds)" />
                        <TextInput
                            id="qualify_frequency"
                            type="number"
                            className="mt-1 block w-full"
                            value={data.qualify_frequency}
                            onChange={(e) => setData('qualify_frequency', e.target.value)}
                        />
                        <InputError className="mt-2" message={errors.qualify_frequency} />
                    </div>
                </div>
                
                {/* Codec Selection */}
                <div>
                    <InputLabel value="Allowed Codecs" />
                    <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        {codecOptions.map((codec) => (
                            <label key={codec} className="flex items-center space-x-2">
                                <Checkbox
                                    checked={data.codecs.includes(codec)}
                                    onChange={() => handleCodecChange(codec)}
                                />
                                <span className="text-sm text-gray-600">{codec.toUpperCase()}</span>
                            </label>
                        ))}
                    </div>
                    <InputError className="mt-2" message={errors.codecs} />
                </div>

                {/* Advanced Settings */}
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel htmlFor="mailboxes" value="Voice Mailbox" />
                        <TextInput
                            id="mailboxes"
                            className="mt-1 block w-full"
                            value={data.mailboxes}
                            onChange={(e) => setData('mailboxes', e.target.value)}
                            placeholder="user@context"
                        />
                        <InputError className="mt-2" message={errors.mailboxes} />
                    </div>

                    <div>
                        <InputLabel htmlFor="direct_media" value="Direct Media" />
                        <select
                            id="direct_media"
                            className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            value={data.direct_media}
                            onChange={(e) => setData('direct_media', e.target.value === 'true')}
                        >
                            <option value={false}>Disabled</option>
                            <option value={true}>Enabled</option>
                        </select>
                        <InputError className="mt-2" message={errors.direct_media} />
                    </div>
                </div>

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Update User</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600">User updated successfully!</p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}