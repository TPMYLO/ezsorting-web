<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class RawImageService
{
    /**
     * Convert RAW image to JPG for preview
     * Uses Google Drive's thumbnail/preview generation capabilities
     *
     * @param string $fileId Google Drive file ID
     * @param string $fileName Original file name
     * @param \Google\Client $client Authenticated Google Client
     * @return string|null Path to cached preview image
     */
    public function getOrCreatePreview(string $fileId, string $fileName, \Google\Client $client): ?string
    {
        // Generate cache filename
        $cacheFileName = $fileId . '.jpg';
        $cachePath = 'thumbnails/' . $cacheFileName;

        // Check if preview already exists in cache
        if (Storage::disk('public')->exists($cachePath)) {
            Log::info("RAW preview cache hit for file: {$fileName}");
            return Storage::disk('public')->url($cachePath);
        }

        try {
            $driveService = new \Google\Service\Drive($client);

            Log::info("Attempting to create RAW preview for: {$fileName} (ID: {$fileId})");

            // Method 1: Try to get thumbnail from Google Drive
            try {
                $file = $driveService->files->get($fileId, [
                    'fields' => 'thumbnailLink,hasThumbnail,contentHints/thumbnail'
                ]);

                $hasThumbnail = $file->getHasThumbnail();
                Log::info("File metadata retrieved", ['hasThumbnail' => $hasThumbnail]);

                if ($hasThumbnail) {
                    $thumbnailLink = $file->getThumbnailLink();
                    if ($thumbnailLink) {
                        // Download from thumbnailLink with larger size
                        $thumbnailUrl = str_replace('=s220', '=s1600', $thumbnailLink);

                        Log::info("Downloading thumbnail from: {$thumbnailUrl}");

                        // Use authenticated HTTP client
                        $httpClient = $client->authorize();
                        $thumbnailResponse = $httpClient->get($thumbnailUrl);
                        $thumbnailContent = $thumbnailResponse->getBody()->getContents();

                        if (strlen($thumbnailContent) > 0) {
                            Storage::disk('public')->put($cachePath, $thumbnailContent);
                            Log::info("RAW preview created from thumbnailLink");
                            return Storage::disk('public')->url($cachePath);
                        }
                    }

                    // Try contentHints thumbnail (base64 encoded)
                    $contentHints = $file->getContentHints();
                    if ($contentHints && $contentHints->getThumbnail()) {
                        $thumbnailData = base64_decode($contentHints->getThumbnail()->getImage());
                        if ($thumbnailData) {
                            Storage::disk('public')->put($cachePath, $thumbnailData);
                            Log::info("RAW preview created from contentHints");
                            return Storage::disk('public')->url($cachePath);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Method 1 failed: " . $e->getMessage());
            }

            // Method 2: Try to download and extract embedded preview from RAW file
            try {
                Log::info("Attempting to download RAW file for preview extraction");

                // Download first 5MB of the RAW file (usually contains embedded JPEG preview)
                $response = $driveService->files->get($fileId, [
                    'alt' => 'media'
                ]);

                // Get the body content (limit to 5MB)
                $rawContent = $response->getBody()->read(5242880); // 5MB

                // Try to extract embedded JPEG from RAW file
                $preview = $this->extractEmbeddedPreview($rawContent);
                if ($preview) {
                    Storage::disk('public')->put($cachePath, $preview);
                    Log::info("RAW preview extracted from embedded JPEG");
                    return Storage::disk('public')->url($cachePath);
                }
            } catch (\Exception $e) {
                Log::warning("Method 2 failed: " . $e->getMessage());
            }

            Log::warning("Could not create preview for RAW file: {$fileName}");
            return null;

        } catch (\Exception $e) {
            Log::error("Failed to create RAW preview for {$fileName}: " . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Extract embedded JPEG preview from RAW file data
     * Most RAW formats contain an embedded full-size JPEG preview
     */
    private function extractEmbeddedPreview(string $rawData): ?string
    {
        // Look for JPEG markers in the RAW data
        // JPEG files start with FFD8 and end with FFD9
        $jpegStart = "\xFF\xD8\xFF";
        $jpegEnd = "\xFF\xD9";

        $startPos = strpos($rawData, $jpegStart);
        if ($startPos === false) {
            return null;
        }

        // Find the end marker after the start
        $endPos = strpos($rawData, $jpegEnd, $startPos);
        if ($endPos === false) {
            return null;
        }

        // Extract the JPEG data (including end marker)
        $jpegData = substr($rawData, $startPos, $endPos - $startPos + 2);

        // Verify it's a valid JPEG
        $imageInfo = @getimagesizefromstring($jpegData);
        if ($imageInfo !== false && $imageInfo[2] === IMAGETYPE_JPEG) {
            return $jpegData;
        }

        return null;
    }

    /**
     * Clear cache for a specific file
     */
    public function clearCache(string $fileId): void
    {
        $cacheFileName = $fileId . '.jpg';
        $cachePath = 'thumbnails/' . $cacheFileName;

        if (Storage::disk('public')->exists($cachePath)) {
            Storage::disk('public')->delete($cachePath);
            Log::info("Cleared RAW preview cache for file ID: {$fileId}");
        }
    }

    /**
     * Clear all cached previews older than specified days
     */
    public function clearOldCache(int $days = 7): void
    {
        $files = Storage::disk('public')->files('thumbnails');
        $threshold = now()->subDays($days)->timestamp;

        foreach ($files as $file) {
            $lastModified = Storage::disk('public')->lastModified($file);

            if ($lastModified < $threshold) {
                Storage::disk('public')->delete($file);
            }
        }

        Log::info("Cleared RAW preview cache older than {$days} days");
    }
}
