<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    protected function normalizeAuthInputs(Request $request): array
    {
        $data = $request->all();

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

        if (!isset($data['last_name']) || trim((string)$data['last_name']) === '') {
            if (isset($data['lastName']) && trim((string)$data['lastName']) !== '') {
                $data['last_name'] = trim((string)$data['lastName']);
            } elseif (isset($data['lastname']) && trim((string)$data['lastname']) !== '') {
                $data['last_name'] = trim((string)$data['lastname']);
            }
        }

        if (!isset($data['school']) || trim((string)$data['school']) === '') {
            if (isset($data['school_name']) && trim((string)$data['school_name']) !== '') {
                $data['school'] = trim((string)$data['school_name']);
            } elseif (isset($data['schoolName']) && trim((string)$data['schoolName']) !== '') {
                $data['school'] = trim((string)$data['schoolName']);
            }
        }

        if (!isset($data['phone']) || trim((string)$data['phone']) === '') {
            if (isset($data['phone_number']) && trim((string)$data['phone_number']) !== '') {
                $data['phone'] = trim((string)$data['phone_number']);
            } elseif (isset($data['phoneNumber']) && trim((string)$data['phoneNumber']) !== '') {
                $data['phone'] = trim((string)$data['phoneNumber']);
            }
        }

        return $data;
    }

    public function register(Request $request): JsonResponse
    {
        $input = $this->normalizeAuthInputs($request);

        $validator = Validator::make($input, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'grade' => 'required',
            'school' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $grade = Grade::find($input['grade']) ?: Grade::where('name', $input['grade'])->orWhere('name', 'Grade ' . $input['grade'])->first();

        if (! $grade || ! $grade->isUserAssignable()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Users can only be assigned to Grade 9, Grade 10, Grade 11, or Grade 12.',
            ], 422);
        }

        $user = User::create([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'grade' => $grade->id,
        ]);

        $school = (isset($input['school']) && !is_null($input['school']) && trim((string)$input['school']) !== '') ? trim((string)$input['school']) : null;

        $user->studentProfile()->create([
            'grade_id' => $grade->id,
            'school' => $school,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful',
            'user' => $user->load('studentProfile.grade'),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials',
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => $user->load('studentProfile.grade'),
        ], 200);
    }
}