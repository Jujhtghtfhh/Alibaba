# Aligaga – a small AliExpress-like shop

A Laravel 12 + React (Inertia) web app with a REST API for three related resources:

**Category → Item → Review** (a category has many items, an item has many reviews).

- REST API: all 15 methods (LIST, CREATE, READ, UPDATE, DELETE for each resource) + a dashboard
- Pagination, filtering, hypermedia links (`_links`), correct status codes (200, 201, 204, 400, 404, 422)
- MySQL database filled with realistic demo data (6 categories, 24 items, 60 reviews)
- OpenAPI specification with Swagger UI, a Postman collection with tests, Pest feature tests
- A deliberately minimal React shop page that uses the API

## Quick start

You need PHP 8.2+ (with `pdo_mysql`), Composer, Node.js 18+ and MySQL 8 or MariaDB (XAMPP, Laragon or the MySQL installer all work).

1. Start MySQL. `.env` uses database `aligaga`, user `root` and an empty password (the XAMPP/Laragon default).
   If your MySQL user or password is different, change the `DB_*` lines in `.env`.
   The database is created automatically if it does not exist yet.
2. Install everything, create the tables and fill them with demo data:
   ```bash
   composer setup
   ```
3. Start the server:
   ```bash
   php artisan serve
   ```

| Open | What you see |
| --- | --- |
| http://127.0.0.1:8000 | The shop (React). It reads everything from the API. |
| http://127.0.0.1:8000/docs | Swagger UI for the OpenAPI spec (`public/openapi.yaml`). "Try it out" calls the real API. |
| http://127.0.0.1:8000/api/dashboard | The API entry point (JSON). |

The `/docs` page loads Swagger UI from a CDN, so it needs internet access. Without internet, open `public/openapi.yaml` in any OpenAPI viewer (e.g. import it into Postman).

`composer setup` runs `composer install`, creates `.env` and the app key, runs `php artisan migrate:fresh --seed`, then `npm install` and `npm run build`.
While working on the frontend, run `npm run dev` next to `php artisan serve`.

## Demo for the defense (about 15 seconds)

1. Reset the database to fresh demo data and start the server:
   ```bash
   composer demo
   ```
2. In Postman: **Import** → `postman/aligaga.postman_collection.json` → open the collection → **Run** → **Run Aligaga Shop API**.
   22 requests with 74 tests run in about one second:
   - **01–15**: all 15 API methods. The collection creates its own category, item and review, updates them and deletes them again, so it can be run any number of times.
   - **Dashboard**: the resource built from categories, items and reviews.
   - **Error responses**: 404 (deleted category, missing item), 422 (invalid fields, invalid filter), 400 (malformed JSON).

   The same run from a terminal: `npx newman run postman/aligaga.postman_collection.json`
3. Automated tests (they use an in-memory SQLite database, so your MySQL data is not touched):
   ```bash
   php artisan test
   ```

The required cases in the collection:

| Case | Request | Answer |
| --- | --- | --- |
| Resource created | `02 CREATE category`, `06 CREATE item`, `10 CREATE review` | `201 Created` + `Location` header + the new resource |
| Resource deleted | `13 DELETE review`, `14 DELETE item`, `15 DELETE category` | `204 No Content`, empty body |
| Resource not found | `404 - READ the deleted category` | `404` `{"message": "Category not found."}` |
| Broken JSON body | `400 - CREATE category with malformed JSON` | `400` `{"message": "Malformed JSON in the request body: Syntax error."}` |
| Valid JSON, invalid data | `422 - CREATE item with invalid fields` | `422` with an `errors` object listing every invalid field |

## Data model

| Table | Fields |
| --- | --- |
| `categories` | `id`, `name` (unique, max 100), `description` (nullable), `created_at`, `updated_at` |
| `items` | `id`, `category_id` → categories, `name`, `description` (nullable), `price` (decimal 10,2), `stock`, `created_at`, `updated_at` |
| `reviews` | `id`, `item_id` → items, `author_name`, `rating` (1–5), `comment`, `created_at`, `updated_at` |

