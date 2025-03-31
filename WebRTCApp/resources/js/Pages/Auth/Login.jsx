import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Log in" />

            <form className="form-signin" onSubmit={submit}>
                <div className="pmd-card-title card-header-border text-center">
                    {status && (
                        <div className="alert alert-success text-center showAlert" role="alert">
                            {status}
                        </div>
                    )}
                </div>

                <div className="pmd-card-body">
                    {/* Email Field */}
                    <div className="form-group pmd-textfield pmd-textfield-floating-label">
                        <InputLabel htmlFor="email" value="Email" />
                        <div className="input-group">
                            <div className="input-group-addon">
                                <i className="material-icons md-dark pmd-sm">perm_identity</i>
                            </div>
                            <TextInput
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                className="form-control"
                                autoComplete="username"
                                isFocused={true}
                                onChange={(e) => setData('email', e.target.value)}
                            />
                        </div>
                        <InputError message={errors.email} className="mt-2" />
                    </div>

                    {/* Password Field */}
                    <div className="form-group pmd-textfield pmd-textfield-floating-label">
                        <InputLabel htmlFor="password" value="Password" />
                        <div className="input-group">
                            <div className="input-group-addon">
                                <i className="material-icons md-dark pmd-sm">lock_outline</i>
                            </div>
                            <TextInput
                                id="password"
                                type="password"
                                name="password"
                                value={data.password}
                                className="form-control"
                                autoComplete="current-password"
                                onChange={(e) => setData('password', e.target.value)}
                            />
                        </div>
                        <InputError message={errors.password} className="mt-2" />
                    </div>

                    {/* Remember Me Checkbox */}
                    <div className="form-group pmd-textfield pmd-textfield-floating-label mt-3">
                        <label className="flex items-center checkbox">
                            <Checkbox
                                name="remember"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                            />
                            <span className="ms-2 text-sm remember">Remember me</span>
                        </label>
                    </div>
                </div>

                {/* Footer */}
                <div className="pmd-card-footer card-footer-no-border card-footer-p16 text-center">
                    {canResetPassword && (
                        <div className="forgot-password">
                            <Link
                                href={route('password.request')}
                                className="connect-color-2"
                            >
                                Forgot your password?
                            </Link>
                        </div>
                    )}
                    <PrimaryButton className="btn pmd-ripple-effect btn-primary btn-block mt-4" disabled={processing}>
                        Log in
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
