<?php

namespace Tests\Feature\Mail;

use App\Jobs\FetchMailboxJob;
use App\Models\Mailbox;
use App\Services\MailboxImapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;
use Webklex\PHPIMAP\Client;

class FetchMailboxJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_fetch_stores_error_on_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->mock(MailboxImapService::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('connect')->andThrow(new RuntimeException('AUTHENTICATIONFAILED')));

        try {
            dispatch_sync(new FetchMailboxJob($mailbox));
            $this->fail('Job should rethrow the connection error.');
        } catch (RuntimeException) {
        }

        $mailbox->refresh();
        $this->assertSame('AUTHENTICATIONFAILED', $mailbox->last_fetch_error);
        $this->assertNotNull($mailbox->last_fetch_error_at);
    }

    public function test_successful_fetch_clears_previous_error(): void
    {
        $mailbox = Mailbox::factory()->withFetchError()->create();
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('getFolder->query->unseen->get')->andReturn(collect());
        $this->mock(MailboxImapService::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('connect')->andReturn($client));

        dispatch_sync(new FetchMailboxJob($mailbox));

        $mailbox->refresh();
        $this->assertNull($mailbox->last_fetch_error);
        $this->assertNotNull($mailbox->last_fetched_at);
    }
}
