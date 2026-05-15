# Examples

Two ready-to-copy examples showing what a `laravel-mcp-server` server looks like in practice.

## `01-hello-world/`

The minimal working example — a single tool that greets the authenticated user.

Use it to verify your Sanctum + Claude Desktop wiring before building real tools.

| File | Role |
|------|------|
| `server.php` | Entry point. Bootstraps Laravel, authenticates, runs the server. |
| `HelloWorldMcpServer.php` | The server class. Place this under `app/Mcp/` in your project. |

Run:

```bash
MCP_API_TOKEN=<your-sanctum-token> php examples/01-hello-world/server.php
```

## `02-knowledge-base/`

A realistic example: expose a per-user `documents` table as MCP tools, plus a resource and a prompt.

This is the pattern used in production by [Botler](https://botlers.app) — every tool is automatically scoped to the user behind the Sanctum token.

| File | Role |
|------|------|
| `server.php` | Entry point. |
| `KnowledgeBaseMcpServer.php` | 3 tools (list/create/delete) + 1 resource + 1 prompt. |

Run:

```bash
MCP_API_TOKEN=<your-sanctum-token> php examples/02-knowledge-base/server.php
```

## Wiring in Claude Desktop / Claude Code

Once your server runs from the CLI, point your client at it. Full guide in [`docs/claude-config.md`](../docs/claude-config.md).

## Building your own

1. Subclass `AbstractAuthenticatedMcpServer`
2. Implement `serverName()` and `registerCapabilities()`
3. Inside `registerCapabilities()`, call `$this->server->tool(...)`, `->resource(...)`, `->prompt(...)`
4. Always scope queries with `$this->user` for tenant safety
