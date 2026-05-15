# Wiring your server into a Claude client

Once you can run your MCP server from the CLI, the next step is to register it with the LLM client of your choice.

## Claude Desktop

Edit (or create) `~/Library/Application Support/Claude/claude_desktop_config.json` (macOS) — same file under `%APPDATA%/Claude/` on Windows:

```json
{
  "mcpServers": {
    "my-laravel-server": {
      "command": "php",
      "args": [
        "/absolute/path/to/your-laravel-app/bin/mcp-server.php"
      ],
      "env": {
        "MCP_API_TOKEN": "1|abc123...your-sanctum-token"
      }
    }
  }
}
```

Restart Claude Desktop. Your tools appear under the 🔌 icon.

## Claude Code

Inside any project, register the server in `.claude/settings.json`:

```json
{
  "mcpServers": {
    "my-laravel-server": {
      "command": "php",
      "args": ["/absolute/path/to/your-laravel-app/bin/mcp-server.php"],
      "env": { "MCP_API_TOKEN": "1|abc123..." }
    }
  }
}
```

Or globally in `~/.claude/settings.json`. Verify with:

```bash
claude mcp list
```

## Generating a Sanctum token

In tinker, on the user account that should be impersonated by Claude:

```php
$user = \App\Models\User::find(1);
$token = $user->createToken('claude-desktop-mac', ['*'])->plainTextToken;
echo $token;
```

Copy the output (one line: `1|xxxxxxxxxx...`) into `MCP_API_TOKEN`.

To revoke the token later:

```php
\Laravel\Sanctum\PersonalAccessToken::where('name', 'claude-desktop-mac')->delete();
```

## Production deployment

The MCP server runs as a child process of the LLM client — there is no long-running daemon. Each Claude session spawns a fresh PHP process per server.

That means:

- Cold start cost = your Laravel boot time (~100–300 ms)
- No need for Octane / FrankenPHP, but they help if you build many tools
- Each process is short-lived, so opcache + preload pays off
- Log to STDERR (Claude swallows STDOUT for the JSON-RPC channel)

## Debugging

- Tail your Laravel log: `tail -f storage/logs/laravel.log`
- Run the server in your terminal and pipe a JSON-RPC request to it manually:

```bash
echo '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' \
  | MCP_API_TOKEN=xxx php server.php
```

If you see your tool listing, the wiring is correct.
