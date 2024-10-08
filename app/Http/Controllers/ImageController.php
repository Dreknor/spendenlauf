<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\MediaLibrary\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImageController extends Controller
{
    public function getImage(Media $media_id){

        $response = new BinaryFileResponse($media_id->getPath());
        $response->headers->set('Content-Disposition', 'inline; filename="' . $media_id->file_name . '"');

        return $response;

    }
}
