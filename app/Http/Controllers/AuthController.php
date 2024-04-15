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

    public function send_reset_link_email(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'message' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        $email = $validatedData['email'];

        $user = User::where('email', $email)->whereIn('role_as', [2, 3, 4, 5])->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.', 'status' => false], 404);
        }

        $token = substr(md5(uniqid(rand(), 1)), 3, 10);
        $user->token = $token;
        $user->save();

        $link = url('users/reset_password') . '?uid=' . base64_encode($user->id) . '&token=' . $token . '&email=' . $email;

        try {
            Mail::to($email)->send(new ResetPasswordMail($user, $link));
            return response()->json(['message' => 'Reset password link has been sent!', 'status' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send email: ' . $e->getMessage()], 500);
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
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $validatedData = $validator->validated();

        $user = User::where([
            'id' => $validatedData['id'],
            'token' => $validatedData['token']
        ])->first();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Password reset link invalid or expired.']);
        }

        if ($user->validatePassword($validatedData['password'])) {
            return response()->json(['status' => false, 'message' => 'Please try a different password! This password is already used!']);
        }

        // Check the strength of the password
        $uppercase = preg_match('@[A-Z]@', $validatedData['password']);
        $lowercase = preg_match('@[a-z]@', $validatedData['password']);
        $number    = preg_match('@[0-9]@', $validatedData['password']);

        if (!$uppercase || !$lowercase || !$number || strlen($validatedData['password']) < 8) {
            return response()->json(['status' => false, 'message' => 'Password should have at least 1 uppercase letter, 1 lowercase letter, 1 number, and be at least 8 characters long.']);
        }

        // Reset password
        $user->password = md5($validatedData['password']);
        $user->token = ''; // Clear the reset token
        $user->save();

        return response()->json(['status' => true, 'message' => 'Password reset successfully.']);
    }
}
