import { AuthLink, GoogleOption } from '@/components/auth/auth-bits';
import { Button } from '@/components/ui/button';
import { GradientField, gradientButtonClass } from '@/components/ui/gradient-animated-form';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

type RegisterForm = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm<RegisterForm>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), { onFinish: () => reset('password', 'password_confirmation') });
    };

    return (
        <AuthLayout title="Create an account" description="Track your orders and check out faster next time.">
            <Head title="Create an account" />

            <GoogleOption />

            <form onSubmit={submit} className="space-y-5" noValidate>
                <GradientField
                    id="name"
                    label="Name"
                    required
                    autoFocus
                    autoComplete="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    disabled={processing}
                    error={errors.name}
                />
                <GradientField
                    id="email"
                    label="Email address"
                    type="email"
                    required
                    autoComplete="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    disabled={processing}
                    error={errors.email}
                />
                <GradientField
                    id="password"
                    label="Password"
                    type="password"
                    revealable
                    required
                    autoComplete="new-password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    disabled={processing}
                    error={errors.password}
                />
                <GradientField
                    id="password_confirmation"
                    label="Confirm password"
                    type="password"
                    revealable
                    required
                    autoComplete="new-password"
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                    disabled={processing}
                    error={errors.password_confirmation}
                />

                <Button type="submit" className={gradientButtonClass} loading={processing}>
                    Create account
                </Button>
            </form>

            <p className="mt-6 text-center text-sm opacity-80">
                Already have an account? <AuthLink href={route('login')}>Sign in</AuthLink>
            </p>
        </AuthLayout>
    );
}
