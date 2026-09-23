<?php

namespace Tests\Feature\Itil;

use App\Models\ServiceCatalogItem;
use App\Models\Team;
use App\Services\ServiceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_item_creates_correctly_typed_service_request_ticket(): void
    {
        $team = Team::query()->create(['name' => 'IT', 'slug' => 'it']);
        $item = ServiceCatalogItem::query()->create([
            'team_id' => $team->id,
            'name' => 'Neuer Laptop',
        ]);

        $ticket = (new ServiceCatalogService)->createRequest($item, 'kunde@example.com', 'Max');

        $this->assertSame('service_request', $ticket->type);
        $this->assertDatabaseHas('ticket_service_requests', [
            'ticket_id' => $ticket->id,
            'service_catalog_item_id' => $item->id,
        ]);
    }
}
