# Deploying Loom & Lane

This is the production checklist. Local development keeps working on SQLite with
no extra services: see the README.

## 1. Services to provision

| Service | Used for | Notes |
| --- | --- | --- |
| **PostgreSQL** (managed) | Catalogue, orders, users | Turn on automatic daily backups and point-in-time recovery. Postgres 15 or newer. |
| **Redis** (managed) | Sessions, cache, locks, rate limits, queues | A small instance is plenty. Must be shared by every app server. |
| **S3-compatible bucket + CDN** | Product and category photos | e.g. S3 + CloudFront, or Cloudflare R2 with a public domain. |
| **Sentry** | Exceptions and warning-level logs | The free tier is enough to start. |
| **Email provider** | Order, shipping, refund and newsletter emails; password resets | Postmark, Resend or Amazon SES. |
| **Meilisearch** (optional) | Product search with typo tolerance and ranking | Self-hosted or Meilisearch Cloud. Without it, search uses the database. |
| **Uptime / heartbeat monitor** | `/up` checks and scheduler heartbeats | e.g. Better Stack, Healthchecks.io, Cronitor. |
| **Stripe** | Payments | Live keys plus a webhook endpoint (step 4). |

The PHP runtime needs the `pdo_pgsql` and `redis` extensions.

## 2. Environment

Start from [`.env.production.example`](../.env.production.example). It lists every
setting and what it is for. Keep secrets in the host's secret settings, not in
git. The important ones:

- `APP_KEY`: generate it once with `php artisan key:generate --show` and never change it,
  because changing it signs everyone out.
- `DB_SSLMODE=require`. Most managed Postgres providers insist on TLS.
- `TRUSTED_PROXIES`: without it, rate limits see the load balancer's IP for every visitor.
- `AWS_URL` is the **CDN** URL, not the bucket URL. Product images are built from it.

## 3. Deploy steps

Run these on every release:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize        # caches config, routes, views and events
php artisan scout:sync-index-settings   # only when using Meilisearch; harmless to repeat
php artisan queue:restart   # once queue workers are running (see below)
```

After `php artisan optimize`, `env()` only works inside `config/` files, so read
settings with `config()` everywhere else.

**Run once on a new server:** add a cron entry for the scheduler:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler runs two jobs:

- `shop:reconcile-checkouts` every 15 minutes settles orders whose webhook never arrived;
- `model:prune` daily deletes orders cancelled more than 30 days ago.

Both are `onOneServer()`, so every app server can run cron safely. Set the
`HEARTBEAT_*` URLs so you hear about it if cron stops.

The scheduler also runs `queue:monitor` every 5 minutes. It logs a warning
(which goes to Sentry) if more than 100 jobs are waiting.

**Queue worker (required):** Stripe webhooks are processed on the queue, so at
least one worker must always be running. Use a process supervisor such as
Supervisor or systemd, or your host's worker feature:

```ini
[program:loom-lane-worker]
command=php /path/to/app/artisan queue:work redis --sleep=1 --max-time=3600
autostart=true
autorestart=true
stopwaitsecs=60
numprocs=1
```

- `--max-time` restarts the worker hourly, which keeps memory in check.
- `queue:restart` on deploy makes workers load the new code.
- Each job sets its own tries, backoff and timeout.
- Jobs that run out of retries go to the `failed_jobs` table. List them with
  `php artisan queue:failed` and retry them with `php artisan queue:retry all`.

Locally, `composer dev` already runs a worker. If you run the server another
way, start `php artisan queue:work`, otherwise webhooks will only be recorded,
not applied.

## 4. Stripe webhook

1. In the Stripe dashboard (Developers → Webhooks), add an endpoint at `https://<your domain>/stripe/webhook`.
2. Select these events:
   - `checkout.session.completed`
   - `checkout.session.async_payment_succeeded`
   - `checkout.session.async_payment_failed`
   - `checkout.session.expired`
   - `charge.refunded` (refunds made in the dashboard update the order and email the customer)
   - `charge.dispute.created`, `charge.dispute.updated`, `charge.dispute.closed`
3. Copy the signing secret into `STRIPE_WEBHOOK_SECRET`.
4. Send a test event from the dashboard and check that it returns `200`.

How a webhook is handled:

1. The endpoint checks the signature and stores the event in `stripe_events`,
   keyed by Stripe's event ID.
2. It queues a `ProcessStripeEvent` job and replies `200` straight away, with no
   Stripe API call while Stripe waits.
3. The job fetches the session from Stripe and updates the order. Stripe errors are
   retried with backoff for about 1h45m.

