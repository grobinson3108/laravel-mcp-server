#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Entry point for the hello-world MCP server example.
 *
 * Run from your Laravel project root:
 *   MCP_API_TOKEN=<your-sanctum-token> php examples/01-hello-world/server.php
 *
 * Wire up in Claude Desktop config:
 *   {
 *     "mcpServers": {
 *       "hello": {
 *         "command": "php",
 *         "args": ["/absolute/path/to/server.php"],
 *         "env": { "MCP_API_TOKEN": "your-token" }
 *       }
 *     }
 *   }
 */

use App\Mcp\HelloWorldMcpServer;
use GrobinSon\LaravelMcpServer\McpServerKernel;

// __DIR__ is examples/01-hello-world ; basePath is the Laravel project root (3 levels up).
$basePath = dirname(__DIR__, 3);

$kernel = McpServerKernel::bootFromBasePath($basePath);
$user = $kernel->authenticateFromEnv();

(new HelloWorldMcpServer($user))->run();
