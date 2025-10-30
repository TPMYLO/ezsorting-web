<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class GoogleDriveController extends Controller
{
    protected GoogleDriveService $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        return redirect($this->driveService->getAuthUrl());
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        $code = $request->get('code');

        if (!$code) {
            return redirect()->route('sorting.index')->with('error', 'Authorization failed');
        }

        try {
            $token = $this->driveService->authenticate($code);

            // Store access token in session
            Session::put('google_drive_token', $token);

            return redirect()->route('sorting.index')->with('success', 'Google Drive connected successfully');
        } catch (\Exception $e) {
            \Log::error('Google OAuth failed: ' . $e->getMessage());
            return redirect()->route('sorting.index')->with('error', 'Failed to connect to Google Drive');
        }
    }

    /**
     * List folders
     */
    public function listFolders(Request $request)
    {
        try {
            $this->setTokenFromSession();
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
            $this->setTokenFromSession();
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
            $this->setTokenFromSession();
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
            $this->setTokenFromSession();
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
            $this->setTokenFromSession();
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
     * Set access token from session
     */
    private function setTokenFromSession()
    {
        $token = Session::get('google_drive_token');

        if (!$token) {
            throw new \Exception('Google Drive not connected. Please authorize first.');
        }

        $this->driveService->setAccessToken(json_encode($token));
    }
}
