# Why a PHP MCP server?

A short FAQ for the inevitable "why not use the Node SDK?".

## "Isn't MCP a TypeScript thing?"

The Model Context Protocol is **transport-agnostic**. The Anthropic team ships an excellent TypeScript SDK because TS is their reference implementation, but the wire protocol is just JSON-RPC over stdio (or HTTP). Any language can serve and consume it.

Production PHP teams already have:
- An ORM that knows their data (Eloquent)
- Their authentication primitives (Sanctum, Passport, custom guards)
- Their authorization (Policies, Gates)
- Their queue/cache/storage facades
- Their existing test suite

Re-implementing all of that in TypeScript just to expose tools to an LLM is a tax that gets old fast. This package lets you skip the tax.

## "Doesn't booting Laravel for every Claude tool call kill performance?"

No, because **Laravel boots once per Claude session**, not once per tool call. The process stays alive as long as the Claude client keeps the channel open — typically the entire conversation.

Cold-start measurements on a modest stack (PHP 8.3, no Octane):
- Laravel 12 boot: ~120 ms
- Sanctum token resolution: ~5 ms
- First tool call ready: ~150 ms

After that, every tool call is just an Eloquent query.

## "What about HTTP transport for cloud deployments?"

Roadmap. The current focus is the local stdio transport because that's where 95% of real-world MCP usage lives today (Claude Desktop, Claude Code, ChatGPT desktop). HTTP/SSE is planned once the protocol stabilizes.

## "Why Sanctum specifically?"

It's the smallest piece of Laravel auth that fits the MCP model:
- One token per device (revocable)
- No session cookies (no CSRF)
- Token abilities map to per-tool authorization
- Already in 90% of modern Laravel projects

If you're on Passport or a custom guard, see [ARCHITECTURE.md → Customizing the auth resolver](ARCHITECTURE.md#customizing-the-auth-resolver).

## "Can I expose my Filament resources / Nova actions through this?"

Yes — every tool callback runs inside a fully booted Laravel container. You can call any service, any policy, any model exactly like in a controller. The MCP layer is thin on purpose.