A redelivered event maps to the same row, and it is queued again only if it hasn't
been processed. The row's `attempts` and `last_error` columns show what went wrong.
Processed events are kept for 90 days, and they never store customer details.

The reconciliation job is a safety net. Payments still complete if the webhook or
the worker is down, but orders can take up to 30 minutes to settle.

## 5. Email

Customers are emailed automatically, and each email is sent exactly once:

| Email | Sent when |
| --- | --- |
| Order confirmation | Payment is confirmed (for Bacs, when it settles) |
| Shipped, with tracking | Staff click **Mark shipped** in the admin area |
| Refund | A refund is recorded, from the admin area or the Stripe dashboard |
| Newsletter confirmation | Someone signs up (double opt-in) |
| Password reset | A customer asks for one |

All of these go through the queue, so a worker must be running.

**Provider: Resend** (installed). To set it up:

1. Create an account at resend.com, then under **Domains** add your shop's domain.
   A subdomain such as `mail.yourdomain.co.uk` keeps email reputation separate.
2. Add the DNS records Resend shows (DKIM and SPF) at your domain registrar, then
   wait for the domain to show **Verified**.
3. Add a DMARC record (TXT on `_dmarc`, starting with `v=DMARC1; p=none;`), then
   tighten it to `p=quarantine` once mail is flowing.
4. Create an API key with **Sending access** for that domain only, and set
   `RESEND_KEY`, `MAIL_MAILER=resend` and `MAIL_FROM_ADDRESS`
   (e.g. `orders@yourdomain.co.uk`).
5. Check it with `php artisan shop:test-email you@example.com`.

To switch provider later, Amazon SES (`MAIL_MAILER=ses`) and Postmark are also supported by Laravel.

Locally, `MAIL_MAILER=log` writes every email to `storage/logs/laravel.log`.

**Newsletter.** Only `NewsletterSubscriber::mailable()` addresses may be emailed:
they confirmed the link and haven't unsubscribed. Every newsletter must include
`$subscriber->unsubscribeUrl()`, and also send it in the `List-Unsubscribe`
header with `List-Unsubscribe-Post: List-Unsubscribe=One-Click`. Sign-ups from
before double opt-in was added were never confirmed, so don't email them.

## 6. Sign in with Google

Customers can choose **Continue with Google** from the account icon in the header,
which is on every page. They come back signed in to the page they started on,
with their bag intact. Email and password sign-in still works alongside it.

**Setting up Google (one time):**

