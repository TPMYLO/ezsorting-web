<?php

namespace App\Http\Controllers;

use App\Models\SortingSession;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SortingController extends Controller
{
    protected GoogleDriveService $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    /**
     * Display the sorting interface
     */
    public function index()
    {
        // Get or create active session
        $session = SortingSession::where('user_id', Auth::id())
            ->whereIn('status', ['setup', 'active', 'paused'])
            ->first();

        return Inertia::render('Sorting/Index', [
            'session' => $session,
            'googleConnected' => session()->has('google_drive_token'),
        ]);
    }

    /**
     * Create new sorting session
     */
    public function createSession(Request $request)
    {
        $request->validate([
            'source_folder_id' => 'required|string',
            'source_folder_name' => 'required|string',
        ]);

        try {
            // Get images from Google Drive
            $this->setTokenFromSession();
            $images = $this->driveService->listImages($request->source_folder_id);

            // Create session
            $session = SortingSession::create([
                'user_id' => Auth::id(),
                'source_folder_id' => $request->source_folder_id,
                'source_folder_name' => $request->source_folder_name,
                'images' => $images,
                'total_images' => count($images),
                'sorted_images' => 0,
                'remaining_images' => count($images),
                'current_image_index' => 0,
                'status' => 'setup',
            ]);

            return response()->json([
                'success' => true,
                'session' => $session
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add destination folder to session
     */
    public function addDestinationFolder(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sorting_sessions,id',
            'folder_name' => 'required|string|max:255',
        ]);

        try {
            $session = SortingSession::findOrFail($request->session_id);

            // Check if user owns this session
            if ($session->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Create folder in Google Drive
            $this->setTokenFromSession();
            $folder = $this->driveService->createFolder(
                $request->folder_name,
                $session->source_folder_id
            );

            // Add to session
            $destinationFolders = $session->destination_folders ?? [];
            $destinationFolders[] = $folder;

            $session->update([
                'destination_folders' => $destinationFolders
            ]);

            return response()->json([
                'success' => true,
                'folder' => $folder,
                'session' => $session->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove destination folder from session
     */
    public function removeDestinationFolder(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sorting_sessions,id',
            'folder_id' => 'required|string',
        ]);

        try {
            $session = SortingSession::findOrFail($request->session_id);

            if ($session->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $destinationFolders = collect($session->destination_folders ?? [])
                ->reject(fn($folder) => $folder['id'] === $request->folder_id)
                ->values()
                ->toArray();

            $session->update([
                'destination_folders' => $destinationFolders
            ]);

            return response()->json([
                'success' => true,
                'session' => $session->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start sorting
     */
    public function startSorting(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sorting_sessions,id',
        ]);

        try {
            $session = SortingSession::findOrFail($request->session_id);

            if ($session->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            if (empty($session->destination_folders)) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one destination folder is required'
                ], 400);
            }

            $session->update(['status' => 'active']);

            return response()->json([
                'success' => true,
                'session' => $session->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sort image to folder
     */
    public function sortImage(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sorting_sessions,id',
            'image_index' => 'required|integer',
            'destination_folder_id' => 'required|string',
        ]);

        try {
            $session = SortingSession::findOrFail($request->session_id);

            if ($session->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $images = $session->images;
            $imageIndex = $request->image_index;

            if (!isset($images[$imageIndex])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image index'
                ], 400);
            }

            $image = $images[$imageIndex];

            // Move file in Google Drive
            $this->setTokenFromSession();
            $result = $this->driveService->moveFile(
                $image['id'],
                $request->destination_folder_id
            );

            if (!$result) {
                throw new \Exception('Failed to move file');
            }

            // Update session
            $sortedImages = $session->sorted_images + 1;
            $remainingImages = $session->remaining_images - 1;
            $currentIndex = min($imageIndex + 1, $session->total_images - 1);

            $session->update([
                'sorted_images' => $sortedImages,
                'remaining_images' => $remainingImages,
                'current_image_index' => $currentIndex,
                'status' => $remainingImages <= 0 ? 'completed' : 'active',
                'completed_at' => $remainingImages <= 0 ? now() : null,
            ]);

            return response()->json([
                'success' => true,
                'session' => $session->fresh(),
                'next_image' => $remainingImages > 0 ? $images[$currentIndex] : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Skip image
     */
    public function skipImage(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sorting_sessions,id',
            'direction' => 'required|in:next,previous',
        ]);

        try {
            $session = SortingSession::findOrFail($request->session_id);

            if ($session->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $currentIndex = $session->current_image_index;
            $totalImages = $session->total_images;

            if ($request->direction === 'next') {
                $newIndex = min($currentIndex + 1, $totalImages - 1);
            } else {
                $newIndex = max($currentIndex - 1, 0);
            }

            $session->update([
                'current_image_index' => $newIndex
            ]);

            return response()->json([
                'success' => true,
                'session' => $session->fresh(),
                'current_image' => $session->images[$newIndex] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset/Delete session
     */
    public function resetSession(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sorting_sessions,id',
        ]);

        try {
            $session = SortingSession::findOrFail($request->session_id);

            if ($session->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $session->delete();

            return response()->json([
                'success' => true
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
        $token = session('google_drive_token');

        if (!$token) {
            throw new \Exception('Google Drive not connected. Please authorize first.');
        }

        $this->driveService->setAccessToken(json_encode($token));
    }
}
