# Token-Authenticated API + Showcase Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the raw-JSON `/api/leads` nav link with a real Laravel Sanctum Bearer-token API and an on-brand "API Access" showcase page, including a fixed read-only token for the demo account.

**Architecture:** Install Sanctum and expose `GET /api/leads` under `routes/api.php` behind `auth:sanctum`, scoping results through `$request->user()->leads()` (not the `Auth::id()`-based global scope, which is a no-op under token auth). A web "API Access" page lets regular users generate/regenerate a personal token and shows a live `curl` example + pretty-printed sample response; the read-only demo account instead shows a fixed token seeded by `DatabaseSeeder`.

**Tech Stack:** Laravel 13, Laravel Sanctum 4, Blade, Tailwind (CDN, no build step), SQLite, PHPUnit feature tests.

**Run commands via WSL:** all `php`/`composer` commands run inside WSL Ubuntu, e.g.
`wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && <command>"`.

---

### Task 1: Install Sanctum, wire API routing, enable tokens on User

No test in this task — it is setup verified by a successful migration and a green existing suite.

**Files:**
- Modify: `composer.json` / `composer.lock` (via composer)
- Modify: `bootstrap/app.php:10-15` (add `api:` to `withRouting`)
- Modify: `app/Models/User.php:16-19` (add `HasApiTokens`)
- Create: `routes/api.php`
- Modify: `routes/web.php:35-38` (remove the inline `/api/leads` closure)

- [ ] **Step 1: Install Sanctum**

Run:
```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && composer require laravel/sanctum"
```
Expected: composer adds `laravel/sanctum` (v4.x) to `require`, updates `composer.lock`. Sanctum's `personal_access_tokens` migration auto-loads from the package (no publish needed).

- [ ] **Step 2: Add `HasApiTokens` to the User model**

In `app/Models/User.php`, add the import and trait:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
```

- [ ] **Step 3: Register API routing in bootstrap/app.php**

In `bootstrap/app.php`, update the `withRouting` call to add the `api:` line:

```php
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
```

- [ ] **Step 4: Create routes/api.php**

Create `routes/api.php` with exactly:

```php
<?php

use App\Http\Controllers\Api\LeadApiController;
use Illuminate\Support\Facades\Route;

// Token-authenticated, read-only leads API. Results are scoped to the
// authenticated token's owner inside the controller (see LeadApiController),
// because the model's global owner scope keys off the web-session guard and is
// a no-op under stateless Bearer-token auth.
Route::middleware('auth:sanctum')->get('/leads', [LeadApiController::class, 'index']);
```

- [ ] **Step 5: Remove the old inline closure from routes/web.php**

In `routes/web.php`, delete these lines (currently the last route in the auth group):

```php
    // --- THE API ENDPOINT ---
    Route::get('/api/leads', function () {
        return response()->json(Lead::query()->select(['id', 'name', 'status', 'insurance_type', 'lead_score'])->get());
    });
