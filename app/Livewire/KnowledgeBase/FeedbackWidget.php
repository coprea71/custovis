<?php

namespace App\Livewire\KnowledgeBase;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseArticleFeedback;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * "War dieser Artikel hilfreich?" — anonymous (no user stored), one vote
 * per article and session. Embedded by pages that already checked that
 * the viewer may read the article (agent browser, customer portal).
 */
class FeedbackWidget extends Component
{
    #[Locked]
    public int $articleId;

    public function vote(bool $helpful): void
    {
        if ($this->alreadyVoted()) {
            return;
        }

        $article = KnowledgeBaseArticle::query()->findOrFail($this->articleId);
        KnowledgeBaseArticleFeedback::query()->create(['article_id' => $article->id, 'helpful' => $helpful]);

        session()->push('kb_feedback_voted', $article->id);
    }

    public function render()
    {
        return view('livewire.knowledge-base.feedback-widget', [
            'voted' => $this->alreadyVoted(),
        ]);
    }

    private function alreadyVoted(): bool
    {
        return in_array($this->articleId, session('kb_feedback_voted', []), true);
    }
}
