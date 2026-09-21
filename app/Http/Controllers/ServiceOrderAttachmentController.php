<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrderAttachment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;

class ServiceOrderAttachmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Serve an attachment (inline preview) or force download, always through
     * the Laravel Storage filesystem. The physical path is never exposed.
     */
    public function show(ServiceOrderAttachment $attachment)
    {
        $this->authorize('view', $attachment);

        $headers = ['Content-Type' => $attachment->mime_type];

        if (request()->boolean('download')) {
            return Storage::disk('local')->download($attachment->path, $attachment->name, $headers);
        }

        return Storage::disk('local')->response($attachment->path, $attachment->name, $headers);
    }
}