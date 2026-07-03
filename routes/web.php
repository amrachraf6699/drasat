<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController as AdminAdminController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\BankTransferController as AdminBankTransferController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\LocaleController as AdminLocaleController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Storefront\Auth\LoginController as StorefrontLoginController;
use App\Http\Controllers\Storefront\Auth\OAuthController as StorefrontOAuthController;
use App\Http\Controllers\Storefront\Auth\RegisterController as StorefrontRegisterController;
use App\Http\Controllers\Storefront\CartController as StorefrontCartController;
use App\Http\Controllers\Storefront\CheckoutController as StorefrontCheckoutController;
use App\Http\Controllers\Storefront\FaqController as StorefrontFaqController;
use App\Http\Controllers\Storefront\HomeController as StorefrontHomeController;
use App\Http\Controllers\Storefront\LibraryController as StorefrontLibraryController;
use App\Http\Controllers\Storefront\LocaleController as StorefrontLocaleController;
use App\Http\Controllers\Storefront\ProductController as StorefrontProductController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', StorefrontHomeController::class)->name('home');
Route::get('/faq', StorefrontFaqController::class)->name('faq.index');
Route::post('/locale/{locale}', [StorefrontLocaleController::class, 'update'])->name('locale.update');
Route::get('/studies', [StorefrontProductController::class, 'index'])->name('studies.index');
Route::get('/studies/{product:slug}', [StorefrontProductController::class, 'show'])->name('studies.show');
Route::get('/cart', [StorefrontCartController::class, 'show'])->name('cart.show');
Route::post('/cart/items', [StorefrontCartController::class, 'store'])->name('cart.items.store');
Route::delete('/cart/items/{cartItem}', [StorefrontCartController::class, 'destroy'])->name('cart.items.destroy');

Route::middleware('guest:web')->group(function () {
    Route::get('/login', [StorefrontLoginController::class, 'create'])->name('login');
    Route::post('/login', [StorefrontLoginController::class, 'store'])->name('login.store');
    Route::get('/register', [StorefrontRegisterController::class, 'create'])->name('register');
    Route::post('/register', [StorefrontRegisterController::class, 'store'])->name('register.store');
    Route::get('/auth/{provider}/redirect', [StorefrontOAuthController::class, 'redirect'])->name('oauth.redirect');
    Route::get('/auth/{provider}/callback', [StorefrontOAuthController::class, 'callback'])->name('oauth.callback');
});

