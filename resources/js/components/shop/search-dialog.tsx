import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { type FormEvent, useState } from 'react';

export function SearchDialog() {
    const [open, setOpen] = useState(false);
    const [term, setTerm] = useState('');

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const q = term.trim();

        if (!q) return;

        router.get(route('shop.index'), { q }, { onSuccess: () => setOpen(false) });
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" size="icon" aria-label="Search">
                    <Search className="size-5!" />
                </Button>
            </DialogTrigger>

            <DialogContent className="top-[20%] translate-y-0 sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="font-display text-2xl">Search the shop</DialogTitle>
                    <DialogDescription>Search by product name, cloth or type, such as merino or outerwear.</DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="flex gap-2" role="search">
                    <Input
                        type="search"
                        autoFocus
                        value={term}
                        onChange={(e) => setTerm(e.target.value)}
                        placeholder="Search products"
                        aria-label="Search products"
                        className="h-11"
                    />
                    <Button type="submit" size="lg" className="h-11">
                        Search
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
