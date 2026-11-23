/**
 * Storefront types.
 *
 * These mirror the JSON produced by the Laravel API resources in
 * `app/Http/Resources`, so keep the two in sync when adding fields.
 */

export interface NavLink {
    label: string;
    href: string;
}

export interface FooterLinkGroup {
    title: string;
    links: NavLink[];
}

export interface Category {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    href: string;
    /** Optional photo URL. A styled placeholder is shown when omitted. */
    image: string | null;
    /** Background colour used by the image placeholder. */
    swatch: string;
    /** Number of active products; only present where the server counts them. */
    productCount?: number;
}

export interface ColourOption {
    name: string;
    hex: string;
}

export type ProductBadge = 'new' | 'sale' | 'bestseller';

export interface Product {
    id: number;
    slug: string;
    name: string;
    type: string;
    description: string;
    materials: string | null;
    /** Price in minor units (pence) to avoid floating point issues. */
    price: number;
    /** Original price in minor units when the product is discounted. */
    compareAtPrice: number | null;
    badge: ProductBadge | null;
    colours: ColourOption[];
    sizes: string[];
    image: string | null;
    swatch: string;
    href: string;
    category?: { name: string; slug: string; href: string };
    /** Listings only: false when every variant is sold out. */
    inStock?: boolean;
    /** Product page only: units per variant, capped at the per-line maximum. */
    stock?: VariantStock[];
}

export interface VariantStock {
    colour: string;
    size: string;
    available: number;
}

export interface CatalogueFilters {
    q: string | null;
    type: string | null;
    sort: 'featured' | 'newest' | 'price-asc' | 'price-desc';
    sale: boolean;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

/** Shape of a paginated Laravel resource collection. */
export interface Paginated<T> {
    data: T[];
    links: { first: string | null; last: string | null; prev: string | null; next: string | null };
    meta: {
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
        per_page: number;
        links: PaginationLink[];
    };
}

export interface CartLine {
    key: string;
    colour: string;
    colourHex: string | null;
    size: string;
    quantity: number;
    /** Units this shopper can have, capped at the per-line maximum. */
    available: number;
    unitPrice: number;
    total: number;
    product: {
        name: string;
        type: string;
        swatch: string;
        image: string | null;
        href: string;
    };
}

export interface Cart {
    lines: CartLine[];
    count: number;
    /** True when a line wants more than is in stock; checkout would be refused. */
    hasStockIssues: boolean;
    subtotal: number;
    shipping: number;
    total: number;
}

export type OrderStatus = 'pending' | 'processing' | 'paid' | 'failed' | 'cancelled' | 'partially_refunded' | 'refunded';

export interface Order {
    reference: string;
    status: OrderStatus;
    statusLabel: string;
    currency: string;
    subtotal: number;
    shipping: number;
    total: number;
    /** VAT included in the total; null when the shop isn't charging VAT. */
    vat: { rate: string; amount: number } | null;
    placedAt: string;
    items: {
        id: number;
        name: string;
        colour: string;
        size: string;
        quantity: number;
        unitPrice: number;
        total: number;
    }[];
}

export interface Testimonial {
    quote: string;
    author: string;
    location: string;
    rating: number;
}

/** An order as shown in the customer's account (App\Http\Resources\AccountOrderResource). */
export interface AccountOrder {
    reference: string;
    placedAt: string;
    shippedAt: string | null;
    returnBy: string | null;
    progress: { step: number; steps: number; title: string; detail: string; tone: 'active' | 'done' | 'waiting' | 'muted' };
    currency: string;
    subtotal: number;
    shipping: number;
    total: number;
    vat: { rate: string; amount: number } | null;
    refunded: number;
    tracking: { carrier: string | null; number: string | null; url: string | null } | null;
    /** Only on the order page. */
    deliverTo?: { name: string | null; lines: string[] };
    href: string;
    items: {
        id: number;
        name: string;
        colour: string;
        size: string;
        quantity: number;
        total: number;
        image: string | null;
        swatch: string;
        href: string | null;
    }[];
}
