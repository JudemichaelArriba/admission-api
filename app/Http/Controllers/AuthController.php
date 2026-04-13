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
use App\Http\Requests\AdminRegisterRequest;
use App\Models\ApplicantDocument;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function applicantSignup(ApplicantSignupRequest $request)
    {
        $validated = $request->validated();

        $result = DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
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

            $uploadedFile = $request->file('birth_certificate');
            $binaryData = file_get_contents($uploadedFile->getRealPath());

            ApplicantDocument::create([
                'applicant_id'      => $applicant->id,
                'document_type'     => 'birth_certificate',
                'file_content'      => $binaryData,
                'original_filename' => $uploadedFile->getClientOriginalName(),
                'mime_type'         => $uploadedFile->getMimeType(),
                'file_size'         => $uploadedFile->getSize(),
                'sha256'            => hash_file('sha256', $uploadedFile->getRealPath()),
                'scan_status'       => 'pending',
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
            $responseData['applicant'] = $user->applicant;

            if ($user->applicant->status === 'approved' && $user->applicant->student) {
                $responseData['Student'] = new StudentDataResource($user->applicant);
                $responseData['applicant']->makeHidden(['student', 'course']);
            }
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

    public function registerAdmin(AdminRegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::ADMIN,
        ]);

        $token = $user->createToken('admin-api')->plainTextToken;

        return response()->json([
            'message' => 'Admin created successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            $token = Str::random(8);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            Mail::to($user->email)->send(new ResetPasswordMail($token, $user->email));
        }

        return response()->json([
            'message' => 'If an account exists with this email, a password reset token has been sent.'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $validated['email'])->first();

        if (!$record || !Hash::check($validated['token'], $record->token)) {
            return response()->json(['message' => 'Invalid reset token.'], 400);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
            return response()->json(['message' => 'This reset token has expired. Please request a new one.'], 400);
        }

        $user = User::where('email', $validated['email'])->first();
        
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 400);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        return response()->json([
            'message' => 'Password has been successfully reset.'
        ]);
    }
}