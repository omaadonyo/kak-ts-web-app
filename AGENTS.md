# AGENTS.md

## Sessions

### 2026-06-04 — Multi-role booking workflow

**Goal**: Build a complete multi-role service booking workflow (client, technician, admin) with company sub-accounts, milestone tracking, payments, and client approval.

**Changes**:
- Migration `add_role_and_client_fields_to_users_table` — role, client_type, parent_company_id, phone.
- Migration `add_assigned_to_to_book_services_table` — assigned_to FK.
- Migration `add_approved_at_to_projects_table` — approved_at, completed_at.
- Migration `create_milestones_table` — milestones schema.
- Migration `create_payments_table` — payments schema.
- Model `User` — role helpers, company relationships.
- Model `Milestone`, `Model` `Payment` — new models.
- Model `BookService` — added assignedTo().
- Model `Project` — added milestones(), approved_at/completed_at casts.
- Model `Invoice` — added payments().
- AppServiceProvider — 10 authorization gates.
- Sidebar — role-aware labels + @can gates.
- Dashboard — role-based baseQuery() scoping.
- book-services-list.blade.php — role-based queries, assign() method, role-aware title, client/tech info display, role-gated action buttons, admin assign UI with dropdown.
- app.blade.php — `!p-0` on flux:main.
- book-services-list.blade.php — role-based queries, assign() method, role-aware title, client/tech info display, role-gated action buttons, admin assign UI with dropdown.
- `⚡users-index.blade.php` — admin user management (list, inline role editing, pagination).
- `⚡reports.blade.php` — business metrics dashboard (counts, revenue, status breakdowns, recent activity).
- `⚡transactions-index.blade.php` — combined table of invoices, receipts, and quotations.
- Routes added: `users`, `reports`, `transactions`.
- Sidebar — added Transactions under Documents, Users + Reports under Management (gated `@can('manage-users')`).
- app.blade.php — `!p-0` on flux:main.
- AGENTS.md created.

**Commands**:
- `php artisan migrate`
- `npm run build`
- `git add -A && git commit`
- `git remote add origin ... && git push`
