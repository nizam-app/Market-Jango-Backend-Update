<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileHelper
{
    public static function upload($files, $userType = 'user', $disk = 'public')
    {
        if (!$files) return null;

        $paths = [];

        if (!is_array($files) && !(is_object($files) && method_exists($files, 'count'))) {
            $files = [$files];
        }

        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());

            $imageExtensions = ['jpg','jpeg','png','webp'];
            $fileType = in_array($extension, $imageExtensions) ? 'image' : 'document';

            // Folder per user type + file type
            $folder = "$userType/" . ($fileType === 'image' ? 'images' : 'documents');

            $fileName = Str::uuid()->toString() . '.' . $extension;
            $filePath = $file->storeAs($folder, $fileName, $disk);

            $paths[] = [
                'path' => $filePath,
                'type' => $fileType,
            ];
        }
        return $paths;


    }

    public static function delete($filePaths, $disk = 'public')
    {
        if (!$filePaths) return false;

        foreach ((array)$filePaths as $path) {
            Storage::disk($disk)->delete($path);
        }

        return true;
    }

    public static function update($newFiles, $oldFiles, $userType = 'user', $disk = 'public')
    {
        if ($newFiles) {
            self::delete($oldFiles, $disk);
            return self::upload($newFiles, $userType, $disk);
        }
        return $oldFiles;
    }
}

