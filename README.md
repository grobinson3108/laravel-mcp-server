<div align="center">

# 🔌 Laravel MCP Server

**Build authenticated [Model Context Protocol](https://modelcontextprotocol.io) servers in Laravel.**

Expose your app's data and actions to Claude Desktop, Claude Code, ChatGPT and any other MCP client — with Sanctum auth out of the box.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2+-777BB4.svg)](https://www.php.net/)
[![Laravel 11/12/13](https://img.shields.io/badge/Laravel-11_|_12_|_13-FF2D20.svg)](https://laravel.com/)
[![MCP](https://img.shields.io/badge/MCP-compatible-10b981.svg)](https://modelcontextprotocol.io)

</div>

---

## Why this exists

The MCP ecosystem is overwhelmingly TypeScript. If you run a PHP / Laravel SaaS, you have two options:

1. Re-implement your domain in Node just to expose tools to Claude (huge tax, two codebases)
2. Find a way to host an MCP server **inside your existing Laravel app**, with the same Eloquent models, the same Sanctum tokens, the same Policies

This package is option 2.

It's a thin layer over [`logiscape/mcp-sdk-php`](https://github.com/logiscape/mcp-sdk-php) that handles the boring parts:

- Bootstrapping Laravel inside a CLI process
- Resolving a Sanctum token to an authenticated user
- Wiring `auth()->setUser()` so every tool callback runs identity-aware
- A clean abstract base class so your servers stay focused on **what tools to expose**

Born from production code shipping in [Botler](https://botlers.app) (chatbot SaaS), serving real Claude Desktop and Claude Code traffic.

---

## Quick start

```bash
composer require grobinson3108/laravel-mcp-server
```

Create a server class under `app/Mcp/`:

```php
<?php

namespace App\Mcp;

use GrobinSon\LaravelMcpServer\AbstractAuthenticatedMcpServer;

class MyMcpServer extends AbstractAuthenticatedMcpServer
{
    public static function serverName(): string
    {
        return 'my-app';
    }

    protected function registerCapabilities(): void
    {
        $this->server->tool(
            'list_my_orders',
            'List the authenticated user\'s recent orders.',
            fn (int $limit = 10) => $this->user
                ->orders()
                ->latest()
                ->limit($limit)
                ->get(['id', 'total', 'status'])
                ->toJson(),
        );
    }
}
```

Create an entry-point script (`bin/mcp-server.php`):

```php
#!/usr/bin/env php
<?php

use App\Mcp\MyMcpServer;
use GrobinSon\LaravelMcpServer\McpServerKernel;

$kernel = McpServerKernel::bootFromBasePath(dirname(__DIR__));
$user = $kernel->authenticateFromEnv();

(new MyMcpServer($user))->run();
```

Generate a token:

```php
$token = $user->createToken('claude-desktop')->plainTextToken;
```

Wire into Claude Desktop's config:

```json
{
  "mcpServers": {
    "my-app": {
      "command": "php",
      "args": ["/path/to/your/project/bin/mcp-server.php"],
      "env": { "MCP_API_TOKEN": "1|paste-token-here" }
    }
  }
}
```

Done. Restart Claude Desktop, your tool appears under the 🔌 icon.

Full step-by-step in [`INSTALL.md`](INSTALL.md).

---

## What you get

| Class | Role |
|-------|------|
| `McpServerKernel` | Boots Laravel from CLI + resolves Sanctum token + injects authenticated user. |
| `AbstractAuthenticatedMcpServer` | Base class for your server. Receives the user, exposes `$this->server` for tool/resource/prompt registration. |
| `Auth\SanctumTokenResolver` | Default Sanctum token → User resolver. Subclass it to add tenant checks, subscription gates, etc. |

You also get **two examples** under [`examples/`](examples/):

- **`01-hello-world/`** — single-tool sanity check
- **`02-knowledge-base/`** — realistic per-user CRUD with tools + resource + prompt

---

## Identity flow in one diagram

```
Claude Desktop
      │
      │  spawns process with MCP_API_TOKEN env var
      ▼
bin/mcp-server.php
      │
      │  McpServerKernel::bootFromBasePath()
      ▼
Laravel boots
      │
      │  authenticateFromEnv()
      ▼
SanctumTokenResolver → User
      │
      │  auth()->setUser($user)
      ▼
new MyMcpServer($user)->run()
      │
      ▼
Every tool callback closes over $this->user
      │
      ▼
Tools query Eloquent ->where('user_id', $this->user->id)
```

Read more: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

---

## Documentation

| Doc | Topic |
|-----|-------|
| [`INSTALL.md`](INSTALL.md) | Step-by-step installation, including Sanctum setup |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | How the two-layer design works, identity flow, custom resolvers |
| [`docs/claude-config.md`](docs/claude-config.md) | Wiring into Claude Desktop and Claude Code, debugging tips |
| [`docs/why-php.md`](docs/why-php.md) | The "why not just use the TypeScript SDK?" FAQ |
| [`examples/README.md`](examples/README.md) | The two example servers explained |

---

## What's not in scope (yet)

- ❌ HTTP / SSE transport — stdio only for now (95% of real-world MCP usage today)
- ❌ A built-in tools registry / discovery — you wire tools manually, on purpose, for visibility
- ❌ Auto-generated OpenAPI from tool signatures — out of scope, use [`spatie/laravel-data`](https://github.com/spatie/laravel-data) if you want it

These may land in v2. Open an issue if you have strong opinions.

---

## Comparison

| | This package | Plain `logiscape/mcp-sdk-php` | TypeScript SDK |
|---|:---:|:---:|:---:|
| Auto-boot Laravel | ✅ | ❌ (DIY) | ❌ (no Laravel) |
| Sanctum auth out of the box | ✅ | ❌ | ❌ |
| Identity-scoped tool callbacks | ✅ | ❌ | ❌ |
| Run inside your existing PHP app | ✅ | ✅ | ❌ |
| stdio transport | ✅ | ✅ | ✅ |
| HTTP/SSE transport | 🛠️ planned | ✅ | ✅ |

---

## Contributing

PRs welcome — particularly:
- Tests against multiple Laravel versions
- Alternative auth resolvers (Passport, custom guards)
- HTTP transport prototype
- Tighter type hints on tool callback signatures

## License

MIT — see [`LICENSE`](LICENSE).

---

## About the author

**Greg Robinson** — AI Architect, RAG and agentic systems, [Audelalia](https://audelalia.fr) (🇫🇷 Montpellier).

This package is one of several extracted from production SaaS work. See:
- [vibe-coding-arsenal](https://github.com/grobinson3108/vibe-coding-arsenal) — 38 Claude Code commands and skills
- *(more on [github.com/grobinson3108](https://github.com/grobinson3108))*

If this saved you time and you'd like to support: a star ⭐ goes a long way.
