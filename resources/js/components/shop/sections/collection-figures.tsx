import { LogosMetricOverDualRowLogos, type MarqueeItem } from '@/components/ui/logos-metric-over-dual-row-logos';
import { formatPrice } from '@/lib/format';
import { Briefcase, Droplets, Feather, Gem, Layers, Leaf, Recycle, Scissors, Shirt, Snowflake, Sprout, Wind } from 'lucide-react';
import type { ReactNode } from 'react';
import { Container } from '../container';

export interface CollectionFigures {
    pieces: number;
    cloths: string[];
    names: { name: string; swatch?: string | null }[];
    freeDeliveryOver: number;
    returnDays: number;
}

const clothIcons: Record<string, ReactNode> = {
    'Merino wool': <Snowflake />,
    Lambswool: <Feather />,
    Cashmere: <Gem />,
    Linen: <Wind />,
    'Organic cotton': <Leaf />,
    'Waxed cotton': <Droplets />,
    'Selvedge denim': <Layers />,
    Flannel: <Shirt />,
    'TENCEL™ lyocell': <Sprout />,
    Canvas: <Scissors />,
    Leather: <Briefcase />,
    'Recycled nylon': <Recycle />,
};

/** Delivery and returns, over the cloths the collection is made from and the pieces in it. */
export function CollectionFiguresSection({ collection }: { collection: CollectionFigures }) {
    if (collection.pieces === 0) return null;

    const cloths: MarqueeItem[] = collection.cloths.map((cloth) => ({ label: cloth, icon: clothIcons[cloth] ?? <Shirt /> }));
    const pieces: MarqueeItem[] = collection.names.map((piece) => ({ label: piece.name, swatch: piece.swatch ?? undefined }));

    return (
        <Container>
            <LogosMetricOverDualRowLogos
                className="border-border border-t"
                label="The collection"
                eyebrow=""
                metric={null}
                description={null}
                facts={[`Free UK delivery over ${formatPrice(collection.freeDeliveryOver)}`, `Free returns within ${collection.returnDays} days`]}
                topRow={cloths}
                topRowLabel="Cloths we use"
                bottomRow={pieces}
                bottomRowLabel="Pieces in the collection"
            />
        </Container>
    );
}
