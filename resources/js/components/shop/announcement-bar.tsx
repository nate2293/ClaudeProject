export function AnnouncementBar({ message }: { message: string }) {
    return (
        <div className="bg-foreground text-background">
            <p className="mx-auto max-w-7xl px-4 py-2 text-center text-xs font-medium tracking-wide sm:text-sm">{message}</p>
        </div>
    );
}
