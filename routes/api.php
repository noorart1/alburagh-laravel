<?php

use App\Http\Controllers\Api\AbrestController;
use Illuminate\Support\Facades\Route;

Route::prefix('abrest')->group(function () {

    Route::get('/content_count/{type?}', [
        AbrestController::class,
        'contentCount',
    ]);
    Route::post('/register', [
        AbrestController::class,
        'register',
    ]);
    Route::post('/user_code_expire_date', [
        AbrestController::class,
        'userCodeExpireDate',
    ]);
    Route::get('/content_list', [
        AbrestController::class,
        'contentList',
    ]);
    Route::match(['get', 'post'], '/check_new_version', [
        AbrestController::class,
        'checkNewVersion',
    ]);
    Route::post('/add_device_token', [
        AbrestController::class,
        'addDeviceToken',
    ]);
    Route::post('/user_read_books', [
        AbrestController::class,
        'userReadBooks',
    ]);
    Route::post('/set_user_receipt', [
        AbrestController::class,
        'setUserReceipt',
    ]);
    Route::post('/add_user_read_books', [
        AbrestController::class,
        'addUserReadBooks',
    ]);
    Route::post('/send_mail', [
        AbrestController::class,
        'sendMail',
    ]);
    Route::post('/login', [
        AbrestController::class,
        'login',
    ]);
    Route::post('/change_pass', [
        AbrestController::class,
        'changePass',
    ]);
    Route::post('/check_code', [
        AbrestController::class,
        'checkCode',
    ]);
    Route::post('/check_mail_valid', [
        AbrestController::class,
        'checkMailValid',
    ]);
    Route::post('/reset_pass', [
        AbrestController::class,
        'resetPass',
    ]);
    Route::post('/submit_code', [
        AbrestController::class,
        'submitCode',
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

});