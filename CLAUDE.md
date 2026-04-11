# Gitant — Project Conventions

Bounty platform for open source issues. Funders post bounties on GitHub/GitLab issues; hunters resolve them via pull requests.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | React 19 + Vite + TypeScript + Tailwind CSS v4 + shadcn/ui |
| Database | PostgreSQL 16 via Eloquent/Migrations |
| Cache/Queue | Redis 7 + Laravel Queues |
| Real-time | Laravel Reverb (WebSocket) |
| Auth | Socialite (GitHub + GitLab OAuth) + Sanctum (SPA cookies) |
| Payments | Stripe Connect + Laravel Cashier |
| Search | Laravel Scout (database driver; Meilisearch for production) |
| i18n | react-i18next (FR/EN) + Laravel lang files |
| Infra | Docker (PHP-FPM + Nginx + PostgreSQL + Redis) |

## Getting Started

```bash
cp .env.example .env
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
npm install && npm run dev
```

## Commands

```bash
# Dev server (local PHP)
php artisan serve
npm run dev

# Run tests
php artisan test

# Type check
npm run type-check

# Build for production
npm run build
php artisan optimize
```

## Architecture

### Backend (`app/`)

```
app/
  Http/
    Controllers/       # Thin controllers — delegate to Actions or Services
    Middleware/
    Requests/          # Form Request validation classes
  Actions/             # Single-responsibility action classes (e.g. CreateBounty)
  Models/              # Eloquent models
  Services/            # Domain services (e.g. StripeService, WebhookVerifier)
  Events/              # Laravel events
  Listeners/           # Event listeners (may dispatch jobs)
  Jobs/                # Queued jobs (e.g. ProcessWebhook, TransferPayout)
  Notifications/       # Email + in-app notifications
  Policies/            # Authorization policies (one per model)
```

### Frontend (`resources/js/`)

```
resources/js/
  Pages/               # Inertia page components (route → Page component)
  Layouts/             # Shared layout wrappers (GuestLayout, AppLayout)
  Components/
    ui/                # shadcn/ui base components (Button, Card, etc.)
    bounty/            # Domain-specific components
    auth/
  hooks/               # Custom React hooks
  lib/
    utils.ts           # cn() helper and shared utilities
  i18n/                # i18next initialization
  types/               # TypeScript types (shared with Inertia shared props)
```

## Conventions

### PHP / Laravel
- **Controllers**: Resource controllers for CRUD; dedicated controllers for auth flows. Keep them thin — delegate business logic to `Actions/`.
- **Actions**: One class, one public `handle()` method. No static methods.
- **Models**: Use `$fillable`, not `$guarded`. Always define `$casts`.
- **Migrations**: Never edit a migration that has been pushed to `main`. Always create a new migration.
- **Policies**: Every model that requires authorization must have a Policy registered in `AuthServiceProvider`.
- **Validation**: Always use Form Requests. Never validate in controllers.
- **Naming**: `snake_case` for DB columns/migrations, `camelCase` for PHP methods, `StudlyCase` for classes.
- **Webhooks**: Verify signatures in dedicated middleware before any processing (GitHub HMAC-SHA256, GitLab token).

### TypeScript / React
- **Props typing**: Every component must have a typed `Props` interface.
- **Inertia pages**: Export a default function component. Accept typed `PageProps` via generics.
- **Tailwind**: Use `cn()` from `@/lib/utils` to merge classes. No inline `style` attributes unless strictly necessary.
- **shadcn/ui**: Add new components to `resources/js/Components/ui/`. Do not modify generated components — extend via `className` overrides.
- **i18n**: Use `useTranslation()` hook everywhere. Never hardcode user-facing strings; all must go through `t('key')`.
- **File naming**: `PascalCase` for components, `camelCase` for hooks (`useXxx`), `kebab-case` for utility files.

### Git
- Branch naming: `feat/`, `fix/`, `chore/`, `docs/`
- Commit messages: Conventional Commits (`feat: add bounty creation`, `fix: correct Stripe webhook handler`)
- Never push directly to `main`. Use PRs.

### Security
- Webhook signatures must be verified for every inbound GitHub/GitLab webhook.
- Never store Stripe secret keys, OAuth secrets, or private keys in the repo.
- Use `$table->unsignedBigInteger()` for all foreign keys.
- Rate-limit OAuth callbacks and API routes.
- CSP headers are configured in middleware.

### Payments (Stripe Connect)
- Funder pays bounty amount + 10% platform commission via Stripe Checkout.
- Funds held on Gitant's Stripe account (escrow).
- On payout: `Transfer` to hunter's connected account for 100% of bounty.
- On refund: `Refund` to funder's original payment method.
- `STRIPE_WEBHOOK_SECRET` must be set and verified on every Stripe event.

### i18n
- Backend lang files: `lang/en/` and `lang/fr/`.
- Frontend locale files: `public/locales/en/common.json` and `public/locales/fr/common.json`.
- Default locale: `en`. Supported: `en`, `fr`.

## Environment Variables

See `.env.example` for the full list. Critical ones to set before running:

```
GITHUB_CLIENT_ID / GITHUB_CLIENT_SECRET
GITLAB_CLIENT_ID / GITLAB_CLIENT_SECRET
STRIPE_KEY / STRIPE_SECRET / STRIPE_WEBHOOK_SECRET
REVERB_APP_KEY / REVERB_APP_SECRET
```
