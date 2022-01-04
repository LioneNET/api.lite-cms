<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
  public function login(LoginRequest $request): JsonResponse
  {
    $user = User::query()->where('email', $request->input('email'))->first();

    if (!$user || !Hash::check($request->input('password'), $user->password)) {
      return response()->json([
        'error' => 'The provided credentials are incorrect.'
      ], 401);
    }

    return response()->json([
      'token' => $user->createToken($request->input('device_name'))->plainTextToken,
    ]);
  }

  public function logout(Request $request): JsonResponse
  {
    Auth::guard('web')->logout();
    $request->user()->tokens()->delete();
    return response()->json([], 204);
  }
}
