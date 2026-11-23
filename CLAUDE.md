# Agent instructions

Instructions for AI coding agents (Claude Code and similar) working in this repository.

## What this is
A product catalogue proof of concept that must grow into a real product. Laravel 12 JSON API
(`backend/`), React 19 + TypeScript SPA (`frontend/`), Docker Compose to run both with Mailpit.
Read `DECISIONS.md` before changing behaviour: it records the assumptions behind each acceptance criterion.

## Boundaries: do not break these
- The frontend never calls Fake Store (or any product source) directly. All product data goes
  through the backend API, and all network code in the frontend lives in `frontend/src/api/`.
- Nothing outside `backend/app/Catalogue/Sources/` may know about Fake Store field names or URLs.
  Sources map their data into `App\Catalogue\Product`. New sources implement
  `App\Catalogue\Contracts\ProductRepository` and are bound in `CatalogueServiceProvider`.
- Business rules (featured, top-rated ranking, search semantics) belong in `CatalogueService` or the
  repository contract, not in controllers or React components.
- The public JSON shape is defined by `backend/app/Http/Resources`. If you change it, update
  `frontend/src/api/types.ts` in the same change.
- Email goes through Laravel Mail. Do not add provider SDKs: provider choice is configuration.
- Styling: colours, fonts and spacing come from `frontend/src/theme/tokens.css` variables. Do not
  hard-code them in components or in `styles.css`.

## Conventions
- PHP: Laravel Pint style (`vendor/bin/pint`). Final classes, and readonly value objects for domain types. Validate input with FormRequests or `$request->validate`.
- TypeScript: strict; no `any`; no enums or parameter properties (`erasableSyntaxOnly`).
- Prefer small, explicit code over new dependencies. Ask before adding a package.
- No secrets in the repo. New configuration goes in `backend/.env.example` with a safe default and a comment.

## Testing: required for every behaviour change
- Backend: `cd backend && php artisan test` (or `docker compose run --rm --no-deps backend php artisan test`).
  Feature tests fake Fake Store with `fakeFakeStore()` / `Http::fake()`; stray HTTP calls fail the test.
  Test config lives in `phpunit.xml` as `<server>` entries. Do not switch them to `<env>`: real
  environment variables would then override them.
- Frontend: `cd frontend && npm test`, plus `npm run lint` and `npm run typecheck`.
  Tests render the real app with `renderApp()` and stub the network with `mockApi()`. Query by role and label.
- Write the test first for bug fixes. Make sure a new test fails without your change.

## Before you say you're done
Run both test suites and lint. For anything touching Docker, email or configuration, also run
`docker compose up --build` and check the flow end to end (the enquiry email should appear in Mailpit
at http://localhost:8025). Several bugs in this project only appeared in that environment.
