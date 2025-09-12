<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['status' => __($status)]);
    }

    public function resetWithDefault(Request $request, $token)
    {
        // Ottieni email da query string (GET) o da body (POST)
        $email = $request->input('email') ?: $request->query('email');
        
        if (!$email) {
            return response()->json(['error' => 'Email richiesta'], 400);
        }
        
        $user = \App\Models\User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['error' => 'Utente non trovato'], 404);
        }

        // Genera una password casuale
        $newPassword = Str::random(10);

        // Verifica il token e resetta la password
        $status = Password::reset(
            ['email' => $email, 'password' => $newPassword, 'password_confirmation' => $newPassword, 'token' => $token],
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password)
                ])->save();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            // Invia la nuova password via mail
            Mail::to($user->email)->send(new \App\Mail\NuovaPassword($newPassword));
            
            // Se è una GET (link da mail), reindirizza al componente Angular di successo
            if ($request->isMethod('get')) {
                $frontendUrl = config('app.frontend_url', 'http://localhost:4200');
                return redirect($frontendUrl . '/password-reset-success');
            }
            
            // Se è una POST, restituisci JSON
            return response()->json(['status' => 'Password aggiornata e inviata']);
        } else {
            return response()->json(['error' => __($status)], 400);
        }
    }
}