```

Also remove the now-unused `use App\Models\Lead;` import at the top of `routes/web.php` (the `LeadController` import stays).

- [ ] **Step 6: Run migrations to create personal_access_tokens**

Run:
```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && php artisan migrate"
```
Expected: migration `...create_personal_access_tokens_table` runs (DONE). (The controller it references is created in Task 2; the route file referencing it is fine as long as routes aren't cached — do not run `route:cache` here.)

- [ ] **Step 7: Commit**

```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && git add composer.json composer.lock app/Models/User.php bootstrap/app.php routes/api.php routes/web.php && git commit -m 'feat: install Sanctum and scaffold token API routing'"
```

---

### Task 2: Token-authenticated GET /api/leads endpoint

**Files:**
- Create: `app/Http/Controllers/Api/LeadApiController.php`
- Create: `tests/Feature/LeadApiTokenTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/LeadApiTokenTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_a_token(): void
    {
        $this->getJson('/api/leads')->assertUnauthorized();
    }

    public function test_valid_token_returns_the_owners_leads(): void
    {
        $user = User::factory()->create();
        Lead::factory()->forUser($user)->count(2)->create();

        $token = $user->createToken('api-access')->plainTextToken;

        $this->withToken($token)->getJson('/api/leads')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonStructure([['id', 'name', 'status', 'insurance_type', 'lead_score']]);
    }

    public function test_token_only_returns_its_owners_leads(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        Lead::factory()->forUser($alice)->count(2)->create();
        Lead::factory()->forUser($bob)->count(3)->create();

        $aliceToken = $alice->createToken('api-access')->plainTextToken;

        $this->withToken($aliceToken)->getJson('/api/leads')
            ->assertOk()
            ->assertJsonCount(2);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run:
```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && php artisan test --filter=LeadApiTokenTest"
```
Expected: FAIL — `Target class [App\Http\Controllers\Api\LeadApiController] does not exist` (the route references a controller not yet created).

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/Api/LeadApiController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadApiController extends Controller
{
    /**
     * Return the authenticated token owner's leads as JSON.
     *
     * Scoped via the relationship ($request->user()->leads()) rather than the
     * model's global owner scope: that scope keys off the web-session guard
     * (Auth::id()), which is null under stateless Bearer-token auth and would
     * otherwise leave the query unscoped.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->leads()
                ->select(['id', 'name', 'status', 'insurance_type', 'lead_score'])
                ->get()
        );
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run:
```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && php artisan test --filter=LeadApiTokenTest"
```
Expected: PASS (3 tests).

- [ ] **Step 5: Verify the existing ownership suite still passes**

Run:
```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && php artisan test --filter=LeadOwnershipTest"
```
Expected: PASS — `LeadOwnershipTest` hits `/api/leads` with session `actingAs`; Sanctum's guard falls back to the web session for first-party requests, so these stay green.

- [ ] **Step 6: Commit**

```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && git add app/Http/Controllers/Api/LeadApiController.php tests/Feature/LeadApiTokenTest.php && git commit -m 'feat: token-authenticated GET /api/leads endpoint'"
```

---

### Task 3: API Access page — controller + routes (generate/regenerate)

**Files:**
- Create: `app/Http/Controllers/ApiAccessController.php`
- Modify: `routes/web.php` (add two routes in the `auth` + `demo.readonly` group)
- Create: `resources/views/api/show.blade.php` (placeholder this task; full UI in Task 5)
- Modify: `tests/Feature/LeadApiTokenTest.php` (add web-side tests)

- [ ] **Step 1: Write the failing tests**

Append these methods inside the `LeadApiTokenTest` class in `tests/Feature/LeadApiTokenTest.php`:

```php
    public function test_api_access_page_renders_for_a_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/api-access')
            ->assertOk()
            ->assertSee('GET /api/leads');
    }

    public function test_user_can_generate_an_api_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/api-access/token')
            ->assertRedirect(route('api.show'))
            ->assertSessionHas('plain_text_token');

        $this->assertTrue(
            $user->fresh()->tokens()->where('name', 'api-access')->exists()
        );
    }

    public function test_regenerating_replaces_the_previous_token(): void
    {
        $user = User::factory()->create();
        $user->createToken('api-access');

        $this->actingAs($user)->post('/api-access/token')->assertRedirect();

        $this->assertSame(
            1,
            $user->fresh()->tokens()->where('name', 'api-access')->count()
        );
    }

    public function test_demo_account_cannot_generate_a_token(): void
    {
        $demo = User::factory()->create(['email' => config('demo.email')]);

        $this->actingAs($demo)->post('/api-access/token')->assertRedirect();

        $this->assertFalse(
            $demo->fresh()->tokens()->where('name', 'api-access')->exists()
        );
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run:
```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && php artisan test --filter=LeadApiTokenTest"
```
Expected: FAIL — the new tests fail with a 404 / missing route `api.show` (the earlier 3 tests still pass).

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/ApiAccessController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiAccessController extends Controller
{
    /**
     * Show the API Access page: the user's token (demo = fixed; regular user =
     * just-generated plaintext, or a regenerate prompt), a curl example, and a
     * live pretty-printed sample of their /api/leads response.
     */
    public function show(Request $request): View
    {
        $user = $request->user();

        if ($user->isDemo()) {
            $demoToken = $user->tokens()->where('name', 'api-access')->first();
            $displayToken = $demoToken
                ? $demoToken->id.'|'.config('demo.api_token')
                : null;
        } else {
            // Sanctum reveals the plaintext only at creation; flashed by regenerate().
            $displayToken = session('plain_text_token');
        }

        $hasToken = $user->tokens()->where('name', 'api-access')->exists();

        $sampleLeads = $user->leads()
            ->select(['id', 'name', 'status', 'insurance_type', 'lead_score'])
            ->get();

        return view('api.show', [
            'displayToken' => $displayToken,
            'hasToken' => $hasToken,
            'sampleLeads' => $sampleLeads,
            'appUrl' => rtrim(config('app.url'), '/'),
        ]);
    }

    /**
     * Generate (or regenerate) the user's single API token. Blocked for the
     * demo account by the demo.readonly middleware before reaching here.
     */
    public function regenerate(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->tokens()->where('name', 'api-access')->delete();
        $token = $user->createToken('api-access');

        return redirect()
            ->route('api.show')
            ->with('plain_text_token', $token->plainTextToken);
    }
}
```

- [ ] **Step 4: Add the routes**

In `routes/web.php`, inside the existing `Route::middleware(['auth', 'demo.readonly'])->group(...)` block, add (after the `/reporting` view route, alongside the other named routes):

```php
    // API Access showcase page + token generation (demo blocked from POST).
    Route::get('/api-access', [\App\Http\Controllers\ApiAccessController::class, 'show'])->name('api.show');
    Route::post('/api-access/token', [\App\Http\Controllers\ApiAccessController::class, 'regenerate'])->name('api.token.regenerate');
```

- [ ] **Step 5: Create a minimal placeholder view (real UI in Task 5)**

Create `resources/views/api/show.blade.php`:

```blade
@extends('layouts.app')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-white">API Access</h2>
    <p class="font-mono text-amber-400">GET /api/leads</p>
</div>
@endsection
```

- [ ] **Step 6: Run tests to verify they pass**

Run:
```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && php artisan test --filter=LeadApiTokenTest"
```
Expected: PASS (7 tests). Note `test_demo_account_cannot_generate_a_token` passes because `BlockDemoWrites` intercepts the POST (`back()` redirect) before the controller runs, so no token is created.

- [ ] **Step 7: Commit**

```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && git add app/Http/Controllers/ApiAccessController.php routes/web.php resources/views/api/show.blade.php tests/Feature/LeadApiTokenTest.php && git commit -m 'feat: API Access page controller, routes, and token generation'"
```

---

### Task 4: Demo fixed read-only token (config + seeder)

**Files:**
- Modify: `config/demo.php` (add `api_token`)
- Modify: `database/seeders/DatabaseSeeder.php` (seed the demo token, idempotent)
- Modify: `.env.production.example` (document `DEMO_API_TOKEN`)
- Modify: `tests/Feature/LeadApiTokenTest.php` (add the demo-token auth test)

- [ ] **Step 1: Write the failing test**

Append this method inside the `LeadApiTokenTest` class:

```php
    public function test_demo_seeded_token_authenticates_and_is_scoped(): void
    {
        $demo = User::factory()->create(['email' => config('demo.email')]);
        Lead::factory()->forUser($demo)->create();

        // Mirror exactly what DatabaseSeeder seeds for the demo account.
        $tokenModel = $demo->tokens()->create([
            'name' => 'api-access',
            'token' => hash('sha256', config('demo.api_token')),
            'abilities' => ['*'],
        ]);

        $plainText = $tokenModel->id.'|'.config('demo.api_token');

        $this->withToken($plainText)->getJson('/api/leads')
            ->assertOk()
            ->assertJsonCount(1);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && php artisan test --filter=test_demo_seeded_token_authenticates_and_is_scoped"
```
Expected: FAIL — `config('demo.api_token')` is `null`, so the hashed token is `hash('sha256', '')` and the assembled `plainText` won't match a usable token / the assertion fails.

- [ ] **Step 3: Add the config value**

In `config/demo.php`, add inside the returned array (after `'email' => ...,`):

```php
    /*
    |--------------------------------------------------------------------------
    | Demo API Token (secret half)
    |--------------------------------------------------------------------------
    |
    | The fixed, read-only Sanctum token secret shown on the API Access page
    | for the demo account, so any visitor can call GET /api/leads. The full
    | Bearer value is "{tokenId}|{this secret}", where tokenId is the seeded
    | personal_access_tokens row id. This grants read-only access to the demo
    | account's leads only.
    |
    */

    'api_token' => env('DEMO_API_TOKEN', 'demoReadOnlyApiToken000000000000000000000'),
```

- [ ] **Step 4: Seed the demo token idempotently**

In `database/seeders/DatabaseSeeder.php`, update `run()` so it seeds the token after provisioning leads:

```php
    public function run(): void
    {
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo Agent',
                'password' => Hash::make('password'),
            ],
        );

        if ($demoUser->leads()->doesntExist()) {
            DemoData::provisionLeadsFor($demoUser);
        }

        // Fixed, read-only API token so demo visitors can call GET /api/leads.
        if ($demoUser->tokens()->where('name', 'api-access')->doesntExist()) {
            $demoUser->tokens()->create([
                'name' => 'api-access',
                'token' => hash('sha256', config('demo.api_token')),
                'abilities' => ['*'],
            ]);
        }
    }
