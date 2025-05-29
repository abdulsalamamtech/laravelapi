<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /* 
     * Register a new user
     * @param array User $user
     * @response Response object
    **/
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            DB::beginTransaction();
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
    
            $token = $user->createToken('auth-token')->plainTextToken;
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'user' => $user,
                'token' => $token,
            ], 201);
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            error_log($th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error, user registration failed',
            ], 500);
        }

    }

    /* 
     * Login a user
     * @param array User $user
     * @response Response object
    **/
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = $request->user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /* 
     * Logout a user
     * @param array User $user
     * @response Response object
    **/
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /* 
     * Get auth user
     * @param array User $user
     * @response Response object
    **/
    public function user(Request $request)
    {
        return response()->json($request->user());
    } 
    
    /* 
     * Logout user from all devices
     * @param array User $user
     * @response Response object
    **/
    public function logoutDevices(Request $request)
    {
        $request->user()->token()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}

