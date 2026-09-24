<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Services\Chat\ChatAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatAttachmentController extends Controller
{
    public function __invoke(Request $request, ChatMessage $message, ChatAccess $access): StreamedResponse
    {
        abort_unless($message->attachment_path && $access->canView($request->user(), $message->conversation()), 404);

        return Storage::disk($message->attachment_disk)->download($message->attachment_path, $message->attachment_name);
    }
}
