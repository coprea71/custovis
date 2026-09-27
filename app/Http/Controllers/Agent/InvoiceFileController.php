<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Livewire\Agent\Team\Invoicing\InvoiceManager;
use App\Models\Invoice;
use App\Models\Team;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceFileController extends Controller
{
    public function __invoke(Team $team, Invoice $invoice, string $format): StreamedResponse
    {
        abort_unless($invoice->team_id === $team->id, 404);
        InvoiceManager::authorizeView($team);

        $path = $format === 'pdf' ? $invoice->pdf_path : $invoice->xml_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $invoice->fileBaseName().($format === 'xml' ? '_xrechnung.xml' : '.pdf'));
    }
}
