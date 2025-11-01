<?php

namespace App\Http\Controllers;

use App\Models\GoogleDriveToken;
use App\Services\GoogleDriveService;
use App\Services\RawImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleDriveController extends Controller
{
    protected GoogleDriveService $driveService;
    protected RawImageService $rawImageService;

    public function __construct(GoogleDriveService $driveService, RawImageService $rawImageService)
    {
        $this->driveService = $driveService;
        $this->rawImageService = $rawImageService;
    }

    /**
     * Redirect to Google OAuth for Drive access
     */
    public function redirectToGoogle()
    {
        // Store in session that this is a Drive access request
        session(['google_oauth_type' => 'drive_access']);

        return Socialite::driver('google')
            ->redirectUrl(route('auth.google.callback'))
            ->scopes(['https://www.googleapis.com/auth/drive'])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent'
            ])
            ->redirect();
    }

    // handleGoogleCallback moved to SocialiteController to handle both auth and drive flows

    /**
     * Disconnect Google Drive
     */
    public function disconnect()
    {
        GoogleDriveToken::where('user_id', Auth::id())->delete();

        return redirect()->route('sorting.index')
            ->with('success', 'Google Drive disconnected successfully');
    }

    /**
     * Check connection status
     */
    public function checkConnection()
    {
        $token = GoogleDriveToken::where('user_id', Auth::id())->first();

        return response()->json([
            'connected' => $token !== null,
            'google_email' => $token?->google_email,
        ]);
    }

    /**
     * List folders
     */
    public function listFolders(Request $request)
    {
        try {
            $this->setTokenFromDatabase();
            $parentId = $request->get('parent_id');
            $folders = $this->driveService->listFolders($parentId);

            return response()->json([
                'success' => true,
                'folders' => $folders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List images in folder
     */
    public function listImages(Request $request)
    {
        try {
            $this->setTokenFromDatabase();
            $folderId = $request->get('folder_id');

            if (!$folderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Folder ID is required'
                ], 400);
            }

            $images = $this->driveService->listImages($folderId);

            return response()->json([
                'success' => true,
                'images' => $images
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create folder
     */
    public function createFolder(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'required|string'
        ]);

        try {
            $this->setTokenFromDatabase();
            $folder = $this->driveService->createFolder(
                $request->name,
                $request->parent_id
            );

            return response()->json([
                'success' => true,
                'folder' => $folder
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Move file
     */
    public function moveFile(Request $request)
    {
        $request->validate([
            'file_id' => 'required|string',
            'destination_folder_id' => 'required|string'
        ]);

        try {
            $this->setTokenFromDatabase();
            $result = $this->driveService->moveFile(
                $request->file_id,
                $request->destination_folder_id
            );

            return response()->json([
                'success' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file details
     */
    public function getFile(Request $request, string $fileId)
    {
        try {
            $this->setTokenFromDatabase();
            $file = $this->driveService->getFile($fileId);

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'file' => $file
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get preview for RAW or other image files
     * This endpoint handles RAW conversion and caching
     */
    public function getPreview(Request $request, string $fileId)
    {
        try {
            \Log::info("Preview request received for file ID: {$fileId}");

            $this->setTokenFromDatabase();

            // Use Google Drive Service to get file metadata (properly authenticated)
            $driveService = new \Google\Service\Drive($this->driveService->getClient());
            $file = $driveService->files->get($fileId, [
                'fields' => 'name,mimeType,fileExtension'
            ]);

            $fileName = $file->getName() ?? 'unknown';
            $mimeType = $file->getMimeType() ?? 'unknown';

            // Get extension from fileExtension field or from filename
            $extension = '';
            $fileExtension = $file->getFileExtension();
            if (!empty($fileExtension)) {
                $extension = strtolower($fileExtension);
            } elseif ($fileName !== 'unknown') {
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            }

            \Log::info("File metadata", [
                'name' => $fileName,
                'extension' => $extension,
                'mimeType' => $mimeType
            ]);

            // Check if it's a RAW format
            $rawFormats = ['arw', 'cr2', 'cr3', 'nef', 'nrw', 'raf', 'rw2', 'orf', 'pef', 'dng'];
            $isRaw = in_array($extension, $rawFormats);

            if ($isRaw) {
                \Log::info("Detected RAW format, attempting to generate preview");

                // Use RAW image service to get or create preview
                $previewUrl = $this->rawImageService->getOrCreatePreview(
                    $fileId,
                    $fileName,
                    $this->driveService->getClient()
                );

                if ($previewUrl) {
                    \Log::info("Preview created successfully, redirecting to: {$previewUrl}");
                    // Redirect to the cached preview
                    return redirect($previewUrl);
                }

                \Log::warning("Failed to generate preview for RAW file: {$fileName}");

                // If preview creation failed, return a placeholder image
                return response()->file(public_path('images/no-preview.svg'), [
                    'Content-Type' => 'image/svg+xml',
                ]);
            }

            // For non-RAW files, use standard Google Drive preview
            \Log::info("Non-RAW file, redirecting to Google Drive preview");
            return redirect("https://drive.google.com/uc?id={$fileId}&export=view");

        } catch (\Exception $e) {
            \Log::error('Preview fetch failed: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'file_id' => $fileId
            ]);

            // Return error as image instead of JSON to prevent frontend breaking
            return response()->file(public_path('images/error-preview.svg'), [
                'Content-Type' => 'image/svg+xml',
            ]);
        }
    }

    /**
     * Proxy thumbnail from Google Drive
     * This allows us to display thumbnails with authentication
     */
    public function getThumbnail(Request $request, string $fileId)
    {
        try {
            $this->setTokenFromDatabase();

            // Get file metadata to get thumbnail link
            $file = $this->driveService->getClient()->getHttpClient()->get(
                "https://www.googleapis.com/drive/v3/files/{$fileId}?fields=thumbnailLink"
            );

            $fileData = json_decode($file->getBody()->getContents(), true);

            if (!isset($fileData['thumbnailLink'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No thumbnail available'
                ], 404);
            }

            // Download thumbnail
            $thumbnail = $this->driveService->getClient()->getHttpClient()->get($fileData['thumbnailLink']);

            return response($thumbnail->getBody()->getContents())
                ->header('Content-Type', 'image/jpeg')
                ->header('Cache-Control', 'public, max-age=86400'); // Cache for 24 hours

        } catch (\Exception $e) {
            \Log::error('Thumbnail fetch failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set access token from database
     */
    private function setTokenFromDatabase()
    {
        $tokenRecord = GoogleDriveToken::where('user_id', Auth::id())->first();

        if (!$tokenRecord) {
            throw new \Exception('Google Drive not connected. Please authorize first.');
        }

        // Check if token is expired and refresh if needed
        if ($tokenRecord->isExpired() && $tokenRecord->refresh_token) {
            // TODO: Implement token refresh logic
            // For now, just use the existing token
        }

        $this->driveService->setAccessToken(json_encode($tokenRecord->getTokenArray()));
    }
}
