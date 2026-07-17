# Part 2 — Issue Report (Wind4Life Laravel)

This is a review of the inherited codebase as if I now own it. Findings are grouped by
**Security**, **Performance**, **Bad Implementation / Correctness**, **Usability**, and
**Downsides / Trade-offs**. Every finding lists *what & where*, *why it matters*, and *the fix*.

Severity legend: 🔴 critical · 🟠 high · 🟡 medium · ⚪ low.

I verified each item by reading the source directly; file/line references are from the current tree.

---

## Security

### 🔴 S1 — IDOR: any authenticated user can read/modify any other user
**Where:** [`app/Http/Controllers/Api/UserController.php`](app/Http/Controllers/Api/UserController.php) `show()` (lines ~43-48) and `update()` (lines ~53-58); [`app/Http/Requests/UpdateUserRequest.php`](app/Http/Requests/UpdateUserRequest.php) `authorize()` returns `true`.

The class docblock claims *"Django filters the queryset to the authenticated user only"*. `index()` honours that (`whereKey($authUser->getKey())`), but `show()` and `update()` resolve the target purely from the `{username}` URL segment with no comparison to `$request->user()`:

```php
public function update(UpdateUserRequest $request, string $username): UserResource
{
    $user = User::query()->where('username', $username)->firstOrFail();
    $user->update($request->validated());  // no ownership check
    return new UserResource($user);
}
```

