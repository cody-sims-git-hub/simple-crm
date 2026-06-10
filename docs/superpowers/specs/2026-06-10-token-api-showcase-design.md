# Token-Authenticated API + Showcase Page — Design

**Date:** 2026-06-10
**Status:** Approved

## Problem

The app currently exposes a `GET /api/leads` endpoint as an inline closure in
`routes/web.php`, surfaced through a raw-JSON link in the primary navigation
(`📡 GET /api/leads`, opens a new tab). It is authenticated only by the web
session cookie, so it is not callable as a real API — and dumping unstyled JSON
into the main nav reads as unpolished.

This is a portfolio/demo project. The goal is to turn the half-feature into a
**genuinely callable, token-authenticated API** with a polished in-app
showcase page — something a technical reviewer can copy a `curl` command for and
run from their own terminal, demonstrating real API engineering and that the
multi-tenant scoping holds on the API surface too.

## Goals

- A real, stateless, Bearer-token-authenticated `GET /api/leads` endpoint.
- A styled, on-brand "API Access" page showing the endpoint, the user's token,
  a ready-to-run `curl` example, and a live pretty-printed sample response.
- The shared read-only demo account exposes a fixed, working, read-only token
  so any visitor can try the API without being able to mutate data.
- Per-user scoping proven on the API by tests, including cross-user isolation.

## Non-Goals (YAGNI)

- Multiple named tokens per user.
- Token expiry, abilities/scopes management UI.
- Write endpoints (POST/PUT/DELETE) on the API.
- Pagination / filtering on the API.

One read-only token per user, one read-only GET endpoint.

## Critical Design Constraint: scoping under token auth

`App\Models\Lead` enforces multi-tenancy with a global `owner` scope that keys
off `Auth::id()` — the **default (`web`) guard**:

```php
static::addGlobalScope('owner', function (Builder $query) {
    if (Auth::check()) {
        $query->where($query->getModel()->qualifyColumn('user_id'), Auth::id());
    }
});
```

Under a pure Bearer-token request there is **no web session**, so
`Auth::check()` is false and `Auth::id()` is null → the global scope becomes a
**no-op** → an unscoped `Lead::query()` would return **every user's leads**.

**Resolution:** the API controller MUST scope through the authenticated user's
relationship rather than relying on the global scope:

```php
$request->user()->leads()->select([...])->get();
```

The relationship constraint (`where user_id = <token owner>`) is independent of
the global scope and is always applied. A cross-user isolation test guards this.

## Architecture / Components

### 1. Laravel Sanctum

- Add `laravel/sanctum` (composer).
- Publish/commit the `personal_access_tokens` migration.
- Add `Laravel\Sanctum\HasApiTokens` trait to `App\Models\User`.

### 2. API routing

- Wire `routes/api.php` into `bootstrap/app.php`:
  ```php
  ->withRouting(
      web: __DIR__.'/../routes/web.php',
      api: __DIR__.'/../routes/api.php',
      commands: __DIR__.'/../routes/console.php',
      health: '/up',
  )
  ```
  This auto-applies the `/api` prefix and the `api` middleware group. The
  existing `shouldRenderJsonWhen(api/*)` exception rendering already complements
  this.
- `routes/api.php`:
  ```php
  Route::middleware('auth:sanctum')->get('/leads', [LeadApiController::class, 'index']);
  ```
  Final URL: `GET /api/leads` (unchanged from today).
- Remove the inline `GET /api/leads` closure from `routes/web.php`.

### 3. API controller

`App\Http\Controllers\Api\LeadApiController@index`:

```php
public function index(Request $request)
{
    return response()->json(
        $request->user()->leads()
            ->select(['id', 'name', 'status', 'insurance_type', 'lead_score'])
            ->get()
    );
}
```

Same JSON shape as today (id, name, status, insurance_type, lead_score), scoped
via the relationship per the constraint above.

### 4. Token management (web side)

A new "API Access" page and supporting actions, in the existing
`auth` + `demo.readonly` route group in `routes/web.php`:

- `GET  /api-access`            → `ApiAccessController@show`  (name `api.show`)
- `POST /api-access/token`      → `ApiAccessController@regenerate` (name `api.token.regenerate`)

`regenerate` deletes the user's existing tokens, creates a fresh one, flashes
the **plaintext** to the session (Sanctum reveals it only at creation), and
redirects back. Because this is a POST inside the `demo.readonly` group, the
demo account is blocked from it by `BlockDemoWrites` — which is the desired
carve-out (the demo never needs to generate).

Token name: `"api-access"`. Token prefix for display: Sanctum's default plaintext
form (`<id>|<40-char>`); we display it verbatim with a copy button. (We will not
customize the token prefix; "crm_…" in the mockup was illustrative.)

### 5. Demo fixed token (carve-out)

- Add `'api_token'` to `config/demo.php`, sourced from `env('DEMO_API_TOKEN')`
  with a committed default for the demo deployment.
- In `DatabaseSeeder`, after ensuring the demo user exists, **idempotently**
  seed one `personal_access_tokens` row (name `api-access`) for the demo user
  whose `token` column is `hash('sha256', <config plaintext>)`, matching how
  Sanctum hashes and looks up tokens. Seeder is idempotent (skip if the demo
  user already has an `api-access` token) since it runs on every deploy.
