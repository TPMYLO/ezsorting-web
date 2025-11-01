<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\GoogleDriveToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect to Google for authentication
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    /**
     * Handle Google callback for authentication OR Drive access
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            // Check if this is a Drive access request (based on session)
            $oauthType = session('google_oauth_type');
            $isDriveAccess = $oauthType === 'drive_access';

            \Log::info('Google OAuth callback started', [
                'oauth_type' => $oauthType,
                'is_drive_access' => $isDriveAccess,
                'is_authenticated' => Auth::check(),
                'user_id' => Auth::id(),
            ]);

            $googleUser = Socialite::driver('google')->user();

            \Log::info('Google user retrieved', [
                'google_email' => $googleUser->getEmail(),
                'has_token' => !empty($googleUser->token),
                'has_refresh_token' => !empty($googleUser->refreshToken),
            ]);

            // Clear the session flag
            session()->forget('google_oauth_type');

            if ($isDriveAccess && Auth::check()) {
                // This is for Drive access - user is already logged in
                \Log::info('Processing as Drive access flow');
                return $this->handleDriveAccess($googleUser);
            } else {
                // This is for user authentication
                \Log::info('Processing as user authentication flow');
                return $this->handleUserAuthentication($googleUser);
            }

        } catch (\Exception $e) {
            \Log::error('Google OAuth failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'oauth_type' => session('google_oauth_type'),
                'is_authenticated' => Auth::check(),
            ]);

            if (Auth::check()) {
                return redirect()->route('sorting.index')
                    ->with('error', 'Failed to connect to Google Drive: ' . $e->getMessage());
            } else {
                return redirect()->route('login')
                    ->with('error', 'Failed to login with Google: ' . $e->getMessage());
            }
        }
    }

    /**
     * Handle user authentication flow
     */
    private function handleUserAuthentication($googleUser)
    {
        // Find or create user
        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            // Update google_id if not set
            if (!$user->google_id) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]);
            }
        } else {
            // Create new user
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => Hash::make(Str::random(24)), // Random password for OAuth users
                'email_verified_at' => now(),
            ]);
        }

        // Login user
        Auth::login($user, true);

        return redirect()->intended(route('sorting.index'))
            ->with('success', 'Successfully logged in with Google!');
    }

    /**
     * Handle Google Drive access flow
     */
    private function handleDriveAccess($googleUser)
    {
        // Validate user is authenticated
        if (!Auth::check()) {
            \Log::error('Google Drive callback: User not authenticated');
            return redirect()->route('login')
                ->with('error', 'Please log in first before connecting Google Drive.');
        }

        $userId = Auth::id();
        if (!$userId) {
            throw new \Exception('User ID is null after authentication check');
        }

        $token = $googleUser->token;
        $refreshToken = $googleUser->refreshToken;
        $expiresIn = $googleUser->expiresIn;

        // Store or update token in database
        $driveToken = GoogleDriveToken::updateOrCreate(
            ['user_id' => $userId],
            [
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'expires_in' => $expiresIn,
                'expires_at' => now()->addSeconds($expiresIn),
                'google_email' => $googleUser->getEmail(),
            ]
        );

        // Verify token was saved successfully
        if (!$driveToken || !$driveToken->exists) {
            throw new \Exception('Failed to save Google Drive token to database');
        }

        \Log::info('Google Drive connected successfully for user: ' . $userId);

        return redirect()->route('sorting.index')
            ->with('success', 'Google Drive connected successfully!');
    }
}
