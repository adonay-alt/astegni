<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Grade;
use App\Models\StudentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\OtpMail;

class AuthController extends Controller
{
    /**
     * Normalize request inputs so first_name, last_name, grade, school, and phone can be sent in camelCase or combined format.
     */
    protected function normalizeAuthInputs(Request $request): array
    {
        $data = $request->all();

        // Normalize first_name
        if (!isset($data['first_name']) || trim((string)$data['first_name']) === '') {
            if (isset($data['firstName']) && trim((string)$data['firstName']) !== '') {
                $data['first_name'] = trim((string)$data['firstName']);
            } elseif (isset($data['firstname']) && trim((string)$data['firstname']) !== '') {
                $data['first_name'] = trim((string)$data['firstname']);
            } elseif (isset($data['name']) && trim((string)$data['name']) !== '') {
                $parts = explode(' ', trim((string)$data['name']), 2);
                $data['first_name'] = $parts[0];
                if ((!isset($data['last_name']) || trim((string)$data['last_name']) === '') && isset($parts[1])) {
                    $data['last_name'] = $parts[1];
                }
            }
        }

        // Normalize last_name
        if (!isset($data['last_name']) || trim((string)$data['last_name']) === '') {
            if (isset($data['lastName']) && trim((string)$data['lastName']) !== '') {
                $data['last_name'] = trim((string)$data['lastName']);
            } elseif (isset($data['lastname']) && trim((string)$data['lastname']) !== '') {
                $data['last_name'] = trim((string)$data['lastname']);
            }
        }

        // Normalize school
        if (!isset($data['school']) || is_null($data['school']) || trim((string)$data['school']) === '') {
            if (isset($data['school_name']) && !is_null($data['school_name']) && trim((string)$data['school_name']) !== '') {
                $data['school'] = trim((string)$data['school_name']);
            } elseif (isset($data['schoolName']) && !is_null($data['schoolName']) && trim((string)$data['schoolName']) !== '') {
                $data['school'] = trim((string)$data['schoolName']);
            } else {
                $data['school'] = null;
            }
        }

        // Normalize phone
        if (!isset($data['phone']) || is_null($data['phone']) || trim((string)$data['phone']) === '') {
            if (isset($data['phone_number']) && !is_null($data['phone_number']) && trim((string)$data['phone_number']) !== '') {
                $data['phone'] = trim((string)$data['phone_number']);
            } elseif (isset($data['phoneNumber']) && !is_null($data['phoneNumber']) && trim((string)$data['phoneNumber']) !== '') {
                $data['phone'] = trim((string)$data['phoneNumber']);
            } else {
                $data['phone'] = null;
            }
        }

        return $data;
    }

    /**
     * Resolve the user's role strictly respecting RBAC assignments.
     * Prioritizes specific sub-roles (academic_editor, tutor_moderator, etc.)
     * before falling back to generic super_admin or student.
     */
    protected function resolveUserRole(User $user): string
    {
        $rawRole = strtolower(trim((string)$user->role));

        // 1. If explicit administrative role is set in the database, ALWAYS prioritize it!
        if (in_array($rawRole, [
            'academic_editor',
            'tutor_moderator',
            'support_moderator',
            'finance_auditor',
            'super_admin'
        ])) {
            return $rawRole;
        }

        // 2. Handle aliases like 'tutor' or 'academic'
        if (str_contains($rawRole, 'academic') || str_contains($rawRole, 'curriculum')) {
            return 'academic_editor';
        }
        if (str_contains($rawRole, 'tutor')) {
            return 'tutor_moderator';
        }
        if (str_contains($rawRole, 'support')) {
            return 'support_moderator';
        }

        // 3. If is_admin is true and no specific sub-role is set, default to super_admin
        if (!empty($user->is_admin)) {
            return 'super_admin';
        }

        // 4. Check if user is an approved tutor
        $tutorProfile = $user->tutorProfile()->first();
        if ($tutorProfile && strtolower((string)$tutorProfile->status) === 'approved') {
            return 'tutor_moderator';
        }

        // 5. Default to student
        return 'student';
    }

    /**
     * Reusable formatter to ensure every auth response has the identical, complete RBAC payload.
     */
    protected function formatUserPayload(User $user): array
    {
        $profile = $user->studentProfile()->with('grade')->first();
        $tutorProfile = $user->tutorProfile()->first();
        $isTutorApproved = $tutorProfile && strtolower((string)$tutorProfile->status) === 'approved';
        $resolvedRole = $this->resolveUserRole($user);

        return [
            'id' => $user->id,
            'name' => trim("{$user->first_name} {$user->last_name}") ?: ($user->name ?? 'User'),
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'grade' => (int) $user->grade,
            'grade_name' => ($profile && $profile->grade) ? $profile->grade->name : null,
            'is_admin' => (int) $user->is_admin,
            'role' => $resolvedRole, // Exact normalized RBAC role
            'school' => $profile ? $profile->school : null,
            'phone' => $profile ? $profile->phone : null,
            'photo_url' => $user->profile_photo_url,
            'avatar_url' => $user->profile_photo_url,
            'tutor_status' => $tutorProfile ? $tutorProfile->status : null,
            'is_tutor' => $isTutorApproved,
            'tutor_profile_id' => $tutorProfile ? $tutorProfile->id : null,
        ];
    }