- **Reconstructing the displayable token at render time:** a Sanctum Bearer
  token's plaintext form is `"{tokenId}|{plaintext}"`. The `tokenId` is the
  auto-assigned `personal_access_tokens.id`, not known until the row exists, so
  the page does **not** store it in config. Instead, the page looks up the demo
  user's `api-access` token row, reads its `id`, and renders
  `"{$token->id}|".config('demo.api_token')`. `config('demo.api_token')` holds
  only the secret half. This is the value shown in the token card and the `curl`
  example for the demo account, and it authenticates because its `sha256` matches
  the seeded `token` column.
- The page detects the demo user (`$user->isDemo()`) and always renders this
  fixed token, rather than the generate/regenerate flow.

### 6. The API Access page (Blade)

`resources/views/api/show.blade.php`, on-brand with the existing dark/monospace
theme (gray-900/950 cards, rounded-2xl, amber/emerald/blue accents). Sections:

1. **Header** — `📡 API Access`, with a "Read-only · scoped to your account" badge.
2. **Endpoint** — `GET /api/leads` + one-line description of token auth + scoping.
3. **Your token** — a card showing the token with a **Copy** button.
   - Demo: the fixed token (always shown).
   - Regular user, just generated: the flashed plaintext (shown once).
   - Regular user, token already exists: a masked indicator + **Regenerate**.
   - Regular user, no token yet: a **Generate token** button.
4. **Try it** — a ready-to-run `curl -H "Authorization: Bearer <token>" <APP_URL>/api/leads`
   with a Copy button. (Only rendered with a real token when one is available to
   display; otherwise prompts to generate.)
5. **Live response** — pretty-printed (`JSON_PRETTY_PRINT`), syntax-styled sample
   of the current user's actual `/api/leads` payload, rendered server-side.

Copy buttons: a tiny inline vanilla-JS `navigator.clipboard.writeText` handler,
consistent with the existing no-build-step Blade approach (cf. the
`toggleEditDrawer` script in `leads/index.blade.php`).

### 7. Navigation

In `resources/views/layouts/app.blade.php`, replace:
```html
<a href="/api/leads" target="_blank" class="… text-amber-400 font-mono text-xs">📡 GET /api/leads</a>
```
with a normal in-app nav item:
```html
<a href="{{ route('api.show') }}" class="…">📡 API Access</a>
```

## Data Flow

**External consumer:**
`curl -H "Authorization: Bearer <token>" /api/leads`
→ `auth:sanctum` resolves the token → token owner set as `$request->user()`
→ `LeadApiController@index` queries `$request->user()->leads()`
→ JSON array of that user's leads.

**In-app page:**
`GET /api-access` → `ApiAccessController@show` → renders token (demo fixed /
flashed / masked / generate prompt) + a server-side live sample of the user's
leads.

**Token generation (regular user):**
`POST /api-access/token` → delete old tokens, `createToken('api-access')`,
flash plaintext, redirect to `api.show`. (Demo blocked by `BlockDemoWrites`.)

## Error Handling

- Missing/invalid Bearer token on `/api/leads` → `401 Unauthorized` (Sanctum),
  rendered as JSON via the existing `shouldRenderJsonWhen(api/*)` config.
- Demo account POSTing to `/api-access/token` → `403` / redirect-back with the
  existing read-only message (handled by `BlockDemoWrites`, no new code).

## Testing

New `tests/Feature/LeadApiTokenTest.php` (or extend existing API coverage):

- `GET /api/leads` **without** a token → `401`.
- `GET /api/leads` **with** a valid token → `200`, correct JSON shape.
- **Cross-user isolation:** a token minted for Alice returns only Alice's leads,
  never Bob's (directly guards the scoping constraint).
- Demo fixed token authenticates and returns the demo user's leads.
- Demo account is blocked from `POST /api-access/token` (`403`/redirect).
- Regular user can generate a token via `POST /api-access/token`.

Existing `tests/Feature/LeadOwnershipTest.php` (uses session `actingAs` against
`/api/leads`) must keep passing: Sanctum's guard falls back to the web session
for first-party requests, so `actingAs($user)` still satisfies `auth:sanctum`.

## Docs

Update the README "API" / "JSON API" sections to document Bearer-token usage and
the per-user scoping, replacing the implication that it is a session-only view.

## Affected / New Files

- `composer.json` / `composer.lock` — add Sanctum.
- `bootstrap/app.php` — register `api:` routing.
- `app/Models/User.php` — `HasApiTokens`.
- `routes/api.php` — **new**, the token-auth endpoint.
- `routes/web.php` — remove the closure; add `api.show` + `api.token.regenerate`.
- `app/Http/Controllers/Api/LeadApiController.php` — **new**.
- `app/Http/Controllers/ApiAccessController.php` — **new**.
- `database/migrations/*_create_personal_access_tokens_table.php` — **new** (Sanctum).
- `config/demo.php` — add `api_token`.
- `database/seeders/DatabaseSeeder.php` — seed the demo fixed token (idempotent).
- `resources/views/api/show.blade.php` — **new**.
- `resources/views/layouts/app.blade.php` — swap the nav link.
- `tests/Feature/LeadApiTokenTest.php` — **new**.
- `README.md` — update API docs.
