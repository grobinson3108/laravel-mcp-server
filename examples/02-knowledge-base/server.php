#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Entry point for the knowledge-base MCP server example.
 *
 * Requires a Laravel app with a `documents` table (see KnowledgeBaseMcpServer).
 *
 * Run from your Laravel project root:
 *   MCP_API_TOKEN=<token> php examples/02-knowledge-base/server.php
 */

use App\Mcp\KnowledgeBaseMcpServer;
use GrobinSon\LaravelMcpServer\McpServerKernel;

$basePath = dirname(__DIR__, 3);

$kernel = McpServerKernel::bootFromBasePath($basePath);
$user = $kernel->authenticateFromEnv();

(new KnowledgeBaseMcpServer($user))->run();
