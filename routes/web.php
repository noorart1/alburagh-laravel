<?php

use App\Http\Controllers\Api\AbrestController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

Route::prefix('app/abrest')
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->group(function () {

        Route::post('/add_stats', [
            AbrestController::class,
            'addStats',
        ]);
        Route::get('/get_settings', [
            AbrestController::class,
            'getSettings',
        ]);
        Route::get('/content_count/{type?}', [
            AbrestController::class,
            'contentCount',
        ]);
        Route::get('/admin/language/{locale}', function (string $locale) {
            abort_unless(in_array($locale, ['ar', 'en'], true), 404);
        
            session(['locale' => $locale]);
        
            return redirect()->back();
        })->name('admin.language');
        Route::get('/content_list', [
            AbrestController::class,
            'contentList',
        ]);

        Route::get('/rate/{bookId}/{rate}', [
            AbrestController::class,
            'rate',
        ]);

        Route::get('/increase_download/{bookId}', [
            AbrestController::class,
            'increaseDownload',
        ]);

        Route::get('/increase_readings/{bookId}', [
            AbrestController::class,
            'increaseReadings',
        ]);

        Route::post('/login', [
            AbrestController::class,
            'login',
        ]);

        Route::post('/register', [
            AbrestController::class,
            'register',
        ]);

        Route::post('/check_code', [
            AbrestController::class,
            'checkCode',
        ]);

        Route::post('/submit_code', [
            AbrestController::class,
            'submitCode',
        ]);

        Route::post('/user_code_expire_date', [
            AbrestController::class,
            'userCodeExpireDate',
        ]);

        Route::post('/change_pass', [
            AbrestController::class,
            'changePass',
        ]);

        Route::post('/reset_pass', [
            AbrestController::class,
            'resetPass',
        ]);

        Route::post('/check_mail_valid', [
            AbrestController::class,
            'checkMailValid',
        ]);

        Route::post('/send_mail', [
            AbrestController::class,
            'sendMail',
        ]);

        Route::post('/set_user_receipt', [
            AbrestController::class,
            'setUserReceipt',
        ]);

        Route::post('/add_device_token', [
            AbrestController::class,
            'addDeviceToken',
        ]);

        Route::match(['get', 'post'], '/check_new_version', [
            AbrestController::class,
            'checkNewVersion',
        ]);

        Route::post('/user_read_books', [
            AbrestController::class,
            'userReadBooks',
        ]);

        Route::post('/add_user_read_books', [
            AbrestController::class,
            'addUserReadBooks',
        ]);
    });