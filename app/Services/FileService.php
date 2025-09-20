<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\File\File;
use Illuminate\Http\UploadedFile;

interface FileService
{
    public function uploadFichier(UploadedFile $fichier, String $chemin, String $filename):File;

    public function readFile($filePath):File;
}