Route::middleware('auth:web')->group(function () {
    Route::post('/logout', [StorefrontLoginController::class, 'destroy'])->name('logout');
    Route::get('/checkout', [StorefrontCheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/bank-transfer', [StorefrontCheckoutController::class, 'bankTransfer'])->name('checkout.bank-transfer');
    Route::post('/checkout/paypal/create', [StorefrontCheckoutController::class, 'createPaypalOrder'])->name('checkout.paypal.create');
    Route::post('/checkout/paypal/capture', [StorefrontCheckoutController::class, 'capturePaypalOrder'])->name('checkout.paypal.capture');
    Route::get('/library', [StorefrontLibraryController::class, 'index'])->name('library.index');
    Route::get('/library/documents/{media}/download', [StorefrontLibraryController::class, 'download'])->name('library.documents.download');
});

Route::prefix('manage')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::post('/logout', [AdminLoginController::class, 'destroy'])->name('logout');
        Route::post('/locale/{locale}', [AdminLocaleController::class, 'update'])->name('locale.update');
        Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::post('/notifications/read', [AdminNotificationController::class, 'markAllRead'])->name('notifications.read');

        Route::get('admins', [AdminAdminController::class, 'index'])->middleware('permission:admins.view,admin')->name('admins.index');
        Route::post('admins', [AdminAdminController::class, 'store'])->middleware('permission:admins.create,admin')->name('admins.store');
        Route::get('admins/{admin}', [AdminAdminController::class, 'show'])->middleware('permission:admins.view,admin')->name('admins.show');
        Route::put('admins/{admin}', [AdminAdminController::class, 'update'])->middleware('permission:admins.update,admin')->name('admins.update');
        Route::patch('admins/{admin}', [AdminAdminController::class, 'update'])->middleware('permission:admins.update,admin');
        Route::delete('admins/{admin}', [AdminAdminController::class, 'destroy'])->middleware('permission:admins.delete,admin')->name('admins.destroy');

        Route::get('roles', [AdminRoleController::class, 'index'])->middleware('permission:roles.view|roles.manage,admin')->name('roles.index');
        Route::post('roles', [AdminRoleController::class, 'store'])->middleware('permission:roles.create|roles.manage,admin')->name('roles.store');
        Route::get('roles/{role}', [AdminRoleController::class, 'show'])->middleware('permission:roles.view|roles.manage,admin')->name('roles.show');
        Route::put('roles/{role}', [AdminRoleController::class, 'update'])->middleware('permission:roles.update|roles.manage,admin')->name('roles.update');
        Route::patch('roles/{role}', [AdminRoleController::class, 'update'])->middleware('permission:roles.update|roles.manage,admin');
        Route::delete('roles/{role}', [AdminRoleController::class, 'destroy'])->middleware('permission:roles.delete|roles.manage,admin')->name('roles.destroy');

        Route::get('products', [AdminProductController::class, 'index'])->middleware('permission:products.view,admin')->name('products.index');
        Route::post('products', [AdminProductController::class, 'store'])->middleware('permission:products.create,admin')->name('products.store');
        Route::get('products/{product}', [AdminProductController::class, 'show'])->middleware('permission:products.view,admin')->name('products.show');
        Route::put('products/{product}', [AdminProductController::class, 'update'])->middleware('permission:products.update,admin')->name('products.update');
        Route::patch('products/{product}', [AdminProductController::class, 'update'])->middleware('permission:products.update,admin');
        Route::delete('products/{product}', [AdminProductController::class, 'destroy'])->middleware('permission:products.delete,admin')->name('products.destroy');

        Route::get('faqs', [AdminFaqController::class, 'index'])->middleware('permission:faqs.view|faqs.manage,admin')->name('faqs.index');
        Route::post('faqs', [AdminFaqController::class, 'store'])->middleware('permission:faqs.create|faqs.manage,admin')->name('faqs.store');
        Route::get('faqs/{faq}', [AdminFaqController::class, 'show'])->middleware('permission:faqs.view|faqs.manage,admin')->name('faqs.show');
        Route::put('faqs/{faq}', [AdminFaqController::class, 'update'])->middleware('permission:faqs.update|faqs.manage,admin')->name('faqs.update');
        Route::patch('faqs/{faq}', [AdminFaqController::class, 'update'])->middleware('permission:faqs.update|faqs.manage,admin');
        Route::delete('faqs/{faq}', [AdminFaqController::class, 'destroy'])->middleware('permission:faqs.delete|faqs.manage,admin')->name('faqs.destroy');

        Route::get('settings', [AdminSettingController::class, 'index'])->middleware('permission:settings.view|settings.manage,admin')->name('settings.index');
        Route::get('settings/{setting}', [AdminSettingController::class, 'show'])->middleware('permission:settings.view|settings.manage,admin')->name('settings.show');
        Route::put('settings/{setting}', [AdminSettingController::class, 'update'])->middleware('permission:settings.update|settings.manage,admin')->name('settings.update');
        Route::patch('settings/{setting}', [AdminSettingController::class, 'update'])->middleware('permission:settings.update|settings.manage,admin');

        Route::get('orders', [AdminOrderController::class, 'index'])->middleware('permission:orders.view,admin')->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->middleware('permission:orders.view,admin')->name('orders.show');
        Route::get('bank-transfers', [AdminBankTransferController::class, 'index'])->middleware('permission:transfers.view|transfers.review,admin')->name('bank-transfers.index');
        Route::get('bank-transfers/{bankTransfer}', [AdminBankTransferController::class, 'show'])->middleware('permission:transfers.view|transfers.review,admin')->name('bank-transfers.show');
        Route::patch('bank-transfers/{bankTransfer}/approve', [AdminBankTransferController::class, 'approve'])->middleware('permission:transfers.approve|transfers.review,admin')->name('bank-transfers.approve');
        Route::patch('bank-transfers/{bankTransfer}/reject', [AdminBankTransferController::class, 'reject'])->middleware('permission:transfers.reject|transfers.review,admin')->name('bank-transfers.reject');
        Route::get('users', [AdminUserController::class, 'index'])->middleware('permission:users.view,admin')->name('users.index');
        Route::get('users/{user}', [AdminUserController::class, 'show'])->middleware('permission:users.view,admin')->name('users.show');
    });
});
