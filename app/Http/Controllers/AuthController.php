<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Applicant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function applicantSignup(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => 'required|email|max:255|unique:users,email|unique:applicants,email',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'course_id' => 'required|exists:courses,id',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $result = DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => UserRole::APPLICANT,
            ]);

            $applicant = Applicant::create([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'address' => $validated['address'] ?? null,
                'course_id' => $validated['course_id'],
            ]);

            $token = $user->createToken('applicant-api')->plainTextToken;

            $this->logAudit($request, 'applicant_signed_up', 'applicant', $applicant->id, [
                'email' => $user->email,
            ]);

            return [$user, $applicant, $token];
        });

        return response()->json([
            'message' => 'Signup successful',
            'token' => $result[2],
            'user' => [
                'id' => $result[0]->id,
                'name' => $result[0]->name,
                'email' => $result[0]->email,
                'role' => $result[0]->role->value,
            ],
            'applicant' => $result[1],
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;
        $applicantId = $user->applicant?->id;

        $this->logAudit($request, 'user_logged_in', 'user', $user->id, [
            'role' => $user->role->value,
            'applicant_id' => $applicantId,
        ]);

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            'applicant_id' => $applicantId,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();

        $this->logAudit($request, 'user_logged_out', 'user', $user->id);

        return response()->json(['message' => 'Logout successful']);
    }
}
