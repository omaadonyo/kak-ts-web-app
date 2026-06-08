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

### 2026-06-07 — Button bar redesign

**Changes**:
- `⚡book-services-list.blade.php` — Moved assign button next to status badge (inline dropdown with search, or tech name pill when assigned). Redesigned footer action buttons with unique color per action (indigo=assessment, blue=view assessment, amber=quote, cyan=project, slate=report, emerald=invoice, red=delete) and SVG icons. Removed duplicate assign dropdown from footer.

**Commands**:
- `npm run build`

### 2026-06-08 — Fix quotation dynamic totals, tech quotation access

**Goal**: Fix quotation line-item totals not updating dynamically when quantity/unit-price changed. Confirm technician role can access and add assessments + quotations.

**Root cause**: `updatedLineItems()` Livewire hook does not fire for nested array property changes (`lineItems.0.quantity`), so per-item totals stayed at 0 until add/remove.

**Fix**: Removed `updatedLineItems()` from all three files. Subtotal computed property now derives from `quantity × unit_price` directly. View displays computed total inline. Save methods compute `total` per item via `array_map` just before persisting.

**Changes**:
- `⚡tech-service-action.blade.php` — removed `updatedLineItems()`; `getSubtotalProperty()` computes from raw values; view total column uses inline calculation; `saveQuotation()` builds `$lineItems` with computed totals
- `⚡assessment-form.blade.php` — same pattern as above
- `⚡quotation-form.blade.php` — same pattern as above; uses `#[Computed]` for subtotal/tax/grandTotal
- `AGENTS.md` — session tracking updated

**Commands**:
- `npm run build`

### 2026-06-08 — Superadmin overhaul: colorful dashboards, service-based sales, login tracking

**Goal**: Overhaul superadmin pages — remove subscriptions, base sales on booked services, make dashboard colorful with tech performance/unassigned/due-overdue projects, track actual user login/logout with session duration.

**Changes**:
- `routes/web.php` — removed `superadmin.subscriptions` route
- `resources/views/layouts/app/sidebar.blade.php` — removed Subscriptions sidebar link; renamed Sales to Sales Reports
- `app/Providers/AppServiceProvider.php` — registered `Login`/`Logout` event listeners for `UserLog` tracking
- `app/Listeners/LogUserLogin.php` — new listener; creates `UserLog` with action=login, IP, user agent
- `app/Listeners/LogUserLogout.php` — new listener; creates `UserLog` with action=logout, session duration from last login
- `⚡superadmin-sales.blade.php` — rewritten: metrics based on `BookService` counts + `Invoice`/`Payment` totals, gradient stat cards, monthly service performance chart, service type breakdown, top clients table
- `⚡superadmin-dashboard.blade.php` — rewritten: gradient stat cards for users/services/technicians/projects, roles bar chart, service status breakdown, idle technicians list, technician performance table (assigned/assessed/quoted/project/completed + efficiency bar), due & overdue projects panels
- `⚡superadmin-logs.blade.php` — rewritten: login/logout/active-today/avg-session stat cards, user search, action filter, date range filter, login/logout badge icons, device type indicator (mobile/desktop), IP display, user role shown, empty state illustration
- `AGENTS.md` — session tracking updated

**Commands**:
- `npm run build`

### 2026-06-08 — Fix assessment form for techs, fix assign dropdown bug

**Goal**: Fix technician role unable to add assessment at `book-services/{id}/assessment` URL. Fix assign technician dropdown returning empty results.

**Root cause 1**: `$isEditable` was a public property initialized to `false` and only set in `mount()`. If mount didn't fire reliably, it stayed `false` for techs, showing "not completed" message.

**Fix 1**: Replaced `public bool $isEditable = false` with computed `getIsEditableProperty()` that always evaluates `Auth::user()->isAdmin() || Auth::user()->isTechnician()` fresh.

**Root cause 2**: `⚡book-services-list.blade.php:196` queried `User::where('role', 'tech')` but the role is stored as `'technician'` everywhere else.

**Fix 2**: Changed `'tech'` → `'technician'` so the assign dropdown shows technicians.

**Changes**:
- `⚡assessment-form.blade.php` — removed `$isEditable` public property; added `getIsEditableProperty()` computed property
- `⚡book-services-list.blade.php` — fixed `role = 'tech'` → `role = 'technician'`
- `AGENTS.md` — session tracking updated

**Commands**:
- `npm run build`
