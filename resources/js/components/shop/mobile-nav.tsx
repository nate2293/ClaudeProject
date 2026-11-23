import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Sheet, SheetClose, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { storeName } from '@/data/storefront';
import type { NavLink } from '@/types/shop';
import { Link } from '@inertiajs/react';
import { Menu } from 'lucide-react';

interface MobileNavProps {
    links: NavLink[];
    account: NavLink;
}

export function MobileNav({ links, account }: MobileNavProps) {
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

                <SheetClose asChild>
                    <Link href={account.href} className="text-muted-foreground hover:text-foreground text-sm font-medium">
                        {account.label}
                    </Link>
                </SheetClose>
            </SheetContent>
        </Sheet>
    );
}