    /**
     * Register a new user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $input = $this->normalizeAuthInputs($request);

        $validator = Validator::make($input, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'grade' => 'required',
            'school' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $gradeRecord = $this->resolveAssignableUserGrade($input['grade'] ?? null);

        if (!$gradeRecord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => [
                    'grade' => ['The selected grade is invalid or not available for user assignment.'],
                ],
            ], 422);
        }

        // Generate 6-digit OTP
        $otp = (string) random_int(100000, 999999);

        $user = DB::transaction(function () use ($input, $gradeRecord, $otp) {
            $createdUser = User::create([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'email' => strtolower(trim($input['email'])),
                'password' => Hash::make($input['password']),
                'grade' => $gradeRecord->id,
                'role' => 'student', // Default new registrations to student
                'is_admin' => 0,
                'otp' => $otp,
                'otp_expires_at' => now()->addMinutes(10),
            ]);

            $school = (isset($input['school']) && !is_null($input['school']) && trim((string)$input['school']) !== '') ? trim((string)$input['school']) : null;
            $phone = (isset($input['phone']) && !is_null($input['phone']) && trim((string)$input['phone']) !== '') ? trim((string)$input['phone']) : null;

            $createdUser->studentProfile()->create([
                'grade_id' => $gradeRecord->id,
                'school' => $school,
                'phone' => $phone,
            ]);

            return $createdUser;
        });

        // Send Email with exception safety
        try {
            Mail::to($user->email)->send(new OtpMail($otp));
        } catch (\Throwable $e) {
            Log::error('OTP Email delivery failed for user ' . $user->email . ': ' . $e->getMessage());
        }

        $otpExpiresAt = $user->otp_expires_at instanceof \Carbon\CarbonInterface
            ? $user->otp_expires_at->toISOString()
            : (is_string($user->otp_expires_at) ? $user->otp_expires_at : now()->addMinutes(10)->toISOString());

        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful. Please check your email for the OTP.',
            'data' => [
                'email' => $user->email,
                'otp_expires_at' => $otpExpiresAt,
            ]
        ], 201);
    }

    /**
     * Verify the OTP sent to the user's email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (!$user || !$user->otp) {
            return response()->json(['status' => 'error', 'message' => 'Invalid OTP'], 422);
        }

        if ($user->otp_expires_at && now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'OTP has expired.'], 422);
        }

        if (!hash_equals((string) $user->otp, (string) $request->otp)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid or expired code.'], 422);
        }

        // OTP is correct! Clear it from the database so it cannot be used again.
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->email_verified_at = now();
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;
        $userData = $this->formatUserPayload($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Email verified successfully!',
            'token' => $token,
            'user' => $userData,
            'data' => [
                'token' => $token,
                'user' => $userData,
            ],
        ], 200);
    }

    /**
     * Resend a fresh 6-digit OTP to user email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found with this email address.',
            ], 422);
        }

        if (!is_null($user->email_verified_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email is already verified. Please sign in.',
            ], 400);
        }

        // Generate fresh 6-digit OTP, invalidating any previous OTP
        $otp = (string) random_int(100000, 999999);
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        try {
            Mail::to($user->email)->send(new OtpMail($otp));
        } catch (\Throwable $e) {
            Log::error('OTP Resend Email delivery failed for user ' . $user->email . ': ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'A new OTP has been sent to your email address.',
            'data' => ['email' => $user->email],
        ], 200);
    }

    /**
     * Authenticate a user and return a Sanctum token along with the complete RBAC user payload.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Invalid email or password',
            ], 401);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json([
                'status' => 'error',
                'code' => 'EMAIL_NOT_VERIFIED',
                'message' => 'Please verify your email address before signing in.',
                'data' => [
                    'email' => $user->email,
                    'can_resend_otp' => true,
                ],
            ], 403);
        }

        if (isset($user->is_active) && !$user->is_active) {
            return response()->json([
                'status' => 'error',
                'code' => 'ACCOUNT_DEACTIVATED',
                'message' => 'Your account has been deactivated. Please contact an administrator.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $userData = $this->formatUserPayload($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'token' => $token,
            'user' => $userData, // Explicit top-level user object for Next.js Admin
            'data' => [
                'token' => $token,
                'user' => $userData, // Nested user object for Mobile/Student frontend
            ],
        ], 200);
    }

    /**
     * Update authenticated user profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $input = $this->normalizeAuthInputs($request);

        $validator = Validator::make($input, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'grade' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $gradeRecord = $this->resolveAssignableUserGrade($input['grade']);

        if (!$gradeRecord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Users can only be assigned to Grade 9, Grade 10, Grade 11, or Grade 12.',
            ], 422);
        }

        $user->update([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => strtolower(trim($input['email'])),
            'grade' => $gradeRecord->id,
        ]);

        $userData = $this->formatUserPayload($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $userData,
            ],
            'user' => $userData,
        ], 200);
    }

    /**
     * Resolve and validate user grade (must be Grade 9, 10, 11, or 12).
     *
     * @param mixed $inputGrade
     * @return Grade|null
     */
    protected function resolveAssignableUserGrade($inputGrade): ?Grade
    {
        if ($inputGrade === null || (is_string($inputGrade) && trim($inputGrade) === '')) {
            return null;
        }

        $rawStr = trim((string) $inputGrade);

        // 1. Try lookup by exact database primary key ID if numeric
        if (is_numeric($rawStr)) {
            $gradeById = Grade::find((int) $rawStr);
            if ($gradeById && $gradeById->isUserAssignable()) {
                return $gradeById;
            }
        }

        // 2. Try exact or case-insensitive match on grades.name (e.g., "Grade 9", "grade 10")
        $gradeByName = Grade::whereRaw('LOWER(name) = ?', [strtolower($rawStr)])->first();
        if ($gradeByName && $gradeByName->isUserAssignable()) {
            return $gradeByName;
        }

        // 3. Try lookup by number format (e.g. input "9" -> name "Grade 9")
        preg_match('/\d+/', $rawStr, $matches);
        if (!empty($matches)) {
            $num = (int) $matches[0];
            $gradeByNum = Grade::where('name', 'Grade ' . $num)
                ->orWhere('name', (string) $num)
                ->first();
            if ($gradeByNum && $gradeByNum->isUserAssignable()) {
                return $gradeByNum;
            }
        }

        return null;
    }

