<?php

namespace App\Http\Controllers;

use App\Models\GoogleDriveToken;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleDriveController extends Controller
{
    protected GoogleDriveService $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    /**
     * Redirect to Google OAuth for Drive access
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/drive'])
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->redirect();
    }

    /**
     * Handle Google OAuth callback for Drive access
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $token = $googleUser->token;
            $refreshToken = $googleUser->refreshToken;
            $expiresIn = $googleUser->expiresIn;

            // Store or update token in database
            GoogleDriveToken::updateOrCreate(
                ['user_id' => Auth::id()],
                [
                    'access_token' => $token,
                    'refresh_token' => $refreshToken,
                    'expires_in' => $expiresIn,
                    'expires_at' => now()->addSeconds($expiresIn),
                    'google_email' => $googleUser->getEmail(),
                ]
            );

            return redirect()->route('sorting.index')
                ->with('success', 'Google Drive connected successfully!');

        } catch (\Exception $e) {
            \Log::error('Google Drive OAuth failed: ' . $e->getMessage());
            return redirect()->route('sorting.index')
                ->with('error', 'Failed to connect to Google Drive. Please try again.');
        }
    }

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
