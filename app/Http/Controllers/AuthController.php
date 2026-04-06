<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Applicant;
use App\Models\User;
use App\Http\Requests\ApplicantSignupRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\StudentDataResource;

class AuthController extends Controller
{
    public function applicantSignup(ApplicantSignupRequest $request)
    {
        $validated = $request->validated();

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

        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;


        $user->load(['applicant.student', 'applicant.course']);

        $responseData = [
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role->value,
            ],
        ];

   
        if ($user->applicant) {
            $responseData['Student'] = new StudentDataResource($user->applicant);
        }

        return response()->json($responseData);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();

        $this->logAudit($request, 'user_logged_out', 'user', $user->id);

        return response()->json(['message' => 'Logout successful']);
    }
}
