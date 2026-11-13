import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type Dashboard, type Item, type Paginated, type Review, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { type FormEvent, useEffect, useState } from 'react';

/*
 * A deliberately simple shop page. All data comes from the REST API: the page starts at
 * GET /api/dashboard and then moves around by following the "_links" and pagination links
 * that the API returns.
 */

type View =
    | { name: 'home' }
    | { name: 'items'; title: string; description: string | null; itemsUrl: string }
    | { name: 'item'; itemUrl: string; back: View };

export default function Shop() {
    const { auth } = usePage<SharedData>().props;
    const [view, setView] = useState<View>({ name: 'home' });

    const openItem = (item: Item) => setView({ name: 'item', itemUrl: item._links.self.href, back: view });
    const goHome = () => setView({ name: 'home' });

    return (
        <>
            <Head title="Shop" />
            <div className="mx-auto max-w-4xl px-4 py-6">
                <header className="mb-8 flex items-center justify-between border-b pb-4">
                    <button onClick={goHome} className="text-2xl font-bold text-orange-600">
                        Aligaga
                    </button>
                    <nav className="flex gap-4 text-sm">
                        <a href={route('docs')} className="hover:underline">
                            API docs
                        </a>
                        {auth.user ? (
                            <Link href={route('dashboard')} className="hover:underline">
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link href={route('login')} className="hover:underline">
                                    Log in
                                </Link>
                                <Link href={route('register')} className="hover:underline">
                                    Register
                                </Link>
                            </>
                        )}
                    </nav>
                </header>

                {view.name === 'home' && <HomePage openView={setView} openItem={openItem} />}
                {view.name === 'items' && <ItemsPage key={view.itemsUrl} view={view} openItem={openItem} goBack={goHome} />}
                {view.name === 'item' && <ItemPage key={view.itemUrl} itemUrl={view.itemUrl} goBack={() => setView(view.back)} />}
            </div>
        </>
    );
}

/** Loads JSON from the API. Changing `reloadKey` loads it again. */
function useApi<T>(url: string | null, reloadKey = 0): T | null {
    const [data, setData] = useState<T | null>(null);

    useEffect(() => {
        if (!url) {
            return;
        }

        let isCurrentRequest = true;

        fetch(url, { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((json) => {
                if (isCurrentRequest) {
                    setData(json);
                }
            });

        return () => {
            isCurrentRequest = false;
        };
    }, [url, reloadKey]);

    return data;
}

function HomePage({ openView, openItem }: { openView: (view: View) => void; openItem: (item: Item) => void }) {
    const dashboard = useApi<Dashboard>(route('api.dashboard'));

    if (!dashboard) {
        return <Loading />;
    }

    const { totals, categories, top_rated_items, latest_reviews } = dashboard.data;

    const tiles = [
        { title: 'All items', description: null, count: totals.items, itemsUrl: dashboard._links.items.href },
        ...categories.map((category) => ({
            title: category.name,
            description: category.description,
            count: category.items_count,
            itemsUrl: category._links.items.href,
        })),
    ];

    return (
        <div className="space-y-10">
            <p className="text-muted-foreground">
                {totals.items} items in {totals.categories} categories · {totals.reviews} reviews · average rating ★ {totals.average_rating}
            </p>

            <section>
                <h2 className="mb-3 text-lg font-semibold">Categories</h2>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    {tiles.map((tile) => (
                        <button
                            key={tile.itemsUrl}
                            onClick={() => openView({ name: 'items', ...tile })}
                            className="rounded-lg border p-4 text-left hover:border-orange-500"
                        >
                            <span className="block font-medium">{tile.title}</span>
                            <span className="text-muted-foreground text-sm">{countOf(tile.count, 'item')}</span>
                        </button>
                    ))}
                </div>
            </section>

            <section>
                <h2 className="mb-3 text-lg font-semibold">Top rated items</h2>
                <ItemList items={top_rated_items} openItem={openItem} />
            </section>

            <section>
                <h2 className="mb-3 text-lg font-semibold">Latest reviews</h2>
                <ReviewList reviews={latest_reviews} showItemName />
            </section>
        </div>
    );
}

function ItemsPage({ view, openItem, goBack }: { view: Extract<View, { name: 'items' }>; openItem: (item: Item) => void; goBack: () => void }) {
    const [pageUrl, setPageUrl] = useState(view.itemsUrl);
    const [search, setSearch] = useState('');
    const page = useApi<Paginated<Item>>(pageUrl);

    function searchItems(event: FormEvent) {
        event.preventDefault();

        const url = new URL(view.itemsUrl);
        url.searchParams.set('search', search);
        setPageUrl(url.toString());
    }

    return (
        <div className="space-y-4">
            <BackButton onClick={goBack} />
            <div>
                <h1 className="text-2xl font-bold">{view.title}</h1>
                {view.description && <p className="text-muted-foreground">{view.description}</p>}
            </div>

            <form onSubmit={searchItems} className="flex gap-2">
                <Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search by name" />
                <Button type="submit">Search</Button>
            </form>

            {!page ? (
                <Loading />
            ) : (
                <>
                    {page.data.length > 0 ? (
                        <ItemList items={page.data} openItem={openItem} />
                    ) : (
                        <p className="text-muted-foreground">No items found.</p>
                    )}

                    <div className="flex items-center justify-between text-sm">
                        <Button variant="outline" disabled={!page.links.prev} onClick={() => page.links.prev && setPageUrl(page.links.prev)}>
                            ← Previous
                        </Button>
                        <span>
                            Page {page.meta.current_page} of {page.meta.last_page}
                        </span>
                        <Button variant="outline" disabled={!page.links.next} onClick={() => page.links.next && setPageUrl(page.links.next)}>
                            Next →
                        </Button>
                    </div>
                </>
            )}
        </div>
    );
}

function ItemPage({ itemUrl, goBack }: { itemUrl: string; goBack: () => void }) {
    const [reloadKey, setReloadKey] = useState(0);
    const item = useApi<{ data: Item }>(itemUrl, reloadKey)?.data;
    const reviews = useApi<Paginated<Review>>(item ? item._links.reviews.href : null, reloadKey);

    if (!item) {
        return <Loading />;
    }

    return (
        <div className="space-y-8">
            <BackButton onClick={goBack} />

            <div className="space-y-2">
                <p className="text-muted-foreground text-sm">{item.category_name}</p>
                <h1 className="text-2xl font-bold">{item.name}</h1>
                <p className="text-sm">{ratingText(item)}</p>
                <p className="text-3xl font-bold text-orange-600">€{item.price.toFixed(2)}</p>
                <p className="text-muted-foreground text-sm">{item.stock > 0 ? `${item.stock} in stock` : 'Out of stock'}</p>
                {item.description && <p>{item.description}</p>}
            </div>

            <section>
                <h2 className="mb-3 text-lg font-semibold">Reviews</h2>
                {reviews ? <ReviewList reviews={reviews.data} /> : <Loading />}
            </section>

            <ReviewForm itemId={item.id} onCreated={() => setReloadKey((key) => key + 1)} />
        </div>
    );
}

const emptyReview = { author_name: '', rating: '5', comment: '' };

function ReviewForm({ itemId, onCreated }: { itemId: number; onCreated: () => void }) {
    const [review, setReview] = useState(emptyReview);
    const [errors, setErrors] = useState<Record<string, string[]>>({});

    async function postReview(event: FormEvent) {
        event.preventDefault();

        const response = await fetch(route('api.reviews.store'), {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...review, item_id: itemId, rating: Number(review.rating) }),
        });

        if (!response.ok) {
            const body = await response.json();
            setErrors(body.errors ?? { form: [body.message] });
            return;
        }

        setErrors({});
        setReview(emptyReview);
        onCreated();
    }

    return (
        <form onSubmit={postReview} className="space-y-4 rounded-lg border p-4">
            <h2 className="text-lg font-semibold">Write a review</h2>

            <div className="grid gap-2">
                <Label htmlFor="author_name">Your name</Label>
                <Input id="author_name" value={review.author_name} onChange={(event) => setReview({ ...review, author_name: event.target.value })} />
                <InputError message={errors.author_name?.[0]} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="rating">Rating</Label>
                <select
                    id="rating"
                    value={review.rating}
                    onChange={(event) => setReview({ ...review, rating: event.target.value })}
                    className="border-input bg-background h-10 rounded-md border px-3"
                >
                    {[5, 4, 3, 2, 1].map((stars) => (
                        <option key={stars} value={stars}>
                            {stars} ★
                        </option>
                    ))}
                </select>
                <InputError message={errors.rating?.[0]} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="comment">Comment</Label>
                <textarea
                    id="comment"
                    rows={3}
                    value={review.comment}
                    onChange={(event) => setReview({ ...review, comment: event.target.value })}
                    className="border-input bg-background rounded-md border px-3 py-2"
                />
                <InputError message={errors.comment?.[0]} />
            </div>

            <InputError message={errors.form?.[0]} />
            <Button type="submit">Post review</Button>
        </form>
    );
}

