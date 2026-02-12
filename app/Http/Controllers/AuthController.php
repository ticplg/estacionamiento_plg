<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Http\Request;
use App\Models\PasswordRecorvery;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Jobs\SendPasswordResetEmail;

class AuthController extends Controller
{
    
    public function login_user(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = request(['email', 'password']);

        if (!Auth::attempt($credentials))
        
        return response()->json([
            'message' => 'Unauthorized'
        ], 401);

        $user = $request->user();
        
        if($user->is_guest)
        {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        return response()->json([
            'status' => true,
            'token_type' => 'Bearer Token',
            'access_token' => $user->createToken('Personal Access Token')->plainTextToken,
            'show_update_profile' => $user->show_update_profile,
            'data'  => $user,
        ], 200);
    }

    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Successfully logged out'
        ], 200);
    }
    
}
