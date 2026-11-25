import { LucideIcon } from 'lucide-react';
import type { NavLink } from './shop';

export interface Auth {
    user: User;
    /** False for customers who only sign in with Google. */
    hasPassword: boolean;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    /** Storefront departments for the main navigation. */
    navigation: NavLink[];
    shop: {
        currency: string;
        freeShippingThreshold: number;
        /** Days after delivery an unworn item can be returned (config/shop.php). */
        returnDays: number;
        standardShippingRate: number;
        maxQuantity: number;
        lowStockThreshold: number;
        business: BusinessDetails;
    };
    cart: { count: number };
    flash: { success: string | null; error: string | null };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    /** Google profile photo, for customers who signed in with Google. */
    avatar_url?: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

/** Legal business details from config/shop.php, shown on policy pages. Null when not yet configured. */
export interface BusinessDetails {
    legalName: string;
    address: string | null;
    email: string | null;
    companyNumber: string | null;
    vatNumber: string | null;
    icoNumber: string | null;
    host: string | null;
    emailProvider: string | null;
}
