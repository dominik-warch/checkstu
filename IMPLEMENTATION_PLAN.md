# checkstu — Implementation Plan

_An internal household task-management web app for a small group of shared users._

> Status: **in progress** — P0 + P1 shipped and tested; **P2 (recurrence engine) is next.**
> This document is the single source of truth: sections below are the design; the **Build status**
> section immediately below records what actually exists in the working tree today.

---

## 0. Build status — as of 2026-07-06

**checkstu runs.** P0 (bootstrap) and P1 (core app) are complete, plus several follow-ups and a
production Docker setup. **53 tests green** (PHPUnit), `tsc` clean, `npm run build` clean.
⚠️ **No git commits yet** — the working tree *is* the current state (offer stands to baseline it).

### Shipped
- **P0 — bootstrap.** Scaffolded via `composer create-project laravel/react-starter-kit`. Real
  pinned stack: **Laravel 12.62, Inertia 2, React 19, Tailwind 4, Vite 6, TypeScript 5.7, PHPUnit
  11.5 (not Pest), PHP 8.5.7.** SQLite (WAL + FK + busy_timeout). Locale `de`, timezone
  Europe/Berlin. German `lang/de/*` + frontend i18n (`resources/js/lib/i18n.ts`; German UI / English
  code).
- **P1 — core.** Username login (self-registration disabled); domain schema per §4
  (tasks/occurrences/categories/dependencies/recurrence_dates/templates/completion_logs) — **only
  one-off tasks wired so far** (recurrence engine = P2, but the schema columns already exist);
  create/edit/delete; complete + admin **complete-on-behalf** (attribution + `acted_by` log);
  Today / Tasks (Meine·Alle) / Upcoming / Family screens; admin Users management; mobile bottom-nav
  shell; **swipe-to-complete**; factories + `DemoSeeder` + `TaskTemplateSeeder`.
- **Access control — three layered features:**
  - **Roles** admin (parents) / member (kids) / **guest** — guest sees & completes **only** tasks
    assigned to them (`TaskOccurrence::scopeVisibleTo`).
  - **Unassigned = up-for-grabs** — unassigned tasks appear in everyone's "Meine" tab (not guests),
    labelled "Für alle".
  - **Private tasks** (`tasks.is_private`) — visible/actionable **only to the creator**, overrides
    role/guest/up-for-grabs (see §6).
- **Docker / deploy** (§12.3) — `docker/prod/Dockerfile` + `docker-compose.yml` (app + worker +
  scheduler on one image, single SQLite volume), **trusted proxies** (`bootstrap/app.php`),
  `checkstu:create-user`. Built and run-verified (incl. `X-Forwarded-Proto: https` → https redirects).

### Next up (in order)
1. **P2 — recurrence engine** (the big one): `simshaun/recurr` for RRULE, explicit-date schedules
   (garbage pickup), `tasks:materialize` scheduled command (rolling 60-day horizon),
   generate-on-complete for `relative`. Only one-off is wired today — see §4.3–4.4.
2. **Template-catalogue picker** in the create flow (§4.8) — land it with P2 (needs materialization).
3. Then: dependency UX polish → **PWA** (§8.8) → **notifications** (§11, v2) → **CalDAV completion
   sync** (§10, v2).

### How to run / verify
- **Dev:** `composer run dev`, then log in as **`dominik` / `password`** (seeded). Demo accounts:
  `dominik`,`sara` (admin) · `leo`,`leni` (member) · `opa` (guest).
- **Tests** `php artisan test` (53) · **Types** `npx tsc --noEmit` · **Build** `npm run build`.
- **Docker:** `cp .env.docker.example .env` (set APP_KEY/APP_URL) → `docker compose up -d --build`
  → `docker compose exec app php artisan checkstu:create-user`; front with a TLS reverse proxy → `app:8080`.

### Gotchas for a returning session
- Tests are **PHPUnit class-style**, not Pest (`tests/Pest.php` is vestigial, ignore it).
- Base `app/Http/Controllers/Controller.php` is **bare** — use `Gate::authorize()` in controllers,
  not `$this->authorize()`.
- `UserFactory` uses a process-wide **sequence** for username/email (faker `unique()` flaked the suite).
- Migrations are **edited in place** (greenfield, uncommitted); `php artisan migrate:fresh --seed` is fine.
- `docker/prod/nginx.conf` must stay minimal (**only** `access_log off;`) — the base image already
  sets `client_max_body_size`/`gzip`; redeclaring them crashes nginx.
- Everything is **one shared space** (no multi-tenancy); visibility exceptions are guest scope +
  private tasks only.

---

## 1. Vision & scope

checkstu helps everyone in a household see, share, and complete recurring and one-off chores
with as few taps as possible, on their phone or tablet. One person is not the "manager" — the
household shares the task pool, sees who's responsible for what, and the app makes the next
actionable thing obvious.

### Requirements traceability (from `idea.md`)

| Requirement (original, German) | How it's covered |
|---|---|
| App for household chores, multiple users / account system | Session auth, 4 individual accounts, roles admin/member (§6) |
| Task urgency | `priority` (0–3) on the task (§4) |
| Task due date | `due_date` on each occurrence (§4) |
| Task responsibility / assignment | `default_assignee_id` on task, per-occurrence `assignee_id` (§4) |
| Task dependencies | self-referencing `task_dependencies` + actionability engine (§5) |
| Recurring tasks, regular intervals (daily/weekly/biweekly) | `rrule` recurrence via `simshaun/recurr` (§4) |
| Recurring tasks, irregular intervals (garbage pickup) | `explicit_dates` recurrence via `task_recurrence_dates` (§4) |
| Ad-hoc tasks | `one_off` recurrence = a task with exactly one occurrence (§4) |
| **Catalogue of predefined todos for fast creation** | `task_templates` catalogue + create-from-template flow (§4.8, §8.3D) |
| List view with filters (my tasks, sorting) | Query-param-driven filtered list, server-side (§7, §8) |
| Simple UX, few clicks | Bottom nav + FAB + swipe-to-complete + optimistic UI (§8) |
| Usable on phone & tablet | Mobile-first Tailwind, installable PWA (§8, §9) |
| Simple stack, SQLite | Laravel + Inertia + React + SQLite, single file (§2, §11) |
| Sync tasks/appointments via CalDAV or similar, external dep OK | One-way CalDAV push (create/update/**complete**/delete on external server), client-only — **v2** (§10) |

### Explicit non-goals for v1
- **No calendar/CalDAV sync in v1** — it's a v2 feature. Completing a task in checkstu syncing
  through to your external todo/calendar app is the v2 headline behaviour (§10).
