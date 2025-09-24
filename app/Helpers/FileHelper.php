<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class FileHelper
{
    /**
     * Normalise un nom de fichier : supprime les caractères spéciaux, met en minuscule, slugify
     * Ex: "Mon CV Jean (2).PDF" → "mon_cv_jean_2.pdf"
     */
    public static function normalizeFileName(?string $filename): ?string
    {
        if (!$filename) {
            return null;
        }

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Nettoie le nom : slug + minuscule + underscores
        $cleanName = Str::slug($name, '_');

        // Reconstitue avec l'extension (en minuscule)
        return $cleanName . ($ext ? '.' . strtolower($ext) : '');
    }
}
