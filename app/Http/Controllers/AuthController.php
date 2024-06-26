<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|max:191',
                'password' => 'required|min:6',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }

            $validatedData = $validator->validated();

            $user = User::where('email', $validatedData['email'])
                ->first();

            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Invalid login credentials. Please try again.'], 422);
            }

            if (!$user->status) {
                return response()->json(['status' => false, 'message' => 'You cannot log in to your account because it is not active. Please contact HR or the administrator.'], 422);
            }

            if (!$user->validatePassword($validatedData['password'])) {
                return response()->json(['status' => false, 'message' => 'Invalid login credentials. Please try again.'], 422);
            }

            $token = $user->createToken('token')->plainTextToken;

            Auth::login($user);
            session()->put('token', $token);
            return response(
                [
                    'token' => $token,
                    'status' => true,
                    'message' => "Login successfully",
                    'data' => [
                        'name' => $user->full_name,
                        'id' => $user->id
                    ]
                ],
                200,
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            if (Auth::guard('sanctum')->user()) {
                Auth::guard('sanctum')->user()->tokens()->delete();
                session()->forget('token');

                return response([
                    'status' => true,
                    'message' => 'Logged out successfully.',
                ], 200);
            }

            // If the request does not have a user, return a failed response
            return response([
                'status' => false,
                'message' => 'Token mismatch. Please try again later.'
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error fetching birthday records: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function send_reset_link_email(Request $request)
    {
        $email = $request->input('email');

        // Perform custom validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['status' => false, 'message' => 'Invalid email format'], 422);
        }

        $user = User::where('email', $email)->whereIn('role_as', [2, 3, 4, 5])->first();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User with this email does not exist'], 422);
        }

        // Additional custom conditions if needed
        if (!$user->isActive()) {
            return response()->json(['status' => false, 'message' => 'User is not active'], 422);
        }

        $token = substr(md5(uniqid(rand(), 1)), 3, 10);
        $user->token = $token;
        $user->save();

        
        $url = config('services.reset_password_url.' . config('app.env'));

        $link = $url . '?uid=' . base64_encode($user->id) . '&token=' . $token . '&email=' . $email;

        try {
            Mail::to($email)->send(new ResetPasswordMail($user, $link));
            return response()->json(['status' => true, 'message' => 'Reset password link has been sent!'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }

    public function reset_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|confirmed|min:8',
            'password_confirmation' => 'required|min:8',
            'id' => 'required',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        $user = User::where([
            'id' => $validatedData['id'],
            'token' => $validatedData['token']
        ])->first();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Password reset link invalid or expired.'], 422);
        }

        if ($user->validatePassword($validatedData['password'])) {
            return response()->json(['status' => false, 'message' => 'Please try a different password! This password is already used!'], 422);
        }

        // Check the strength of the password
        $uppercase = preg_match('@[A-Z]@', $validatedData['password']);
        $lowercase = preg_match('@[a-z]@', $validatedData['password']);
        $number    = preg_match('@[0-9]@', $validatedData['password']);

        if (!$uppercase || !$lowercase || !$number || strlen($validatedData['password']) < 8) {
            return response()->json(['status' => false, 'message' => 'Password should have at least 1 uppercase letter, 1 lowercase letter, 1 number, and be at least 8 characters long.'], 422);
        }

        // Reset password
        try {
            $user->password = md5($validatedData['password']);
            // Clear the reset token
            $user->token = '';
            $user->save();

            return response()->json(['status' => true, 'message' => 'Password reset successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Error resetting password: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
}
