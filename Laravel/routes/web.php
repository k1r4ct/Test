<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\Auth\NewPasswordController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});
Route::get('/public/storage/{fileId}/{fileName}', function ($fileId,$filename) {
    $id = $fileId;  //id del contratto per recuperare la cartella corrispondente
    $file=$filename; // nome del file per recuperare il file esatto
    //dd(public_path());
    return response()->download(public_path("Storage\\$id\\$file"));
});
require __DIR__.'/auth.php';

Route::get('passwordreset/{token}', [NewPasswordController::class, 'resetWithDefault'])
                ->middleware('guest')
                ->name('passwordreset.get');

Route::post('passwordreset/{token}', [NewPasswordController::class, 'resetWithDefault'])
                ->middleware('guest')
                ->name('passwordreset.post');

Route::post('/forgot-password', function (Request $request) {
    $request->validate(['email' => 'required|email']);
    $status = Password::sendResetLink($request->only('email'));

    return $status === Password::RESET_LINK_SENT
                ? response()->json(['status' => __($status)])
                : response()->json(['email' => __($status)], 500);
});

// Rotta per inviare il link di reset password (da Angular)
Route::post('passwordreset', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');

Route::post('storeLeadExternal', [\App\Http\Controllers\LeadController::class, 'storeLeadExternal'])
    ->middleware('guest')
    ->name('storeLeadExternal');
     // Assicurati che l'utente sia autenticato per questa rotta