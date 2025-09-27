<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileHelper
{
    /**
     * Single or multiple file upload
     * @param mixed $files Request file(s)
     * @param string $folder Folder name
     * @return array|string Uploaded file path(s)
     */
    public static function upload($files, $folder = 'uploads', $disk = 'public')
    {
        if (!$files) return null;

        $paths = [];
        // multiple image or file check
        if (is_array($files) || (is_object($files) && method_exists($files, 'count'))) {
            foreach ($files as $file) {
                $extension = $file->getClientOriginalExtension();
                $filename  = Str::uuid()->toString() . '.' . $extension;
                $paths[]   = $file->storeAs($folder, $filename, $disk);
            }
            return $paths;
        }
        //get file name
        $extension = $files->getClientOriginalExtension();
        $filename  = Str::uuid()->toString() . '.' . $extension;

        return $files->storeAs($folder, $filename, $disk);
    }

    public static function delete($filePaths, $disk = 'public')
    {
        if (!$filePaths) return false;

        Storage::disk($disk)->delete($filePaths);
        return true;
    }

    public static function update($newFiles, $oldFiles, $folder = 'uploads', $disk = 'public')
    {
        if ($newFiles) {
            self::delete($oldFiles, $disk);
            return self::upload($newFiles, $folder, $disk);
        }
        return $oldFiles;
    }

}
