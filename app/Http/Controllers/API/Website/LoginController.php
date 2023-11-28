<?php

namespace App\Http\Controllers\API\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Configuration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;


class LoginController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            // User not found, return registration request
            return response()->json(['message' => 'Registration request'], Response::HTTP_OK);
        }

        if ($user->password !== $request->password) {
            // Invalid password
            return response()->json(['error' => 'Invalid Password'], Response::HTTP_BAD_REQUEST);
        }

        if ($user->api_token === null) {
            // Generate OTP and send it
            $user->otp = random_int(1000, 9999);
            $user->save();
            $messageText = 'Your verification code is: ' . $user->otp;
            $recipients = $user->phone;
            // To do : send sms
            return response()->json(['message' => 'OTP sent'], Response::HTTP_OK);
        }

        // API token exists, return "resend OTP" message
        return response()->json(['message' => 'Resend OTP'], Response::HTTP_OK);
    }
    public function createUser(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'nullable|email|unique:users',
            'birthday' => 'required',
            'phone' => 'required|unique:users',
            'about_us' => 'required',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        try {
            $userInstance = new User([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'birthday' => $request->birthday,
                'phone' => $request->phone,
                'about_us' => $request->about_us,
                'password' => Hash::make($request->password),
                'otp' => random_int(1000, 9999),
                'role' => 'authenticated',
            ]);

            $userInstance->save();

            $success = [
                'otp' => 'OTP sent',
            ];

            $messageText = 'Your verification code is: ' . $userInstance->otp;
            $recipients = $userInstance->phone;
            // To do: send SMS

            return response()->json(['message' => $success], Response::HTTP_OK);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Internal server error', 'error' => $e], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function verifyOtp(Request $request)
    {
        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_BAD_REQUEST);
        }
        if ($user->otp == $request->otp) {
            if (empty($user->api_token)) {
                $user->api_token = Str::random(60);
            }
            $user->otp = null;
            $riyalToPoint = Configuration::getDefaultConfiguration(Configuration::RIYAL_TO_POINT);

            $user->points = $riyalToPoint;  // 1 Riyal when creating account
            $user->save();
            $success = [
                'token' => $user->api_token,
                'user' => $user,
            ];
        } else {
            $success = ['otp' => 'Invalid OTP'];
        }
        return response()->json(['message' => $success], Response::HTTP_OK);
    }
    public function resendOTP(Request $request)
    {
        $user = User::where('phone', $request->phone)->first();
        if ($user) {
            $user->otp = random_int(1000, 9999);
            $user->save();
            $messageText = 'Your verification code is: ' . $user->otp;
            $recipients = $user->phone;
            // To do: send SMS here
            $response = ['otp' => 'OTP sent'];
        } else {
            $response = ['firstLogin' => 'Registration request'];
        }
        return response()->json(['message' => $response], Response::HTTP_OK);
    }
    public function logout(Request $request)
    {
        $authUser = getTokenUserId($request->header('Authorization'));

        if ($authUser) {
            $user = User::find($authUser);

            if ($user) {
                $user->api_token = null;
                $user->save();
                return response()->json(['message' => ['logout' => 'User logged out successfully']], Response::HTTP_OK);
            }
        }

        return response()->json(['message' => ['login' => 'You are not logged in']], Response::HTTP_OK);
    }
    public function userProfile(Request $request)
    {
        $authUser = getTokenUserId($request->header('Authorization'));

        $userInstance = User::with('reviews', 'shippingaddresses' , 'orders')->find($authUser);

        if (!$userInstance) {
            $message = 'Not Found.';
            return response()->json(['message' => $message], 404);
        }

        return response()->json(['user' => $userInstance], Response::HTTP_OK);
    }
    public function updateUserProfile(Request $request)
    {
        $authUser = getTokenUserId($request->header('Authorization'));
        if (!$authUser) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        $userInstance = User::find($authUser);

        $requestData = $request->validate([
            'email' => 'email|unique:users,email,' . $userInstance->id,
            'phone' => 'unique:users,phone,' . $userInstance->id,
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'about_us' => 'required|string',
        ]);


        $userInstance->update($requestData);

        return response()->json(['message' => $userInstance], Response::HTTP_OK);
    }

    public function changePassword(Request $request)
    {
        $authUser = getTokenUserId($request->header('Authorization'));

        if (!$authUser) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        $userInstance = User::find($authUser);

        $validator = Validator::make($request->all(), [
            'oldpassword' => 'required',
            'newpassword' => 'required|min:8',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }
        if ($userInstance->password !== $request->oldpassword) {
            return response()->json(['error' => 'Wrong password.'], Response::HTTP_BAD_REQUEST);
        }

        $userInstance->password = $request->newpassword;
        $userInstance->save();

        return response()->json(['message' => ['password' => 'Password has been updated successfully']], Response::HTTP_OK);
    }

    public function getUser(Request $request)
    {
        $authUser =getTokenUserId($request->header('Authorization'));

        $user = User::where('id', $authUser)->first();

        if (!$user) {
            $message = 'Not Found.';
            return response()->json(['message' => $message], 404);
        }

        return response()->json(['user' => $user], Response::HTTP_OK);
    }

    ////////////////////////////////// Admin //////////////////////////////////

    public function loginAdmin(Request $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages(['message' => 'Invalid credentials']);
            }

            $token = $user->createToken('admin-token')->plainTextToken;

            $user->api_token = Str::random(60);
            $user->save();



            return response()->json(['api_token' => $user->api_token, 'user' => $user]);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



}
