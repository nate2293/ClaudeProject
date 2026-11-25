import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { KeyRound, LifeBuoy, Link2, LogOut, Mail, Package, Palette, RotateCcw, UserRound, type LucideIcon } from 'lucide-react';
import { useEffect, useRef } from 'react';

interface Item {
    label: string;
    href: string;
    icon: LucideIcon;
    /** Paths that count as this item being open (defaults to href). */
    match?: string;
}

const groups = (): Item[][] => [
    [
        { label: 'My orders', href: route('account.orders'), icon: Package, match: '/account/orders' },
        { label: 'Returns', href: route('pages.returns'), icon: RotateCcw },
        { label: 'Need help?', href: route('pages.contact'), icon: LifeBuoy },
    ],
    [
        { label: 'My details', href: route('profile.edit'), icon: UserRound },
        { label: 'Password', href: route('password.edit'), icon: KeyRound },
        { label: 'Contact preferences', href: route('account.contact'), icon: Mail },
        { label: 'Social accounts', href: route('account.social'), icon: Link2 },
        { label: 'Appearance', href: route('appearance'), icon: Palette },
    ],
];

function useActive() {
    const path = usePage().url.split('?')[0];

    return (item: Item) => (item.match ? path.startsWith(item.match) : path === new URL(item.href).pathname);
}

/** Phones: every account section in one scrollable row above the content. */
export function AccountSectionsBar() {
    const isActive = useActive();
    const activeRef = useRef<HTMLAnchorElement>(null);

    // Bring the current section into view, e.g. "Contact preferences" at the far end.
    useEffect(() => activeRef.current?.scrollIntoView({ block: 'nearest', inline: 'center' }), []);

    return (
        <nav aria-label="Account sections" className="-mx-4 mb-4 overflow-x-auto px-4 lg:hidden">
            <ul className="flex w-max gap-2">
                {groups()
                    .flat()
                    .map((item) => (
                        <li key={item.label}>
                            <Link
                                href={item.href}
                                ref={isActive(item) ? activeRef : undefined}
                                aria-current={isActive(item) ? 'page' : undefined}
                                className={cn(
                                    'flex items-center gap-2 rounded-full border px-3.5 py-2 text-sm whitespace-nowrap',
                                    isActive(item) ? 'bg-primary text-primary-foreground border-primary' : 'bg-background',
                                )}
                            >
                                <item.icon className="size-4" strokeWidth={1.5} aria-hidden />
                                {item.label}
                            </Link>
                        </li>
                    ))}
            </ul>
        </nav>
    );
}

/** The account area's left-hand column: who's signed in, and every account section. */
export function AccountSidebar() {
    const { auth } = usePage<SharedData>().props;
    const getInitials = useInitials();
    const isActive = useActive();

    return (
        <aside className="space-y-2">
            <div className="bg-background rounded-card flex items-center gap-4 border p-5">
                <Avatar className="size-14">
                    {auth.user.avatar_url && <AvatarImage src={auth.user.avatar_url} alt="" referrerPolicy="no-referrer" />}
                    <AvatarFallback className="bg-primary text-primary-foreground font-display text-lg">{getInitials(auth.user.name)}</AvatarFallback>
                </Avatar>
                <div className="min-w-0">
                    <p className="text-muted-foreground text-sm">Hi,</p>
                    <p className="font-display text-lg leading-tight break-words">{auth.user.name}</p>
                </div>
            </div>

            <nav aria-label="Account" className="space-y-2">
                {groups().map((items, i) => (
                    <ul key={i} className="bg-background rounded-card divide-y overflow-hidden border">
                        {items.map((item) => {
                            const active = isActive(item);

                            return (
                                <li key={item.label}>
                                    <Link
                                        href={item.href}
                                        aria-current={active ? 'page' : undefined}
                                        className={cn(
                                            'flex items-center gap-3 border-l-2 px-5 py-3.5 text-sm transition-colors',
                                            active ? 'border-primary bg-secondary/60 font-semibold' : 'hover:bg-secondary/40 border-transparent',
                                        )}
                                    >
                                        <item.icon className="size-[18px] shrink-0" strokeWidth={1.5} aria-hidden />
                                        {item.label}
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                ))}

                <div className="bg-background rounded-card overflow-hidden border">
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="hover:bg-secondary/40 flex w-full items-center gap-3 border-l-2 border-transparent px-5 py-3.5 text-sm transition-colors"
                    >
                        <LogOut className="size-[18px]" strokeWidth={1.5} aria-hidden />
                        Sign out
                    </Link>
                </div>
            </nav>
        </aside>
    );
}