**Why it matters:** `PATCH /api/users/{anyone}` lets any logged-in user rename any other account; `GET /api/users/{anyone}` discloses arbitrary users. Classic Broken Object Level Authorization (OWASP API #1). The blast radius is currently limited by `UpdateUserRequest` only allowing `name`, but the authorization hole is the real defect and will widen the moment more fields are added.

**Fix:** Apply admin middleware to every route that can affect or inspect **another** user (`GET/PATCH /api/users/{username}`, and any future admin user listing). Keep self-service on `/users/me` (and the already-scoped `GET /api/users`).

That separation is enough: regular users never hit the `{username}` routes, so the IDOR disappears without a per-request ownership check. Example:

```php
Route::middleware('admin')->group(function () {
	Route::get('users/{username}', [UserController::class, 'show']);
	Route::patch('users/{username}', [UserController::class, 'update']);
});
```

Register the `admin` alias so it rejects non-`is_superuser` callers with `403` (see S2). Add a `PATCH /api/users/me` (or equivalent) if authenticated users still need to update their own `name`. Policies remain useful later for mixed “self or admin” endpoints, but they are not required once those routes are cleanly split.

---

### 🟠 S2 — Admin privilege fields exist but are never enforced
**Where:** [`app/Models/User.php`](app/Models/User.php) casts `is_staff` and `is_superuser`, and [`InitialUserSeeder`](database/seeders/InitialUserSeeder.php) sets both on the seeded admin. However, there is no admin middleware alias in [`bootstrap/app.php`](bootstrap/app.php), no `Gate`, no `Policy`, and no `can:` middleware on any route in [`routes/api.php`](routes/api.php).

**Why it matters:** The application records privileged roles but gives them no meaning. Every authenticated account receives the same access, and controllers have to remember ad-hoc ownership checks. That architectural gap caused S1: operations involving another user are reachable without proving either ownership or administrator status. It also means future admin endpoints may appear protected simply because they require Sanctum, when Sanctum proves identity only — not authority.

**Fix:** Add two complementary authorization layers:

1. Register an `admin` middleware alias that requires `is_superuser` (or a clearly defined role/permission), and apply it to route groups that are exclusively administrative.
2. Add policies for object-level rules. User operations should allow **self or admin**, for example:

```php
public function update(User $actor, User $target): bool
{
    return $actor->is($target) || $actor->is_superuser;
}
```

Use `$this->authorize('update', $target)` or `->middleware('can:update,user')` rather than duplicating conditions in controllers. Keep `/users/me` for normal self-service; move listing, viewing, or changing **other users** behind the admin/policy boundary. Add feature tests proving regular users receive `403` and administrators are allowed.

---

### 🟠 S3 — No rate limiting anywhere (brute-force on login)
**Where:** [`routes/api.php`](routes/api.php) — the only middleware is `auth:sanctum`; `POST /api/auth-token` is public and unthrottled. No `throttle` middleware appears anywhere in the app.

**Why it matters:** `POST /api/auth-token` can be hammered for password guessing / credential stuffing with no lockout. Combined with the seeded `admin`/`admin` (S8) this is a trivial takeover.

**Fix:** Add throttling, e.g. `Route::post('auth-token', ...)->middleware('throttle:5,1')` and a global `throttle:api` on the authenticated group. Consider per-username + per-IP limits.

---

### 🟠 S4 — Username enumeration + timing side-channel on login
**Where:** [`app/Http/Controllers/Api/AuthTokenController.php`](app/Http/Controllers/Api/AuthTokenController.php) lines ~35-41.

```php
$user = User::query()->where('username', $credentials['username'])->first();
if (! $user || ! Hash::check($credentials['password'], $user->password)) { ... }
```

When the username doesn't exist, `Hash::check()` is short-circuited, so the request returns measurably faster than for a real username with a wrong password. The error message is generic (good), but the timing difference leaks which usernames are valid.

**Why it matters:** Lets an attacker build a list of valid accounts to focus brute-force (feeds S3).

**Fix:** Always perform a hash comparison against a dummy hash when the user is missing, to equalise timing:
```php
$user = User::where('username', $credentials['username'])->first();
$hash = $user?->password ?? '$2y$12$'.str_repeat('.', 53); // fixed dummy
if (! Hash::check($credentials['password'], $hash) || ! $user) {
    throw ValidationException::withMessages(['username' => ['Invalid credentials.']]);
}
```

---

### 🟠 S5 — Sanctum tokens never expire
**Where:** [`config/sanctum.php`](config/sanctum.php) line ~51: `'expiration' => null`.

**Why it matters:** A leaked bearer token (log, browser history, git, proxy) is valid **forever**; there is no rotation or TTL. The comment justifies it as "DRF authtoken parity", but that is a security downgrade, not a requirement.

**Fix:** Set a sane TTL (e.g. `'expiration' => 60 * 24 * 7`), add token pruning (`sanctum:prune-expired`), and expose a logout/revoke endpoint (`$user->currentAccessToken()->delete()`), which the API currently lacks.

---

### 🟡 S6 — Wide-open CORS
**Where:** [`config/cors.php`](config/cors.php): `allowed_methods => ['*']`, `allowed_origins => ['*']`, `allowed_headers => ['*']`.

**Why it matters:** Any website can call the API from a browser. `supports_credentials` is `false` (so cookie theft is limited), but with token auth it still allows any origin to script authenticated calls if it obtains a token, and it is a poor production default.

**Fix:** Restrict `allowed_origins` to known front-end origins (env-driven), and narrow methods/headers to what's used.

---

### 🟡 S7 — Insecure default framework config in `.env.example`
**Where:** [`.env.example`](.env.example): `APP_DEBUG=true`, `LOG_LEVEL=debug`.

**Why it matters:** `.env.example` is the template most deployments copy. Shipping `APP_DEBUG=true` risks a production deploy that leaks stack traces, env values, and query contents via Ignition error pages. `debug` log level can persist sensitive data.

**Fix:** Default the example to `APP_ENV=production`-safe values or add a prominent comment; ensure the deploy pipeline sets `APP_DEBUG=false`, `LOG_LEVEL=warning`.

---

### 🟡 S8 — Seeded `admin` / `admin` superuser
**Where:** [`database/seeders/InitialUserSeeder.php`](database/seeders/InitialUserSeeder.php) line ~25 (`Hash::make('admin')`), wired into the default `db:seed` via [`DatabaseSeeder.php`](database/seeders/DatabaseSeeder.php).

**Why it matters:** Every environment that runs the default seeder gets a superuser with a guessable password. If a non-local environment is ever seeded, it's an instant full compromise (and there's no rate limiting to slow the guess).

**Fix:** Generate a random password and print it once, or require the credentials from env and refuse to seed the admin outside `local`/`testing`.

---

### ⚪ S9 — Every FormRequest `authorize()` returns `true`
**Where:** all files in [`app/Http/Requests/`](app/Http/Requests/).

**Why it matters:** Authorization is entirely absent from the request layer; the app relies solely on "is authenticated". There is no per-object policy anywhere (no `Policy` classes, no `Gate`). Fine for genuinely shared data, but it means S1 is not an isolated slip — there is no authorization backbone to catch the next one.

