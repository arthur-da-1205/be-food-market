<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\PasswordValidationRules;
use App\Helpers\ResponseFormatter;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class APIUserController extends Controller
{

    use PasswordValidationRules;

    public function login(Request $request)
    {
        try {

            //Input Validation
            $request->validate([
                'email' => 'email|required',
                'password' => 'required'
            ]);

            // Credentials checking
            $credentials = request('email', 'password');
            if (Auth::attempt($credentials)) {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized',
                ], 'Authentication Failed', 500);
            }

            //If hash not sesuai
            $user = User::where('email', $request->email)->first();
            if (!Hash::check($request->password, $user->password, [])) {
                throw new \Exception('Invalid Credentials');
            }

            //Login user
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'Authenticated');
        } catch (Exception $e) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $e
            ], 'Authentication Failed', 500);
        }
    }

    public function register(Request $request)
    {
        try {
            // Input validation
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'max:255', 'unique:user'],
                'password' => $this->passwordRules(),
            ]);

            // Create User
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'houseNumber' => $request->houseNumber,
                'phoneNumber' => $request->phoneNumber,
                'city' => $request->city,
                'password' => Hash::make($request->password),
            ]);

            // Get user email and make the token
            $user = User::where('email', $request->email)->first();
            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ]);
        } catch (Exception $e) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $e
            ], 'Authentication Failed', 500);
        }
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken()->delete();

        return ResponseFormatter::success($token, 'Token Revoked');
    }

    public function fetch(Request $request)
    {
        return ResponseFormatter::success($request->user(), 'Get all data');
    }

    public function updateProfile(Request $request)
    {
        $data = $request->all();

        $user = Auth::user();
        $user->update($data);

        return ResponseFormatter::success($user, 'Profile Updated');
    }

    public function uploadPhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|max:2048'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error([
                'error' => $validator->error,
            ], 'Upadate Photo Error', 401);
        }

        if ($request->file('file')) {
            $file = $request->file('file')->store('assets/user', 'public');
        }

        $user = Auth::user();
        $user->profile_photo_path = $file;
        $user->update();

        return ResponseFormatter::success([$file], 'Photo has been Uploaded');
    }
}
