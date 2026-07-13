<?php
namespace App\Core\Helpers;

use App\Core\Exceptions\AppException;

class UploadHelper
{
    /**
     * Securly uploads an image and returns the file path.
     * 
     * @param array $file The $_FILES array item
     * @param string $subDir The subdirectory inside uploads/
     * @return string The relative path to the uploaded file
     * @throws AppException if upload is invalid or malicious
     */
    public static function uploadImage($file, $subDir = '')
    {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new AppException("Upload failed or no file provided", 400);
        }

        $tmpPath = $file['tmp_name'];
        
        // 1. Verify MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($mimeType, $allowedMimes)) {
            throw new AppException("Invalid file type. Only JPG and PNG are allowed.", 400);
        }

        // 2. Verify Extension Whitelist
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpeg', 'png', 'jpg'];
        if (!in_array($ext, $allowedExts)) {
            throw new AppException("Invalid file extension. Only JPG and PNG are allowed.", 400);
        }

        // 3. Generate a safe random filename
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        
        $uploadDir = __DIR__ . '/../../../uploads/';
        if ($subDir) {
            $uploadDir .= trim($subDir, '/') . '/';
        }

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($tmpPath, $destination)) {
            throw new AppException("Failed to move uploaded file.", 500);
        }

        $path = '/uploads/';
        if ($subDir) {
            $path .= trim($subDir, '/') . '/';
        }
        return $path . $filename;
    }
}