**Fix:** Introduce Policies for user-scoped resources and move ownership checks into `authorize()`.

---

## Performance

### 🟠 P1 — N+1 on `GET /api/readings`
**Where:** [`app/Http/Controllers/Api/ReadingController.php`](app/Http/Controllers/Api/ReadingController.php) `index()` (line ~38) builds `Reading::query()` **without** `->with('tags')`; [`app/Http/Resources/ReadingResource.php`](app/Http/Resources/ReadingResource.php) line ~24 falls back to `$this->tags()->pluck('name')` per row.

**Why it matters:** A page of N readings issues 1 + N queries for tags. Under load this multiplies DB round-trips linearly with page size. (Note the nested `AnemometerReadingController::index` *does* eager-load — so this is an inconsistent, easy-to-miss omission.)

**Fix:** `Reading::query()->with('tags')` before pagination.

---

### 🟠 P2 — `recentReadings` runs a query per anemometer (N+1)
**Where:** [`app/Http/Controllers/Api/AnemometerController.php`](app/Http/Controllers/Api/AnemometerController.php) `recentReadings()` lines ~116-125:

```php
$anemometers->getCollection()->transform(function (Anemometer $a): Anemometer {
    $recent = $a->readings()->withoutGlobalScopes()->with('tags')
        ->orderByDesc('recorded_at')->limit(5)->get();   // one query per anemometer
    $a->setAttribute('recent_readings', $recent);
    return $a;
});
```

**Why it matters:** For a default page of anemometers this is one extra query each (plus the two correlated `selectSub` averages already per row). It's the "5 latest readings" feature from the README, so it's on a hot path.

**Fix:** Load the latest readings in bulk — e.g. a windowed query or the `staudenmeir/eloquent-eager-limit` pattern, or fetch the union of latest-5-per-group in a single query and group in PHP.

---

### 🟠 P3 — `tags_exact` filter materialises the whole candidate set in PHP
**Where:** [`app/Http/Filters/ReadingFilter.php`](app/Http/Filters/ReadingFilter.php) `filterTagsExact()` lines ~67-96.

It SQL-prunes to readings carrying *any* requested tag, `->get()`s them **all** with tags eager-loaded, does set-equality in PHP, then rebuilds `Reading::whereIn('id', $matchingIds)`.

**Why it matters:** Memory and time scale with the number of readings that carry a popular tag — potentially the whole table for a common tag. It also throws away pagination benefits (loads everything, then re-queries). On a large dataset this can OOM.

**Fix:** Do it in one SQL pass, e.g. group by reading, `HAVING COUNT(DISTINCT tag) = :n` over the requested set combined with a NOT-EXISTS for extra tags — no PHP-side full load. The existing comment cites `ONLY_FULL_GROUP_BY` worries; that's solvable without loading the table.

---

### 🟡 P4 — Global ordering scope injects filesorts everywhere
**Where:** [`app/Models/Reading.php`](app/Models/Reading.php) `booted()` lines ~52-54 adds a permanent `orderBy('recorded_at','desc')` global scope.

**Why it matters:** The ordering is applied to *every* Reading query — including `whereHas`/`distinct` subqueries, aggregate/`exists` checks, and eager-load loads where ordering is useless overhead (extra filesort). It's also the reason `tags_exact`'s unit test had to work around `ONLY_FULL_GROUP_BY`. Global scopes that add ORDER BY are a known foot-gun.

**Fix:** Replace the global scope with an explicit local scope (`scopeOrderByRecorded`, which already exists) applied only where the ordering is actually wanted (list endpoints).

---

### 🟡 P5 — Unbounded eager load on anemometer detail
**Where:** [`app/Http/Controllers/Api/AnemometerController.php`](app/Http/Controllers/Api/AnemometerController.php) `show()` uses `Anemometer::with('readings.tags')`; [`AnemometerDetailResource`](app/Http/Resources/AnemometerDetailResource.php) serialises **all** readings.

**Why it matters:** With the seeder producing 100 readings each (and real deployments far more), `GET /api/anemometers/{id}` loads and serialises every reading + tags for that anemometer in one response — a large, slow, memory-heavy payload with no pagination.

