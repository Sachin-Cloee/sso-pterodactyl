<?php

namespace WemX\Sso\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Pterodactyl\Models\User;

class SsoController
{
    /**
     * Attempt to login the user.
     */
    public function handle(string $token): RedirectResponse
    {
        if (!$this->hasToken($token)) {
            return redirect()->back()->withError('Token does not exist or has expired.');
        }

        try {
            Auth::loginUsingId($this->getToken($token));
            $this->invalidateToken($token);

            return redirect()->intended('/');
        } catch (\Throwable) {
            return redirect()->back()->withError('Something went wrong, please try again.');
        }
    }

    /**
     * Handle incoming SSO token requests.
     */
    public function webhook(Request $request): JsonResponse
    {
        if (!config('sso-wemx.secret')) {
            return response()->json(['success' => false, 'message' => 'Please configure a SSO Secret'], 403);
        }

        if ($request->input('sso_secret') !== config('sso-wemx.secret')) {
            return response()->json(['success' => false, 'message' => 'Please provide valid credentials'], 403);
        }

        $user = User::findOrFail($request->input('user_id'));
        if ($user->root_admin) {
            return response()->json(['success' => false, 'message' => 'You cannot automatically login to admin accounts.'], 501);
        }

        if ($user->use_totp) {
            return response()->json(['success' => false, 'message' => 'Logging into accounts with 2 Factor Authentication enabled is not supported.'], 501);
        }

        return response()->json(['success' => true, 'redirect' => route('sso-wemx.login', $this->generateToken($user->id))]);
    }

    /**
     * Generate a random access token and store the user_id inside
     * Tokens are only valid for 60 seconds
     */
    protected function generateToken(int|string $user_id): string
    {
        $token = Str::random(config('sso-wemx.token.length', 48));
        Cache::add($token, $user_id, config('sso-wemx.token.lifetime', 60));

        return $token;
    }

    /**
     * Returns the value of the token.
     */
    protected function getToken(string $token): mixed
    {
        return Cache::get($token);
    }

    /**
     * Returns true or false based on if the token exists.
     */
    protected function hasToken(string $token): bool
    {
        return Cache::has($token);
    }

    /**
     * Invalidates the token so it can no longer be used.
     */
    protected static function invalidateToken(string $token): void
    {
        Cache::forget($token);
    }
}
