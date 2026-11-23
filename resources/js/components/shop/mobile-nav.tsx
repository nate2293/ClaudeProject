import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Sheet, SheetClose, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { storeName } from '@/data/storefront';
import { type SharedData } from '@/types';
import type { NavLink } from '@/types/shop';
import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { SignInOptions } from './account-menu';

interface MobileNavProps {
    links: NavLink[];
}

export function MobileNav({ links }: MobileNavProps) {
    const { auth } = usePage<SharedData>().props;

    return (
        <Sheet>
            <SheetTrigger asChild>
                <Button variant="ghost" size="icon" className="lg:hidden" aria-label="Open menu">
                    <Menu className="size-5!" />
                </Button>
            </SheetTrigger>

            <SheetContent side="left" className="flex w-full max-w-xs flex-col">
                <SheetHeader className="text-left">
                    <SheetTitle className="font-display text-2xl">{storeName}</SheetTitle>
                </SheetHeader>

                <nav className="mt-6 flex flex-col" aria-label="Mobile">
                    {links.map((link) => (
                        <SheetClose asChild key={link.label}>
                            <Link href={link.href} className="hover:text-brand py-3 text-lg font-medium transition-colors">
                                {link.label}
                            </Link>
                        </SheetClose>
                    ))}
                </nav>

                <Separator className="my-4" />

                {auth.user ? (
                    <div className="flex flex-col gap-3 text-sm font-medium">
                        <p className="text-muted-foreground truncate font-normal">Signed in as {auth.user.name}</p>
                        <SheetClose asChild>
                            <Link href={route('profile.edit')} className="hover:text-brand">
                                Account settings
                            </Link>
                        </SheetClose>
                        <Link href={route('logout')} method="post" as="button" className="hover:text-brand text-left">
                            Sign out
                        </Link>
                    </div>
                ) : (
                    <SignInOptions />
                )}
            </SheetContent>
        </Sheet>
    );
}
