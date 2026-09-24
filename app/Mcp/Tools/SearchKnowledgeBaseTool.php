<?php

namespace App\Mcp\Tools;

use App\Models\ApiClient;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Services\KnowledgeBaseService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Durchsucht die für diesen Zugang freigegebenen öffentlichen Hilfe-Artikel, um Standardfragen am Telefon zu beantworten.')]
class SearchKnowledgeBaseTool extends TeamScopedTool
{
    protected string $name = 'search_knowledge_base';

    private const LIMIT = 5;

    private const EXCERPT_LENGTH = 1500;

    public function __construct(private readonly KnowledgeBaseService $knowledgeBase) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Suchbegriff oder Frage des Anrufers')->required(),
        ];
    }

    /**
     * Category whitelist comes only from the key's configuration, never from
     * tool arguments; an empty whitelist yields no results (fail closed).
     */
    protected function perform(Request $request, ApiClient $client): array
    {
        $data = $request->validate(['query' => ['required', 'string', 'max:500']]);

        $allowed = KnowledgeBaseCategory::withDescendantIds($client->kbCategories()->pluck('knowledge_base_categories.id')->all());

        if ($allowed === []) {
            return ['articles' => []];
        }

        $articles = $this->knowledgeBase->search($data['query'], publicOnly: true)
            ->whereIn('category_id', $allowed)
            ->limit(self::LIMIT)
            ->get();

        return ['articles' => $articles->map(fn (KnowledgeBaseArticle $article) => [
            'title' => $article->title,
            'content' => Str::limit($article->body, self::EXCERPT_LENGTH),
        ])->all()];
    }
}
