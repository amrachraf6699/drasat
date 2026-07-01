<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Storefront\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureProvider($provider);

        $socialUser = Socialite::driver($provider)->stateless()->user();

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
}
