import AccountLayout, { AccountPanel } from '@/layouts/account-layout';
import { type ReactNode } from 'react';

/** My details, Password and Appearance: account sections with a single form panel. */
export default function SettingsLayout({ children }: { children: ReactNode }) {
    return (
        <AccountLayout>
            <AccountPanel className="p-5 sm:p-6">
                <div className="max-w-xl space-y-12">{children}</div>
            </AccountPanel>
        </AccountLayout>
    );
}
