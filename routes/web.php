<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::redirect('/admin', '/admin/login');

Route::get('login', [AdminController::class, 'loginForm'])->name('login');
Route::post('login', [AdminController::class, 'login'])->name('login.post');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminController::class, 'loginForm'])->name('login');
    Route::post('login', [AdminController::class, 'login'])->name('login.post');
    Route::get('register', [AdminController::class, 'registerForm'])->name('register');
    Route::post('register', [AdminController::class, 'register'])->name('register.post');
    Route::post('logout', [AdminController::class, 'logout'])->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('users', [AdminController::class, 'users'])->name('users');
        Route::get('active-users', [AdminController::class, 'activeUsers'])->name('active-users');
        Route::post('users/{user}/status', [AdminController::class, 'updateUserStatus'])->name('users.status');
        Route::get('riders', [AdminController::class, 'riders'])->name('riders');
        Route::post('riders/{rider}/status', [AdminController::class, 'updateRiderStatus'])->name('riders.status');
        Route::post('riders/{rider}/update', [AdminController::class, 'updateRider'])->name('riders.update');
        Route::post('riders/{rider}/notify', [AdminController::class, 'notifyRider'])->name('riders.notify');
        Route::get('virtual-accounts', [AdminController::class, 'virtualAccounts'])->name('virtual-accounts');
        Route::get('rider-wallets', [AdminController::class, 'riderWallets'])->name('rider-wallets');
        Route::get('manual-payments', [AdminController::class, 'manualPayments'])->name('manual-payments');
        Route::post('manual-payments/{transaction}/approve', [AdminController::class, 'approveManualPayment'])->name('manual-payments.approve');
        Route::get('orders', [AdminController::class, 'orders'])->name('orders');
        Route::get('reports', [AdminController::class, 'reports'])->name('reports');
        Route::get('kyc', [AdminController::class, 'kyc'])->name('kyc');
        Route::post('kyc/{rider}/review', [AdminController::class, 'reviewKyc'])->name('kyc.review');
        Route::get('disputes', [AdminController::class, 'disputes'])->name('disputes');
        Route::post('disputes/{dispute}/resolve', [AdminController::class, 'resolveDispute'])->name('disputes.resolve');
        Route::get('withdrawals', [AdminController::class, 'withdrawals'])->name('withdrawals');
        Route::post('withdrawals/{withdrawal}/approve', [AdminController::class, 'approveWithdrawal'])->name('withdrawals.approve');
        Route::get('settings', [AdminController::class, 'settings'])->name('settings');
        Route::post('settings/profile', [AdminController::class, 'updateProfile'])->name('settings.profile');
        Route::post('settings/password', [AdminController::class, 'updatePassword'])->name('settings.password');
    });
});
