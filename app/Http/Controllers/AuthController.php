<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['error' => 'invalid_credentials'], 401);
        }

        if (empty($user->email_verified_at)) {
            return response()->json(['error' => 'email_not_verified'], 403);
        }

        // Placeholder token issuance — replace with real JWT issuance.
        return response()->json(['token' => 'placeholder-token'], 200);
    }
}
