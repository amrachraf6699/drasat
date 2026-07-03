<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Storefront\CartService;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class OAuthController extends Controller
{
    public function __construct(private CartService $carts)
    {
    }

    public function redirect(string $provider): SymfonyRedirectResponse
    {
        $this->ensureProvider($provider);

        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureProvider($provider);
        $this->recoverOauthQuery($request);

        if ($request->filled('error')) {
            return $this->redirectToLoginWithOauthError(__('storefront.auth.oauth_cancelled'));
        }

        if (! $request->filled('code')) {
            return $this->redirectToLoginWithOauthError(__('storefront.auth.oauth_failed'));
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (ClientException $exception) {
            Log::warning('OAuth callback token exchange failed.', [
                'provider' => $provider,
                'status' => $exception->getResponse()?->getStatusCode(),
                'body' => (string) $exception->getResponse()?->getBody(),
            ]);

            return $this->redirectToLoginWithOauthError(__('storefront.auth.oauth_failed'));
        }

        $user = User::query()
            ->where('oauth_provider', $provider)
            ->where('oauth_provider_id', $socialUser->getId())
            ->first();

        if (! $user && $socialUser->getEmail()) {
            $user = User::where('email', $socialUser->getEmail())->first();
        }

        if ($user) {
            $user->update([
                'oauth_provider' => $provider,
                'oauth_provider_id' => $socialUser->getId(),
                'oauth_avatar' => $socialUser->getAvatar(),
            ]);
        } else {
            $user = User::create([
                'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: 'Dirasat User',
                'email' => $socialUser->getEmail(),
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
                'oauth_provider' => $provider,
                'oauth_provider_id' => $socialUser->getId(),
                'oauth_avatar' => $socialUser->getAvatar(),
            ]);
        }

        $this->carts->mergeSessionCartIntoUser($request, $user);

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('library.index'))->with('status', __('storefront.auth.login_success'));
    }

    protected function ensureProvider(string $provider): void
    {
        abort_unless(in_array($provider, ['google', 'facebook'], true), 404);
    }

    private function recoverOauthQuery(Request $request): void
    {
        if ($request->filled('code') || $request->filled('error')) {
            return;
        }

        $queryString = $request->server('QUERY_STRING');

        if (! is_string($queryString) || $queryString === '') {
            $requestUri = $request->server('REQUEST_URI');
            $queryString = is_string($requestUri) ? (parse_url($requestUri, PHP_URL_QUERY) ?: '') : '';
        }

        if ($queryString === '') {
            return;
        }

        parse_str($queryString, $query);

        foreach (['code', 'state', 'error', 'error_description'] as $key) {
            if (array_key_exists($key, $query) && ! $request->query->has($key)) {
                $request->query->set($key, $query[$key]);
            }
        }
    }

    private function redirectToLoginWithOauthError(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors([
            'oauth' => $message,
        ]);
    }
}