Deleting a category also deletes its items, and deleting an item also deletes its reviews (`ON DELETE CASCADE`).
The `users` table comes from the Laravel starter kit and is not part of the shop API.

## API

Base URL: `http://127.0.0.1:8000/api`. Requests and responses are JSON (`Content-Type: application/json`).
The full specification of every method is in [`public/openapi.yaml`](public/openapi.yaml) (Swagger UI at `/docs`).

| # | Method | URL | What it does | Success |
| --- | --- | --- | --- | --- |
| 1 | GET | `/categories` | List categories | 200 |
| 2 | POST | `/categories` | Create a category | 201 |
| 3 | GET | `/categories/{id}` | Read a category | 200 |
| 4 | PUT / PATCH | `/categories/{id}` | Update a category | 200 |
| 5 | DELETE | `/categories/{id}` | Delete a category | 204 |
| 6 | GET | `/items` | List items | 200 |
| 7 | POST | `/items` | Create an item | 201 |
| 8 | GET | `/items/{id}` | Read an item | 200 |
| 9 | PUT / PATCH | `/items/{id}` | Update an item | 200 |
| 10 | DELETE | `/items/{id}` | Delete an item | 204 |
| 11 | GET | `/reviews` | List reviews | 200 |
| 12 | POST | `/reviews` | Create a review | 201 |
| 13 | GET | `/reviews/{id}` | Read a review | 200 |
| 14 | PUT / PATCH | `/reviews/{id}` | Update a review | 200 |
| 15 | DELETE | `/reviews/{id}` | Delete a review | 204 |
| + | GET | `/dashboard` | Shop overview built from all three resources | 200 |

`PUT` replaces the whole resource, so every required field must be sent. `PATCH` changes only the fields that are sent.

Example: `POST /api/items`
```json
{
  "category_id": 1,
  "name": "Mechanical Keyboard 87 Keys",
  "description": "Hot-swappable mechanical keyboard with RGB backlight.",
  "price": 42.50,
  "stock": 75
}
```

### Pagination

Every list is paginated: `?page=2&per_page=5` (`per_page` is 10 by default, at most 100).
The answer has the page in `data`, links to other pages in `links` and the counts in `meta`:

```json
{
  "data": [ { "id": 1, "name": "Wireless Bluetooth 5.3 Earbuds", "...": "..." } ],
  "links": {
    "first": "http://127.0.0.1:8000/api/items?category_id=1&per_page=1&page=1",
    "last": "http://127.0.0.1:8000/api/items?category_id=1&per_page=1&page=4",
    "prev": null,
    "next": "http://127.0.0.1:8000/api/items?category_id=1&per_page=1&page=2"
  },
  "meta": { "current_page": 1, "last_page": 4, "per_page": 1, "total": 4, "...": "..." }
}
```

### Filtering

| List | Query parameters |
| --- | --- |
| `GET /categories` | `search` (name contains) |
| `GET /items` | `category_id`, `search` (name contains), `min_price`, `max_price` |
| `GET /reviews` | `item_id`, `min_rating` (1–5) |

Filters can be combined, e.g. `GET /api/items?category_id=1&min_price=10&max_price=30`.
An invalid filter value (for example `min_price=cheap` or `per_page=500`) returns `422`.

### Hypermedia

Every category, item and review has `_links`: where to send the request and with which HTTP method.
Lists also link to the other pages, `POST` answers with a `Location` header, and `GET /api/dashboard` links to every list.
The React shop page starts at `/api/dashboard` and then moves around by following these links.

```json
{
  "data": {
    "id": 1,
    "category_id": 1,
    "category_name": "Electronics",
    "name": "Wireless Bluetooth 5.3 Earbuds",
    "price": 15.99,
    "stock": 420,
    "reviews_count": 3,
    "average_rating": 4,
    "_links": {
      "self": { "href": "http://127.0.0.1:8000/api/items/1", "method": "GET" },
      "update": { "href": "http://127.0.0.1:8000/api/items/1", "method": "PUT" },
      "delete": { "href": "http://127.0.0.1:8000/api/items/1", "method": "DELETE" },
      "category": { "href": "http://127.0.0.1:8000/api/categories/1", "method": "GET" },
      "reviews": { "href": "http://127.0.0.1:8000/api/reviews?item_id=1", "method": "GET" }
    }
  }
}
```

