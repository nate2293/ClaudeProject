import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/hooks/use-initials';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LogOut, Settings, User } from 'lucide-react';
import { GoogleButton } from './google-button';

/** The header's account control: a sign-in dialog for guests, an account menu once signed in. */
export function AccountMenu() {
    const { auth } = usePage<SharedData>().props;

    return auth.user ? <SignedInMenu /> : <SignInDialog />;
}

function SignedInMenu() {
    const { auth } = usePage<SharedData>().props;
    const getInitials = useInitials();
    const user = auth.user;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" aria-label={`Account menu for ${user.name}`} className="rounded-full">
                    <Avatar className="size-8">
                        {user.avatar_url && <AvatarImage src={user.avatar_url} alt="" referrerPolicy="no-referrer" />}
                        <AvatarFallback className="bg-secondary text-xs font-semibold">{getInitials(user.name)}</AvatarFallback>
                    </Avatar>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-60">
                <DropdownMenuLabel className="font-normal">
                    <p className="truncate font-medium">{user.name}</p>
                    <p className="text-muted-foreground truncate text-xs">{user.email}</p>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link href={route('profile.edit')} className="cursor-pointer">
                        <Settings />
                        Account settings
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link href={route('logout')} method="post" as="button" className="w-full cursor-pointer">
                        <LogOut />
                        Sign out
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function SignInDialog() {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="ghost" size="icon" aria-label="Sign in">
                    <User className="size-5!" />
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle className="font-display text-2xl">Sign in</DialogTitle>
                    <DialogDescription>Save your details for faster checkout. You’ll come straight back to this page.</DialogDescription>
                </DialogHeader>
                <SignInOptions />
            </DialogContent>
        </Dialog>
    );
}

/** Google first, email as the alternative. Shared by the dialog and the mobile menu. */
export function SignInOptions() {
    const { url } = usePage();

    return (
        <div className="space-y-4">
            <GoogleButton />
            <div className="text-muted-foreground flex items-center gap-3 text-xs" aria-hidden>
                <span className="bg-border h-px flex-1" />
                or
                <span className="bg-border h-px flex-1" />
            </div>
            <div className="flex flex-col gap-2 text-center text-sm">
                <Link href={route('login', { return: url })} className="font-medium underline underline-offset-4">
                    Sign in with email
                </Link>
                <Link href={route('register', { return: url })} className="text-muted-foreground hover:text-foreground">
                    New here? Create an account
                </Link>
            </div>
        </div>
    );
}
