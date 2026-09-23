<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsappWebhookJob;
use App\Models\WhatsappAccount;
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

    public function receive(Request $request, WhatsappAccount $account): Response
    {
        if (! $account->active) {
            abort(404);
        }

        $this->verifySignature($request, $account);

        $entries = $request->input('entry', []);

        foreach ($entries as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach ($change['value']['messages'] ?? [] as $message) {
                    ProcessWhatsappWebhookJob::dispatch($account, $change['value'], $message);
                }
            }
        }

        return response()->noContent();
    }

    private function verifySignature(Request $request, WhatsappAccount $account): void
    {
        $signature = $request->header('X-Hub-Signature-256', '');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $account->app_secret);

        abort_unless(hash_equals($expected, $signature), 403, 'Invalid webhook signature.');
    }
}