```

- [ ] **Step 5: Document the env var**

In `.env.production.example`, add near the other app settings:

```
# Fixed read-only API token (secret half) shown on the demo's API Access page
DEMO_API_TOKEN=demoReadOnlyApiToken000000000000000000000
```

- [ ] **Step 6: Run the test to verify it passes**

Run:
```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && php artisan test --filter=LeadApiTokenTest"
```
Expected: PASS (8 tests).

- [ ] **Step 7: Commit**

```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && git add config/demo.php database/seeders/DatabaseSeeder.php .env.production.example tests/Feature/LeadApiTokenTest.php && git commit -m 'feat: seed a fixed read-only API token for the demo account'"
```

---

### Task 5: Build the API Access page UI

Replaces the Task 3 placeholder with the full, on-brand page. Verified by extending the render test.

**Files:**
- Modify: `resources/views/api/show.blade.php` (full UI)
- Modify: `tests/Feature/LeadApiTokenTest.php` (strengthen the render assertions)

- [ ] **Step 1: Strengthen the render test**

Replace the `test_api_access_page_renders_for_a_user` method body in `LeadApiTokenTest` with:

```php
    public function test_api_access_page_renders_for_a_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/api-access')
            ->assertOk()
            ->assertSee('GET /api/leads')
            ->assertSee('Generate token');
    }
