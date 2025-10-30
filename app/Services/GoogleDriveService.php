<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    protected Client $client;
    protected Drive $service;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('google.client_id'));
        $this->client->setClientSecret(config('google.client_secret'));
        $this->client->setAccessType('offline');
        $this->client->setApprovalPrompt('force');
        $this->client->setScopes([Drive::DRIVE]);

        // Set refresh token if available
        if (config('google.refresh_token')) {
            $this->client->refreshToken(config('google.refresh_token'));
        }

        $this->service = new Drive($this->client);
    }

    /**
     * Get authorization URL for OAuth
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Exchange authorization code for access token
     */
    public function authenticate(string $code): array
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        return $token;
    }

    /**
     * Set access token
     */
    public function setAccessToken(string $token): void
    {
        $this->client->setAccessToken($token);
    }

    /**
     * List folders in Google Drive
     */
    public function listFolders(?string $parentId = null): array
    {
        $query = "mimeType='application/vnd.google-apps.folder' and trashed=false";

        if ($parentId) {
            $query .= " and '{$parentId}' in parents";
        }

        $optParams = [
            'q' => $query,
            'fields' => 'files(id, name, parents, modifiedTime, webViewLink)',
            'orderBy' => 'name',
        ];

        $results = $this->service->files->listFiles($optParams);

        return array_map(function ($file) {
            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'parents' => $file->getParents(),
                'modifiedTime' => $file->getModifiedTime(),
                'webViewLink' => $file->getWebViewLink(),
            ];
        }, $results->getFiles());
    }

    /**
     * List image files in a folder
     */
    public function listImages(string $folderId): array
    {
        $extensions = config('google.supported_extensions');
        $queryParts = array_map(fn($ext) => "name contains '.{$ext}'", $extensions);
        $extensionQuery = '(' . implode(' or ', $queryParts) . ')';

        $query = "'{$folderId}' in parents and {$extensionQuery} and trashed=false";

        $optParams = [
            'q' => $query,
            'fields' => 'files(id, name, mimeType, size, thumbnailLink, webContentLink, modifiedTime, imageMediaMetadata)',
            'orderBy' => 'name',
        ];

        $results = $this->service->files->listFiles($optParams);

        return array_map(function ($file) {
            $metadata = $file->getImageMediaMetadata();
            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize(),
                'thumbnailLink' => $file->getThumbnailLink(),
                'webContentLink' => $file->getWebContentLink(),
                'modifiedTime' => $file->getModifiedTime(),
                'width' => $metadata ? $metadata->getWidth() : null,
                'height' => $metadata ? $metadata->getHeight() : null,
            ];
        }, $results->getFiles());
    }

    /**
     * Create a folder in Google Drive
     */
    public function createFolder(string $name, string $parentId): array
    {
        $fileMetadata = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId]
        ]);

        $folder = $this->service->files->create($fileMetadata, [
            'fields' => 'id, name, parents'
        ]);

        return [
            'id' => $folder->getId(),
            'name' => $folder->getName(),
            'parents' => $folder->getParents(),
        ];
    }

    /**
     * Move file to another folder
     */
    public function moveFile(string $fileId, string $newParentId): bool
    {
        try {
            // Get the file's current parents
            $file = $this->service->files->get($fileId, ['fields' => 'parents']);
            $previousParents = join(',', $file->getParents());

            // Move the file to the new folder
            $this->service->files->update($fileId, new DriveFile(), [
                'addParents' => $newParentId,
                'removeParents' => $previousParents,
                'fields' => 'id, parents'
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to move file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get file metadata
     */
    public function getFile(string $fileId): ?array
    {
        try {
            $file = $this->service->files->get($fileId, [
                'fields' => 'id, name, mimeType, size, thumbnailLink, webContentLink, modifiedTime, imageMediaMetadata'
            ]);

            $metadata = $file->getImageMediaMetadata();

            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize(),
                'thumbnailLink' => $file->getThumbnailLink(),
                'webContentLink' => $file->getWebContentLink(),
                'modifiedTime' => $file->getModifiedTime(),
                'width' => $metadata ? $metadata->getWidth() : null,
                'height' => $metadata ? $metadata->getHeight() : null,
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to get file: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get thumbnail URL with proper authentication
     */
    public function getThumbnailUrl(string $fileId, int $size = 800): string
    {
        return "https://drive.google.com/thumbnail?id={$fileId}&sz=w{$size}";
    }

    /**
     * Download file content
     */
    public function downloadFile(string $fileId): ?string
    {
        try {
            $response = $this->service->files->get($fileId, ['alt' => 'media']);
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            \Log::error('Failed to download file: ' . $e->getMessage());
            return null;
        }
    }
}
