<?php

declare(strict_types=1);

namespace GrobinSon\LaravelMcpServer;

use Illuminate\Contracts\Auth\Authenticatable;
use Mcp\Server\McpServer as UnderlyingMcpServer;

/**
 * Base class for an authenticated, Laravel-aware MCP server.
 *
 * Extend this class, implement registerCapabilities(), and call run().
 * The constructor receives the resolved user — every tool/resource/prompt
 * registered inside this server will be scoped to that user.
 */
abstract class AbstractAuthenticatedMcpServer
{
    protected UnderlyingMcpServer $server;

    public function __construct(
        protected readonly Authenticatable $user,
        ?string $serverName = null,
    ) {
        $this->server = new UnderlyingMcpServer($serverName ?? static::serverName());
    }

    /**
     * Public name of the MCP server, surfaced to clients (Claude Desktop / Code).
     */
    abstract public static function serverName(): string;

    /**
     * Register tools, resources and prompts on $this->server here.
     */
    abstract protected function registerCapabilities(): void;

    final public function run(): void
    {
        $this->registerCapabilities();
        $this->server->run();
    }

    final public function user(): Authenticatable
    {
        return $this->user;
    }

    final public function underlying(): UnderlyingMcpServer
    {
        return $this->server;
    }
}
