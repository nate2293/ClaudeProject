import ShopLayout from '@/layouts/shop-layout';
import { type ReactNode } from 'react';
import { Container } from './container';
import { PageHeader } from './page-header';

export interface InfoSection {
    heading: string;
    body: ReactNode;
}

interface InfoPageProps {
    title: string;
    description: string;
    sections: InfoSection[];
    /** Optional note shown below the content, e.g. "Last updated". */
    footnote?: string;
}

/** Shared template for static customer-information pages (delivery, returns, etc.). */
export function InfoPage({ title, description, sections, footnote }: InfoPageProps) {
    return (
        <ShopLayout title={title}>
            <PageHeader title={title} description={description} breadcrumbs={[{ label: 'Home', href: route('home') }, { label: title }]} />

            <Container className="max-w-3xl py-12 sm:py-16">
                <div className="space-y-10">
                    {sections.map((section) => (
                        <section key={section.heading}>
                            <h2 className="font-display text-2xl">{section.heading}</h2>
                            <div className="text-muted-foreground [&_a]:text-foreground [&_strong]:text-foreground mt-3 space-y-3 leading-relaxed [&_a]:underline [&_a]:underline-offset-4">
                                {section.body}
                            </div>
                        </section>
                    ))}
                </div>

                {footnote && <p className="text-muted-foreground mt-14 border-t pt-6 text-xs">{footnote}</p>}
            </Container>
        </ShopLayout>
    );
}