**Fix:** Paginate or cap readings in the detail view, or drop them from detail and rely on the dedicated nested `/anemometers/{id}/readings` endpoint.

---

### 🟠 P6 — Missing index for global reading dates and newest-first ordering
**Where:** [`2025_01_01_000020_create_readings_table.php`](database/migrations/2025_01_01_000020_create_readings_table.php) only defines `(anemometer_id, recorded_at)`. Global list/export queries filter or order by `recorded_at` without constraining the leading `anemometer_id`.

**Evidence:** Against the seeded MySQL database (5,000 readings), `EXPLAIN` reports:

```text
SELECT * FROM readings ORDER BY recorded_at DESC LIMIT 15
type=ALL, key=NULL, rows=4879, Extra=Using filesort

SELECT * FROM readings
WHERE recorded_at >= NOW() - INTERVAL 7 DAY
ORDER BY recorded_at DESC
type=ALL, key=NULL, rows=4879, Extra=Using where; Using filesort
```

MySQL cannot efficiently use the second column of a composite B-tree without constraining its leftmost column. By contrast, the existing index is correctly used for an anemometer-scoped range (`type=range`, `Using index condition; Backward index scan`), so it should be retained.

**Why it matters:** `GET /api/readings`, global date-filtered exports, and any global “recent readings” operation scan and sort the whole table. Cost grows directly with reading volume.

**Fix:** Add a standalone index on `recorded_at`. Also make ordering deterministic by adding `id` as a tie-breaker at query level:

```php
$table->index('recorded_at');
// Query: ORDER BY recorded_at DESC, id DESC
```

Verify with `EXPLAIN ANALYZE` after migration. Do not remove `(anemometer_id, recorded_at)` because it serves a different left-prefix query pattern.

---

### 🟡 P7 — Anemometer name listing always filesorts; partial search cannot use a normal index
**Where:** [`2025_01_01_000010_create_anemometers_table.php`](database/migrations/2025_01_01_000010_create_anemometers_table.php) has no index on `name`; [`AnemometerExportController`](app/Http/Controllers/Api/AnemometerExportController.php) orders by name and uses `AllowedFilter::partial('name')`.

**Evidence:** Both `ORDER BY name` and `WHERE name LIKE '%meter%' ORDER BY name` produce `type=ALL`, `key=NULL`, `Using filesort` in MySQL.

**Why it matters:** It is negligible at the current 50 rows, but becomes a full scan + sort as the anemometer catalogue grows.

**Fix:** Decide the required search semantics:
- If prefix search is acceptable, index `name` and use `name LIKE 'term%'` / Spatie's begins-with strict filter. The index can serve filtering and ordering.
- If true contains search is required, a B-tree will not fix `%term%`; use a MySQL FULLTEXT index (with its tokenisation limitations) or a search service.

Adding an index while retaining a leading-wildcard predicate would create write/storage cost without fixing that filter.

---

### 🟡 P8 — Polymorphic pivot primary-key order is suboptimal for tag filters
**Where:** [`2025_01_01_000040_create_taggables_table.php`](database/migrations/2025_01_01_000040_create_taggables_table.php) defines:

```php
$table->primary(['tag_id', 'taggable_id', 'taggable_type']);
$table->index(['taggable_id', 'taggable_type']);
```

Tag filtering starts from the unique `tags.name` index, resolves `tag_id`, and then constrains `taggable_type`. Because `taggable_type` is third—after `taggable_id`—the primary key cannot use it as part of that range lookup.

**Evidence:** The tag-filter plan uses the primary key only on `tag_id`, estimates roughly 491 pivot rows per matched tag, then applies `taggable_type` as a filter; the outer materialised semi-join uses a temporary table and filesort.

**Why it matters:** As more taggable model types or rows are introduced, every tag lookup scans pivot entries belonging to irrelevant types.

**Fix:** For the polymorphic design, order the unique key as `(tag_id, taggable_type, taggable_id)` and retain the reverse lookup index `(taggable_id, taggable_type)` for loading a reading's tags. Confirm the new plan before replacing the existing primary key.

---

### ⚪ P9 — Text UUIDs make high-volume indexes and joins unnecessarily large
**Where:** UUID primary/foreign keys on `readings`, `anemometers`, `tags`, and `taggables` are stored as text via `$table->uuid()`.

