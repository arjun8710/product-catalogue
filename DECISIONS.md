# Decisions and assumptions

## Assumptions where the acceptance criteria were unclear

**Home page: who chooses the 5 "top rated" products?**
The criterion says "GIVEN *I have chosen* 5 products", so "top rated" on the home page is an
editorial choice by the business, not a calculation. The choice is configured as an ordered list of
product IDs (`FEATURED_PRODUCT_IDS`, default `3,5,11,12,18`: the best-rated products, spread across
categories). Products are shown in the configured order. If a chosen product disappears from the
data source, the page still renders without it and a warning is logged. The count is not hard-coded
to 5; the business decides.

**Category page: what is "top rated for that category"?**
This criterion does not say "I have chosen", and asking the business to curate a list per category
adds admin work nobody asked for. So within a category, "top rated" is calculated from the data
source's ratings: highest average first, ties broken by number of ratings. The top 3
(`CATEGORY_TOP_RATED_COUNT`) are shown first, followed by "additional products": the rest of the
category in the source's order.
*Trade-off:* "top rated" means curated on the home page and calculated on category pages. If the
business wants curation everywhere, only `CatalogueService::categoryListing()` changes.

**Search: what does "text matching that search term" mean?**
- It matches the title, description and category name, case-insensitively.
- Every word must match (`slim fit` finds products containing both words).
- Matching is from the start of a word, so `jack` finds "jacket" but `mens` does **not** match
  "womens". Plain substring matching returned women's products for "mens"; we found this while
  testing against the real data.
- Apostrophes are ignored, so `womens` finds "Women's".
- Search runs when the button is clicked (or Enter is pressed), as the criterion describes, not as
  you type. Blank terms are not submitted. Terms are limited to 100 characters.
- Results live at `/search?q=...`, so they can be shared and work with back/forward.

**Product page:** clicking a tile anywhere (home, category, search) opens `/products/{id}`.

**Enquiry: what counts as "contact details", and what is "the sales inbox"?**
- Name and email are required; phone and message are optional. That is enough to reply without
  putting customers off.
- The product is looked up on the server from its ID, so the email cannot be spoofed with a fake
  product name or price.
- The sales inbox is a configured address (`SALES_INBOX_ADDRESS`). Locally it is Mailpit, so the
  reviewer can see the email without an account. The email's Reply-To is the customer, so sales can
  simply hit reply.
- The "Thank you" message replaces the form on the same page and repeats the product and email
  address, so the customer knows it worked.
- An enquiry is only acknowledged after the email has actually been handed to the mail server. If
  sending fails, the customer gets an error and can retry, rather than a "thank you" for a lost lead.

**Other assumptions**
- Prices: Fake Store has no currency, so we declare USD (configurable). Prices travel as integer minor
  units (cents) to avoid floating-point errors and are formatted in the visitor's locale. Only
  two-decimal currencies are supported for now.
- Category names are shown as the source provides them, capitalised with CSS (Fake Store's
  "jewelery" spelling is kept). URLs use slugs: `men's clothing` becomes `mens-clothing`.
- No customer accounts, cart or checkout: the brief is browse and enquire.
- Modern evergreen browsers only.

## Requirements dropped or simplified, and why

| Simplification | Why it is acceptable for the PoC | What production needs |
| --- | --- | --- |
| No database: enquiries are emailed, not stored | The criterion only asks for an email, and no database means less to run. Failures are visible to the customer, not silent. | Store each enquiry, then send the email from a queued job with retries, so no lead is ever lost. |
| Email sent during the request | Fine at PoC volume and keeps the "thank you" honest | Queue (e.g. SQS) once enquiries are persisted |
| Featured products set by environment variable | No admin UI was asked for. It is one line to change. | Admin screen or CMS storing the selection |
| Whole Fake Store catalogue fetched once and cached for 10 minutes; search and filtering in memory | 20 products; Fake Store has no search endpoint | A real source would search and paginate natively, behind the same interface |
| No pagination | 20 products | Add `page`/`limit` to the repository contract and API |
| Spam protection is validation plus rate limiting (5 enquiries per minute per IP) | Enough to stop casual abuse of a demo | Bot protection (e.g. Cloudflare Turnstile) and monitoring |
| Docker images are development images (`php artisan serve`, dev dependencies kept so tests run in the container) | Optimised for "runs in under 10 minutes" | Multi-stage production images with php-fpm or FrankenPHP, cached config and routes |
| Frontend tests stub `fetch`; no browser end-to-end tests | They still drive real routing, data fetching and forms per criterion, and run in about 3 seconds | Playwright tests of the five criteria against `docker compose` |
| Product images hot-linked from the data source | Fine for a demo | Image CDN with resizing |
| No API versioning | One client, deployed together with the API | Version the API when a mobile app (which cannot be force-updated) arrives |

## How the design supports growth beyond the PoC

Each thing the brief says will change sits behind its own boundary:

- **Product data source.** The application depends on the `ProductRepository` interface
  (`backend/app/Catalogue/Contracts`), never on Fake Store. Fake Store's field names, its lack of a
  search endpoint and its caching all live in one adapter class, which maps the payload into our own
  `Product` model. Moving to a database, a product information system or a commerce platform means
  writing one new adapter and changing one binding in `CatalogueServiceProvider`. The API feature
  tests describe the behaviour any adapter must preserve.
- **Business rules** (what is featured, how top rated is ranked, what search matches) live in
  `CatalogueService` and are unit-tested against an in-memory repository, independent of any source.
- **Public API contract.** Laravel API Resources define the JSON the client sees, separately from
  the domain model. Data-source changes do not leak into the client.
- **Email provider.** Laravel's mail drivers are the abstraction: SMTP and Mailpit today, Amazon SES or
  Postmark tomorrow, by changing `MAIL_MAILER` and credentials. No code changes.
- **Look and feel.** Every colour, font, radius and spacing value is a CSS custom property in
  `frontend/src/theme/tokens.css`. Components use semantic markup with structural CSS only. When the
  creative work lands, the brand goes into the tokens (and fonts) first; components change only for
  genuinely new layouts.
- **Frontend to backend.** All network code is in `frontend/src/api`, with TypeScript types mirroring
  the API resources. React Query handles caching, cancellation and retries (4xx errors are not retried).
- **More clients.** The backend is a plain JSON API, so a mobile app can use it as-is.
- **AWS.** The Compose services map directly to AWS: the React build on S3 and CloudFront (or
  nginx on ECS), the API on ECS Fargate or App Runner behind an ALB (trusted-proxy ranges are already
  set so rate limiting sees real client IPs), SES for email, ElastiCache via `CACHE_STORE=redis`, and
  configuration from Secrets Manager or SSM.
- **Safety net.** 48 backend and 19 frontend tests cover each acceptance criterion and the failure
  paths: upstream down, mail down, validation, rate limiting, escaping in emails, and CORS. CI runs them
  plus lint, type-check and a Docker build on every pull request.

## What I would do next with another day

1. Persist enquiries and send emails from a queued job with retries; a simple admin view of leads.
2. Let the business manage featured products (database plus a small admin screen) instead of an
   environment variable.
3. Production Docker images and infrastructure as code (CDK or Terraform) for ECS, SES and CloudFront,
   with a deploy pipeline.
4. Playwright end-to-end tests for the five criteria against `docker compose`, and an OpenAPI spec that
   generates the frontend types so the contract cannot drift.
5. Serve stale cached data when the product source is down (stale-while-revalidate), plus structured
   logging and error tracking.
6. Pagination, sorting and filters on category and search pages; bot protection on the enquiry form.
