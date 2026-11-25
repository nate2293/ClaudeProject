import { AuthStatus } from '@/components/auth/auth-bits';
import { Button } from '@/components/ui/button';
import { gradientButtonClass } from '@/components/ui/gradient-animated-form';
import AuthLayout from '@/layouts/auth-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    return (
        <AuthLayout title="Check your inbox" description="Click the link we emailed you to verify your address.">
            <Head title="Verify email" />

            {status === 'verification-link-sent' && <AuthStatus>We’ve sent a new verification link to your email address.</AuthStatus>}

            <form onSubmit={submit} className="space-y-5 text-center">
                <Button type="submit" className={gradientButtonClass} loading={processing}>
                    Resend the email
                </Button>
                <Link href={route('logout')} method="post" as="button" className="mx-auto block text-sm opacity-80 hover:underline">
                    Sign out
                </Link>
            </form>
        </AuthLayout>
    );
}
