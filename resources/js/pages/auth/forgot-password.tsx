import { AuthLink, AuthStatus } from '@/components/auth/auth-bits';
import { Button } from '@/components/ui/button';
import { GradientField, gradientButtonClass } from '@/components/ui/gradient-animated-form';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEventHandler } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <AuthLayout title="Forgot your password?" description="Enter your email and we’ll send you a link to choose a new one.">
            <Head title="Forgot password" />

            {status && <AuthStatus>{status}</AuthStatus>}

            <form onSubmit={submit} className="space-y-6" noValidate>
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
                <Button type="submit" className={gradientButtonClass} magneticStrength={0.15} disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" />}
                    Email me a reset link
                </Button>
            </form>

            <p className="mt-6 text-center text-sm opacity-80">
                Remembered it? <AuthLink href={route('login')}>Sign in</AuthLink>
            </p>
        </AuthLayout>
    );
}