- **No automatic assignee rotation** — parents delegate every task manually (§4).
- **No multi-household / tenancy** — one implicit shared space (this home), 4 admin-managed
  accounts, no invitations or household switcher (§6).
- No fully offline-first writes (installable + read-resilient only — §9).
- No two-way calendar sync near-term — even v2 is one-way push only (§10.7).
- No fine-grained permission system (three roles: admin/member/guest — §6).
- No SSR (client-render only, simpler — §9).

---

## 2. Tech stack

| Layer | Choice | Version (July 2026) | Notes |
|---|---|---|---|
| Language | PHP | **8.2+** (running **8.5.7**) | Kit requires ^8.2 |
| Framework | Laravel | **12.62** (installed) | React starter kit's current pin |
| SPA bridge | Inertia.js | **2.x** (installed `@inertiajs/react ^2.0`) | Resolved — see note below |
| UI | React | **19.x** | Shipped by the kit |
| Styling | Tailwind CSS + shadcn/ui | **Tailwind 4**, shadcn (Radix) | CSS-first config, copy-in components |
| Build | Vite | **6.x** | Shipped by starter kit |
| DB | SQLite | — | WAL + foreign keys + busy_timeout |
| Recurrence | `simshaun/recurr` | 2025-maintained | RRULE expansion |
| Calendar | `sabre/vobject` + `Sabre\DAV\Client` | vobject 4.5.6, dav 4.7 | iCal generation + CalDAV HTTP (§10) |
| Tests | PHPUnit | **11.5** (kit default) | Kit ships PHPUnit, not Pest |
| Formatting | Pint / ESLint / Prettier | shipped by kit | |

