import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Register({ role }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: role || 'student',
        // Campos extra
        semestre: '',
        intereses: [],
        experiencia: '',
        especialidad: '',
        disponibilidad: '',
    });

    const interesesOptions = [
        'Programación',
        'Diseño',
        'Bases de Datos',
        'Redes',
        'Inteligencia Artificial',
    ];

    const submit = (e) => {
        e.preventDefault();

        post(route('register.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    const headerMsg = (
        <h3 className="text-gray-900 text-xl font-medium mb-4 text-center">
            Registro {role === 'mentor' ? (
                    <strong className="text-[#ec3636]">Mentor</strong>
                ) : (
                    <strong className="text-[#ec3636]">Estudiante</strong>
                )}
        </h3>
    );

    const footerElements = (
        <div className="space-y-4 text-center">
            <PrimaryButton 
                type="submit"
                className="w-full bg-blue-600 hover:bg-blue-700 text-white font-normal text-xs py-2 px-4 rounded-md transition duration-150 ease-in-out flex justify-center items-center" 
                disabled={processing}
            >
                REGISTRAR
            </PrimaryButton>
            <div className="text-center">
                <p className="text-sm text-gray-600">
                    ¿Ya tienes una cuenta?{' '}
                    <Link
                        className="text-[#9fc031] hover:underline"
                        href={route('login', { role })}
                    >
                        Inicia sesión aquí
                    </Link>
                </p>
            </div>
        </div>
    );

    return (
        <GuestLayout headerMsg={headerMsg} footerElements={footerElements} onSubmit={submit}>
            <Head title={`Registro - ${role === 'mentor' ? 'Mentor' : 'Estudiante'}`} />

            <div className="space-y-3 max-w-md mx-auto">
                {/* Nombre */}
                <div>
                    <InputLabel htmlFor="name" value="Nombre" />
                    <div className="flex items-center border border-gray-300 rounded-md px-3 py-2">
                        <span className="material-icons text-gray-500 mr-2">perm_identity</span>
                        <TextInput
                            id="name"
                            name="name"
                            value={data.name}
                            className="w-full focus:outline-none"
                            autoComplete="name"
                            isFocused={true}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                        />
                    </div>
                    <InputError message={errors.name} className="mt-1 text-red-600 text-sm" />
                </div>
                {/* Email */}
                <div>
                    <InputLabel htmlFor="email" value="Email" />
                    <div className="flex items-center border border-gray-300 rounded-md px-3 py-2">
                        <span className="material-icons text-gray-500 mr-2">email</span>
                        <TextInput
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            className="w-full focus:outline-none"
                            autoComplete="username"
                            onChange={(e) => setData('email', e.target.value)}
                            required
                        />
                    </div>
                    <InputError message={errors.email} className="mt-1 text-red-600 text-sm" />
                </div>
                {/* Campos dinámicos por rol */}
                {role === 'student' && (
                    <>
                        {/* Semestre */}
                        <div>
                            <InputLabel htmlFor="semestre" value="Semestre" />
                            <div className="flex items-center border border-gray-300 rounded-md px-3 py-2">
                                <span className="material-icons text-gray-500 mr-2">school</span>
                                <select
                                    id="semestre"
                                    name="semestre"
                                    value={data.semestre}
                                    onChange={(e) => setData('semestre', e.target.value)}
                                    className="w-full focus:outline-none bg-transparent"
                                    required
                                >
                                    <option value="">Selecciona tu semestre</option>
                                    {[...Array(10)].map((_, i) => (
                                        <option key={i + 1} value={i + 1}>
                                            Semestre {i + 1}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <InputError message={errors.semestre} className="mt-1 text-red-600 text-sm" />
                        </div>
                        {/* Intereses */}
                        <div>
                            <InputLabel htmlFor="intereses" value="Áreas de Interés" />
                            <div className="flex flex-wrap gap-2 mt-1">
                                {interesesOptions.map((option) => (
                                    <label key={option} className="flex items-center text-sm">
                                        <input
                                            type="checkbox"
                                            value={option}
                                            checked={data.intereses.includes(option)}
                                            onChange={(e) => {
                                                const checked = e.target.checked;
                                                setData('intereses', checked
                                                    ? [...data.intereses, option]
                                                    : data.intereses.filter((i) => i !== option)
                                                );
                                            }}
                                            className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span className="ml-1">{option}</span>
                                    </label>
                                ))}
                            </div>
                            <InputError message={errors.intereses} className="mt-1 text-red-600 text-sm" />
                        </div>
                    </>
                )}
                {role === 'mentor' && (
                    <>
                        {/* Experiencia */}
                        <div>
                            <InputLabel htmlFor="experiencia" value="Experiencia" />
                            <div className="flex items-center border border-gray-300 rounded-md px-3 py-2">
                                <span className="material-icons text-gray-500 mr-2">work_outline</span>
                                <TextInput
                                    id="experiencia"
                                    name="experiencia"
                                    value={data.experiencia}
                                    className="w-full focus:outline-none"
                                    onChange={(e) => setData('experiencia', e.target.value)}
                                    required
                                />
                            </div>
                            <InputError message={errors.experiencia} className="mt-1 text-red-600 text-sm" />
                        </div>
                        {/* Especialidad */}
                        <div>
                            <InputLabel htmlFor="especialidad" value="Especialidad" />
                            <div className="flex items-center border border-gray-300 rounded-md px-3 py-2">
                                <span className="material-icons text-gray-500 mr-2">star_outline</span>
                                <TextInput
                                    id="especialidad"
                                    name="especialidad"
                                    value={data.especialidad}
                                    className="w-full focus:outline-none"
                                    onChange={(e) => setData('especialidad', e.target.value)}
                                    required
                                />
                            </div>
                            <InputError message={errors.especialidad} className="mt-1 text-red-600 text-sm" />
                        </div>
                        {/* Disponibilidad */}
                        <div>
                            <InputLabel htmlFor="disponibilidad" value="Disponibilidad" />
                            <div className="flex items-center border border-gray-300 rounded-md px-3 py-2">
                                <span className="material-icons text-gray-500 mr-2">schedule</span>
                                <TextInput
                                    id="disponibilidad"
                                    name="disponibilidad"
                                    value={data.disponibilidad}
                                    className="w-full focus:outline-none"
                                    onChange={(e) => setData('disponibilidad', e.target.value)}
                                    required
                                />
                            </div>
                            <InputError message={errors.disponibilidad} className="mt-1 text-red-600 text-sm" />
                        </div>
                    </>
                )}
                {/* Contraseña */}
                <div>
                    <InputLabel htmlFor="password" value="Contraseña" />
                    <div className="flex items-center border border-gray-300 rounded-md px-3 py-2">
                        <span className="material-icons text-gray-500 mr-2">lock_outline</span>
                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            className="w-full focus:outline-none"
                            autoComplete="new-password"
                            onChange={(e) => setData('password', e.target.value)}
                            required
                        />
                    </div>
                    <InputError message={errors.password} className="mt-1 text-red-600 text-sm" />
                </div>
                <div>
                    <InputLabel htmlFor="password_confirmation" value="Confirmar Contraseña" />
                    <div className="flex items-center border border-gray-300 rounded-md px-3 py-2">
                        <span className="material-icons text-gray-500 mr-2">verified_user</span>
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            className="w-full focus:outline-none"
                            autoComplete="new-password"
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            required
                        />
                    </div>
                    <InputError message={errors.password_confirmation} className="mt-1 text-red-600 text-sm" />
                </div>
            </div>
        </GuestLayout>
    );
}
