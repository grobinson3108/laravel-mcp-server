# Architecture

A short technical tour of how `laravel-mcp-server` works under the hood.

## The two-layer design

```
┌───────────────────────────────────────────────────┐
│  Your code                                        │
│  ┌──────────────────────────────┐                 │
│  │ class MyMcpServer extends    │  registers      │
│  │   AbstractAuthenticatedMcpServer  ─────┐        │
│  └──────────────────────────────┘        ▼        │
├──────────────────────────────────────── │ ────────┤
│  This package                          │          │
│  ┌──────────────────────┐  ┌──────────────────┐  │
│  │ McpServerKernel      │  │ Abstract         │  │
│  │  ─ bootstrap Laravel │  │ AuthenticatedMcp │  │
│  │  ─ resolve Sanctum   │  │ Server           │  │
│  │  ─ inject user       │  │  ─ enforce auth  │  │
│  └──────────┬───────────┘  └────────┬─────────┘  │
│             │                       │             │
├─────────────┼───────────────────────┼─────────────┤
│  Upstream   │                       │             │
│             ▼                       ▼             │
│  ┌─────────────────┐   ┌──────────────────────┐  │
│  │ Laravel kernel  │   │ logiscape/mcp-sdk-php│  │
│  │  + Sanctum      │   │  (stdio JSON-RPC)    │  │
│  └─────────────────┘   └──────────────────────┘  │
└───────────────────────────────────────────────────┘
```

`McpServerKernel` is the **boot layer**. It does three things:

1. Bootstraps the host Laravel app (composer autoload + `bootstrap/app.php`)
2. Resolves a Sanctum token from `MCP_API_TOKEN`
3. Calls `auth()->setUser($user)` so the rest of the process is identity-aware

`AbstractAuthenticatedMcpServer` is the **server layer**. It owns a `Mcp\Server\McpServer` instance and exposes it to your subclass through `$this->server`.

## Why two classes instead of one

The split exists so that the entry-point script (`server.php`) can keep its single responsibility: bootstrap and authenticate. Server classes stay focused on registering tools / resources / prompts.

It also means tests can instantiate a server class directly with a fake `Authenticatable`, without booting Laravel.

## Identity flow

```
Claude Desktop
      │
      │  spawns process with MCP_API_TOKEN env
      ▼
server.php
      │
      │  McpServerKernel::bootFromBasePath()
      ▼
Laravel boot
      │
      │  authenticateFromEnv()
      ▼
SanctumTokenResolver::resolve()
      │
      │  PersonalAccessToken::findToken(...)
      ▼
auth()->setUser($user)
      │
      ▼
new MyMcpServer($user)->run()
      │
      │  every tool callback closes over $this->user
      ▼
Tools query Eloquent with ->where('user_id', $this->user->id)
```

## Why Sanctum and not session auth

Sanctum's `personal_access_tokens` table is the simplest match for the MCP transport model:

- The Claude client passes the token via env var (no cookie jar, no CSRF)
- Tokens are revocable per device (revoke = unplug a Claude install)
- Token abilities (`tokenCan('mcp:read')`) map cleanly to per-tool authorization

If you have a different auth backend (Passport, custom guards), drop in your own resolver — see [Customizing the auth resolver](#customizing-the-auth-resolver).

## Transport

Currently stdio only — the LLM client (Claude Desktop, Claude Code, ChatGPT desktop) spawns the PHP process and exchanges JSON-RPC over stdin/stdout. This matches the most common MCP deployment.

HTTP/SSE transport is on the roadmap.

## Customizing the auth resolver

Subclass `SanctumTokenResolver` or implement your own:

```php
namespace App\Mcp;

use GrobinSon\LaravelMcpServer\Auth\SanctumTokenResolver;
use Illuminate\Contracts\Auth\Authenticatable;

class TenantAwareTokenResolver extends SanctumTokenResolver
{
    public function resolve(string $token): ?Authenticatable
    {
        $user = parent::resolve($token);

        if (! $user) return null;
        if ($user->subscription_status !== 'active') return null;

        return $user;
    }
}
```

Then wire it in your `server.php`:

```php
$user = (new TenantAwareTokenResolver)->resolve(getenv('MCP_API_TOKEN'));
abort_unless($user, 'Inactive subscription');
auth()->setUser($user);
```
