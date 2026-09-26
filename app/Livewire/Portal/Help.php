<?php

namespace App\Livewire\Portal;

use App\Support\HelpTopics;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Screen guides for customers. Deliberately reachable without login so that
 * customers who cannot sign in can still read how to get access.
 */
#[Layout('layouts.portal')]
class Help extends Component
{
    #[Locked]
    public ?string $topic = null;

    public function mount(?string $topic = null): void
    {
        abort_if($topic !== null && ! array_key_exists($topic, HelpTopics::forPortal()), 404);

        $this->topic = $topic;
    }

    public function render()
    {
        return view('livewire.portal.help', [
            'topics' => HelpTopics::forPortal(),
            'body' => $this->topic ? HelpTopics::html("portal/{$this->topic}") : null,
        ]);
    }
}
