<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Models\Document;
use GrobinSon\LaravelMcpServer\AbstractAuthenticatedMcpServer;

/**
 * Realistic MCP server example: expose a per-user knowledge base.
 *
 * Demonstrates the core pattern from a production codebase:
 * - Tools are scoped to the authenticated user via Eloquent constraints
 * - Tool descriptions are written FOR the LLM (intent + return contract)
 * - A resource exposes machine-readable docs about the API
 * - A prompt walks the user through a typical workflow
 *
 * This example assumes a `documents` table with columns:
 *   id, user_id, title, content, indexed_at, created_at
 */
class KnowledgeBaseMcpServer extends AbstractAuthenticatedMcpServer
{
    public static function serverName(): string
    {
        return 'knowledge-base';
    }

    protected function registerCapabilities(): void
    {
        $this->registerTools();
        $this->registerResources();
        $this->registerPrompts();
    }

    private function registerTools(): void
    {
        $this->server
            ->tool(
                'list_documents',
                'List all documents owned by the authenticated user. Returns id, title, indexed status and creation date.',
                fn () => $this->listDocuments(),
            )
            ->tool(
                'create_document',
                'Create a new document in the user\'s knowledge base. Returns the new document id.',
                fn (string $title, string $content) => $this->createDocument($title, $content),
            )
            ->tool(
                'delete_document',
                'Delete a document by id. Only the owner can delete their own documents.',
                fn (int $document_id) => $this->deleteDocument($document_id),
            );
    }

    private function registerResources(): void
    {
        $this->server->resource(
            uri: 'kb://schema',
            name: 'Knowledge base schema',
            description: 'Markdown description of the documents table and lifecycle.',
            callback: fn (): string => <<<MD
            # Knowledge base schema

            Each document belongs to a single user (`user_id` FK).

            ## Lifecycle
            - `created` → fresh, not yet processed
            - `indexed` → embeddings generated, searchable
            - `failed`  → indexing error (see `error_message`)

            ## Limits
            - 1000 documents per user
            - 10MB content per document
            MD,
            mimeType: 'text/markdown',
        );
    }

    private function registerPrompts(): void
    {
        $this->server->prompt(
            'seed-knowledge-base',
            'Walks the model through populating a fresh knowledge base.',
            fn (string $business_topic): string => <<<PROMPT
            I want to seed my knowledge base with documents about {$business_topic}.

            Suggested workflow:
            1. Call list_documents to see what's already there
            2. Generate 5 to 10 distinct documents covering different angles
            3. Call create_document for each one
            4. Confirm success by listing again

            Keep each document focused on one topic and at least 200 words.
            PROMPT,
        );
    }

    private function listDocuments(): string
    {
        $docs = Document::where('user_id', $this->user->getAuthIdentifier())
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'indexed_at', 'created_at']);

        if ($docs->isEmpty()) {
            return 'No documents yet. Use create_document to add one.';
        }

        return "Documents ({$docs->count()}):\n".$docs
            ->map(fn ($d) => sprintf(
                '- [%d] %s (status: %s, created: %s)',
                $d->id,
                $d->title,
                $d->indexed_at ? 'indexed' : 'pending',
                $d->created_at->format('Y-m-d H:i'),
            ))
            ->implode("\n");
    }

    private function createDocument(string $title, string $content): string
    {
        if (mb_strlen(trim($title)) < 3) {
            return 'Error: title must be at least 3 characters.';
        }
        if (mb_strlen(trim($content)) < 10) {
            return 'Error: content must be at least 10 characters.';
        }

        $doc = Document::create([
            'user_id' => $this->user->getAuthIdentifier(),
            'title' => $title,
            'content' => $content,
        ]);

        return "Created document #{$doc->id} — \"{$title}\". Status: pending indexing.";
    }

    private function deleteDocument(int $documentId): string
    {
        $doc = Document::where('id', $documentId)
            ->where('user_id', $this->user->getAuthIdentifier())
            ->first();

        if (! $doc) {
            return "Document {$documentId} not found or access denied.";
        }

        $doc->delete();

        return "Document {$documentId} deleted.";
    }
}
