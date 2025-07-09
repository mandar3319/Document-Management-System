<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Style\Color;

class PresentationConverter {
    private $sourcePath;
    private $documentId;
    private $psScript;

    public function __construct($sourcePath, $documentId) {
        $this->sourcePath = $sourcePath;
        $this->documentId = $documentId;
        $this->psScript = __DIR__ . '/../scripts/convert_pptx.ps1';
    }

    public function getSlideImages() {
        // Check session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'error' => 'Session expired. Please refresh the page.',
                'slides' => []
            ];
        }

        // Create output directory under the web-accessible path
        $imagesDir = dirname(__DIR__) . '/uploads/temp/' . $this->documentId;
        if (!file_exists($imagesDir)) {
            if (!mkdir($imagesDir, 0777, true)) {
                error_log("Failed to create directory: " . $imagesDir);
                return [
                    'success' => false,
                    'error' => 'Failed to create output directory',
                    'slides' => []
                ];
            }
            // Ensure proper permissions
            chmod($imagesDir, 0777);
        }

        // Convert paths to absolute paths
        $absoluteSourcePath = realpath($this->sourcePath);
        $absoluteImagesDir = realpath($imagesDir);
        $absoluteScriptPath = realpath($this->psScript);

        if (!$absoluteSourcePath) {
            error_log("Source file not found: " . $this->sourcePath);
            return [
                'success' => false,
                'error' => 'PowerPoint file not found: ' . $this->sourcePath,
                'slides' => []
            ];
        }

        if (!$absoluteScriptPath) {
            error_log("PowerShell script not found: " . $this->psScript);
            return [
                'success' => false,
                'error' => 'PowerShell script not found: ' . $this->psScript,
                'slides' => []
            ];
        }

        // Execute PowerShell script with absolute paths and verbose output
        $command = sprintf(
            'powershell.exe -ExecutionPolicy Bypass -NoProfile -File "%s" -PptxFile "%s" -OutputDir "%s" -Verbose 2>&1',
            $absoluteScriptPath,
            $absoluteSourcePath,
            $absoluteImagesDir
        );

        error_log("Executing command: " . $command);
        
        exec($command, $output, $returnCode);

        // Log any errors
        if ($returnCode !== 0) {
            $errorMessage = implode("\n", $output);
            error_log("PowerShell script error: " . $errorMessage);
            return [
                'success' => false,
                'error' => $errorMessage,
                'slides' => []
            ];
        }

        // Get list of generated images and convert to web URLs
        $slides = glob($imagesDir . '/slide-*.jpg');
        sort($slides, SORT_NATURAL);
        
        if (empty($slides)) {
            error_log("No slides were generated");
            // If no slides were generated, create a preview slide
            $this->createPreviewSlide($imagesDir);
            $slides = glob($imagesDir . '/slide-*.jpg');
        }

        // Set proper permissions for generated files
        foreach ($slides as $slide) {
            chmod($slide, 0644);
        }
        
        // Convert file paths to web URLs
        $webSlides = array_map(function($slidePath) {
            // Get the path relative to the document root
            $relativePath = str_replace(dirname(__DIR__), '', $slidePath);
            // Convert Windows backslashes to forward slashes
            $webPath = str_replace('\\', '/', $relativePath);
            // Remove any double slashes
            $webPath = preg_replace('#/+#', '/', $webPath);
            // Create the web-accessible URL with timestamp to prevent caching
            return '/dmsportal' . $webPath . '?v=' . time();
        }, $slides);
        
        error_log("Generated web paths: " . print_r($webSlides, true));
        
        return [
            'success' => true,
            'error' => null,
            'slides' => $webSlides,
            'total_slides' => count($slides)
        ];
    }

    private function createPreviewSlide($imagesDir) {
        // Increased dimensions for 4K support
        $width = 3840;
        $height = 2160;
        $image = imagecreatetruecolor($width, $height);

        // Enable alpha blending for better quality
        imagealphablending($image, true);
        imagesavealpha($image, true);

        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $blue = imagecolorallocate($image, 51, 122, 183);
        $gray = imagecolorallocate($image, 238, 238, 238);

        // Fill background
        imagefill($image, 0, 0, $white);
        imagefilledrectangle($image, 0, 0, $width, 300, $blue);
        imagefilledrectangle($image, 0, $height - 200, $width, $height, $gray);

        // Add text with larger font sizes
        $filename = basename($this->sourcePath);
        $filesize = $this->formatFileSize(filesize($this->sourcePath));

        // Use larger font sizes for 4K resolution
        $this->drawText($image, "PowerPoint Preview", $width/2, 160, 7, $white, true);
        $this->drawText($image, $filename, $width/2, $height/2 - 40, 6, $black, true);
        $this->drawText($image, "File Size: " . $filesize, $width/2, $height/2 + 40, 5, $black, true);
        $this->drawText($image, "Click Download to view the full presentation", $width/2, $height - 120, 5, $black, true);

        // Save with maximum quality
        imagejpeg($image, $imagesDir . "/slide-001.jpg", 100);
        imagedestroy($image);
    }

    private function drawText($image, $text, $x, $y, $size, $color, $center = false) {
        if ($center) {
            $textWidth = strlen($text) * imagefontwidth($size);
            $x = $x - ($textWidth / 2);
        }
        imagestring($image, $size, $x, $y, $text, $color);
    }

    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
} 
