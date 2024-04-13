<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        try {
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

            $token = $user->createToken('token')->plainTextToken;

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
        } catch (\Exception $e) {
            Log::error('Error fetching birthday records: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
    public function logout(Request $request)
    {
        try {
            if (Auth::guard('sanctum')->user()) {
                Auth::guard('sanctum')->user()->tokens()->delete();
                session()->forget('token');

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
        } catch (\Exception $e) {
            Log::error('Error fetching birthday records: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
}
