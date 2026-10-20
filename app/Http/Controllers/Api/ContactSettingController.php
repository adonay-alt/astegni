<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactSetting;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Cache;

class ContactSettingController extends Controller
{
    /**
     * Public endpoint for Student App & Website: GET /api/contact-settings
     */
    public function getPublic()
    {
        $settings = Cache::remember('platform_contact_settings', 3600, function () {
            return ContactSetting::first() ?? ContactSetting::create([
                'email' => 'support@asteghi.com',
                'phone' => '+251 911 000 000',
            ]);
        });

        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }

    /**
     * Backwards-compatible alias for getPublic()
     */
    public function show()
    {
        return $this->getPublic();
    }

    /**
     * Admin endpoint: GET /api/admin/settings/contact
     */
    public function getAdmin()
    {
        $settings = ContactSetting::first() ?? ContactSetting::create([
            'email' => 'support@asteghi.com',
            'phone' => '+251 911 000 000',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }

    /**
     * Admin update endpoint: PUT /api/admin/settings/contact
     */
    public function updateAdmin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'secondary_phone' => 'nullable|string|max:50',
        ]);

        $settings = ContactSetting::first();
        $oldValues = $settings ? $settings->toArray() : [];

        if (!$settings) {
            $settings = new ContactSetting();
        }

        $settings->fill($validated);
        $settings->save();

        // Clear public cache immediately
        Cache::forget('platform_contact_settings');

        // Audit Trail
        if (class_exists(AuditLogService::class)) {
            AuditLogService::log(
                'settings.contact_updated',
                'App\Models\ContactSetting',
                $oldValues,
                $settings->toArray()
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Platform contact settings updated successfully.',
            'data' => $settings
        ]);
    }

    /**
     * Backwards-compatible alias for updateAdmin()
     */
    public function update(Request $request)
    {
        return $this->updateAdmin($request);
    }
}