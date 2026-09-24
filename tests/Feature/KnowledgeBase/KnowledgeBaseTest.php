<?php

namespace Tests\Feature\KnowledgeBase;

use App\Livewire\Admin\KnowledgeBase\ArticleEditor;
use App\Livewire\Agent\KnowledgeBase\ArticleBrowser;
use App\Livewire\Agent\TicketKnowledgePanel;
use App\Livewire\Agent\TicketWorkspace;
use App\Livewire\KnowledgeBase\FeedbackWidget;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Services\KnowledgeBaseService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    private KnowledgeBaseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->category = KnowledgeBaseCategory::query()->create(['name' => 'Drucker']);
    }

    public function test_publishing_creates_versions_and_rollback_restores_previous_content(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(ArticleEditor::class)
            ->set('category_id', $this->category->id)
            ->set('title', 'Drucker neu starten')
            ->set('body', "Schritt 1\nSchritt 2")
            ->call('publish');

        $article = KnowledgeBaseArticle::query()->firstOrFail();
        $firstVersion = $article->currentVersion;

        Livewire::actingAs($admin)->test(ArticleEditor::class, ['article' => $article])
            ->set('body', "Schritt 1\nSchritt 3")
            ->call('publish')
            ->call('compare', $firstVersion->id)
            ->assertSee('+ Schritt 3')
            ->call('rollback', $firstVersion->id);

        $article->refresh();
        $this->assertSame(3, $article->versions()->count());
        $this->assertSame("Schritt 1\nSchritt 2", $article->body);
        $this->assertSame(3, $article->currentVersion->version_number);
        $this->assertDatabaseHas('audit_logs', ['action' => 'kb_article.published', 'user_id' => $admin->id]);
    }

    public function test_search_finds_articles_by_keyword_and_filters_public(): void
    {
        $service = app(KnowledgeBaseService::class);
        $this->article('VPN einrichten', 'Anleitung für den VPN-Client', 'public');
        $this->article('Interne VPN-Server', 'Nur für Admins', 'internal');
        $this->article('Passwort zurücksetzen', 'Self-Service', 'public');

        $this->assertEqualsCanonicalizing(
            ['VPN einrichten', 'Interne VPN-Server'],
            $service->search('VPN')->pluck('title')->all()
        );
        $this->assertSame(['VPN einrichten'], $service->search('VPN', publicOnly: true)->pluck('title')->all());
    }

    public function test_article_markdown_is_rendered_without_raw_html(): void
    {
        $article = $this->article('XSS', '**fett** <script>alert(1)</script> [x](javascript:alert(1))', 'public');

        $html = $article->bodyHtml();

        $this->assertStringContainsString('<strong>fett</strong>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_feedback_is_stored_once_per_session(): void
    {
        $article = $this->article('FAQ', 'Antwort', 'public');

        Livewire::test(FeedbackWidget::class, ['articleId' => $article->id])
            ->call('vote', true)
            ->call('vote', false)
            ->assertSee('Danke');

        $this->assertDatabaseCount('knowledge_base_article_feedback', 1);
        $this->assertDatabaseHas('knowledge_base_article_feedback', ['article_id' => $article->id, 'helpful' => true]);
    }

    public function test_agent_can_insert_article_and_mark_ticket_resolved(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('agent');
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $team->users()->attach($agent);
        $ticket = Ticket::query()->create(['team_id' => $team->id, 'source' => 'api', 'subject' => 'Drucker']);
        $article = $this->article('Drucker neu starten', 'Aus- und einschalten', 'internal');

        Livewire::actingAs($agent)->test(TicketKnowledgePanel::class, ['ticketId' => $ticket->id])
            ->set('kbSearch', 'Drucker')
            ->assertSee('Drucker neu starten')
            ->call('markResolved', $article->id)
            ->call('insert', $article->id)
            ->assertDispatched('kb-article-insert', articleId: $article->id);

        $this->assertSame($article->id, $ticket->fresh()->resolved_with_article_id);

        Livewire::actingAs($agent)->test(TicketWorkspace::class, ['ticket' => $ticket])
            ->dispatch('kb-article-insert', articleId: $article->id)
            ->assertSet('replyBody', "Drucker neu starten\n\nAus- und einschalten");
    }

    public function test_users_without_kb_permission_cannot_browse(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ArticleBrowser::class)
            ->assertForbidden();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('system_admin');

        return $user;
    }

    private function article(string $title, string $body, string $visibility): KnowledgeBaseArticle
    {
        return app(KnowledgeBaseService::class)->publish(null, [
            'category_id' => $this->category->id,
            'title' => $title,
            'body' => $body,
            'visibility' => $visibility,
        ], $this->admin());
    }
}
