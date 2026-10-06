<?php

namespace App\Http\Controllers;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ImageController extends Controller
{
    public function __invoke(Media $media)
    {
        abort_unless(str_starts_with((string) $media->mime_type, 'image/'), 404);

        return response()->file($media->getPath(), [
            'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
