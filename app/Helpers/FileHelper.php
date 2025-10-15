<?php

namespace App\Helpers;

use Cloudinary\Cloudinary;


class FileHelper
{
    public static function upload($files, $userType = 'user')
    {
        if (!$files) return null;

        $paths = [];
        //Cloudinary init manually
        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => [
                'secure' => true,
            ],
        ]);

        if (!is_array($files) && !(is_object($files) && method_exists($files, 'count'))) {
            $files = [$files];
        }
        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $imageExtensions = ['jpg','jpeg','png','webp'];
            $fileType = in_array($extension, $imageExtensions) ? 'image' : 'document';

            // Windows Local: SSL skip (development only)
            $upload = $cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                [
                    'folder' => "$userType/$fileType",
                    'resource_type' => 'auto',
                    'ssl_verify' => false, // !!! crucial for Windows local
                ]
            );
            $paths[] = [
                'url'       => $upload['secure_url'],
                'public_id' => $upload['public_id'],
                'type'      => $fileType,
            ];
        }
        return $paths;
    }

    public static function delete($fileInfos)
    {
        if (!$fileInfos) return false;

        foreach ((array)$fileInfos as $file) {
            if (isset($file['public_id'])) {
                Cloudinary::destroy($file['public_id']);
            }
        }

        return true;
    }

    public static function update($newFiles, $oldFiles, $userType = 'user')
    {
        if ($newFiles) {
            self::delete($oldFiles);
            return self::upload($newFiles, $userType);
        }
        return $oldFiles;
    }
}
