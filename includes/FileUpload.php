<?php
/**
 * FileUpload Helper Class
 * Secure file upload handling with MIME type validation
 */

class FileUpload {
    private $uploadDir;
    private $allowedTypes;
    private $maxSize;
    private $errors = [];
    private $uploadedFile;
    
    // Allowed MIME types mapping
    private static $mimeTypes = [
        // Images
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        // Documents
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        // Archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
    ];

    /**
     * Constructor
     * @param string $uploadDir Directory to upload files
     * @param array $allowedTypes Allowed file extensions ['jpg', 'png', 'gif']
     * @param int $maxSize Maximum file size in bytes (default 2MB)
     */
    public function __construct(string $uploadDir, array $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], int $maxSize = 2097152) {
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
        $this->allowedTypes = array_map('strtolower', $allowedTypes);
        $this->maxSize = $maxSize;
        
        // Create directory if not exists
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload a file
     * @param array $file $_FILES['fieldname']
     * @param string $prefix Filename prefix
     * @return bool|string Returns filename on success, false on failure
     */
    public function upload(array $file, string $prefix = 'file'): bool|string {
        $this->errors = [];
        
        // Check if file was uploaded
        if (!isset($file['error']) || is_array($file['error'])) {
            $this->errors[] = 'Parameter upload tidak valid.';
            return false;
        }

        // Check upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                $this->errors[] = 'Tidak ada file yang diupload.';
                return false;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $this->errors[] = 'Ukuran file melebihi batas maksimal.';
                return false;
            default:
                $this->errors[] = 'Terjadi kesalahan saat upload.';
                return false;
        }

        // Check file size
        if ($file['size'] > $this->maxSize) {
            $maxMB = round($this->maxSize / 1048576, 1);
            $this->errors[] = "Ukuran file terlalu besar (Maks {$maxMB}MB).";
            return false;
        }

        // Validate extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedTypes)) {
            $allowed = strtoupper(implode(', ', $this->allowedTypes));
            $this->errors[] = "Format file tidak didukung. Gunakan: {$allowed}";
            return false;
        }

        // Validate MIME type
        if (!$this->validateMimeType($file['tmp_name'], $extension)) {
            $this->errors[] = 'Tipe file tidak valid atau file telah dimodifikasi.';
            return false;
        }

        // Validate image dimensions (for images only)
        if ($this->isImage($extension)) {
            if (!$this->validateImage($file['tmp_name'])) {
                $this->errors[] = 'File bukan gambar yang valid.';
                return false;
            }
        }

        // Generate secure filename
        $newFilename = $this->generateFilename($prefix, $extension);
        $targetPath = $this->uploadDir . $newFilename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->errors[] = 'Gagal menyimpan file.';
            return false;
        }

        // Set secure permissions
        chmod($targetPath, 0644);

        $this->uploadedFile = $newFilename;
        return $newFilename;
    }

    /**
     * Validate MIME type using finfo
     */
    private function validateMimeType(string $tmpFile, string $extension): bool {
        if (!isset(self::$mimeTypes[$extension])) {
            return false;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($tmpFile);
        
        $expectedMime = self::$mimeTypes[$extension];
        
        // Handle multiple possible MIME types for same extension
        if (is_array($expectedMime)) {
            return in_array($detectedMime, $expectedMime);
        }
        
        return $detectedMime === $expectedMime;
    }

    /**
     * Check if extension is an image type
     */
    private function isImage(string $extension): bool {
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    /**
     * Validate image file
     */
    private function validateImage(string $tmpFile): bool {
        $imageInfo = @getimagesize($tmpFile);
        if ($imageInfo === false) {
            return false;
        }

        // Check image type
        $allowedImageTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
        return in_array($imageInfo[2], $allowedImageTypes);
    }

    /**
     * Generate secure unique filename
     */
    private function generateFilename(string $prefix, string $extension): string {
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        return "{$prefix}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get upload errors
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function getError(): string {
        return $this->errors[0] ?? '';
    }

    /**
     * Get uploaded filename
     */
    public function getUploadedFile(): ?string {
        return $this->uploadedFile;
    }

    /**
     * Delete a file
     */
    public function delete(string $filename): bool {
        $filepath = $this->uploadDir . basename($filename);
        if (file_exists($filepath) && is_file($filepath)) {
            return unlink($filepath);
        }
        return false;
    }

    /**
     * Get full path of uploaded file
     */
    public function getFullPath(string $filename): string {
        return $this->uploadDir . basename($filename);
    }

    /**
     * Static helper for quick image upload
     */
    public static function uploadImage(array $file, string $uploadDir, string $prefix = 'img', int $maxSize = 2097152): array {
        $uploader = new self($uploadDir, ['jpg', 'jpeg', 'png', 'gif', 'webp'], $maxSize);
        $result = $uploader->upload($file, $prefix);
        
        return [
            'success' => $result !== false,
            'filename' => $result ?: null,
            'error' => $uploader->getError(),
            'errors' => $uploader->getErrors()
        ];
    }

    /**
     * Static helper for quick thumbnail upload
     */
    public static function uploadThumbnail(array $file, string $prefix = 'thumb'): array {
        return self::uploadImage($file, 'assets/uploads/thumbnails/', $prefix, 5242880); // 5MB
    }

    /**
     * Static helper for quick avatar upload
     */
    public static function uploadAvatar(array $file, int $userId): array {
        return self::uploadImage($file, 'assets/uploads/avatars/', "avatar_{$userId}", 2097152); // 2MB
    }
}
