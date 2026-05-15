<?php

declare(strict_types=1);

namespace App\Mcp;

use GrobinSon\LaravelMcpServer\AbstractAuthenticatedMcpServer;

/**
 * Minimal MCP server example.
 *
 * Exposes 1 tool (`say_hello`) that uses the authenticated user's name.
 * Run via: BOTLER_API_TOKEN=xxx php examples/01-hello-world/server.php
 */
class HelloWorldMcpServer extends AbstractAuthenticatedMcpServer
{
    public static function serverName(): string
    {
        return 'hello-world';
    }

    protected function registerCapabilities(): void
    {
        $this->server->tool(
            'say_hello',
            'Returns a personalized greeting for the authenticated user. Optionally specify a target name.',
            fn (string $name = '') => $this->sayHello($name),
        );
    }

    private function sayHello(string $name): string
    {
        $target = $name !== '' ? $name : 'world';
        $sender = $this->user->name ?? 'an anonymous user';

        return "Hello, {$target}! (greeting sent by {$sender})";
    }
}
