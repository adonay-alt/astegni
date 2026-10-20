<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Mail\NewContactNotificationMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Store a newly submitted contact message (Public).
     * Sends an email notification to support@asteghi.com and saves to MySQL.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $contact = Contact::create([
            'name' => trim($request->input('name')),
            'email' => strtolower(trim($request->input('email'))),
            'subject' => $request->input('subject') ? trim($request->input('subject')) : 'General Inquiry',
            'message' => trim($request->input('message')),
            'status' => 'unread',
        ]);

        // Dispatch email notification to support@asteghi.com
        try {
            $supportRecipient = config('mail.support_email', 'support@asteghi.com');
            Mail::to($supportRecipient)->send(new NewContactNotificationMail($contact));
        } catch (\Throwable $e) {
            Log::warning("Could not dispatch inquiry notification to {$supportRecipient}: " . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Your message has been submitted successfully.',
            'data' => $contact,
        ], 201);
    }

    /**
     * Display a paginated list of submitted contact messages (Admin fallback).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Contact::query()->latest();

        if ($request->filled('status') && in_array($request->status, ['read', 'unread'])) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->input('per_page', 15);
        $messages = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $messages,
            'unread_count' => Contact::unread()->count(),
        ], 200);
    }

    /**
     * Mark a contact message as read (Admin fallback).
     */
    public function markAsRead(Contact $contact): JsonResponse
    {
        $contact->update(['status' => 'read']);

        return response()->json([
            'status' => 'success',
            'message' => 'Message marked as read.',
            'data' => $contact,
        ], 200);
    }

    /**
     * Delete a contact message (Admin fallback).
     */
    public function destroy(Contact $contact): JsonResponse
    {
        $contact->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Message deleted successfully.',
        ], 200);
    }
}