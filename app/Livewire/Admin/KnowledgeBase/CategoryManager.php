<?php

namespace App\Livewire\Admin\KnowledgeBase;

use App\Models\KnowledgeBaseCategory;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class CategoryManager extends Component
{
    public string $name = '';

    public ?int $parent_id = null;

    public function mount(): void
    {
        Gate::authorize('kb.categories.manage');
    }

    public function create(): void
    {
        Gate::authorize('kb.categories.manage');

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:knowledge_base_categories,id'],
        ]);

        KnowledgeBaseCategory::query()->create($data);

        $this->reset(['name', 'parent_id']);
    }

    public function delete(int $categoryId): void
    {
        Gate::authorize('kb.categories.manage');

        $category = KnowledgeBaseCategory::query()->withCount(['articles', 'children'])->findOrFail($categoryId);

        if ($category->articles_count > 0 || $category->children_count > 0) {
            $this->addError('delete', 'Nur leere Kategorien ohne Unterkategorien können gelöscht werden.');

            return;
        }

        $category->delete();
    }

    public function render()
    {
        return view('livewire.admin.knowledge-base.category-manager', [
            'categories' => KnowledgeBaseCategory::tree(),
        ]);
    }
}
