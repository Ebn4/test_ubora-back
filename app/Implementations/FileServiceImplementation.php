<?php

namespace App\Implementations;

use App\Services\FileService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\File;

class FileServiceImplementation implements FileService
{
    public function uploadFichier(UploadedFile $fichier, String $chemin, String $filename): File
    {
        try {
            $filePath = $chemin . '/' . $filename;


            $content = file_get_contents($fichier->getRealPath());

            if ($content === false) {
                throw new \Exception("Impossible de lire le contenu du fichier.");
            }

            Storage::disk('public')->put($filePath, $content);

            if (!Storage::disk('public')->exists($filePath)) {
                throw new \Exception("Le fichier n’a pas été enregistré.");
            }

            return new File(storage_path('app/public/' . $filePath));
        } catch (\Exception $e) {
            Log::error("Erreur pendant l'upload : " . $e->getMessage());
            throw $e;
        }
    }



    public function readFile($filePath): File
    {
        return new File($filePath);
    }
}
