# AI usage

> **TODO (candidate):** this file was drafted by the AI from its own session log. Review every
> statement, add your own review notes and real time in the marked sections, and delete this box.

## Tools and models

- **Claude Code** (VS Code extension), model **Claude Opus 5.5** (`claude-opus-5-5`): planning,
  scaffolding, implementation, tests, Docker setup and first drafts of the documentation.
- The agent ran commands itself (Composer, npm, PHPUnit, Vitest, Docker, curl, headless Chrome) to
  verify its own work, rather than only generating code.

## Agent instruction files

- [`CLAUDE.md`](CLAUDE.md): the architectural boundaries (no direct Fake Store calls from the client,
  provider details confined to source adapters, tokens-only styling), conventions, and the
  "tests plus end-to-end check before done" rule. It was written after the build, from the problems we
  actually hit, so future agent sessions keep to the design and avoid the same traps.

## Plan

1. Read the brief and list where the acceptance criteria are ambiguous, before writing code.
2. Choose a stack: React + TypeScript client; Laravel backend, because it is the stack I know best and
   must defend and change live.
3. Backend first, around one boundary: a `ProductRepository` interface with a Fake Store adapter, a
   source-agnostic `CatalogueService` for the business rules, and API Resources as the public contract.
   Email through Laravel Mail with Mailpit locally.
4. Smoke-test every endpoint against the **real** Fake Store API before writing tests, then write unit
   and feature tests with the upstream faked.
5. Frontend: one typed API module, React Query, pages per criterion, design tokens for the brand.
   Integration tests per criterion with the network stubbed.
6. Docker Compose (nginx, Laravel, Mailpit), then verify the whole flow through the real stack,
   including the email arriving in Mailpit and screenshots of each page.
7. Documentation: README, DECISIONS, this file.

## Key prompts and moments

1. **"can you check pdf assignment in this folder"**: produced a summary of the brief and, more
   usefully, a list of the gaps in the acceptance criteria (who picks "top rated", what search
   matches, how to send email without a paid service). Those became the assumptions in DECISIONS.md.
2. **"yes plz go ahead and i know php laravel so use that in backend"**: Laravel instead of the
   AI's suggested Node backend. The deciding factor was being able to defend and modify the code in
   the follow-up interview.
3. **Interpreting "top rated" twice.** The home criterion says "I have chosen", so it is curated
   configuration; the category criterion does not, so it is calculated from ratings. This was a
   deliberate trade-off, recorded in DECISIONS.md, not an accident.
4. **Verifying against real data, not just tests.** Running search against the real catalogue exposed
   the substring bug below. The end-to-end run through Docker exposed two environment bugs that every
   unit and feature test had missed.
5. **Proving the tests can fail.** After the frontend suite passed first time, two bugs were planted
   deliberately (wrong product link, wrong product ID in the enquiry). The suite caught both.

## Where the AI got things wrong, and how it was caught

| What went wrong | How it was caught | Fix |
| --- | --- | --- |
| Search used substring matching, so `mens` matched "**wo**mens" and returned women's products; it also missed "Women's" when searching `womens` | Smoke test of the search endpoint against the real Fake Store data | Match from word starts and ignore apostrophes; locked in with data-provider tests |
| The "upstream down" test passed through the real fixture, because a shared `Http::fake()` in `setUp()` matched first | The test failed (got 200, expected 503) | Each test arranges its own upstream response |
| `Mail::to($address, $name)`: Laravel's mail fake drops the name argument, so the test disagreed with production behaviour | Feature test failure, then reading the framework source | Pass an explicit `Address` object, which behaves the same in both |
| The first draft of the form `Field` component copied React elements by object spread (relies on React internals) | AI self-review before running tests | Rewrote as an explicit render prop |
| Product page enquiry state was not reset between products | AI self-review | Child component keyed by product ID |
| Docker: corporate CA certificate copied into a subfolder that Alpine's `update-ca-certificates` ignores (Debian behaviour assumed) | Build still failing; minimal reproduction container | Copy to the top-level certificates folder |
| Docker: `php artisan serve` re-reads `.env` and ignores container environment variables, so emails went to `127.0.0.1` and every enquiry returned 503 | End-to-end enquiry through nginx to Mailpit (all automated tests were green) | `--no-reload` flag, with a comment explaining why |
| `phpunit.xml` used `<env force="true">`, which does not override `$_SERVER`; inside Docker the tests used real config and tried to call the real Fake Store | Running the test suite inside the container (15 failures) | `<server>` entries, with a comment; verified with a deliberately misleading environment |
| Frontend tests asserted `$109.00`, but on a UK-locale machine prices render as `US$109.00` | Headless Chrome screenshots showed `US$`; re-ran tests under `LANG=en_GB` | Locale-tolerant assertions |

## My own review

> **TODO (candidate):** record what you checked yourself, what you changed or pushed back on, and
> anything you still disagree with. Interviewers will ask about decisions the AI made, so note any
> you would defend differently.

## Time spent

> **TODO (candidate):** replace with your real numbers.

- AI-assisted planning and build session: about 1 hour of wall-clock time (about 21:00 to 22:00),
  covering reading the brief, the plan, the backend, the frontend, tests, Docker and the documentation drafts.
- Planning: _
- Building: _
- Testing: _
- Reviewing (reading and understanding the code, editing these documents): _
- **Total:** _
