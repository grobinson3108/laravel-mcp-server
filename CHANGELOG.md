# Changelog

All notable changes to `laravel-mcp-server` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- HTTP / SSE transport support
- Tests against multiple Laravel versions via Orchestra Testbench
- Per-tool authorization via Sanctum token abilities

## [0.1.0] — 2026-05-15

### Added
- `McpServerKernel` — Laravel bootstrap + Sanctum auth resolution from CLI
- `AbstractAuthenticatedMcpServer` — extensible base class for identity-scoped MCP servers
- `Auth\SanctumTokenResolver` — pluggable default token-to-user resolver
- Example 1: `hello-world` — single-tool sanity check
- Example 2: `knowledge-base` — realistic per-user CRUD (3 tools + 1 resource + 1 prompt)
- Documentation: `README.md`, `INSTALL.md`, `ARCHITECTURE.md`, `claude-config.md`, `why-php.md`
- MIT License

### Origin
Extracted from production code in [Botler](https://botlers.app), a chatbot SaaS shipping real Claude Desktop and Claude Code traffic.

[Unreleased]: https://github.com/grobinson3108/laravel-mcp-server/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/grobinson3108/laravel-mcp-server/releases/tag/v0.1.0