**Evidence:** MySQL reports `key_len=144` bytes for a UUID lookup and `148` bytes for `(anemometer_id, recorded_at)`, because `CHAR(36)` under `utf8mb4` is much wider than the underlying 128-bit value. The pivot repeats UUIDs across multiple indexes.

**Why it matters:** Larger B-trees consume more disk and buffer-pool memory and reduce the number of keys per index page. This matters most for `readings` and `taggables`, the two fastest-growing tables. Laravel's ordered UUID generation helps insertion locality but not key width.

**Fix:** For a new/high-scale design, store UUIDs as `BINARY(16)` with model casts, or use a compact ordered identifier strategy. This is a disruptive migration and not justified for the exercise's 5,000 rows; treat it as a scale-triggered architectural decision, not an immediate patch.

---

## Bad Implementation / Correctness

### 🔴 B1 — `Reading` silently discards the user-supplied `recorded_at` on create
**Where:** [`app/Models/Reading.php`](app/Models/Reading.php) `booted()` lines ~56-58:

```php
static::creating(function (Reading $reading): void {
    $reading->recorded_at = now();   // overwrites whatever was set
});
```

Meanwhile [`StoreReadingRequest`](app/Http/Requests/StoreReadingRequest.php) **requires** `recorded_at` (`['required', 'date']`) and the controller mass-assigns it — then the model throws it away.

**Why it matters:** This is the most damaging bug in the codebase:
- `POST /api/readings` accepts and validates a timestamp, then stores `now()` instead — silent data corruption. The client is told nothing.
- The demo/factory data is affected: `ReadingFactory` sets `recorded_at` to a random time in the last 7 days, but the hook rewrites every row to creation time. So all seeded readings cluster at seed time.
- Downstream, this **collapses the daily vs weekly averages** in the anemometer export / `recent-readings`: since every reading's `recorded_at` is "now", the 24h window and 7d window contain the same rows, so `average_daily_speed == average_weekly_speed` (exactly the symptom you'd see in the export output).
- The existing test `test_creates_a_reading_with_tags_and_persists_it` sends `recorded_at` but never asserts it, so the bug slips through CI.

**Fix:** Only stamp a default when none was provided, mirroring Django's `auto_now_add` semantics for *new* rows without corrupting explicit input:
```php
static::creating(function (Reading $reading): void {
    $reading->recorded_at ??= now();
});
```
And add a test asserting the persisted `recorded_at` equals the submitted value.

---

### 🟡 B2 — Negative wind speed accepted
**Where:** [`StoreReadingRequest`](app/Http/Requests/StoreReadingRequest.php) (`'speed' => ['required','numeric']`) and [`UpdateReadingRequest`](app/Http/Requests/UpdateReadingRequest.php) (`['sometimes','required','numeric']`).

**Why it matters:** Wind speed in knots can't be negative; `numeric` allows `-50`. Garbage in skews every average/aggregate.

**Fix:** Add `'min:0'` (and a sane upper bound / `decimal` limit) to both requests.

---

### 🟡 B3 — Inconsistent `anemometer` → `anemometer_id` mapping between requests
**Where:** [`StoreReadingRequest::validated()`](app/Http/Requests/StoreReadingRequest.php) remaps the key inside `validated()`, whereas [`UpdateReadingRequest::prepareForValidation()`](app/Http/Requests/UpdateReadingRequest.php) remaps it before validation.

**Why it matters:** Two different mechanisms for the same concern make the code harder to reason about and easy to break; the `validated()` override is a subtle place to hide logic and can surprise future maintainers (e.g. it changes what `validated('anemometer')` returns).

**Fix:** Standardise on `prepareForValidation()` in both, and validate `anemometer_id` consistently.

---

### ⚪ B4 — `float` column for `speed`
**Where:** [`2025_01_01_000020_create_readings_table.php`](database/migrations/2025_01_01_000020_create_readings_table.php) line ~20 (`$table->float('speed')`).

**Why it matters:** Binary floats accumulate rounding error in `AVG()`/`SUM()` aggregates (you can see long noisy decimals like `530.8158899999999` in exports). For measurement data `decimal(6,2)` is usually preferable.

