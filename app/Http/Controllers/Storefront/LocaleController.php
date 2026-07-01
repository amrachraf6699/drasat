<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    public function update(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, config('app.supported_locales', ['en', 'ar']), true), 404);

        $request->session()->put('locale', $locale);
        App::setLocale($locale);

        return back()->with('status', __('storefront.flash.locale_updated'));
    }
}
