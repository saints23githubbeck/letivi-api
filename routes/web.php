<?php

use App\Http\Controllers\UserAuth\OtpController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PayPalController;

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

Route::get('/', function () {
    return view('welcome');
});

// Route::get('mail', function (){
//     return view('mail.otp');
// });

// Route::get('accept', function (){
//     return view('accepted.otp');
// });
// Route::get('error', function (){
//     return view('error.error');
// });
// Route::get('download', function (){
//     return view('download.download');
// });

// OTP
Route::controller(OtpController::class)->group(function () {
    Route::get('/verify', 'verifyOTP');
    Route::get('/password/reset', 'verifyPasswordOtp');
    Route::post('/otp/resend/{token}', 'resendOTP');
    Route::post('/password/resend/{token}', 'resendPasswordOTP');
});

 // PAYPAL CONTROLLER
//  Route::controller(PayPalController::class)->group(function () {
//     Route::get('successTransaction/{user_id}', 'successTransaction')->name('/successTransaction/{user_id}');
//     Route::get('cancelTransaction/{user_id}', 'cancelTransaction')->name('/cancelTransaction/{user_id}');
// });

Route::get('create-transaction', [PayPalController::class, 'createTransaction'])->name('createTransaction');
Route::get('process-transaction', [PayPalController::class, 'processTransaction'])->name('processTransaction');
Route::get('success-transaction', [PayPalController::class, 'successTransaction'])->name('successTransaction');
Route::get('cancel-transaction', [PayPalController::class, 'cancelTransaction'])->name('cancelTransaction');

// Route::get('/phpinfo', function() {
//     // return phpinfo();
//     // echo '<pre>';
//     // print_r(get_loaded_extensions());
// });
