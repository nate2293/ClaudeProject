import { Button } from '@/components/ui/button';
import AccountLayout, { AccountPanel } from '@/layouts/account-layout';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import { useState } from 'react';

type NewsletterState = 'subscribed' | 'pending' | 'off';

const copy: Record<NewsletterState, string> = {
    subscribed: 'You’re subscribed.',
    pending: 'Waiting for you to confirm: check your inbox for our email.',
    off: 'You’re not subscribed.',
};

export default function ContactPreferences({ newsletter }: { newsletter: NewsletterState }) {
    const { auth } = usePage<SharedData>().props;
    const [busy, setBusy] = useState(false);
    const options = { preserveScroll: true, onStart: () => setBusy(true), onFinish: () => setBusy(false) };

    return (
        <AccountLayout title="Contact preferences">
            <AccountPanel className="p-5 sm:p-6">
                <div className="flex items-center gap-3">
                    <Mail className="size-6" strokeWidth={1.5} aria-hidden />
                    <h2 className="font-display text-xl sm:text-2xl">Contact preferences</h2>
                </div>

                <div className="mt-6 flex flex-col gap-4 border-t pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 className="font-semibold">Newsletter</h3>
                        <p className="text-muted-foreground mt-1 max-w-md text-sm">
                            New collections and the occasional sale, no more than twice a month, to {auth.user.email}.
                        </p>
                        <p className="mt-2 text-sm font-medium" role="status">
                            {copy[newsletter]}
                        </p>
                    </div>
                    {newsletter === 'subscribed' ? (
                        <Button variant="outline" disabled={busy} onClick={() => router.delete(route('account.newsletter.unsubscribe'), options)}>
                            Unsubscribe
                        </Button>
                    ) : (
                        <Button disabled={busy} onClick={() => router.post(route('account.newsletter.subscribe'), {}, options)}>
                            {newsletter === 'pending' ? 'Resend confirmation' : 'Subscribe'}
                        </Button>
                    )}
                </div>

                <div className="mt-6 border-t pt-6">
                    <h3 className="font-semibold">Order emails</h3>
                    <p className="text-muted-foreground mt-1 max-w-md text-sm">
                        We always email you about your orders (confirmation, delivery and refunds). These aren’t marketing, so there’s nothing to
                        switch off.
                    </p>
                </div>
            </AccountPanel>
        </AccountLayout>
    );
}