**Fix:** Migrate to `decimal` with an appropriate precision if exactness matters; otherwise round in the resource/DTO for presentation.

---

### 🟠 B5 — Deleting readings/anemometers leaves orphaned `taggables`
**Where:** [`2025_01_01_000040_create_taggables_table.php`](database/migrations/2025_01_01_000040_create_taggables_table.php) can foreign-key `tag_id`, but the polymorphic `taggable_id` has no foreign key to `readings`. [`ReadingController::destroy()`](app/Http/Controllers/Api/ReadingController.php) deletes the reading without detaching tags. Deleting an anemometer cascades readings at the database level, which also bypasses Eloquent model deletion events for those readings.

**Why it matters:** Pivot rows survive after their reading is gone. They permanently inflate `taggables`, degrade tag-filter scans and counts, and represent referential-integrity corruption. A `Reading::deleting` hook alone is insufficient because database-level cascade deletion from `anemometers` does not fire Eloquent events for each reading.

**Fix:** Since only readings are taggable today, prefer a dedicated `reading_tag` pivot with real foreign keys to both `readings` and `tags`, each using `cascadeOnDelete()`. If polymorphism is a hard requirement, explicitly delete matching pivot rows in the same transaction for both reading and anemometer deletion paths, cover bulk/raw deletes, and add an orphan-cleanup migration/job.

---

## Usability

### 🟡 U1 — Nested readings list returns `200 []` for a nonexistent anemometer
**Where:** [`AnemometerReadingController::index()`](app/Http/Controllers/Api/AnemometerReadingController.php) filters `where('anemometer_id', $anemometerId)` with no existence check, so an unknown/soft-typo anemometer id yields an empty success rather than `404`.

**Why it matters:** Callers can't distinguish "anemometer exists but has no readings" from "anemometer doesn't exist", which hides client bugs. `show()` correctly 404s via `firstOrFail`, so the two are inconsistent.

**Fix:** `Anemometer::findOrFail($anemometerId)` first (or route-model binding), then list.

---

### 🟡 U2 — Validated input silently ignored (see B1) gives a misleading contract
**Where:** `POST /api/readings` + `recorded_at`.

**Why it matters:** From the caller's perspective the API "accepts" `recorded_at` (it's required and validated) but the stored value never reflects it. Silent divergence between the documented/validated contract and actual behaviour is a serious DX/usability trap. (Fix is B1.)

---

### ⚪ U3 — No logout / token-revocation endpoint
**Where:** [`routes/api.php`](routes/api.php).

**Why it matters:** Tokens never expire (S5) and there is no way to revoke one, so a user who suspects compromise has no self-service remedy.

**Fix:** Add `DELETE /api/auth-token` that deletes the current (or all) tokens.

---

## Downsides / Trade-offs

### 🟡 D1 — Outdated / vulnerable dependencies
- **`laravel/framework`**: `composer install` reports 3 advisories against the pinned framework version (a CRLF/`Request::getHost` class of issue and a signed-URL issue). **Fix:** `composer update laravel/framework` to the patched 11.x.

### ⚪ D2 — No tenancy/ownership model on domain data
**Where:** `anemometers` / `readings` have no `user_id`; any authenticated user sees and edits everything.

**Why it matters:** May be intentional (shared global wind data), but it's worth an explicit decision — if multi-tenant use is ever expected, this is a costly retrofit. Calling it out so it's a conscious choice, not an accident.

---

## Suggested priority order
1. **B1** (data corruption on every reading create) — fix immediately; add regression test.
2. **S1/S2** (IDOR and missing admin/ownership authorization), then **S3/S4** (login brute-force + enumeration).
3. **S5/S8** (token TTL + seeded admin), **D1** (framework advisories).
4. **B5** (orphaned tag pivots), **P1/P2/P3** (N+1s and the `tags_exact` full-table load), and **P6** (global reading date index).
5. Everything else as hygiene.

## Notes on method
I read the controllers, models, requests, resources, filters, migrations, seeders, and framework
config directly rather than relying on grep alone, and traced the impact of B1 through the create
path, the seeders, and the export averages to confirm the downstream symptom. Database findings
were also checked against live MySQL `SHOW INDEX` and `EXPLAIN` plans on the seeded dataset.

I don't assume this is the complete set of planted issues; it's what I can defend with evidence.
