<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users|max:191',
            'password' => 'required|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'message' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        // dd(md5($validatedData['password']), $user->password);

        $user = User::where('email', $validatedData['email'])
            ->first();

        if (!$user || !$user->validatePassword($validatedData['password'])) {
            return response()->json(['status' => 'failed', 'message' => 'Invalid login credentials. Please try again.'], 422);
        }

        $token = $user->createToken('user_token')->plainTextToken;

        Auth::login($user);
        session()->put('token', $token);
        return response(
            [
                'token' => $token,
                'status' => 'success',
                'message' => "Login successfully",
            ],
            200,
        );
    }
    public function logout(Request $request)
    {
        // Check if the request has a user
        if ($request->user()) {
            // Delete all tokens associated with the user
            $request->user()->tokens()->delete();
            session()->forget('user_token');

            // Return a success response
            return response([
                'status' => 'success',
                'message' => 'Logged out successfully.',
            ], 200);
        }

        // If the request does not have a user, return a failed response
        return response([
            'status' => 'failed',
            'message' => 'Token mismatch. Please try again later.'
        ], 422);
    }
}
