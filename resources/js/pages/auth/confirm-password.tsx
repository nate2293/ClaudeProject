import { Button } from '@/components/ui/button';
import { GradientField, gradientButtonClass } from '@/components/ui/gradient-animated-form';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEventHandler } from 'react';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({ password: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.confirm'), { onFinish: () => reset('password') });
    };

    return (
        <AuthLayout title="Confirm your password" description="This is a secure area. Please confirm your password to continue.">
            <Head title="Confirm password" />

            <form onSubmit={submit} className="space-y-6" noValidate>
                <GradientField
                    id="password"
                    label="Password"
                    type="password"
                    revealable
                    required
                    autoFocus
                    autoComplete="current-password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                />
                <Button type="submit" className={gradientButtonClass} magneticStrength={0.15} disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" />}
                    Confirm password
                </Button>
            </form>
        </AuthLayout>
    );
}
