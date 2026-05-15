# Installation

## Prerequisites

- PHP 8.2+
- Laravel 11, 12, or 13
- Laravel Sanctum 4+ already installed and migrated

If you don't yet have Sanctum:

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

Make sure your `User` model uses `HasApiTokens`.

## 1. Install the package

```bash
composer require grobinson3108/laravel-mcp-server
```

## 2. Create your server class

Put it under `app/Mcp/`:

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
            'whoami',
            'Returns the email of the authenticated user.',
            fn () => $this->user->email,
        );
    }
}
```

## 3. Create the entry-point script

Save as `bin/mcp-server.php` in your Laravel project root:

```php
#!/usr/bin/env php
<?php

use App\Mcp\MyMcpServer;
use GrobinSon\LaravelMcpServer\McpServerKernel;

$kernel = McpServerKernel::bootFromBasePath(dirname(__DIR__));
$user = $kernel->authenticateFromEnv();

(new MyMcpServer($user))->run();
```

Make it executable:

```bash
chmod +x bin/mcp-server.php
```

## 4. Generate a Sanctum token

```bash
php artisan tinker
```

```php
$token = \App\Models\User::find(1)->createToken('claude-desktop')->plainTextToken;
echo $token;
```

Copy the output.

## 5. Wire it into Claude

See [`docs/claude-config.md`](docs/claude-config.md) for the full guide.

Quick version, in `~/Library/Application Support/Claude/claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "my-app": {
      "command": "php",
      "args": ["/absolute/path/to/your/project/bin/mcp-server.php"],
      "env": { "MCP_API_TOKEN": "1|paste-your-token" }
    }
  }
}
```

Restart Claude Desktop. Your tool appears under the 🔌 icon.

## 6. Verify from the CLI

You can run a sanity check without going through Claude:

```bash
echo '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' \
  | MCP_API_TOKEN=<your-token> php bin/mcp-server.php
```

You should see your `whoami` tool in the response.