1. In [Google Cloud Console](https://console.cloud.google.com/), create a project
   (or pick one) for the shop.
2. Set up the **OAuth consent screen** (Google Auth Platform → Branding):
   - User type **External**.
   - Add the app name, support email and logo.
   - Link the privacy policy (`/privacy`) and terms (`/terms`).
   - Add your domain as an authorised domain.
3. **Scopes:** keep only the defaults (`openid`, `email`, `profile`). These need
   no Google review.
4. Under **Clients**, create an OAuth client of type **Web application**. Add these
   authorised redirect URIs:
   - `http://127.0.0.1:8000/auth/google/callback` (local)
   - `https://<your domain>/auth/google/callback` (live)
5. Put the client ID and secret in `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`.
6. Under **Audience**, switch the publishing status from **Testing** to
   **In production**. Until you do, only the test users you list can sign in.

**How accounts are matched:**

- **By Google account ID first**, so a changed Gmail address still finds the
  right account.
- **Then by email.** Google's email must be verified by Google.
  - If that email belongs to a password account whose email was never verified,
    the old password is removed and its sessions are signed out. This stops
    "pre-registered" accounts from keeping access. The customer can set a new
    password in Account settings.
- **Customers who only use Google:**
  - they have no password, and **Account settings → Password** lets them set one;
  - they confirm deleting their account by typing their email.

If the Google keys aren't set, the button tells shoppers to use email instead.

## 7. Admin area

Staff manage the shop at `/admin`:

- **Orders:** to pack & ship, needs review, mark packed or shipped with tracking,
  refund (optionally restocking returned items), clear review flags.
- **Products:** details, price, photo upload, colours and sizes, and stock per variant.
- **Departments.**
- **Payments:** disputes, and Stripe events that didn't process (with a retry button).

To give someone access, they register a normal account on the shop, then you run:

```bash
php artisan shop:make-admin jo@example.com            # grant
php artisan shop:make-admin jo@example.com --revoke   # remove
```

Admins can refund money, so use strong, unique passwords. Consider turning on
Filament's multi-factor authentication before launch.

## 8. Running more than one app server

The app keeps no state on its own server, so you can put several identical
servers behind a load balancer:

| State | Where it lives |
| --- | --- |
| Sessions, bag contents, cart locks, rate limits | Redis |
| Cache (navigation, related products, success-page throttle) | Redis |
| Scheduler locks (`onOneServer`, `withoutOverlapping`) | Redis |
| Maintenance mode | Redis (`APP_MAINTENANCE_DRIVER=cache`) |
| Orders, catalogue, webhook events, failed jobs | PostgreSQL |
| Product photos | S3 + CDN |
| Logs and errors | stderr and Sentry |

Checklist:

- **The same `APP_KEY` and environment on every server.** Sessions and cookies are
  encrypted with the key.
- **Build once and deploy that same build everywhere**, with the same
  `public/build` manifest. Inertia compares asset versions, so a server running an
  older build makes browsers reload the page.
- **Run `migrate --force` once per release**, from your deploy pipeline or a single
  server, not on every server. During a rolling deploy, old and new code run side
  by side for a while. Make schema changes additive first: add columns, ship the
  code, and remove old columns in a later release.
- **Cron can run on every server.** `onOneServer()` makes sure each job runs only once.
- **Run queue workers wherever you like.** Each job runs once however many
  workers there are.
- **Point the load balancer's health check at `/up`.**

Cache invalidation: the navigation refreshes whenever a category is saved or
deleted through the app. If you change categories directly in the database, run
`php artisan cache:forget shop.navigation`. Related-product picks refresh hourly,
but price changes and deactivated products show at once.

## 9. Stock

Stock is tracked separately for every variant (product × colour × size) in
`stock_levels`. A variant that has no stock level counts as sold out.

**How an order uses stock:**

1. **Checkout starts:** the order's units are reserved, all of them or none. Each
   line is one conditional update that only succeeds while
   `on_hand - reserved >= quantity`, so two shoppers can't both get the last unit.
   If a line is short, checkout is refused and the shopper goes back to the bag,
   which explains what's missing.
2. **The payment page expires** after `SHOP_RESERVATION_MINUTES` (30 by default).
   The order is cancelled through the expiry webhook or the reconciliation job,
   and the units return to sale.
3. **Paid:** `on_hand` and `reserved` both go down, in the same database
   transaction as the status change. This happens only once, however many
   notifications arrive.
4. **Failed:** the units are released. Delayed payments such as Bacs keep their
   reservation until they settle.

**Managing stock** (until there is an admin screen):

```bash
php artisan shop:stock merino-crew-neck-jumper                                    # show
php artisan shop:stock merino-crew-neck-jumper 12                                 # set every variant to 12
php artisan shop:stock merino-crew-neck-jumper 5 --colour=Oat --size=M --add      # receive a delivery
```

A new colour or size starts at zero, so set its stock with `shop:stock` before it
can be sold.

**Alert:** add a Sentry rule for *"Order paid for more units than are on hand"*.
It means someone lowered `on_hand` by hand while an order was being paid for, and
that order needs restocking or a refund.

## 10. Search

The search box uses whichever engine `SCOUT_DRIVER` names:

- **`null` (the default):** a database search. It does case-insensitive
  substring matching on the name, type and description. It needs no service and is
  fine up to a few thousand products.
- **`meilisearch`:** typo tolerance (for example "mernio" finds Merino), ranking
  by relevance ("best match" is the default order when searching), and matches on
  colours, materials and category too. Shop filters, sorting and pagination still
  come from the database.

**Setting up Meilisearch:**

1. Set `SCOUT_DRIVER=meilisearch`, `SCOUT_QUEUE=true`, `MEILISEARCH_HOST` and `MEILISEARCH_KEY`.
2. Run `php artisan scout:sync-index-settings`. It sets the searchable attributes
   in ranking order (see `config/scout.php`).
3. Run `php artisan scout:import "App\Models\Product"` to index the catalogue.
   The index is then updated whenever a product or a category name changes.
   Inactive products are left out.

If Meilisearch can't be reached, the error goes to Sentry and the search falls
back to the database, so searching keeps working. With `SCOUT_QUEUE=true`,
indexing is done on the queue, so an outage never blocks saving a product.

To rebuild the index from scratch, run `php artisan scout:flush "App\Models\Product"`
and then the import again.

## 11. Product photos

Upload photos in the admin area (**Products → edit → Photo**). They're stored on
the media disk (`SHOP_MEDIA_DISK`) under `products/`. You can also upload them to
the bucket directly and set the product's `image` column to the path. `App\Support\Media` turns it into a CDN
URL, and full `https://` URLs are used as they are.

Locally, the same paths work on the `public` disk after running
`php artisan storage:link` once.

## 12. Monitoring

- **Uptime:** monitor `GET /up` every minute. It returns 500 if the database or
  Redis can't be reached, not only when PHP is down.
- **Errors:** set `SENTRY_LARAVEL_DSN`. Exceptions are reported automatically.
  With `LOG_STACK=stderr,sentry`, warnings go to Sentry too.
- **Alerts:** set up Sentry alert rules for these messages:
  - *"Stripe event for a checkout session with no matching order"* (critical). Money may
    have been taken for an order we lost. Find the session in the Stripe dashboard.
  - *"Order paid with an unexpected amount"* (warning). Check the order's `review_reason`.
  - *"Stripe webhook received but STRIPE_WEBHOOK_SECRET is not set"* (error).
  - *"Stripe event could not be processed after retries"* (error). Check the event's
    `last_error` in `stripe_events`. The reconciliation job will still settle the order.
  - *"Queue backlog"* (warning). Usually no worker is running.
  - *"Payment disputed"* (critical). Respond with evidence in Stripe before the deadline.
    It's also listed under **Payments → Disputes** in the admin area.
- **Heartbeats:** create one monitor per scheduled job, expecting a ping every
  15 minutes and every day respectively. Allow some grace time. A failed
  reconciliation run (for example a Stripe outage) skips its ping, so the monitor
  alerts on that too.

Sentry is configured with `send_default_pii` off, so customer emails and
addresses are not sent unless you change that.

## 13. Security headers

Every response gets `X-Content-Type-Options`, `X-Frame-Options`,
`Referrer-Policy` and `Permissions-Policy`. Over HTTPS in production, it also gets
`Strict-Transport-Security`. The storefront also sends a strict Content Security
Policy: scripts only from this site, plus a per-request nonce for the one inline
script.

**If you add a third-party script or widget** (analytics, chat, reviews), add its
domains to the policy in `app/Http/Middleware/SecurityHeaders.php`, or the browser
will block it. The admin area doesn't get the CSP, because Livewire needs inline
scripts.

## 14. Legal details

Set these before launch. The privacy policy, terms and email footers show them,
and anything missing appears on those pages as "[… to be added]":

`SHOP_LEGAL_NAME`, `SHOP_ADDRESS`, `SHOP_CONTACT_EMAIL`, `SHOP_COMPANY_NUMBER`,
`SHOP_VAT_NUMBER`, `SHOP_ICO_NUMBER`, `SHOP_HOSTING_PROVIDER`, `SHOP_EMAIL_PROVIDER`.

**VAT:** prices include VAT at `SHOP_VAT_RATE_BPS` (2000 = 20%), and every order
records the VAT it contains. Check with your accountant: for example, children's
clothing is zero-rated, which this single-rate setup doesn't handle.

The privacy policy and terms are drafts written for this shop's setup. Have them
reviewed by a legal professional before launch.

## 15. Backups and the restore drill

The managed database's automatic backups are the primary backup. A backup only
counts once you have restored it, so run the drill **before launch and then
monthly**:

1. Restore the provider's latest backup into a **new, separate** database or
   instance, using the provider's "restore to new instance" or "fork".
2. Compare it with production. This also takes a logical dump you can keep off-provider:

   ```bash
   SOURCE_DATABASE_URL='postgres://…/production' \
   RESTORE_DATABASE_URL='postgres://…/restore_drill' \
   scripts/backup-restore-drill.sh /secure/backup/location
   ```

   The script dumps the source, restores the dump into the drill database and checks
   that every table's row count matches. It refuses to run when both URLs are the same.
   **The drill database's contents are replaced**, so never point
   `RESTORE_DATABASE_URL` at production.
3. Point a local copy of the app at the restored database (`DB_URL=…`) and check
   that recent orders are there.
4. Delete the drill database and record the date, how long the restore took and
   any problems.

Dumps contain customer data. Store them encrypted and with restricted access.
`storage/backups/` is git-ignored for local runs.

## 16. Testing against production services

The default `composer test` uses in-memory SQLite. To run the same suite on
PostgreSQL and Redis:

```bash
createdb loomlane_test        # once
DB_USERNAME=<your postgres user> composer test:services
```

This uses Redis database 15 for sessions and cache, and it is flushed between tests.
Jobs still run inline, so the real Redis queue is only exercised by a running worker.
