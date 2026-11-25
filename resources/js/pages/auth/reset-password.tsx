import { Button } from '@/components/ui/button';
import { GradientField, gradientButtonClass } from '@/components/ui/gradient-animated-form';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface ResetPasswordProps {
    token: string;
    email: string;
}

type ResetPasswordForm = {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { data, setData, post, processing, errors, reset } = useForm<ResetPasswordForm>({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.store'), { onFinish: () => reset('password', 'password_confirmation') });
    };

    return (
        <AuthLayout title="Choose a new password" description="Use at least 8 characters.">
            <Head title="Reset password" />

            <form onSubmit={submit} className="space-y-5" noValidate>
                <GradientField id="email" label="Email address" type="email" autoComplete="email" value={data.email} readOnly error={errors.email} />
                <GradientField
                    id="password"
                    label="New password"
                    type="password"
                    revealable
                    required
                    autoFocus
                    autoComplete="new-password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                />
                <GradientField
                    id="password_confirmation"
                    label="Confirm new password"
                    type="password"
                    revealable
                    required
                    autoComplete="new-password"
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                    error={errors.password_confirmation}
                />
                <Button type="submit" className={gradientButtonClass} loading={processing}>
                    Save new password
                </Button>
            </form>
        </AuthLayout>
    );
}
