<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Contact;
use App\Mail\ContactReplyMail;
use App\Services\AuditLogService;

class AdminContactController extends Controller
{
    /**
     * List contacts with filters, search, pagination, and real-time status counts.
     * GET /api/admin/contacts
     */
    public function index(Request $request)
    {
        $query = Contact::query();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', strtolower($request->status));
        }

        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhere('subject', 'like', "%{$term}%")
                  ->orWhere('message', 'like', "%{$term}%");
            });
        }

        $contacts = $query->orderBy('id', 'desc')->paginate($request->integer('per_page', 20));

        // Real-time status counts
        $counts = [
            'total' => Contact::count(),
            'unread' => Contact::where('status', 'unread')->count(),
            'read' => Contact::where('status', 'read')->count(),
            'replied' => Contact::where('status', 'replied')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $contacts->items(),
            'counts' => $counts,
            'pagination' => [
                'total' => $contacts->total(),
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage(),
            ]
        ]);
    }

    /**
     * Mark an inquiry as read.
     * PATCH /api/admin/contacts/{id}/read
     */
    public function markAsRead($id)
    {
        $contact = Contact::find($id);
        if (!$contact) {
            return response()->json(['status' => 'error', 'message' => 'Contact message not found.'], 404);
        }

        if ($contact->status === 'unread') {
            $oldStatus = $contact->status;
            $contact->update(['status' => 'read']);

            if (class_exists(AuditLogService::class)) {
                AuditLogService::log('contact.read', $contact, ['status' => $oldStatus], [
                    'id' => $contact->id,
                    'status' => 'read',
                    'email' => $contact->email,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Message marked as read.',
            'data' => $contact->fresh(),
        ]);
    }

    /**
     * Dispatch official reply email to student and update database.
     * POST /api/admin/contacts/{id}/reply
     */
    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|min:5',
        ]);

        $contact = Contact::find($id);
        if (!$contact) {
            return response()->json(['status' => 'error', 'message' => 'Inquiry not found.'], 404);
        }

        $replyMessage = trim($request->input('message'));

        // 1. Dispatch actual email to student's inbox
        try {
            Mail::to($contact->email)->send(new ContactReplyMail($contact, $replyMessage));
        } catch (\Throwable $e) {
            Log::error("Failed to send contact reply email to {$contact->email}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Could not dispatch email. Please check your SMTP settings: ' . $e->getMessage()
            ], 500);
        }

        // 2. Update status in database
        $oldStatus = $contact->status;
        $contact->update([
            'status' => 'replied',
            'reply_message' => $replyMessage,
            'replied_at' => now(),
            'admin_id' => auth()->id() ?? 1,
        ]);

        // 3. Log to Audit Trail
        if (class_exists(AuditLogService::class)) {
            AuditLogService::log('contact.replied', $contact, ['status' => $oldStatus], [
                'id' => $contact->id,
                'status' => 'replied',
                'recipient' => $contact->email,
                'subject' => $contact->subject,
                'reply_message_preview' => mb_substr($replyMessage, 0, 100),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Reply successfully sent to {$contact->email}.",
            'data' => $contact->fresh(),
        ]);
    }

    /**
     * Delete a contact inquiry.
     * DELETE /api/admin/contacts/{id}
     */
    public function destroy($id)
    {
        $contact = Contact::find($id);
        if (!$contact) {
            return response()->json(['status' => 'error', 'message' => 'Inquiry not found.'], 404);
        }

        $snapshot = [
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'subject' => $contact->subject,
            'status' => $contact->status,
        ];

        $contact->delete();

        if (class_exists(AuditLogService::class)) {
            AuditLogService::log('contact.deleted', 'App\Models\Contact', $snapshot, null);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Message deleted successfully.'
        ]);
    }
}