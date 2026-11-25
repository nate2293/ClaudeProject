import { AuthLink, AuthStatus, GoogleOption } from '@/components/auth/auth-bits';
import { Button } from '@/components/ui/button';
import { GradientCheckbox, GradientField, gradientButtonClass } from '@/components/ui/gradient-animated-form';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEventHandler } from 'react';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), { onFinish: () => reset('password') });
    };

    return (
        <AuthLayout title="Welcome back" description="Sign in to see your orders and check out faster.">
            <Head title="Sign in" />

            {status && <AuthStatus>{status}</AuthStatus>}
            <GoogleOption />

            <form onSubmit={submit} className="space-y-5" noValidate>
                <GradientField
                    id="email"
                    label="Email address"
                    type="email"
                    required
                    autoFocus
                    autoComplete="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                />
                <GradientField
                    id="password"
                    label="Password"
                    type="password"
                    revealable
                    required
                    autoComplete="current-password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                />

                <div className="flex items-center justify-between gap-4">
                    <GradientCheckbox
                        id="remember"
                        label="Remember me"
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                    />
                    {canResetPassword && (
                        <AuthLink href={route('password.request')} className="text-sm">
                            Forgot password?
                        </AuthLink>
                    )}
                </div>

                <Button type="submit" className={gradientButtonClass} magneticStrength={0.15} disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" />}
                    Sign in
                </Button>
            </form>

            <p className="mt-6 text-center text-sm opacity-80">
                New here? <AuthLink href={route('register')}>Create an account</AuthLink>
            </p>
        </AuthLayout>
    );
}
