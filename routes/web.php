<?php

declare(strict_types=1);

use App\Http\Controllers\Account\EmailVerificationController;
use App\Http\Controllers\Account\PasswordResetController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\ProfileImageController;
use App\Http\Controllers\Account\RegistrationController;
use App\Http\Controllers\Account\SessionController;
use App\Http\Controllers\Shop\LicenceController;
use App\Http\Controllers\Shop\PurchaseController;
use App\Http\Controllers\Shop\ShopController;
use App\Http\Controllers\Tools\CalculatorController;
use App\Http\Controllers\Tools\UnitConverterController;
use App\Providers\LicensingServiceProvider;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// Tools (E1 to E3). The forms and the list are open; using a tool needs a licence that covers it (E5).
Route::get('/tools/unit-converter', [UnitConverterController::class, 'show'])->name('tools.unit-converter');
Route::get('/tools/calculators', [CalculatorController::class, 'index'])->name('tools.calculators');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::post('/tools/unit-converter', [UnitConverterController::class, 'convert'])
        ->middleware('can:'.LicensingServiceProvider::USE_TOOL.",'".UnitConverterController::TOOL."'")
        ->name('tools.unit-converter.convert');
    Route::post('/tools/calculators/{calculator}', [CalculatorController::class, 'calculate'])
        ->middleware('can:'.LicensingServiceProvider::USE_TOOL.',calculator')
        ->name('tools.calculators.calculate');
});

// Accounts (E4): sign-up, sign-in, email confirmation, password recovery and the profile.
Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store']);
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::middleware(['auth', 'verified'])->prefix('profile')->group(function (): void {
    Route::get('/', [ProfileController::class, 'show'])->name('profile');
    Route::put('/details', [ProfileController::class, 'updateDetails'])->name('profile.details');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences');
    Route::post('/deactivate', [ProfileController::class, 'deactivate'])->name('profile.deactivate');
    Route::post('/images/{kind}', [ProfileImageController::class, 'store'])
        ->middleware('throttle:12,1')
        ->name('profile.images.store');
    Route::get('/images/{kind}', [ProfileImageController::class, 'show'])->name('profile.images.show');
});

// The shop and the licence (E5): packages are bought with the password; nothing is charged.
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/licence', [LicenceController::class, 'show'])->name('licence');
    Route::get('/shop', [ShopController::class, 'index'])->name('shop');
    Route::get('/shop/packages/{package}', [ShopController::class, 'show'])->whereNumber('package')->name('shop.package');
    Route::get('/shop/custom', [ShopController::class, 'custom'])->name('shop.custom');
    Route::post('/shop/purchases', [PurchaseController::class, 'store'])->middleware('throttle:6,1')->name('shop.purchases');
});
