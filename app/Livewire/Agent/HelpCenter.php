<?php

namespace App\Livewire\Agent;

use App\Support\HelpTopics;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.agent')]
class HelpCenter extends Component
{
    #[Locked]
    public ?string $topic = null;

    public string $search = '';

    public function mount(?string $topic = null): void
    {
        // Hidden topics answer 404 like the module middleware does for whole areas.
        abort_if($topic !== null && ! array_key_exists($topic, HelpTopics::for(Auth::user())), 404);

        $this->topic = $topic;
    }

    public function render()
    {
        $topics = HelpTopics::for(Auth::user());
        $needle = Str::lower(trim($this->search));

        $matches = array_filter($topics, fn (array $topic) => $needle === ''
            || Str::contains(Str::lower($topic['title'].' '.$topic['summary']), $needle));

        return view('livewire.agent.help-center', [
            'groups' => collect($matches)->groupBy('group', preserveKeys: true),
            'current' => $this->topic ? $topics[$this->topic] : null,
            'body' => $this->topic ? HelpTopics::html($this->topic) : null,
        ]);
    }
}
