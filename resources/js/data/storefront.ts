import type { FooterLinkGroup, NavLink, Testimonial } from '@/types/shop';

/**
 * Static storefront copy and links.
 *
 * Catalogue data (categories, products) comes from Laravel as page props.
 * Links are built with Ziggy's `route()` inside functions so they always
 * resolve against the current route list.
 */

export const storeName = 'Loom & Lane';

export const announcement = 'Free UK delivery over £75, and free returns within 30 days.';

/** Main navigation: "New In", the department links shared by Laravel, then "Sale". */
export function primaryNav(departments: NavLink[]): NavLink[] {
    return [
        { label: 'New In', href: route('shop.index', { sort: 'newest' }) },
        ...departments,
        { label: 'Sale', href: route('shop.index', { sale: 1 }) },
    ];
}

export function footerLinks(departments: NavLink[]): FooterLinkGroup[] {
    return [
        {
            title: 'Shop',
            links: [{ label: 'Shop all', href: route('shop.index') }, ...departments, { label: 'Sale', href: route('shop.index', { sale: 1 }) }],
        },
        {
            title: 'Help',
            links: [
                { label: 'Delivery', href: route('pages.delivery') },
                { label: 'Returns', href: route('pages.returns') },
                { label: 'Size guide', href: route('pages.size-guide') },
                { label: 'Contact us', href: route('pages.contact') },
            ],
        },
        {
            title: 'About',
            links: [
                { label: 'Our story', href: route('pages.about') },
                { label: 'Privacy policy', href: route('pages.privacy') },
                { label: 'Terms of sale', href: route('pages.terms') },
            ],
        },
    ];
}

/** Replace with your real profile URLs. */
export const socialLinks: NavLink[] = [
    { label: 'Instagram', href: 'https://www.instagram.com/' },
    { label: 'Pinterest', href: 'https://www.pinterest.com/' },
    { label: 'TikTok', href: 'https://www.tiktok.com/' },
];

export const testimonials: Testimonial[] = [
    {
        quote: 'The merino jumper is the softest thing I own. Three winters in and it still looks brand new.',
        author: 'Hannah R.',
        location: 'Bristol',
        rating: 5,
    },
    {
        quote: 'Finally a shop where the sizing is consistent. Ordered two shirts and both fitted perfectly.',
        author: 'James T.',
        location: 'Manchester',
        rating: 5,
    },
    {
        quote: 'Beautiful packaging, quick delivery and the overcoat is even better in person.',
        author: 'Priya S.',
        location: 'Edinburgh',
        rating: 4,
    },
];
