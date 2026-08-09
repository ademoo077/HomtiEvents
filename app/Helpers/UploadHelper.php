<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Gestionnaire d'upload de fichiers avec validation MIME réelle.
 */
final class UploadHelper
{
    /** Types MIME autorisés pour les images */
    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /** Extensions autorisées */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Upload un fichier image avec validation complète.
     *
     * @param array{tmp_name: string, name: string, size: int, error: int} $file  $_FILES entry
     * @param string                                                        $destination  Directory path
     * @param int                                                           $maxSize      Max bytes (0 = no limit)
     *
     * @return array{success: bool, path?: string, error?: string}
     */
    public static function uploadImage(array $file, string $destination, int $maxSize = 0): array
    {
        // Vérifier les erreurs PHP upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => self::uploadError($file['error'])];
        }

        // Vérifier la taille
        $maxSize = $maxSize ?: (int) config('security.upload_max', 5242880);
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Fichier trop volumineux (max ' . self::formatSize($maxSize) . ').'];
        }

        // Vérifier l'extension
        $originalName = $file['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return ['success' => false, 'error' => 'Extension non autorisée. Formats acceptés : ' . implode(', ', self::ALLOWED_EXTENSIONS) . '.'];
        }

        // Vérifier le MIME réel via finfo (pas l'extension)
        $realMime = self::realMime($file['tmp_name']);
        if (! in_array($realMime, self::ALLOWED_IMAGE_MIMES, true)) {
            return ['success' => false, 'error' => 'Type MIME non autorisé (' . $realMime . ').'];
        }

        // Vérifier que c'est bien une image
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return ['success' => false, 'error' => 'Fichier image invalide.'];
        }

        return self::store($file, $destination);
    }

    /**
     * Upload d'un document (images + PDF) — utilisé pour l'agrément d'inscription.
     *
     * @param array{tmp_name: string, name: string, size: int, error: int} $file  $_FILES entry
     * @param string                                                        $destination  Directory path
     * @param int                                                           $maxSize      Max bytes (0 = no limit)
     *
     * @return array{success: bool, path?: string, error?: string}
     */
    public static function uploadDocument(array $file, string $destination, int $maxSize = 0): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => self::uploadError($file['error'])];
        }

        $maxSize = $maxSize ?: (int) config('security.upload_max', 5242880);
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Fichier trop volumineux (max ' . self::formatSize($maxSize) . ').'];
        }

        $allowedExts = array_merge(self::ALLOWED_EXTENSIONS, ['pdf']);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (! in_array($ext, $allowedExts, true)) {
            return ['success' => false, 'error' => 'Extension non autorisée. Formats acceptés : ' . implode(', ', $allowedExts) . '.'];
        }

        $realMime = self::realMime($file['tmp_name']);
        $allowedMimes = array_merge(self::ALLOWED_IMAGE_MIMES, ['application/pdf']);
        if (! in_array($realMime, $allowedMimes, true)) {
            return ['success' => false, 'error' => 'Type MIME non autorisé (' . $realMime . ').'];
        }

        if ($ext === 'pdf' && $realMime !== 'application/pdf') {
            return ['success' => false, 'error' => 'Le fichier PDF est invalide.'];
        }

        if ($ext !== 'pdf' && @getimagesize($file['tmp_name']) === false) {
            return ['success' => false, 'error' => 'Fichier image invalide.'];
        }

        return self::store($file, $destination);
    }

    /**
     * Stocke le fichier : répertoire, nom aléatoire, déplacement.
     *
     * @return array{success: bool, path?: string, error?: string}
     */
    private static function store(array $file, string $destination): array
    {
        // Créer le répertoire si nécessaire
        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        // Générer un nom aléatoire
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newName = bin2hex(random_bytes(16)) . '.' . $ext;
        $path = $destination . '/' . $newName;

        // Déplacer le fichier
        if (! move_uploaded_file($file['tmp_name'], $path)) {
            return ['success' => false, 'error' => 'Erreur lors de la sauvegarde du fichier.'];
        }

        // Retourner le chemin relatif depuis public/
        $publicPath = config('paths.public', '');
        $relativePath = str_replace($publicPath, '', $path);
        $relativePath = '/' . ltrim($relativePath, '/');

        return ['success' => true, 'path' => $relativePath];
    }

    /**
     * Upload multiple d'images.
     *
     * @param array<int, array{tmp_name: string, name: string, size: int, error: int}> $files  $_FILES['photos'] entries
     * @param string $destination
     * @param int    $maxSize
     *
     * @return array{successes: array<int, string>, errors: array<int, string>}
     */
    public static function uploadMultiple(array $files, string $destination, int $maxSize = 0): array
    {
        $successes = [];
        $errors = [];

        // Format $_FILES : { name: [...], tmp_name: [...], error: [...], size: [...] }
        if (isset($files['tmp_name']) && is_array($files['tmp_name'])) {
            $count = count($files['tmp_name']);
            for ($i = 0; $i < $count; $i++) {
                $singleFile = [
                    'tmp_name' => $files['tmp_name'][$i],
                    'name'     => $files['name'][$i] ?? '',
                    'size'     => $files['size'][$i] ?? 0,
                    'error'    => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                ];
                $result = self::uploadImage($singleFile, $destination, $maxSize);
                if ($result['success']) {
                    $successes[] = $result['path'];
                } else {
                    $errors[$i] = $result['error'];
                }
            }

            return ['successes' => $successes, 'errors' => $errors];
        }

        // Format tableau de tableaux (plusieurs champs input)
        foreach ($files as $index => $file) {
            if (! is_array($file) || ! isset($file['tmp_name'])) {
                continue;
            }
            $result = self::uploadImage($file, $destination, $maxSize);
            if ($result['success']) {
                $successes[] = $result['path'];
            } else {
                $errors[$index] = $result['error'];
            }
        }

        return ['successes' => $successes, 'errors' => $errors];
    }

    /**
     * Supprimer un fichier uploadé.
     */
    public static function delete(string $path): bool
    {
        $publicPath = config('paths.public', '');
        $fullPath = $publicPath . ltrim($path, '/');

        if (is_file($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    /**
     * Détecter le MIME réel via finfo (pas l'extension).
     */
    private static function realMime(string $tmpPath): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath);

        return $mime !== false ? $mime : 'application/octet-stream';
    }

    /**
     * Message d'erreur humain pour les erreurs PHP upload.
     */
    private static function uploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE   => 'Fichier trop volumineux (limite serveur).',
            UPLOAD_ERR_FORM_SIZE  => 'Fichier trop volumineux (limite formulaire).',
            UPLOAD_ERR_PARTIAL    => 'Upload partiel — fichier incomplet.',
            UPLOAD_ERR_NO_FILE    => 'Aucun fichier reçu.',
            UPLOAD_ERR_NO_TMP_DIR => 'Répertoire temporaire manquant.',
            UPLOAD_ERR_CANT_WRITE => 'Erreur d\'écriture sur le serveur.',
            UPLOAD_ERR_EXTENSION  => 'Extension bloquée par le serveur.',
            default               => 'Erreur inconnue (code ' . $code . ').',
        };
    }

    /**
     * Formater une taille en octets.
     */
    private static function formatSize(int $bytes): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $i = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 1) . ' ' . $units[$i];
    }
}
