<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Stream;
use App\Models\StudentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1.load both grade AND stream
        $profile = StudentProfile::with(['grade', 'stream'])
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'message' => 'Profile retrieved successfully.',
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'is_admin' => (bool) $user->is_admin,
                'role' => $user->role ?? ($user->is_admin ? 'super_admin' : 'student'),

                'grade' => $profile?->grade?->name,
                'grade_id' => $profile?->grade_id,
                'school' => $profile?->school,
                
                // Return stream name for display, and stream_id for frontend selectors
                'stream' => $profile?->stream?->name,
                'stream_id' => $profile?->stream_id,

                'phone' => $profile?->phone,
                'gender' => $profile?->gender,
                'age' => $profile?->age,
                'date_of_birth' => $profile?->date_of_birth,
                'city_town' => $profile?->city_town,
                'region' => $profile?->region,
                'parent_guardian_contact' => $profile?->parent_guardian_contact,
                'photo' => $profile?->photo,
                'photo_url' => $profile?->photo_url,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($user->id)],

            // Student profile fields
            'grade' => ['sometimes', 'nullable'],
            'stream' => ['sometimes', 'nullable'], // Can be ID (1, 2) or Name ("Natural Science")
            'stream_id' => ['sometimes', 'nullable', 'exists:streams,id'],
            'school' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'gender' => ['sometimes', 'nullable', Rule::in(['male', 'female'])],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'city_town' => ['sometimes', 'nullable', 'string', 'max:100'],
            'region' => ['sometimes', 'nullable', 'string', 'max:100'],
            'parent_guardian_contact' => ['sometimes', 'nullable', 'string', 'max:50'],
            'photo' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $userData = [];
        $profileData = [];

        if (array_key_exists('first_name', $validated)) $userData['first_name'] = $validated['first_name'];
        if (array_key_exists('last_name', $validated)) $userData['last_name'] = $validated['last_name'];
        if (array_key_exists('email', $validated)) $userData['email'] = $validated['email'];

        // --- Handle Grade (ID or String) ---
        if ($request->has('grade')) {
            $rawGrade = $request->input('grade');
            if ($rawGrade !== null && (is_numeric($rawGrade) || (is_string($rawGrade) && trim($rawGrade) !== ''))) {
                $rawStr = trim((string) $rawGrade);
                $gradeRecord = is_numeric($rawStr) ? Grade::find((int) $rawStr) : null;
                if (!$gradeRecord) {
                    $gradeRecord = Grade::whereRaw('LOWER(name) = ?', [strtolower($rawStr)])->first();
                }
                if (!$gradeRecord) {
                    preg_match('/\d+/', $rawStr, $matches);
                    if (!empty($matches)) {
                        $num = (int) $matches[0];
                        $gradeRecord = Grade::where('name', 'Grade ' . $num)->orWhere('name', (string) $num)->first();
                    }
                }
                if ($gradeRecord) {
                    $userData['grade'] = $gradeRecord->id;
                    $profileData['grade_id'] = $gradeRecord->id;
                }
            } else {
                $profileData['grade_id'] = null;
            }
        }

        // --- Handle Stream (ID or String) ---
        if ($request->has('stream_id')) {
            $profileData['stream_id'] = $request->input('stream_id');
        } elseif ($request->has('stream')) {
            $rawStream = $request->input('stream');
            if ($rawStream !== null && trim((string)$rawStream) !== '') {
                $rawStr = trim((string) $rawStream);
                // Check if numeric ID (1 or 2)
                $streamRecord = is_numeric($rawStr) ? Stream::find((int) $rawStr) : null;
                
                // If not numeric, match by name ("Natural Science" or "Social Science")
                if (!$streamRecord) {
                    $streamRecord = Stream::whereRaw('LOWER(name) = ?', [strtolower($rawStr)])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($rawStr) . '%'])
                        ->first();
                }

                $profileData['stream_id'] = $streamRecord?->id;
            } else {
                // If set to empty/null (e.g. Grade 8 or Common Core)
                $profileData['stream_id'] = null;
            }
        }

        if (!empty($userData)) {
            $user->update($userData);
        }

        $normalizeOptional = fn($val) => (isset($val) && !is_null($val) && trim((string)$val) !== '') ? trim((string)$val) : null;

        if (array_key_exists('school', $validated)) $profileData['school'] = $normalizeOptional($validated['school']);
        if (array_key_exists('phone', $validated)) $profileData['phone'] = $normalizeOptional($validated['phone']);
        if (array_key_exists('gender', $validated)) $profileData['gender'] = $validated['gender'];
        if (array_key_exists('date_of_birth', $validated)) $profileData['date_of_birth'] = $validated['date_of_birth'];
        if (array_key_exists('city_town', $validated)) $profileData['city_town'] = $normalizeOptional($validated['city_town']);
        if (array_key_exists('region', $validated)) $profileData['region'] = $normalizeOptional($validated['region']);
        if (array_key_exists('parent_guardian_contact', $validated)) $profileData['parent_guardian_contact'] = $normalizeOptional($validated['parent_guardian_contact']);

        if ($request->hasFile('photo')) {
            $existingPhoto = StudentProfile::where('user_id', $user->id)->value('photo');
            if ($existingPhoto && !\Illuminate\Support\Str::startsWith($existingPhoto, ['http://', 'https://'])) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($existingPhoto)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($existingPhoto);
                }
            }
            $profileData['photo'] = $request->file('photo')->store('profile_photos', 'public');
        }

        if (!isset($profileData['grade_id']) && $user->grade) {
            $profileData['grade_id'] = $user->grade;
        }

        if (!empty($profileData)) {
            StudentProfile::updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );
        }

        $user->refresh();

        // Eager load relationships on refreshed profile
        $profile = StudentProfile::with(['grade', 'stream'])
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,

                'grade' => $profile?->grade?->name,
                'grade_id' => $profile?->grade_id,
                'school' => $profile?->school,

                // Returns both name and id safely
                'stream' => $profile?->stream?->name,
                'stream_id' => $profile?->stream_id,

                'phone' => $profile?->phone,
                'gender' => $profile?->gender,
                'age' => $profile?->age,
                'date_of_birth' => $profile?->date_of_birth,
                'city_town' => $profile?->city_town,
                'region' => $profile?->region,
                'parent_guardian_contact' => $profile?->parent_guardian_contact,
                'photo' => $profile?->photo,
                'photo_url' => $profile?->photo_url,
            ],
        ]);
    }

    public function uploadPhoto(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $studentProfile = StudentProfile::firstOrCreate(['user_id' => $user->id]);

        if ($studentProfile->photo && !\Illuminate\Support\Str::startsWith($studentProfile->photo, ['http://', 'https://'])) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($studentProfile->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($studentProfile->photo);
            }
        }

        $photoPath = $request->file('photo')->store('profile_photos', 'public');
        $studentProfile->update(['photo' => $photoPath]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile photo uploaded successfully',
            'data' => [
                'photo_url' => \Illuminate\Support\Facades\Storage::disk('public')->url($photoPath),
            ],
        ], 200);
    }
}