```

Add a demo-page test method:

```php
    public function test_api_access_page_shows_the_fixed_token_for_demo(): void
    {
        $demo = User::factory()->create(['email' => config('demo.email')]);
        $token = $demo->tokens()->create([
            'name' => 'api-access',
            'token' => hash('sha256', config('demo.api_token')),
            'abilities' => ['*'],
        ]);

        $this->actingAs($demo)->get('/api-access')
            ->assertOk()
            ->assertSee($token->id.'|'.config('demo.api_token'))
            ->assertDontSee('Generate token');
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run:
```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && php artisan test --filter=LeadApiTokenTest"
```
Expected: FAIL — the placeholder view does not contain "Generate token" or the demo token string.

- [ ] **Step 3: Build the full view**

Replace the entire contents of `resources/views/api/show.blade.php` with:

```blade
@extends('layouts.app')

@section('content')
@php($isDemo = auth()->user()->isDemo())
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white">📡 API Access</h2>
            <p class="text-xs text-gray-400">Programmatic, token-authenticated access to your pipeline data.</p>
        </div>
        <span class="px-3 py-1 text-xs font-mono font-semibold rounded-full bg-emerald-950 border border-emerald-800 text-emerald-400">Read-only · scoped to your account</span>
    </div>

    {{-- Endpoint --}}
    <div class="bg-gray-900 border border-gray-800 p-6 rounded-2xl">
        <div class="flex items-center space-x-3">
            <span class="px-2 py-0.5 text-xs font-mono font-bold rounded bg-blue-950 text-blue-400 border border-blue-900">GET</span>
            <code class="font-mono text-sm text-amber-400">{{ $appUrl }}/api/leads</code>
        </div>
        <p class="text-sm text-gray-400 mt-3">Returns your leads as JSON (<span class="font-mono text-xs">id, name, status, insurance_type, lead_score</span>), authenticated by a Bearer token and scoped to your account by the same rules as the rest of the app.</p>
    </div>

    {{-- Your token --}}
    <div class="bg-gray-900 border border-gray-800 p-6 rounded-2xl">
        <h3 class="text-sm uppercase font-bold tracking-wider text-gray-400 mb-4">Your token</h3>

        @if($displayToken)
            @unless($isDemo)
                <p class="text-xs text-amber-400 mb-2">Copy this now — for security it won't be shown in full again.</p>
            @endunless
            <div class="flex items-stretch gap-2">
                <code id="api-token" class="flex-1 bg-gray-950 border border-gray-800 rounded-lg px-3 py-2 text-xs text-emerald-300 font-mono break-all">{{ $displayToken }}</code>
                <button type="button" onclick="copyText('api-token', this)" class="shrink-0 bg-gray-800 hover:bg-gray-700 text-xs text-gray-200 px-3 rounded-lg transition">Copy</button>
            </div>
        @elseif($hasToken)
            <p class="text-sm text-gray-400 mb-4">A token already exists for your account. The secret is only shown once at creation — regenerate to get a new one (this invalidates the old token).</p>
        @else
            <p class="text-sm text-gray-400 mb-4">You don't have an API token yet. Generate one to start calling the API.</p>
        @endif

        @unless($isDemo)
            <form action="{{ route('api.token.regenerate') }}" method="POST" class="mt-4">
                @csrf
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-sm py-2 px-4 rounded-lg transition shadow-lg">
                    {{ $hasToken ? 'Regenerate token' : 'Generate token' }}
                </button>
            </form>
        @endunless
    </div>

    {{-- Try it --}}
    <div class="bg-gray-900 border border-gray-800 p-6 rounded-2xl">
        <h3 class="text-sm uppercase font-bold tracking-wider text-gray-400 mb-4">Try it</h3>
        @if($displayToken)
            <div class="flex items-stretch gap-2">
                <pre id="curl-cmd" class="flex-1 bg-gray-950 border border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-300 font-mono overflow-x-auto">curl -H "Authorization: Bearer {{ $displayToken }}" \
     {{ $appUrl }}/api/leads</pre>
                <button type="button" onclick="copyText('curl-cmd', this)" class="shrink-0 bg-gray-800 hover:bg-gray-700 text-xs text-gray-200 px-3 rounded-lg transition">Copy</button>
            </div>
        @else
            <p class="text-sm text-gray-500 font-mono">Generate a token above to get a ready-to-run curl command.</p>
        @endif
    </div>

    {{-- Live response --}}
    <div class="bg-gray-900 border border-gray-800 p-6 rounded-2xl">
        <h3 class="text-sm uppercase font-bold tracking-wider text-gray-400 mb-4">Live response <span class="text-gray-600 normal-case font-normal">— your data, right now</span></h3>
        <pre class="bg-gray-950 border border-gray-800 rounded-lg p-4 text-xs text-gray-300 font-mono overflow-x-auto max-h-96">{{ $sampleLeads->toJson(JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>

<script>
    function copyText(id, btn) {
        const el = document.getElementById(id);
        navigator.clipboard.writeText(el.innerText).then(() => {
            const original = btn.innerText;
            btn.innerText = 'Copied';
            setTimeout(() => { btn.innerText = original; }, 1500);
        });
    }
</script>
@endsection
```

- [ ] **Step 4: Run tests to verify they pass**

Run:
```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && php artisan test --filter=LeadApiTokenTest"
```
Expected: PASS (9 tests).

- [ ] **Step 5: Commit**

```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && git add resources/views/api/show.blade.php tests/Feature/LeadApiTokenTest.php && git commit -m 'feat: build the API Access showcase page UI'"
```

---

### Task 6: Swap the navigation link

**Files:**
- Modify: `resources/views/layouts/app.blade.php:41`

- [ ] **Step 1: Replace the raw-JSON nav link**

In `resources/views/layouts/app.blade.php`, replace line 41:

```html
            <a href="/api/leads" target="_blank" class="block px-4 py-2.5 rounded-xl hover:bg-gray-800 transition text-amber-400 font-mono text-xs">📡 GET /api/leads</a>
```

with:

```html
            <a href="{{ route('api.show') }}" class="block px-4 py-2.5 rounded-xl hover:bg-gray-800 transition">📡 API Access</a>
```

- [ ] **Step 2: Verify the full suite passes**

Run:
```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && php artisan test"
```
Expected: PASS — all tests green (existing suites + `LeadApiTokenTest`).

- [ ] **Step 3: Commit**

```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && git add resources/views/layouts/app.blade.php && git commit -m 'feat: link API Access page from the nav (replace raw-JSON link)'"
```

---

### Task 7: Update the README API docs

**Files:**
- Modify: `README.md` (lines ~65, ~150-152 — the "JSON API" feature bullet and the "API" section)

- [ ] **Step 1: Update the feature bullet (~line 65)**

Replace:
```markdown
- **JSON API** — `GET /api/leads` returns the authenticated user's leads, scoped automatically.
```
with:
```markdown
- **Token API** — `GET /api/leads` returns the authenticated user's leads as JSON, authenticated by a Sanctum Bearer token and scoped to that user. Manage your token on the in-app **API Access** page.
```

- [ ] **Step 2: Update the API section (~line 150-152)**

Replace the body of the `## API` section:
```markdown
`GET /api/leads` — returns the authenticated user's leads as JSON (id, name, status, insurance_type, lead_score). Subject to the same per-user scoping as the rest of the app.
```
with:
```markdown
`GET /api/leads` — returns the authenticated user's leads as JSON (id, name, status, insurance_type, lead_score).

Authenticated with a [Laravel Sanctum](https://laravel.com/docs/sanctum) personal access token. Generate yours on the in-app **API Access** page, then:

```bash
curl -H "Authorization: Bearer <your-token>" https://demo.simsdigitalpartners.com/api/leads
```

Results are scoped to the token owner by the relationship query in `LeadApiController` (the model's global owner scope keys off the web session and is intentionally bypassed for stateless token requests). The read-only demo account exposes a fixed token directly on its API Access page so visitors can try it.
```

- [ ] **Step 3: Commit**

```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && git add README.md && git commit -m 'docs: document the Sanctum token API'"
```

---

### Task 8: Full verification

- [ ] **Step 1: Run the entire test suite**

Run:
```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && php artisan test"
```
Expected: All green (DemoReadOnlyTest, LeadOwnershipTest, LeadApiTokenTest, and any others).

- [ ] **Step 2: Smoke-test the seeder locally (fresh DB)**

Run:
```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && php artisan migrate:fresh --seed && php artisan tinker --execute=\"echo App\\Models\\User::where('email','demo@example.com')->first()->tokens()->where('name','api-access')->count();\""
```
Expected: prints `1` — the demo token is seeded exactly once.

- [ ] **Step 3: Confirm no stray references to the old route remain**

Run:
```bash
wsl -d Ubuntu -- bash -lc "cd /home/cody/workspace/sandbox/simple-crm && grep -rn 'GET /api/leads\|/api/leads' resources/ routes/ | grep -v 'route('"
```
Expected: only the intentional `routes/api.php` definition and view/curl example references; no leftover nav closure.

---

## Notes for the implementer

- **Deployment is out of scope for this plan.** When ready, deploy follows the project's existing flow (push to `main`; on the `vps` host `git pull && docker compose up -d --build`; the demo token is created by the boot-time seeder). Confirm with the user before deploying.
- **Do not run `php artisan route:cache` / `config:cache`** during development — the dev flow relies on uncached routes/config.
- The demo token grants read-only access only (the API has no write routes, and `BlockDemoWrites` still guards every web write). Committing its default secret to the repo is intentional and safe for the throwaway demo account.
