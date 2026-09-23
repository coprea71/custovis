<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\WhatsappAccount;
use App\Services\WhatsappImportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsappWebhookController extends Controller
{
    /**
     * Meta's verification handshake when the webhook is configured.
     */
    public function verify(Request $request, WhatsappAccount $account): Response
    {
        if (
            $request->query('hub_mode') === 'subscribe'
            && hash_equals((string) $account->webhook_verify_token, (string) $request->query('hub_verify_token'))
        ) {
            return response((string) $request->query('hub_challenge'));
        }

        return response('', 403);
    }

    public function receive(Request $request, WhatsappAccount $account, WhatsappImportService $importer): Response
    {
        if (! $account->active) {
            abort(404);
        }

        $this->verifySignature($request, $account);

        $entries = $request->input('entry', []);

        foreach ($entries as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach ($change['value']['messages'] ?? [] as $message) {
                    $this->importMessage($importer, $account, $change['value'], $message);
                }
            }
        }

        return response()->noContent();
    }

    private function importMessage(WhatsappImportService $importer, WhatsappAccount $account, array $value, array $message): void
    {
        $contact = collect($value['contacts'] ?? [])->first();
        $fromName = $contact['profile']['name'] ?? $message['from'];

        $type = $message['type'];
        $text = match ($type) {
            'text' => $message['text']['body'] ?? null,
            default => null,
        };

        $importer->importInboundMessage(
            account: $account,
            waMessageId: $message['id'],
            type: $type,
            text: $text,
            fromPhone: $message['from'],
            fromName: $fromName,
        );
    }

    private function verifySignature(Request $request, WhatsappAccount $account): void
    {
        $signature = $request->header('X-Hub-Signature-256', '');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $account->app_secret);

        abort_unless(hash_equals($expected, $signature), 403, 'Invalid webhook signature.');
    }
}