> **✅ Resolved at scaffold time (2026-07-05).** `composer create-project laravel/react-starter-kit`
> pinned **Laravel 12.62 + Inertia 2 + React 19 + Tailwind 4 + Vite 6 + TypeScript 5.7**, tests via
> **PHPUnit 11.5** (not Pest). (The planning agents' web research had guessed Laravel 13 / Inertia 3; the shipping kit
> is 12/2 — we take the kit's pin, as planned.) Everything in this plan works on Inertia 2;
> optimistic updates are just slightly more manual than the v3 first-class API.

### Why this stack (given the brief)
The user's primary stack is PHP/Laravel with interest in React. **Inertia + React is the sweet
spot**: real React SPA feel and PWA installability, but plain Laravel controllers with **no
separate REST API to design or version**. It reuses existing Laravel skill, scratches the React
interest, and hits the "sleek, low-friction, mobile" bar that an admin-panel approach (Filament)
would fight. A **Filament panel can be added later, for the admin only**, for data cleanup/debug
without compromising the household-facing SPA.

### Language & conventions
- **Code is English, UI is German.** All identifiers, comments, DB columns, routes, commit
  messages, and docs are English; every user-facing string (labels, buttons, empty states,
  notifications, validation messages) is **German**. The seed examples in §4.8/§9 are already German.
- **Single UI locale (`de`)** — no locale switcher. Keep German strings in Laravel `lang/de/*.php`
  (server: validation, mail) and a frontend `de.ts` dictionary (React) rather than hardcoding: one
  place to proofread, and it leaves the door open to add English later without a rewrite.
- **Timezone `Europe/Berlin`** app-wide (`APP_TIMEZONE`); store UTC, compute due-dates in app tz.

---

## 3. Architecture overview

```
┌────────────────────────────────────────────────────────────┐
│  Browser / installed PWA (React 19 + Inertia)               │
│   app-shell (bottom nav, FAB, toaster) · pages · optimistic │
└───────────────▲───────────────────────────┬────────────────┘
                │ Inertia XHR (props/JSON)   │ POST/PATCH/DELETE
                │ full page = server-driven  │ mutations
┌───────────────┴───────────────────────────▼────────────────┐
│  Laravel 12                                                 │
│   Controllers (thin, Inertia::render) → FormRequests →      │
│   Action classes (business logic) → Eloquent → SQLite       │
│   Policies (role checks) · one shared space, no tenancy      │
├─────────────────────────────────────────────────────────────┤
│  Queue (database driver)   Scheduler (cron → schedule:run)  │
│   • tasks:materialize (daily)  • tasks:remind (hourly)      │
│   • SyncTaskToCalDav (on change)  • ReconcileCalDav (nightly)│
└─────────────────────────────────────────────────────────────┘
                │ (later phase, client only)
                ▼
        External CalDAV server (Nextcloud / Baikal / iCloud …)
```

**Key principles:** the DB is the source of truth; server state travels as Inertia props; no
client-side API layer, no Redux, no React Router. Thin controllers, logic in Action classes,
authorization in Policies. One shared space (this home) — no tenant scoping; access is gated by
role.

---

## 4. Domain model & database schema

The central decision: **separate the recurring _definition_ (`tasks`) from its concrete
_instances_ (`task_occurrences`).** A `Task` is the series/template; a `TaskOccurrence` is one
due-dated thing you can check off. **A one-off task is just a task with one occurrence.** This
unifies list views (always query occurrences), completion history (per instance), and recurrence
(completing occurrence N spawns N+1).

Secondary decision: **one shared space, not multi-tenant.** This is a single home with 4 known
users, so there is no `households` table and no per-row tenant scoping — every task/category is
shared. Users have a **role** (`admin` = parents, `member` = kids); admins can additionally act on
others' behalf (complete a task and attribute it to another user — §6).

### 4.1 Entity map

```
User (role: admin|member)          (4 known users, one shared space)
Task ──< TaskOccurrence            (definition spawns concrete instances)
Task >──< Task  (self-ref: task_dependencies, "blocked_by")
Task >──< Category (category_task pivot)
Task ──< TaskRecurrenceDate        (explicit dates for irregular schedules)
TaskOccurrence ── assignee / completed_by ──> User
TaskCompletionLog ── user (attributed) / acted_by (admin who did it) ──> User
```

### 4.2 Migrations (column-level)

SQLite notes: no native ENUM — use `string` columns + PHP backed-enum casts. Booleans store as
integers. **Partial indexes** (`WHERE completed_at IS NULL`) carry the hot read paths.

> **Single shared space.** There is one implicit household (this home), so there is **no
> `households` table and no tenant scoping** — every row below is shared by all users. What we keep
> is **individual user accounts with a role** so tasks can be assigned/attributed and parents (as
> admins) can act on others' behalf.

**`users`** (extend the starter kit's table)
```php
// starter kit gives: id, name, email (unique), password, timestamps, remember_token
$t->string('username')->unique();                // login identifier (not email — see §6)
$t->string('role')->default('member');           // 'admin' (parents) | 'member' (kids)
$t->string('email')->nullable()->change();       // optional; kids won't have one
```
Accounts are **admin-managed** (a parent creates them) — no self-registration, no invitations.
Users log in with `username`; email is optional and email verification is off (§6).

**`categories`** (shared tags)
```php
$t->id();
$t->string('name')->unique();
$t->string('color', 7)->nullable();              // hex #22aa55
$t->timestamps();
```

**`tasks`** (the definition / series)
```php
$t->id();
$t->string('title');
$t->text('description')->nullable();
$t->unsignedTinyInteger('priority')->default(1); // 0=low 1=normal 2=high 3=urgent

$t->foreignId('default_assignee_id')->nullable()->constrained('users')->nullOnDelete();

// Recurrence config (see §4.3 for the canonical model)
$t->string('recurrence_type')->default('one_off'); // one_off|rrule|explicit_dates|relative
$t->string('rrule')->nullable();                   // RFC5545, e.g. FREQ=WEEKLY;INTERVAL=2;BYDAY=MO
$t->date('anchor_date')->nullable();               // DTSTART for rrule / first due date
$t->unsignedSmallInteger('relative_interval_days')->nullable(); // for 'relative'
$t->date('recurrence_ends_on')->nullable();        // optional series end

$t->boolean('is_active')->default(true);           // pause a series without deleting
$t->foreignId('created_by')->constrained('users');
$t->timestamps();
$t->softDeletes();

$t->index('is_active');
$t->index('default_assignee_id');
```

**`task_recurrence_dates`** (explicit irregular dates — the garbage-pickup case)
```php
$t->id();
$t->foreignId('task_id')->constrained()->cascadeOnDelete();
$t->date('due_on');
$t->boolean('is_consumed')->default(false);
$t->unique(['task_id', 'due_on']);
$t->index(['task_id', 'is_consumed', 'due_on']);
```
Populated once from an imported municipal ICS/CSV.

**`task_occurrences`** (concrete, completable instances)
```php
$t->id();
$t->foreignId('task_id')->constrained()->cascadeOnDelete();
$t->date('due_date')->nullable();                // null = "someday"
$t->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
$t->timestamp('completed_at')->nullable();
$t->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
$t->boolean('is_skipped')->default(false);
$t->timestamps();

$t->unique(['task_id', 'due_date']);             // idempotency for materialization (§4.4)
$t->index('due_date');
$t->index(['assignee_id', 'due_date']);

// Partial index for the dominant "open items" query:
// DB::statement("CREATE INDEX task_occurrences_open_idx
//   ON task_occurrences (due_date)
//   WHERE completed_at IS NULL AND is_skipped = 0");
```

**`task_dependencies`** (self-ref, "blocked_by")
```php
$t->id();
$t->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();          // blocked task
$t->foreignId('depends_on_task_id')->constrained('tasks')->cascadeOnDelete(); // blocker
$t->timestamps();
$t->unique(['task_id', 'depends_on_task_id']);
$t->index('depends_on_task_id');
// App-level guard: task_id != depends_on_task_id AND no cycles (§5)
```

**`category_task`** — pivot `(task_id, category_id)` primary key + `category_id` index.

**`task_templates`** (catalogue of predefined todos — see §4.8)
```php
$t->id();
$t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // null = seeded system template
$t->string('name');
$t->text('description')->nullable();
$t->unsignedTinyInteger('priority')->default(1);
$t->string('recurrence_type')->default('one_off'); // one_off|rrule|explicit_dates|relative (explicit_dates templates store no dates — imported on task creation)
$t->string('rrule')->nullable();
$t->unsignedSmallInteger('relative_interval_days')->nullable();
$t->foreignId('suggested_category_id')->nullable()->constrained('categories')->nullOnDelete();
$t->string('icon')->nullable();                    // optional emoji for a quick visual pick
$t->unsignedInteger('usage_count')->default(0);    // catalogue sorts most-used first
$t->timestamps();
$t->index('usage_count');
```

**`task_completion_logs`** (immutable history for "who did what" + stats)
```php
$t->id();
$t->foreignId('task_occurrence_id')->nullable()->constrained()->nullOnDelete();
$t->foreignId('task_id')->constrained();
$t->foreignId('user_id')->constrained();         // attributed completer (whose it counts as)
$t->foreignId('acted_by_user_id')->nullable()->constrained('users')->nullOnDelete(); // admin who did it on their behalf; null = self
$t->string('action');                            // completed|skipped|reopened
$t->date('due_date')->nullable();                // snapshot
$t->timestamp('created_at')->useCurrent();
$t->index(['user_id', 'created_at']);
```

### 4.3 Recurrence — the canonical model (reconciled)

> Two planning tracks proposed different vocabularies for this. **This is the single agreed
> model.** `recurrence_type` is one of four values:

| `recurrence_type` | Anchored to | Columns used | Covers | Next date from |
|---|---|---|---|---|
| **`one_off`** | — | — | ad-hoc single task | none (one occurrence) |
| **`rrule`** | calendar | `rrule`, `anchor_date` | daily / weekly / biweekly / monthly / weekdays | `recurr` expands RRULE |
| **`explicit_dates`** | calendar | `task_recurrence_dates` rows | irregular real-world schedules (trash pickup) | next unconsumed `due_on >= today` |
| **`relative`** | completion | `relative_interval_days` | "every ~N days after I last did it" (water plants) | `completed_at + N days` |

**Why the split matters (the single most important design decision):** a municipal trash calendar
is _not_ expressible as a clean RRULE (holiday shifts). Forcing it into RRULE is fragile — an
explicit date list is authoritative and importable. Conversely, "water plants every 3 days from
when I last watered" is _completion-anchored_ and has no calendar date until you finish the
current one. Modeling `rrule` (calendar-fixed) vs `explicit_dates` (calendar-fixed, irregular) vs
`relative` (completion-floating) explicitly prevents the classic "garbage pickup regenerated from
my completion date" bug.

RRULE examples: weekly Saturday = `FREQ=WEEKLY;BYDAY=SA`; biweekly Monday =
`FREQ=WEEKLY;INTERVAL=2;BYDAY=MO`; every 3rd day = `FREQ=DAILY;INTERVAL=3`.

### 4.4 Occurrence generation (reconciled: rolling horizon + on-complete)

> Two tracks disagreed (one-open-occurrence + healer vs. rolling horizon). **Decision: rolling
> horizon**, because the Upcoming agenda (§8.3E) and calendar export (§10) both need future
> occurrences to display/export.

- **Calendar-anchored (`rrule`, `explicit_dates`) → rolling-horizon materialization.**
  A daily scheduled command `tasks:materialize` ensures every active calendar-anchored task has
  occurrences materialized up to a horizon (`now + 60 days`). `MaterializeOccurrencesAction`
  computes due dates in `[last_materialized, horizon]` (via `recurr` for RRULE, or next unconsumed
  `task_recurrence_dates` rows) and `firstOrCreate`s occurrences. **Idempotency is guaranteed by
  the `unique(task_id, due_date)` index** — the daily re-run never duplicates. Also run once on
  deploy so a fresh install isn't empty.

- **Completion-anchored (`relative`) → generate-on-complete.** No horizon; completing occurrence N
  creates N+1 with `due_date = completed_at + relative_interval_days`.

`CompleteTaskAction` (transactional): mark occurrence done + `completed_by`/`completed_at`; if
`relative`, generate the next occurrence; if `explicit_dates`, mark the matching date row
`is_consumed`; write a `task_completion_logs` row; re-evaluate dependents (§5). Wrap in
`DB::transaction`.

### 4.5 Eloquent relationships (key ones)

```php
// Task
public function occurrences(): HasMany;                 // TaskOccurrence
public function openOccurrence(): HasOne;               // whereNull(completed_at)->oldestOfMany('due_date')
public function recurrenceDates(): HasMany;             // TaskRecurrenceDate
public function categories(): BelongsToMany;
public function dependencies(): BelongsToMany;          // tasks that block THIS (task_dependencies: task_id → depends_on_task_id)
public function dependents(): BelongsToMany;            // tasks blocked BY this (reverse)
public function defaultAssignee(): BelongsTo;

// User  — role is a column ('admin'|'member'); helper: isAdmin()
public function assignedOccurrences(): HasMany;
public function completedOccurrences(): HasMany;        // TaskOccurrence, completed_by
```

**Casts:** `priority => Priority::class`, `recurrence_type => RecurrenceType::class`,
`role => Role::class`, dates as `date`, booleans as `bool` (PHP 8.3 backed enums). **No global
tenant scope** — it's one shared space, so tasks/occurrences/categories are simply all visible to
every signed-in user; authorization is by role (§6), not by ownership.

### 4.6 Derived state (computed, never stored)

Overdue / due-soon change with the clock, so compute them at read time:
```php
// TaskOccurrence::status  — done | skipped | someday | overdue | due_soon | open
```
**Urgency sort** = overdue first, then `priority` desc, then `due_date` asc (nulls last via
`ORDER BY due_date IS NULL, due_date`). Only `priority` is persisted.

### 4.7 Hot queries + indexes (SQLite)

| Query | Index |
|---|---|
| My open tasks by urgency/due | `(assignee_id, due_date)` |
| All open list / overdue | partial `task_occurrences_open_idx` |
| Due soon (today..+2) | `(due_date)` |
| Actionable now (§5) | `task_dependencies(depends_on_task_id)` + partial open index |
| By category | `category_task(category_id)` |
| Next irregular date | `task_recurrence_dates(task_id, is_consumed, due_on)` |
| Completion history/stats | `task_completion_logs(user_id, created_at)` |

Enable **WAL** (`PRAGMA journal_mode=WAL`) + `busy_timeout=5000` so web + queue + scheduler don't
collide; run `ANALYZE` occasionally so the planner uses these indexes.

### 4.8 Task template catalogue (fast recurring-task creation)

Adding a recurring chore should take a couple of taps, so the create flow is backed by a
**catalogue of predefined task templates** (`task_templates`). A template is a reusable blueprint —
title, description, default `priority`, default recurrence (`recurrence_type` + `rrule` /
`relative_interval_days`), and a suggested category — but it carries **no occurrences**. For an
`explicit_dates` template (e.g. garbage collection) only the *type* + category is stored; creating
a task from it opens the date-import step (dates are specific to a year/municipality — §4.3).

- **Source:** the app ships a **seeded starter catalogue** (`created_by = null`) of common household
  chores; anyone can add **custom templates** (`created_by` set). All templates are shared (one
  space) and appear together in the picker, most-used first.
- **Create-from-template:** `CreateTaskFromTemplateAction` copies the template's blueprint fields
  into a new `tasks` row, then runs `MaterializeOccurrencesAction` (§4.4). The user only sets
  assignee + start/due before saving. `usage_count` is incremented so the catalogue self-sorts by
  what you actually use.
- **Save-as-template:** any existing task can be promoted to a shared template to grow the
  catalogue over time.
- **Starter catalogue (seed examples):** Staubsaugen (weekly), Bad putzen (biweekly), Küche wischen
  (weekly), Bettwäsche wechseln (~14 days, `relative`), Pflanzen gießen (~3 days, `relative`), Müll
  rausstellen (`explicit_dates`, dates imported on creation), Fenster putzen (monthly), Kühlschrank
  reinigen (monthly) — each with a sensible default priority + category.

---

## 5. Task dependencies & actionability

**Representation:** adjacency list in `task_dependencies` (`task_id` is blocked _by_
`depends_on_task_id`). Dependencies live at the **task (series) level**, evaluated against each
task's current open occurrence — simpler to author ("Vacuuming is blocked by Tidying") than
per-occurrence wiring.

**"Actionable now"** = an open occurrence whose task has no blocker with an incomplete open
occurrence. `ResolveDependenciesAction` computes this as a derived boolean feeding the "what can I
do now" list — not stored:
```php
$blockedTaskIds = DB::table('task_dependencies as d')
    ->join('task_occurrences as o', 'o.task_id', '=', 'd.depends_on_task_id')
    ->whereNull('o.completed_at')->where('o.is_skipped', false)
    ->distinct()->pluck('d.task_id');
// actionable = open occurrences whereNotIn('task_id', $blockedTaskIds)
```
Show blocked tasks **greyed-out with "Waiting on: {blocker}"**, not hidden — users want to see why.
On completing a task, optimistically flip newly-unblocked tasks to actionable ("1 task unblocked").

**Cycle prevention (write-time):** before inserting `A depends_on B`, reject if `A == B` or if `A`
is reachable from `B` (cheap DFS at household scale). Enforce in a FormRequest for a clean
validation error.

---

## 6. Auth & users

Individual accounts in one shared space: **parents (`admin`) and kids (`member`)**, plus optional
**guest** helpers who only see their own assigned tasks (below). No multi-tenancy, no invitations,
no household switcher.

**Authentication:** session cookies via the starter kit's login/password-reset pages, but the
**login identifier is `username`, not email** (point the starter kit's auth at `username`). Inertia
is a same-origin SPA over classic Laravel session auth — **no Sanctum token flow, no API guard.**
CSRF handled by Inertia.

**Accounts are admin-managed (no open self-registration).** A parent creates the family's accounts
on a simple **Users** admin screen (name, username, role, password). **Email is optional** (kids
won't have one) and email verification is disabled. (If you'd rather allow self-registration, it's
a one-line route toggle; default is closed for a family install.)

**Authorization = Policies + a `role` check. No `spatie/laravel-permission`** (over-engineering for
three roles). `TaskPolicy`, `UserPolicy`:
- **Admin (parent) & member (kid)** share one pool: both may `view` / `create` / `update` /
  `complete` any task.
- **Admin only:** `delete` tasks, manage user accounts (create/edit/reset/delete), and **complete a
  task on behalf of another user** (attribute the completion to someone else — below).
- **Guest (`Role::Guest`)** — a limited helper (e.g. grandparent, babysitter): may **only see and
  complete tasks assigned to them**, nothing else. This is the *one* row-level visibility exception
  to "everyone sees everything" — enforced by a `TaskOccurrence::scopeVisibleTo($user)` applied to
  the Today/Tasks/Upcoming queries, plus `TaskPolicy@view`/`@complete` checking the task is assigned
  to the guest. Guests cannot create/edit/delete tasks or reach the Family/admin area (nav hidden +
  route `abort(403)`). It is *not* multi-tenancy — still one shared space; guests are simply a
  filtered view of it.
- **Private tasks** (`tasks.is_private`) — a task flagged private is visible and actionable
  **only to its creator** (`created_by`), overriding role, guest scope, and the unassigned
  "up-for-grabs" rule (an admin cannot see another member's private task). Enforced in the same
  `scopeVisibleTo` (a `whereHas('task', is_private=false OR created_by=me)` clause) and in
  `TaskPolicy` (view/update/complete/delete). Creators manage their own private tasks regardless of
  role (a member may delete their own private task). Toggled via a checkbox in the create/edit form.
- Roles are a column on `users`; `isAdmin()` / `isGuest()` gate behaviour. Keep it this coarse.

**Complete-as-another-user (admin).** The behaviour you asked for: when a parent checks a task off,
they can choose **whose completion it counts as** (defaulting to the task's assignee, else
themselves). `CompleteTaskAction` accepts an optional `completedByUserId`; `CompleteTaskRequest`
authorizes that only an admin may set it to someone other than the actor. The occurrence's
`completed_by` records the **attributed** user; `task_completion_logs.acted_by_user_id` records the
**admin who actually did it** — so history reads "Mama marked Timo's task done." (This is
attribution, not full login-as impersonation; true impersonation is a possible later add if ever
needed.)

---

## 7. Backend application structure (pragmatic)

**Thin controllers + Action classes + FormRequests + Policies. No repository layer, no CQRS, no
DDD ceremony, no SSR.**

```
app/
  Http/Controllers/          # thin; Inertia::render or redirect()->back()
    TaskController, TaskCompletionController (invokable), UserController (admin), FamilyController
  Http/Requests/             # FormRequest per write: validation + authorize()
    StoreTaskRequest, UpdateTaskRequest, CompleteTaskRequest (authorizes on-behalf → admin only),
    StoreUserRequest (admin), AddDependencyRequest (cycle guard)
  Actions/
    Tasks/{CompleteTask, GenerateNextOccurrence, MaterializeOccurrences, ResolveDependencies}Action
    Users/{CreateUser, UpdateUser}Action
  Models/  Enums/  Support/Recurrence/   # wrappers around recurr + explicit-date logic
```

- **Controller:** authorize → validate (FormRequest) → call one Action → return Inertia/redirect.
- **Action:** one public `handle()`/`__invoke()`, constructor-injected deps, `DB::transaction` for
  multi-write, returns model/DTO. Unit-testable with no HTTP.
- Keep controller prop names stable + typed so the frontend's shared TS types stay in sync.

---

## 8. Frontend architecture, UX & PWA

Scaffold with **`laravel new checkstu --react`** — it provides Inertia + React 19 + Tailwind 4 +
shadcn/ui + TypeScript + auth pages (§2). Everything below is what we add on top.

### 8.1 Mental model
Laravel routes → controllers → `Inertia::render('page', props)`. Each page is a React component in
`resources/js/pages/`. Navigation is server-driven (no React Router). Mutations are plain
POST/PATCH/DELETE hit via `router.*`/`useForm`, returning a redirect; Inertia re-fetches props.
Quick actions use **partial reloads** (`only: [...]`) + **optimistic updates**.

### 8.2 Route → page map

| Route | Page | Purpose |
|---|---|---|
| `GET /` | `pages/home/today.tsx` | "Today / My tasks" home |
| `GET /tasks` | `pages/tasks/index.tsx` | Filterable/sortable list |
| `GET /tasks/{task}` | `pages/tasks/show.tsx` | Detail + quick actions |
| `GET /tasks/create`, `/tasks/{task}/edit` | `pages/tasks/form.tsx` | Create/edit (shared) |
| `GET /upcoming` | `pages/upcoming/index.tsx` | Calendar-ish agenda |
| `GET /family` | `pages/family/index.tsx` | Members + open-task counts |
| `GET /settings/users` | `pages/settings/users.tsx` | **Admin:** manage family accounts |
| auth | `pages/auth/*` | From starter kit |

### 8.3 UX & core screens

**Global interaction primitives**
- **Bottom tab nav** (thumb-reachable): **Today · Tasks · Upcoming · Family**.
- **FAB** (`+`) opens a **create bottom-sheet** from anywhere — one tap to start a task.
- **Swipe-to-complete**: swipe right → "Done" (optimistic); swipe left → snooze/reassign.
- **Long-press** → quick-actions bottom sheet (Done, Reassign, Edit, Snooze, Delete).
- **Persistent Inertia layout** so the shell (nav/FAB/toaster) never unmounts between navigations.

**A. Today (home)** — sections in priority order: Overdue (red) · Due today (swipe-to-complete) ·
Actionable next · collapsed "Blocked (n)". Big scannable cards; friendly empty state.

**B. Task list** — sticky filter/sort bar: segmented **[My tasks | Everyone]** + chips for
Assignee/Category/Status + Sort menu (Urgency · Due · Recent). Active filters show as removable
chips. Rows are swipeable cards. Virtualize only if lists get long.

**C. Task detail** — status pill, tap-to-reassign, due, category; a pinned bottom **primary
action** ("Mark done", or disabled "Blocked — waiting on X"). For **admins**, the complete action
opens a small **"who did it?"** selector (defaults to the assignee, else self) so a parent can
attribute the completion to a kid; members just complete as themselves. **Dependencies** section
(Blocked by / Blocks, tappable); plain-language **recurrence** line.

**D. Create/edit (bottom sheet, minimal clicks)** — the FAB opens the **template catalogue first**:
a searchable, most-used-first grid of predefined todos (§4.8) plus a **"＋ Custom"** tile. Tapping a
template pre-fills title, recurrence, category, and priority, so you only set assignee + due and
save — this is what makes adding recurring chores fast. "Custom" opens the blank form. The form
itself: autofocused title (Enter saves); quick chips for Assignee (default: me — assignment is
always manual), Due (Today/Tomorrow/Pick), Category (default: last used); a "More" expander for
recurrence mode (One-off / Regular / Irregular / every-N-days) + depends-on multi-select. An
overflow action **saves the current task as a new template**. Never expose cron syntax. Optimistic
save, sheet closes, toast confirms.

**E. Upcoming** — **agenda list grouped by day** (Today, Tomorrow, weekday, then dates) — far more
phone-usable than a month grid; optional week-strip to jump. Irregular tasks appear under their
projected next date with a `~` prefix.

**F. Family** — the 4 members with per-member open-task counts (fairness at a glance) and manage
categories. **Admins** get a Users screen to add/edit family accounts (name, username, role, password) — no
invitations, since it's a fixed family.

### 8.4 Filtering/sorting
**URL query params as single source of truth, applied server-side, fetched via Inertia partial
reloads** (not client-side filtering — shareable, back-button-correct, PWA-restorable). A
`use-filters.ts` hook writes filters to the URL and calls
`router.get('/tasks', filters, { only: ['tasks','counts'], preserveState, preserveScroll, replace })`.
Debounce text 300ms; chips/sort fire immediately. Controller returns filtered `tasks` + lightweight
`counts` for badges. Use **deferred props** for heavier below-the-fold sections.

### 8.5 Dependency & recurrence display
Reduce dependencies to one derived boolean: **actionable vs blocked**. Blocked card = muted +
amber "Blocked" badge + "Waiting on: X" + disabled complete. Recurrence = one small badge with a
plain-language label ("Every Mon", "~ every 3 weeks", none for one-off). No node-graph UI in v1.

### 8.6 Styling
**Tailwind 4 (CSS-first `@theme`) + shadcn/ui (Radix).** Semantic status tokens
(`urgent`/`blocked`/`done`) used consistently across badges/borders/swipe backgrounds. Dark mode
via `prefers-color-scheme` + `data-theme` override (starter kit ships the toggle). `vaul` for
iOS-style bottom sheets, `sonner` for toasts. Respect safe-area insets; 16px inputs (no iOS zoom).

### 8.7 Optimistic updates & snappiness
Mark-done / reassign / snooze apply to visible props immediately, fire
`router.patch(..., { only, preserveScroll })`, roll back + toast on error (wrap in
`use-optimistic-complete.ts`). `useForm` for the create sheet (`processing`/`errors`). Loading =
Inertia progress bar + **skeleton cards** for initial/deferred loads (no per-row spinners —
optimistic UI already shows the new state). **Prefetch task detail on hover/touchstart.** If the
kit ships Inertia 3, use its first-class optimistic API + `useHttp`; on v2 it's slightly more
manual but identical UX.

### 8.8 PWA (`vite-plugin-pwa`)
```ts
VitePWA({
  registerType: 'prompt',
  manifest: { name:'checkstu', short_name:'checkstu', display:'standalone',
              start_url:'/', scope:'/', theme_color:'#4f46e5', icons:[192,512,'512 maskable'] },
  workbox: {
    navigateFallback: null,                         // ⚠ shell is Blade, not static index.html
    globPatterns: ['**/*.{js,css,woff2}'],          // precache built assets
    runtimeCaching: [
      { urlPattern: ({request}) => request.headers.get('X-Inertia'),
        handler: 'NetworkFirst', options: { cacheName:'inertia-pages', networkTimeoutSeconds:3 } },
      { urlPattern: /\/storage\/.*\.(png|jpg|webp|svg)$/, handler:'CacheFirst',
        options: { cacheName:'media' } },
    ],
  },
})
```
**Laravel+Inertia caveats:** the HTML shell is served by Laravel, so `navigateFallback` doesn't
apply — cache navigations at runtime with **NetworkFirst** (fresh data wins, cache is fallback).
Publish generated `sw.js`/`manifest.webmanifest` to `public/` with scope `/`. **Never CacheFirst
authenticated Inertia JSON**; **clear caches on logout** (shared-device households). Capture
`beforeinstallprompt` for an "Install checkstu" banner; iOS gets a one-time "Add to Home Screen"
hint.

**Honest offline scope for v1:** ✅ app shell + last-viewed pages + static assets load offline.
⚠️ **offline _writes_ are out of scope** — detect offline and show "changes will sync when
reconnected" / block writes with a clear banner. v1 is **installable + read-resilient**, not
fully offline-first. Revisit background sync in v2 if there's demand.

### 8.9 Accessibility
44×44px min touch targets; don't rely on color alone (blocked = amber + text + icon); real
`<button>`/`<a>`/`<nav aria-current>`; swipe actions always have a non-gesture equivalent (the
quick-actions sheet); respect `prefers-reduced-motion`; WCAG AA contrast in both themes. Radix
primitives give focus traps / aria / ESC for free.

---

## 9. Testing

Default **Pest 4**. Test the domain hard, edges lightly.

**Must-test:**
- **Recurrence** (crown jewels): RRULE expansion (daily/weekly/biweekly/monthly); explicit-date
  materialization order + **idempotency** (run `tasks:materialize` twice → same count);
  `relative` generates next at `completed_at + N`; **DST/timezone boundary** (weekly task across a
  DST switch stays on the right calendar day).
- **Completion flow** — `CompleteTaskAction` sets fields, generates next only for `relative`, is
  transactional.
- **Dependency actionability** — blocked while dependency pending, actionable once done; cycle
  guard rejects.
- **Policies / roles** — a member can view/create/complete tasks but is 403 on admin actions
  (delete task, manage users); a member cannot complete **on behalf of** another user, an admin can
  (Pest dataset of role × action).
- **Complete-on-behalf** — admin sets `completed_by` to another user → occurrence attributed to
  them, `acted_by_user_id` records the admin; a member attempting it is rejected.

**Factories/seeders:** `TaskFactory` states (`fixedWeekly`, `relative`, `explicitDates([...])`,
`withDependency`); a `TaskTemplateSeeder` that seeds the shared starter catalogue of common chores
(§4.8); `DemoSeeder` (**4 users — 2 parents `admin`, 2 kids `member`** — a garbage-pickup
explicit-date task, a couple interval tasks, a dependency pair, a couple create-from-template
examples, and an on-behalf completion in the log) so the app is demoable immediately.

**Front-end:** Pest 4 **browser tests** for the two critical flows only — log in → see today;
complete a task → next appears. No exhaustive component tests for an internal app.

---

## 10. Calendar / CalDAV sync — v2 FEATURE (not in v1)

> **Deferred to v2.** v1 ships without any calendar sync. In v2, checkstu actively **pushes** each
> task to a server the household already uses, and — crucially — **checking a task complete in
> checkstu synchronises that completion** through to the external todo/calendar app. checkstu is a
> **CalDAV client only** (we never run a server) and stays the single source of truth. Sync is
> **one-way, checkstu → external**; reading external edits back is a separate, even-later concern
> (§10.7).

### 10.1 Target behaviour (v2)
Active one-way CalDAV push per opted-in user: on task create / update / **complete** / delete,
checkstu PUTs/updates/deletes the matching object on the user's external calendar so it stays in
step. The design centres on **completion propagation** — the whole point is that ticking a chore
off in checkstu also ticks it off (or removes it) in your own todo/calendar app.

**Object type is a per-target choice, because "done" is expressed differently:**
- **`VTODO` (recommended for todo apps — Nextcloud Tasks, Tasks.org):** completion is native — on
  complete, checkstu sets `STATUS:COMPLETED` + `PERCENT-COMPLETE:100` + `COMPLETED:<ts>`, giving a
  real "checked off" item, which matches the behaviour you described.
- **`VEVENT` (for plain calendar apps — iOS/Google Calendar):** no "done" state exists, so on
  completion checkstu **deletes** the event (or marks `STATUS:CANCELLED`). Broadest support (shows
  up everywhere, no extra app) but the calendar can only *remove* the item, not show a checkmark.
- **Trade-off to let each user pick:** `VTODO` gives true completion sync but needs a tasks-capable
  CalDAV app (iOS Reminders dropped third-party CalDAV VTODO in iOS 13; Android has no native VTODO
  client), whereas `VEVENT` works everywhere but only supports remove-on-complete. Object type is a
  property of the sync target, not hardcoded.

> _Rejected alternative:_ a read-only **ICS subscription feed** is simpler but cannot reflect "I
> completed this" as a checkable state in a todo app — the exact behaviour wanted — so active push
> is required.

### 10.3 Libraries (verified July 2026)
- **`sabre/vobject` ^4.5** (4.5.6, maintained) — iCalendar generation, RRULE/RDATE.
- **`Sabre\DAV\Client`** from **`sabre/dav` ^4.7** (maintained) — WebDAV/CalDAV HTTP: `propfind()`
  for discovery, `request()` for REPORT/PUT/DELETE, XML parsing.
- **Do NOT use `sabre/davclient`** (its own docs say "completely non-functional"),
  `smarcet/CalDAVClient` (abandoned 2019), or other hobby packages.
- Wrap everything behind our own `CalDavClient` service interface (swappable, unit-testable).

```bash
composer require sabre/vobject:^4.5 sabre/dav:^4.7
```

### 10.4 Entity → iCalendar mapping
Task → VEVENT/VTODO: stable **`UID`** (`checkstu-{task_uuid}@host`, generated once, stored — same
UID on re-PUT = update, not duplicate) · `SUMMARY` (title) · `DESCRIPTION` · due →
`DTSTART`(all-day `;VALUE=DATE`) or VTODO `DUE` · `PRIORITY` (map 0–3 → 1/5/9) · `CATEGORIES` ·
`DTSTAMP`/`SEQUENCE`/`LAST-MODIFIED` (bump SEQUENCE on change) · `PRODID -//geoventis//checkstu//EN`.
On completion: VEVENT → delete the event (cleaner calendar); VTODO → `STATUS:COMPLETED` +
`PERCENT-COMPLETE:100`.

**Recurrence → iCal:**
- `rrule` → single master VEVENT with `RRULE` (let the phone expand it — **don't** pre-expand).
- `explicit_dates` (garbage pickup) → single master + explicit **`RDATE`** list (recommended: one
  object/href/ETag, trivial to re-PUT next year). Fallback: N individual VEVENTs (one UID each) if
  a target chokes on long RDATE lists.
- Exceptions (`EXDATE`/`RECURRENCE-ID`) → out of scope for v1.

### 10.5 CalDAV push mechanics
Per-user config (`caldav_syncs`): server base URL, username, **encrypted** app-specific password,
resolved calendar URL, ctag, object mode. Discovery = RFC 6764/4791 handshake via
`propfind()`: `.well-known/caldav` → `current-user-principal` → `calendar-home-set` → list
calendars (or `MKCALENDAR` a dedicated "checkstu" calendar). A **`caldav_objects` mapping table**
(`task_id, uid, href, etag, content_hash`) is the backbone: `href`+`etag` enable targeted
update/delete; `content_hash` skips no-op PUTs. Create = `PUT … If-None-Match:*`; update =
`PUT href If-Match:{etag}` (on 412, checkstu wins → re-PUT); delete = `DELETE href` (404/410 =
success).

### 10.6 Triggering, failure, security
- **Event-driven** `SyncTaskToCalDav` queued job (own `caldav` queue, `WithoutOverlapping` keyed on
  task, ~5s debounce) + **nightly `ReconcileCalDavAccount`** safety net (ctag check → repair drift).
- **Retries** with backoff `[30,120,600,1800]s`; transient errors self-heal via reconciler; auth
  errors (401/403) stop + flag + auto-disable; server-unreachable loses nothing (checkstu is source
  of truth); idempotent via UID/href/If-Match.
- **Security:** app-specific passwords only, `encrypted` cast (APP_KEY), never logged; per-user
  opt-in (off by default); require HTTPS; disconnect bulk-deletes that account's objects.

### 10.7 Explicitly deferred: two-way sync
Reading phone-side edits back is a separate, much harder later project (conflict resolution,
`sync-collection` REPORT + sync-tokens per RFC 6578, inbound iCal parsing/trust, deletion
ambiguity). The v1 mapping table (UID + href + ETag + ctag) is **forward-compatible** with it, so
choosing one-way now costs nothing later.

### 10.8 Testing
Unit-test the **iCal mapper** hardest (fixtures → exact `sabre/vobject` output for RRULE, RDATE,
all-day vs timed, priority/categories). Mock the client for header/parse contracts. Integration
against a throwaway **Radicale/Baikal in Docker** in CI (discovery → create → update/ETag → 412 →
delete → reconcile), round-tripping objects back through `sabre/vobject`. One-time manual check on
a real iPhone + Android to confirm the VEVENT-default holds.

---

## 11. Notifications — phased/optional (NOT in v1)

> Confirmed: **no reminders in v1** — not even the in-app bell. This whole section is post-v1.

Laravel Notifications, multi-channel, introduced late:
- **A — `database`:** in-app "due/overdue" bell (v2). `tasks:remind` hourly command notifies
  assignees (or all members if unassigned). Cheap, no infra.
- **B — `mail`:** email digests via SMTP (Mailpit in dev).
- **C — web push (PWA):** `laravel-notification-channels/webpush` + VAPID + service worker.
  **Requires the PWA phase.** Real mobile value, meaningful setup — defer.

All reminders dispatched **queued** so the scheduler command returns fast.

---

## 12. Local dev & deployment

### 12.1 Local dev
- **Herd** (native macOS — user is on Darwin) over Sail: bundles PHP/Composer/Node, no Docker, and
  SQLite needs no service container.
- **`composer run dev`** runs `artisan serve` + `queue:listen` + `vite` concurrently.
- **Mailpit** for reminder emails; `php artisan schedule:work` to exercise the scheduler.

### 12.2 Bootstrap
```bash
laravel new checkstu --react --pest --database=sqlite --git
cd checkstu && npm install && npm run build && php artisan migrate
```
Then: enable SQLite WAL + FK + `busy_timeout` (config/`AppServiceProvider`); set
`APP_TIMEZONE=Europe/Berlin`; keep Pint/ESLint/Prettier in CI (`composer test`, `composer lint`).

### 12.3 Deployment — Docker (implemented)
**Dockerized** (adapted from a reference Filament/Postgres app → checkstu's SQLite/Inertia stack).
Target **one small VPS or home server, single SQLite file** — no k8s, no managed DB, no Redis.

- **Image:** `docker/prod/Dockerfile` — 3-stage build on `serversideup/php:8.5-fpm-nginx`
  (php-build = composer `--no-dev`; assets = Node/Vite client build; runtime = app + built assets,
  `view:cache`+`route:cache` baked in). `.dockerignore` keeps the context lean.
- **Stack:** `docker-compose.yml` — three containers off one image via `CONTAINER_ROLE`:
  **app** (nginx+php-fpm on 8080), **worker** (`queue:work`), **scheduler** (`schedule:work`).
  Worker/scheduler block on a `storage/.app-ready` flag until the app finishes migrating
  (`docker/prod/99-entrypoint.sh`). `database` queue/cache/session drivers (shared SQLite).
- **Persistence:** one named volume `checkstu-data:/var/www/html/storage`; the SQLite DB lives at
  `storage/database/database.sqlite` (so the volume never hides the `database/migrations` in the image).
- **HTTPS / internet:** put a TLS-terminating reverse proxy (Caddy / Traefik / Cloudflare Tunnel)
  in front, forwarding to `app:8080`. **Trusted proxies** configured in `bootstrap/app.php`
  (`TRUSTED_PROXIES`, default `*`) so `X-Forwarded-Proto: https` yields correct https URLs + secure
  cookies. Verified: forwarding `X-Forwarded-Proto: https` makes redirects come back `https://…`.
  Set `APP_URL=https://…`, `SESSION_SECURE_COOKIE=true`; expose the app **only** via the proxy.
- **First login:** `docker compose exec app php artisan checkstu:create-user` (interactive), or
  `RUN_SEEDER=true` on first boot for the demo family.
- **Entrypoint** runs `migrate --force` + `optimize` on the app container each boot.
- **SQLite backup (single file = SPOF):** **Litestream** streaming the volume's DB to S3/B2, or a
  nightly `sqlite3 .backup` copied off-box. WAL + busy_timeout (config) keep the three containers
  from colliding.

---

## 13. Phased roadmap

Ordering respects hard dependencies. The spine is **P0 → P1 → P2**; after P2, P3/P4 can run in
parallel. P6/P7 are v2.

| Phase | Deliverable | Depends on |
|---|---|---|
| ✅ **P0 — Bootstrap** | **Done.** Scaffolded via `create-project` (no installer); SQLite WAL/FK/busy_timeout; German locale; demo seeder. Actual stack Laravel 12 / Inertia 2 / PHPUnit (not 13/Pest). | — |
| ✅ **P1 — Core CRUD + Auth + Users** | **Done.** Username login; roles admin/member/**guest**; admin Users mgmt; policies; **complete-on-behalf**; one-off tasks + create/edit/delete/complete; task list. Extras: private tasks, unassigned "up-for-grabs". | P0 |
| 🔜 **P2 — Recurrence engine** (NEXT) | 4-type recurrence model; `recurr` RRULE; explicit dates; `MaterializeOccurrencesAction` + `tasks:materialize` (rolling horizon); generate-on-complete for `relative`; **template catalogue picker (§4.8)**; generation/idempotency/DST tests. *Schema columns already exist; only one-off wired.* | P1 |
| ✅ **P3 — Dependencies** | **Done in P1:** `task_dependencies`, `ResolveDependenciesAction`, actionable-now filtering, blocked UI (greyed + "Wartet auf…"). | P2 |
| ◑ **P4 — Filters / UX polish** | **Mostly done:** Meine/Alle filter, Upcoming agenda, detail/edit, swipe-to-complete, Family. *Remaining:* richer category/status filter bar. | P2, P3 |
| ⬜ **P5 — PWA** | Manifest, service worker, offline shell, installability, mobile hardening, logout cache-clear | P1 (best after P4) |
| ⬜ **P6 — Calendar sync (v2)** | One-way CalDAV push incl. **completion sync** (§10); `VTODO` for todo apps / `VEVENT` for calendars; per-user opt-in | P2 |
| ⬜ **P7 — Notifications** | 7a database reminders → 7b email → 7c web push | P2; 7c needs P5 |
| ✅ **Docker / deploy** | **Done** (§12.3): `docker/prod/Dockerfile` + `docker-compose.yml` (app+worker+scheduler), trusted proxies, `checkstu:create-user`. Built & run-verified. | P1 |

**v1 scope = P0–P5 (no reminders). v2 = P6 calendar sync (completion push) + all of P7 (notifications).**

**Carry-forward opinions:** (1) model the `rrule`/`explicit_dates`/`relative` split explicitly —
it's the highest-leverage decision and prevents the garbage-pickup regeneration bug. (2) Make
`tasks:materialize` idempotent via `unique(task_id, due_date)`. (3) No permissions package, no
repository layer, no SSR for v1. (4) Litestream is the one non-boring piece of "SQLite in
production."

---

## 14. Open decisions to confirm before P0

1. **Inertia 2 vs 3** — the only item left, and it needs no decision now: scaffold and take
   whatever the installer pins (§2).

_Decided:_ stack = Inertia + React; **one shared space (no multi-household), 4 admin-managed
accounts, roles admin/member**; login by **username** (email optional); **admins can complete tasks
on behalf of another user** (attribution, §6); no assignee rotation; shared catalogue of predefined
todo templates; **code English / UI German**, single locale `de`; timezone **Europe/Berlin**;
**no notifications in v1**; calendar sync = one-way CalDAV push with completion sync, deferred to v2.
