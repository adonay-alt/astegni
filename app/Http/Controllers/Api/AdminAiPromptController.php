<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\AiSetting;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Validator;
use Exception;

class AdminAiPromptController extends Controller
{
    /**
     * GET /api/admin/ai-prompts
     * Return current AI prompt templates, quiz parameters, and placeholder tags.
     */
    public function index(): JsonResponse
    {
        try {
            $notesPrompt = AiSetting::get('notes_prompt_template', AiSetting::DEFAULT_NOTES_PROMPT);
            $quizQuestionCount = (int) AiSetting::get('quiz_question_count', AiSetting::DEFAULT_QUIZ_COUNT);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'notes_prompt' => $notesPrompt,
                    'default_notes_prompt' => AiSetting::DEFAULT_NOTES_PROMPT,
                    'quiz_question_count' => $quizQuestionCount,
                    'allowed_placeholders' => AiSetting::ALLOWED_PLACEHOLDERS,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load AI prompt settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/admin/ai-prompts
     * Update notes prompt template and chapter quiz question count.
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes_prompt' => 'required|string|min:50',
            'quiz_question_count' => 'required|integer|min:3|max:30',
        ], [
            'notes_prompt.required' => 'The notes prompt template cannot be empty.',
            'notes_prompt.min' => 'The notes prompt template must be at least 50 characters long.',
            'quiz_question_count.required' => 'The quiz question count is required.',
            'quiz_question_count.integer' => 'The quiz question count must be a valid integer.',
            'quiz_question_count.min' => 'Quiz question count must be at least 3 questions.',
            'quiz_question_count.max' => 'Quiz question count cannot exceed 30 questions.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $oldValues = [
                'notes_prompt_template' => AiSetting::get('notes_prompt_template', AiSetting::DEFAULT_NOTES_PROMPT),
                'quiz_question_count' => (int) AiSetting::get('quiz_question_count', AiSetting::DEFAULT_QUIZ_COUNT),
            ];

            $newNotesPrompt = trim($request->input('notes_prompt'));
            $newQuizCount = (int) $request->input('quiz_question_count');

            // Persist settings
            AiSetting::set(
                'notes_prompt_template',
                $newNotesPrompt,
                'text',
                'System prompt template used by Gemini AI when generating chapter revision notes.'
            );

            AiSetting::set(
                'quiz_question_count',
                (string) $newQuizCount,
                'integer',
                'Default number of multiple-choice questions generated per chapter quiz (min: 3, max: 30).'
            );

            // Invalidate cache
            AiSetting::purgeCache();

            $newValues = [
                'notes_prompt_template' => $newNotesPrompt,
                'quiz_question_count' => $newQuizCount,
            ];

            // Audit log to the Immutable Administrative Audit Trail
            if (class_exists(AuditLogService::class)) {
                $adminId = auth()->id() ?? ($request->user() ? $request->user()->id : 1);
                $notesSetting = AiSetting::where('key', 'notes_prompt_template')->first();
                AuditLogService::log(
                    'ai_settings.updated',
                    $notesSetting ?: 'App\Models\AiSetting',
                    $oldValues,
                    $newValues,
                    (int) $adminId
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'AI Prompt and generation parameters updated successfully.',
                'data' => [
                    'notes_prompt' => $newNotesPrompt,
                    'default_notes_prompt' => AiSetting::DEFAULT_NOTES_PROMPT,
                    'quiz_question_count' => $newQuizCount,
                    'allowed_placeholders' => AiSetting::ALLOWED_PLACEHOLDERS,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update AI prompt settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/admin/ai-prompts/reset-notes
     * Reset notes prompt template back to system default.
     */
    public function resetNotesPrompt(Request $request): JsonResponse
    {
        try {
            $oldPrompt = AiSetting::get('notes_prompt_template', AiSetting::DEFAULT_NOTES_PROMPT);
            $currentQuizCount = (int) AiSetting::get('quiz_question_count', AiSetting::DEFAULT_QUIZ_COUNT);

            $notesSetting = AiSetting::set(
                'notes_prompt_template',
                AiSetting::DEFAULT_NOTES_PROMPT,
                'text',
                'System prompt template used by Gemini AI when generating chapter revision notes.'
            );

            AiSetting::purgeCache('notes_prompt_template');

            // Audit log to the Immutable Administrative Audit Trail
            if (class_exists(AuditLogService::class)) {
                $adminId = auth()->id() ?? ($request->user() ? $request->user()->id : 1);
                AuditLogService::log(
                    'ai_settings.reset_notes',
                    $notesSetting ?: 'App\Models\AiSetting',
                    [
                        'notes_prompt_template' => $oldPrompt,
                        'quiz_question_count' => $currentQuizCount,
                    ],
                    [
                        'notes_prompt_template' => AiSetting::DEFAULT_NOTES_PROMPT,
                        'quiz_question_count' => $currentQuizCount,
                    ],
                    (int) $adminId
                );
            }

            $currentQuizCount = (int) AiSetting::get('quiz_question_count', AiSetting::DEFAULT_QUIZ_COUNT);

            return response()->json([
                'status' => 'success',
                'message' => 'Notes prompt template has been reset to system default.',
                'data' => [
                    'notes_prompt' => AiSetting::DEFAULT_NOTES_PROMPT,
                    'default_notes_prompt' => AiSetting::DEFAULT_NOTES_PROMPT,
                    'quiz_question_count' => $currentQuizCount,
                    'allowed_placeholders' => AiSetting::ALLOWED_PLACEHOLDERS,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reset notes prompt template: ' . $e->getMessage(),
            ], 500);
        }
    }
}