### Dashboard (one resource built from several entities)

`GET /api/dashboard` returns:

- `totals`: number of categories, items, items out of stock, reviews and the average rating
- `categories`: every category with its number of items
- `top_rated_items`: the 5 best rated items with their category name, rating and number of reviews
- `latest_reviews`: the 5 newest reviews with the name of the reviewed item
- `_links` to the categories, items and reviews lists

### Status codes

| Code | When | Body |
| --- | --- | --- |
| 200 OK | LIST, READ, UPDATE, dashboard | the resource or the page |
| 201 Created | CREATE | the new resource, plus a `Location` header |
| 204 No Content | DELETE | empty |
| 400 Bad Request | the request body is not valid JSON | `{"message": "Malformed JSON in the request body: Syntax error."}` |
| 404 Not Found | the resource or the URL does not exist | `{"message": "Item not found."}` |
| 422 Unprocessable Content | valid JSON, but it breaks the validation rules (or an invalid filter) | `{"message": "...", "errors": {"price": ["The price field must be at least 0.01."]}}` |

Errors from `/api/...` are always JSON, even when the client does not send `Accept: application/json`.

## Requirements checklist

| Reikalavimas | Kur įgyvendinta |
| --- | --- |
| API metodai bent 3 taikomosios srities objektams, objektai iš kelių laukų | Category, Item, Review – see [Data model](#data-model) |
| CRUD + LIST kiekvienam objektui (15 metodų) | `routes/api.php`, `app/Http/Controllers/Api/` |
| Puslapiavimas | every `index()` method (`->paginate()`), [Pagination](#pagination) |
| Filtravimas | every `index()` method (`->when(...)`), [Filtering](#filtering) |
| Hypermedia | `_links` in `app/Http/Resources/`, pagination `links`, `Location` header |
| Panaudojimo atvejis iš kelių esybių | `GET /api/dashboard` – `app/Http/Controllers/Api/DashboardController.php` |
| OpenAPI specifikacija kiekvienam metodui | `public/openapi.yaml`, Swagger UI at `/docs` |
| Patogus paleidimas ir demonstracija | `composer setup`, `composer demo`, Postman collection |
| DB užpildyta prasmingais duomenimis | MySQL, `database/seeders/ShopSeeder.php` |
| Teisingas turinio tipas ir atsako kodai | JSON responses; 200 / 201 / 204 / 400 / 404 / 422 |
| 15 metodų demonstracija (~15 s), 404, 400/422, 201, 200/204 | `postman/aligaga.postman_collection.json` |
| Git saugykla ir dokumentacija | this repository and this README |

## Where the code is

| Path | What it does |
| --- | --- |
| `routes/api.php` | API routes (`Route::apiResource` registers the 5 methods of each resource) |
| `app/Http/Controllers/Api/` | One controller per resource + `DashboardController` |
| `app/Http/Requests/Api/` | Validation rules (`422` errors) |
| `app/Http/Resources/` | JSON shape of each resource, including `_links` |
| `app/Http/Middleware/RejectInvalidJson.php` | Answers `400` when the body is not valid JSON |
| `bootstrap/app.php` | Registers the API routes, the middleware and the JSON error answers (`404`) |
| `app/Models/` | `Category`, `Item`, `Review` and their relationships |
| `database/migrations/` | Tables and foreign keys |
| `database/seeders/ShopSeeder.php` | Demo data |
| `tests/Feature/Api/` | Pest tests for every endpoint and error case |
| `public/openapi.yaml` | OpenAPI 3 specification |
| `postman/aligaga.postman_collection.json` | Postman collection with tests |
| `resources/js/pages/shop.tsx` | The minimal shop page |

## Putting the project on GitHub

Create an empty repository on GitHub, then in the project folder run:

```bash
git init
git add .
git commit -m "Aligaga shop REST API"
git branch -M main
git remote add origin https://github.com/<your-name>/aligaga.git
git push -u origin main
```

`.gitignore` already leaves out `vendor/`, `node_modules/`, `public/build/` and `.env`.
