import { Button } from '@/components/ui/button';
import AccountLayout, { AccountPanel } from '@/layouts/account-layout';
import { type SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { Link2 } from 'lucide-react';

export default function SocialAccounts({ google }: { google: { connected: boolean } }) {
    const { hasPassword } = usePage<SharedData>().props.auth;

    return (
        <AccountLayout title="Social accounts">
            <AccountPanel className="p-5 sm:p-6">
                <div className="flex items-center gap-3">
                    <Link2 className="size-6" strokeWidth={1.5} aria-hidden />
                    <h2 className="font-display text-xl sm:text-2xl">Social accounts</h2>
                </div>
                <p className="text-muted-foreground mt-2 text-sm">Sign in faster with an account you already have.</p>

                <div className="mt-6 flex flex-col gap-4 border-t pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <svg aria-hidden viewBox="0 0 48 48" className="size-6 shrink-0">
                            <path
                                fill="#EA4335"
                                d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"
                            />
                            <path
                                fill="#4285F4"
                                d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"
                            />
                            <path
                                fill="#FBBC05"
                                d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"
                            />
                            <path
                                fill="#34A853"
                                d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"
                            />
                        </svg>
                        <div>
                            <p className="font-semibold">Google</p>
                            <p className="text-muted-foreground text-sm">{google.connected ? 'Connected' : 'Not connected'}</p>
                        </div>
                    </div>

                    {google.connected ? (
                        <div className="flex flex-col items-start gap-2 sm:items-end">
                            <Button
                                variant="outline"
                                disabled={!hasPassword}
                                onClick={() => router.delete(route('account.social.google.disconnect'), { preserveScroll: true })}
                            >
                                Disconnect
                            </Button>
                            {!hasPassword && (
                                <p className="text-muted-foreground max-w-56 text-xs sm:text-right">
                                    <Link href={route('password.edit')} className="text-foreground underline underline-offset-4">
                                        Set a password
                                    </Link>{' '}
                                    first, so you can still sign in.
                                </p>
                            )}
                        </div>
                    ) : (
                        <Button variant="outline" asChild>
                            <a href={route('auth.google.redirect')}>Connect Google</a>
                        </Button>
                    )}
                </div>
            </AccountPanel>
        </AccountLayout>
    );
}
