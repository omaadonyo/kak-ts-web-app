# AGENTS.md

## Sessions

### 2026-06-04 — Multi-role booking workflow

**Goal**: Build a complete multi-role service booking workflow (client, technician, admin) with company sub-accounts, milestone tracking, payments, and client approval. Add search, pagination, CSV/PDF exports, branded project reports, mauve theme, company client team management, and child-user visibility.

**Changes**:
- `app/Models/User.php` — added `role`, `client_type`, `phone`, `parent_company_id` to `#[Fillable]` (fixes mass-assignment when company creates users)
- `⚡transactions-index.blade.php` — fixed company client scoping to use `whereIn` with child user IDs (was using `where` with single ID)
- `⚡book-service.blade.php` — company clients can now create bookings on behalf of their child users (client selector lists their company users, booking saved under child's `user_id`)
- All index pages & dashboard already had company-child-aware scoping via `$ids = [Auth::id()]; if ($user->isCompany()) $ids = array_merge($ids, $user->companyUsers()->pluck('id')->toArray());`

**Changes**:
- Migration `add_role_and_client_fields_to_users_table` — role, client_type, parent_company_id, phone.
- Migration `add_assigned_to_to_book_services_table` — assigned_to FK.
- Migration `add_approved_at_to_projects_table` — approved_at, completed_at.
- Migration `create_milestones_table` — milestones schema.
- Migration `create_payments_table` — payments schema.
- Model `User` — role helpers, company relationships.
- Model `Milestone`, `Payment` — new models.
- Model `BookService` — assignedTo().
- Model `Project` — milestones(), approved_at/completed_at casts.
- Model `Invoice` — payments().
- AppServiceProvider — 11 authorization gates (+ `manage-company-users`).
- Sidebar — role-aware labels + @can gates.
- Dashboard — role-based baseQuery() scoping.
- All index pages (services, assessments, quotations, invoices, users, transactions) — search + pagination + CSV/PDF export via `response()->streamDownload()`.
- `⚡project-report.blade.php` — branded project report page with PDF export.
- `⚡users-index.blade.php` — admin user management (list, inline role editing, add user, search, pagination, export).
- `⚡company-users-index.blade.php` — company client team management (list, add individual users under company, remove, search, pagination, export).
- Admin booking-on-behalf — admin can create service requests for any client.
- `⚡project-report.blade.php` — branded project profile with PDF export.
- Settings profile page — added phone, client_type, parent_company fields.
- `resources/css/app.css` — mauve gray palette, red accent, button transitions.
- Export templates in `resources/views/exports/` — 7 Blade templates for PDF/CSV.
- Routes added: `users`, `reports`, `transactions`, `company/users`, `project-report`.
- Sidebar — Transactions, Users, Reports, My Team.
- AGENTS.md created.

**Commands**:
- `php artisan migrate`
- `npm run build`
- `git add -A && git commit`
- `git remote add origin ... && git push`

### 2026-06-04 — Upload limit, permission gating

**Changes**:
- `⚡book-service.blade.php` — max upload per photo raised from `5120` (5MB) → `20480` (20MB); UI hint text updated.
- `⚡assessment-form.blade.php` — max upload per photo raised to 20MB; added `$isEditable` property; non-tech/non-admin users see read-only findings + photos (no form, no save button). If no assessment exists, shows "not completed" message.
- `⚡quotation-form.blade.php` — added `$isEditable` property; non-tech/non-admin users see static quotation preview (no line-item editors, no save button). "Accept & Start Project" button guarded by `accept-quotation` gate; visible to client when status is `sent`.
- `AGENTS.md` — session tracking updated.

**Commands**:
- `npm run build`
