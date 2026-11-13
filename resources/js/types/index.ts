import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
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
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

// Shapes of the shop REST API responses (/api/...).

export interface ApiLink {
    href: string;
    method: string;
}

export interface Category {
    id: number;
    name: string;
    description: string | null;
    items_count: number;
    created_at: string;
    updated_at: string;
    _links: { self: ApiLink; update: ApiLink; delete: ApiLink; items: ApiLink };
}

export interface Item {
    id: number;
    category_id: number;
    category_name: string;
    name: string;
    description: string | null;
    price: number;
    stock: number;
    reviews_count: number;
    average_rating: number | null;
    created_at: string;
    updated_at: string;
    _links: { self: ApiLink; update: ApiLink; delete: ApiLink; category: ApiLink; reviews: ApiLink };
}

export interface Review {
    id: number;
    item_id: number;
    item_name: string;
    author_name: string;
    rating: number;
    comment: string;
    created_at: string;
    updated_at: string;
    _links: { self: ApiLink; update: ApiLink; delete: ApiLink; item: ApiLink };
}

export interface Paginated<T> {
    data: T[];
    links: { first: string; last: string; prev: string | null; next: string | null };
    meta: { current_page: number; last_page: number; per_page: number; total: number };
}

export interface Dashboard {
    data: {
        totals: { categories: number; items: number; items_out_of_stock: number; reviews: number; average_rating: number };
        categories: Category[];
        top_rated_items: Item[];
        latest_reviews: Review[];
    };
    _links: { self: ApiLink; categories: ApiLink; items: ApiLink; reviews: ApiLink };
}
