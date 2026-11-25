import type { FooterLinkGroup, NavLink } from '@/types/shop';

/**
 * Static storefront copy and links.
 *
 * Catalogue data (categories, products) comes from Laravel as page props.
 * Links are built with Ziggy's `route()` inside functions so they always
 * resolve against the current route list.
 */

export const storeName = 'Loom & Lane';

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