    /**
     * Send password reset link to user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', strtolower(trim($request->email)))->first();
        if ($user) {
            Password::sendResetLink(['email' => $request->email]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'If an account exists for this email, a password reset link has been sent.',
        ], 200);
    }

    /**
     * Reset user password using token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'status' => 'success',
                'message' => 'Password reset successfully',
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Password reset link has expired or is invalid.',
        ], 422);
    }

    /**
     * Logout the authenticated user by revoking the current Sanctum access token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        app('auth')->forgetGuards();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
        ], 200);
    }

    /**
     * Change the authenticated user's password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The provided current password is incorrect.',
                'errors' => [
                    'current_password' => ['The provided current password is incorrect.'],
                ],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully',
        ], 200);
    }

    /**
     * Refresh authentication token for logged-in user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();

        if (is_null($user->email_verified_at)) {
            return response()->json([
                'status' => 'error',
                'code' => 'EMAIL_NOT_VERIFIED',
                'message' => 'Please verify your email address before signing in.',
            ], 403);
        }

        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        app('auth')->forgetGuards();

        $token = $user->createToken('auth_token')->plainTextToken;
        $userData = $this->formatUserPayload($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Token refreshed successfully',
            'token' => $token,
            'user' => $userData,
            'data' => [
                'token' => $token,
                'user' => $userData,
            ],
        ], 200);
    }

    /**
     * Get authenticated user identity resolved via Sanctum Bearer token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userData = $this->formatUserPayload($user);

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $userData,
            ],
            // Top-level aliases for flexible frontend consumption
            'user' => $userData,
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Authenticate or register a user via Google Sign-In.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function googleLogin(Request $request): JsonResponse
    {
        $input = $this->normalizeAuthInputs($request);

        $validator = Validator::make($input, [
            'email' => 'required|email',
            'google_id' => 'required|string',
            'first_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', strtolower(trim($input['email'])))->first();

        if ($user) {
            if (empty($user->google_id)) {
                $user->google_id = $input['google_id'];
                $user->email_verified_at = $user->email_verified_at ?? now();
                $user->save();
            }
        } else {
            $user = User::create([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'] ?? '',
                'email' => strtolower(trim($input['email'])),
                'google_id' => $input['google_id'],
                'password' => null,
                'role' => 'student',
                'is_admin' => 0,
                'email_verified_at' => now(),
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $needsOnboarding = is_null($user->grade);
        $userData = $this->formatUserPayload($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Google Login successful',
            'token' => $token,
            'needs_profile' => $needsOnboarding,
            'user' => $userData,
            'data' => [
                'token' => $token,
                'user' => $userData,
            ],
        ], 200);
    }

    /**
     * Save student grade and complete profile.
     */
    public function saveStudentGrade(Request $request): JsonResponse
    {
        $user = $request->user();
        $input = $this->normalizeAuthInputs($request);

        $validator = Validator::make($input, [
            'grade' => 'required',
            'school' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $gradeRecord = $this->resolveAssignableUserGrade($input['grade']);

        if (!$gradeRecord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => ['grade' => ['The selected grade is invalid or not available.']],
            ], 422);
        }

        $user->update([
            'grade' => $gradeRecord->id,
        ]);

        $user->studentProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'grade_id' => $gradeRecord->id,
                'school' => $input['school'] ?? null,
                'phone' => $input['phone'] ?? null,
            ]
        );

        $userData = $this->formatUserPayload($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile completed successfully!',
            'data' => [
                'next_step' => 'go_to_learn',
                'user' => $userData,
            ],
            'user' => $userData,
        ], 200);
    }
}