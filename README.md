# Kak TS Web App

A service booking and management platform built with [Laravel](https://laravel.com) + [Livewire](https://livewire.laravel.com) + [Flux](https://fluxui.dev).

## Features

- **Service Booking** — Request services (Plumbing, Electricals, Carpentry) with photo uploads
- **Assessments** — Review and document findings with photo attachments
- **Quotations** — Generate itemized quotes with live totals, tax, and preview
- **Projects** — Track project progress with a visual slider and comment threads
- **Invoices** — Create invoices from quotations, mark as sent/paid
- **Dashboard** — KPI cards, service breakdown charts, recent activity, and quick actions
- **Dark/Light Mode** — Fully adaptable UI with persistent theme toggle
- **Authentication** — Login, registration, email verification, two-factor auth

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 13 |
| Frontend | Livewire 3 + Alpine.js |
| UI Kit | Flux UI |
| Styling | Tailwind CSS v4 |
| Build | Vite |

## Getting Started

```bash
# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate

# Storage link (for photo uploads)
php artisan storage:link

# Build assets
npm run build

# Start dev server
php artisan serve
```

## Routes

| URL | Description |
|-----|-------------|
| `/dashboard` | Main dashboard with stats |
| `/book-service` | Book a new service |
| `/book-services` | List of service requests |
| `/assessments` | All assessment reports |
| `/quotations` | All quotations |
| `/invoices` | All invoices |
| `/settings/profile` | Profile settings |
| `/settings/appearance` | Theme toggle |
| `/settings/security` | Password & 2FA |

## Workflow

1. **Book a Service** — Fill in details and upload photos
2. **Assessment** — Admin reviews and documents findings
3. **Quotation** — Itemized quote generated and sent to client
4. **Accept → Project** — Client accepts quote → project auto-created
5. **Invoice** — Invoice generated from the quotation
