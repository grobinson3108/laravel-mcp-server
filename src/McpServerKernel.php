<?php

declare(strict_types=1);

namespace GrobinSon\LaravelMcpServer;

use GrobinSon\LaravelMcpServer\Auth\SanctumTokenResolver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use RuntimeException;

class McpServerKernel
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $tokenEnvVar = 'MCP_API_TOKEN',
    ) {}

    public static function bootFromBasePath(string $basePath, string $tokenEnvVar = 'MCP_API_TOKEN'): self
    {
        $kernel = new self($basePath, $tokenEnvVar);
        $kernel->bootstrapLaravel();

        return $kernel;
    }

    public function bootstrapLaravel(): void
    {
        $autoload = $this->basePath.'/vendor/autoload.php';
        if (! file_exists($autoload)) {
            throw new RuntimeException("Composer autoload not found at {$autoload}. Run `composer install` first.");
        }
        require $autoload;

        $bootstrap = $this->basePath.'/bootstrap/app.php';
        if (! file_exists($bootstrap)) {
            throw new RuntimeException("Laravel bootstrap not found at {$bootstrap}. Are you in a Laravel project root?");
        }

        $app = require_once $bootstrap;
        $app->make(ConsoleKernel::class)->bootstrap();
    }

    public function authenticateFromEnv(): Authenticatable
    {
        $token = getenv($this->tokenEnvVar);

        if (! $token) {
            $this->fatal("Environment variable {$this->tokenEnvVar} is required.");
        }

        $user = (new SanctumTokenResolver)->resolve($token);

        if (! $user) {
            $this->fatal("Invalid {$this->tokenEnvVar}: token not found or revoked.");
        }

        auth()->setUser($user);

        return $user;
    }

    private function fatal(string $message): never
    {
        fwrite(STDERR, "[mcp-server] Error: {$message}\n");
        exit(1);
    }
}
