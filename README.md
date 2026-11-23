# Loom & Lane

An online clothing shop built with Laravel 12, Inertia 2, React 19, TypeScript,
Tailwind CSS v4 and shadcn/ui. Payments go through Stripe Checkout.

## Local development

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan storage:link
composer dev               # app, queue, logs and Vite together
```

Local development uses SQLite and needs no other services. Search uses the
database, and `php artisan shop:stock <product-slug>` shows or sets stock. For payments, add
Stripe **test** keys to `.env`, then run
`stripe listen --forward-to localhost:8000/stripe/webhook` to receive webhooks
and pay with the test card `4242 4242 4242 4242`. Webhooks are applied by the queue
worker that `composer dev` starts.

## Tests

```bash
composer test              # SQLite in memory
composer test:services     # PostgreSQL + Redis, as in production (see docs/deployment.md)
```

## Deploying

See [docs/deployment.md](docs/deployment.md). It covers the services to provision,
environment settings, deploy steps, cron, the Stripe webhook, monitoring and the
backup restore drill.
