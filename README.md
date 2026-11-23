# Product Catalogue (proof of concept)

A small product catalogue where customers browse featured products, open categories, search,
view product details and send an enquiry that lands in the sales inbox.

It is built as a foundation rather than a throwaway prototype: the product data source, the
look and feel and the email provider can each be replaced without touching the rest of the code.
See [DECISIONS.md](DECISIONS.md) for assumptions and trade-offs, and [AI_USAGE.md](AI_USAGE.md)
for how AI tools were used.

## Run it (about 2 minutes)

Prerequisites: Docker with Compose v2.24 or newer. No accounts, API keys or paid services.

```bash
docker compose up --build
```

| What | URL |
| --- | --- |
| Storefront | http://localhost:8080 |
| Sales inbox (Mailpit, catches every email sent) | http://localhost:8025 |

To try the enquiry flow: open any product, click **Enquire**, submit the form, then open the
Mailpit inbox to see the email the sales team receives.

Stop with `Ctrl+C`, or `docker compose down`.

If a port is already taken, override it: `WEB_PORT=3000 MAILPIT_PORT=9025 docker compose up --build`.

## Stack and why

| Layer | Choice | Why |
| --- | --- | --- |
| Client | React 19 + TypeScript (Vite) | Fastest path to something the entrepreneur can click through; nothing to install for the reviewer beyond Docker. TypeScript gives a typed contract with the API. |
| Backend | PHP 8.3 + Laravel 12 | My strongest backend stack, so I can own and change every line. Laravel's HTTP client, mail drivers, validation, rate limiting and API resources cover the PoC's needs without extra dependencies. |
| Data source | Fake Store API, behind our own `ProductRepository` interface | The client never calls Fake Store. Swapping the source is a new adapter class. |
| Email | Laravel Mail; Mailpit locally | Changing provider (e.g. Amazon SES) is configuration (`MAIL_MAILER`), not code. |
| Local infrastructure | Docker Compose: nginx (static app + `/api` proxy), Laravel, Mailpit | One command to run. Each container maps to an AWS service (ECS/Fargate, S3 + CloudFront, SES). |

## How it fits together

```
Browser ──▶ nginx :8080 ──┬─ /            React app (static files)
                          └─ /api/*  ──▶  Laravel API ──▶ ProductRepository ──▶ Fake Store API
                                                    └───▶ Mail (SMTP ──▶ Mailpit)
```

```
backend/
  app/Catalogue/            Domain: Product, Category, CatalogueService (business rules)
    Contracts/              ProductRepository: the boundary to any product data source
    Sources/                FakeStoreProductRepository (today), InMemoryProductRepository
  app/Http/                 Controllers, request validation, API resources (the public JSON contract)
  app/Mail/                 EnquiryReceived email to the sales inbox
  config/catalogue.php      Featured products, data source, sales inbox, currency
  tests/                    Unit tests (domain) and feature tests (HTTP API, upstream faked)
frontend/
  src/api/                  The only code that talks to the backend: typed client and React Query hooks
  src/pages/                Home, Category, Search, Product (+ enquiry), Not found
  src/components/           Layout, search bar, product grid and tile, enquiry form
  src/theme/tokens.css      Design tokens: the single place to apply the brand's look and feel
  src/test/                 Integration tests per acceptance criterion
```

## API

All responses are JSON. Product data always comes through this API, never directly from Fake Store.

| Method and path | Purpose |
| --- | --- |
| `GET /api/products/featured` | The chosen "top rated" products for the home page |
| `GET /api/categories` | Category list (`slug`, `name`) |
| `GET /api/categories/{slug}` | `{ category, topRated, others }` for a category page |
| `GET /api/products/search?q=term` | Products matching every word of the term |
| `GET /api/products/{id}` | One product |
| `POST /api/enquiries` | `{ productId, name, email, phone?, message? }`; `202` when the email has been sent |

Errors: `404` unknown product or category, `422` validation errors (`errors` per field),
`429` rate limited (enquiries: 5 per minute per IP), `503` product source or mail server unavailable.

## Configuration

Everything has a working default, so no configuration is needed to run the app. To change something,
copy `backend/.env.example` to `backend/.env` and edit it; Docker Compose picks the file up.

| Variable | Default | Purpose |
| --- | --- | --- |
| `FEATURED_PRODUCT_IDS` | `3,5,11,12,18` | The products chosen as "top rated" for the home page, in order |
| `CATEGORY_TOP_RATED_COUNT` | `3` | How many highest-rated products lead each category page |
| `SALES_INBOX_ADDRESS` / `SALES_INBOX_NAME` | `sales@example.com` / `Sales` | Where enquiries are sent |
| `MAIL_MAILER` and `MAIL_*` | `smtp` to Mailpit | Email provider. Use `ses` (with `AWS_*` credentials) or another Laravel mail driver in production |
| `FAKESTORE_BASE_URL`, `FAKESTORE_TIMEOUT_SECONDS`, `FAKESTORE_CACHE_TTL_SECONDS` | Fake Store, 3 s, 10 min | Upstream settings |
| `CATALOGUE_CURRENCY` | `USD` | Fake Store prices carry no currency |
| `STOREFRONT_URL` | `http://localhost:8080` | Used for product links inside emails |
| `CORS_ALLOWED_ORIGINS` | empty | Only needed if the web app is hosted on a different origin from the API |

No secrets are committed; `APP_KEY` is generated on first start.

## Tests

```bash
# Backend: 48 PHPUnit tests (unit + HTTP feature tests; Fake Store and mail are faked)
docker compose run --rm --no-deps backend php artisan test

# Frontend: lint + 19 Vitest/Testing Library tests, without Node installed locally
docker build --target test ./frontend
```

With PHP 8.2+ and Node 22.12+ installed locally you can also run `cd backend && php artisan test` and
`cd frontend && npm ci && npm test`. CI (GitHub Actions) runs both suites, lint, type-check and a Docker build on every pull request.

## Developing without Docker

```bash
docker compose up mailpit            # optional: catches emails on localhost:1025 and shows them on :8025
cd backend && composer run setup && php artisan serve        # API on http://localhost:8000
cd frontend && npm ci && npm run dev                          # app on http://localhost:5173, proxies /api to :8000
```

## Troubleshooting

- **Corporate network or TLS-inspecting proxy (e.g. Zscaler)**: if `composer install` fails during
  `docker compose up --build` with `SSL certificate problem` or `unable to get local issuer certificate`,
  copy your organisation's root CA certificate (PEM format, `.crt` extension) into `backend/docker/certs/`
  and build again. That folder is git-ignored.
- **Port already in use**: see the port overrides under "Run it".
