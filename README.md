# Halcyon Auto — API / BACKEND

REST API behind the **Halcyon Auto** car-sales website. It stores car listings and site content, handles staff sign-in, uploads photos and videos to Cloudinary, and emails the owner when someone uses the contact form.

Frontend repo: **halcyon-auto-web** (Next.js).

## Features

- **Car listings** — create, edit, delete, sold/available status, discount percent and down payment
- **Auth** — token-based staff sign-in (Laravel Sanctum); all write routes are admin-only
- **Uploads** — images, GIFs and videos via Cloudinary (photos 5 MB, GIFs 10 MB, videos 40 MB)
- **Site settings** — hero media (desktop and mobile), contact details, About, Our Trust, location, pop-up ad and finance calculator defaults
- **Careers** — job openings with open/closed status and closing dates
- **FAQ** — questions with topics, publish/hide and drag-free reordering
- **Articles** — drafts and published posts, categories, auto-generated slugs
- **Contact form** — sends an email to the owner through Resend

## Tech stack

| Area | Tools |
|---|---|
| Framework | Laravel (PHP 8.2+) |
| Auth | Laravel Sanctum |
| Media | Cloudinary (signed uploads) |
| Email | Resend |
| Database | MySQL / PostgreSQL / SQLite |

## Getting started

```bash
git clone <this-repo>
cd halcyon-auto-api

composer install
cp .env.example .env
php artisan key:generate

# set your database in .env, then:
php artisan migrate
php artisan serve        # http://localhost:8000
```

Create the first admin user (for example with `php artisan tinker`) and sign in from the website's `/admin/login`.

### Environment variables

| Variable | Purpose |
|---|---|
| `APP_URL`, `DB_*` | Standard Laravel app and database settings |
| `CLOUDINARY_*` | Cloud name, API key and API secret for uploads (see `config/services.snippet.php`, merge it into `config/services.php`) |
| `RESEND_API_KEY` | Resend API key |
| `CONTACT_FROM_EMAIL` | Sender address for contact emails |
| `CONTACT_TO_EMAIL` | Where contact emails are delivered (the owner's inbox) |

> Never commit `.env`. Rotate any secret that has ever been shared or committed.

### Upload limits

Videos can be up to 40 MB, so raise these in `php.ini` (and your web server):

```ini
upload_max_filesize = 45M
post_max_size = 48M
```

## API overview

Public (no sign-in):

| Method | Route | Description |
|---|---|---|
| GET | `/api/listings`, `/api/listings/{id}` | Browse cars (filters, sorting, pagination) |
| GET | `/api/settings` | Saved site content |
| GET | `/api/careers`, `/api/faqs` | Open roles, published FAQs |
| GET | `/api/articles`, `/api/articles/{slug}` | Published articles |
| POST | `/api/contact` | Contact form |
| POST | `/api/login` | Staff sign-in (returns a token) |

Admin (Bearer token): create/update/delete for listings, careers, FAQs and articles; `PUT /api/settings/{group}`; `POST /api/uploads`. See `routes/api.php` for the full list.

## Project structure

```
app/Http/Controllers/Api/   Listings, Auth, Uploads, Settings, Careers, Faq, Articles, Contact
app/Models/                 CarListing, CareerOpening, Faq, Article, SiteSetting
database/migrations/        Tables (FAQ migration seeds a few starter questions)
routes/api.php              All API routes
```

## License

Private project. All rights reserved.