function ItemList({ items, openItem }: { items: Item[]; openItem: (item: Item) => void }) {
    return (
        <ul className="divide-y rounded-lg border">
            {items.map((item) => (
                <li key={item.id}>
                    <button onClick={() => openItem(item)} className="hover:bg-muted flex w-full items-center justify-between gap-4 p-3 text-left">
                        <span>
                            <span className="block font-medium">{item.name}</span>
                            <span className="text-muted-foreground text-sm">
                                {item.category_name} · {ratingText(item)}
                                {item.stock === 0 && ' · out of stock'}
                            </span>
                        </span>
                        <span className="font-semibold whitespace-nowrap">€{item.price.toFixed(2)}</span>
                    </button>
                </li>
            ))}
        </ul>
    );
}

function ReviewList({ reviews, showItemName = false }: { reviews: Review[]; showItemName?: boolean }) {
    if (reviews.length === 0) {
        return <p className="text-muted-foreground">No reviews yet.</p>;
    }

    return (
        <ul className="space-y-3">
            {reviews.map((review) => (
                <li key={review.id} className="rounded-lg border p-3">
                    <p className="text-sm">
                        <span className="text-orange-500">{'★'.repeat(review.rating) + '☆'.repeat(5 - review.rating)}</span>{' '}
                        <span className="font-medium">{review.author_name}</span>
                        {showItemName && <span className="text-muted-foreground"> about {review.item_name}</span>}
                        <span className="text-muted-foreground"> · {new Date(review.created_at).toLocaleDateString()}</span>
                    </p>
                    <p className="mt-1">{review.comment}</p>
                </li>
            ))}
        </ul>
    );
}

function ratingText(item: Item): string {
    return item.average_rating === null ? 'No reviews yet' : `★ ${item.average_rating.toFixed(1)} (${countOf(item.reviews_count, 'review')})`;
}

/** "1 item", "4 items" */
function countOf(count: number, word: string): string {
    return `${count} ${word}${count === 1 ? '' : 's'}`;
}

function BackButton({ onClick }: { onClick: () => void }) {
    return (
        <button onClick={onClick} className="text-muted-foreground text-sm hover:underline">
            ← Back
        </button>
    );
}

function Loading() {
    return <p className="text-muted-foreground">Loading…</p>;
}
