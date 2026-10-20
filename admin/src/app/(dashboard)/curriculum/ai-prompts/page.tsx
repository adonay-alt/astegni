"use client"

import React, { useState, useEffect, useRef } from "react"
import Link from "next/link"
import { apiClient } from "@/lib/api-client"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { Textarea } from "@/components/ui/textarea"
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter,
} from "@/components/ui/card"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog"
import {
  Sparkles,
  RotateCcw,
  Save,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Tag,
  HelpCircle,
  FileText,
  Lock,
  SlidersHorizontal,
  Plus,
  Minus,
  Check,
  Zap,
  History,
  ShieldCheck,
} from "lucide-react"

interface ApiResponseData {
  notes_prompt: string
  default_notes_prompt: string
  quiz_question_count: number
  allowed_placeholders: string[]
}

export default function AiPromptsAndSettingsPage() {
  // Main State
  const [notesPrompt, setNotesPrompt] = useState<string>("")
  const [defaultNotesPrompt, setDefaultNotesPrompt] = useState<string>("")
  const [quizQuestionCount, setQuizQuestionCount] = useState<number>(10)
  const [allowedPlaceholders, setAllowedPlaceholders] = useState<string[]>([
    "{chapter_title}",
    "{grade_level}",
    "{subject_name}",
  ])

  // Track original state to detect unsaved changes
  const [initialPrompt, setInitialPrompt] = useState<string>("")
  const [initialQuizCount, setInitialQuizCount] = useState<number>(10)

  // Loading & Action State
  const [isLoading, setIsLoading] = useState<boolean>(true)
  const [isSaving, setIsSaving] = useState<boolean>(false)
  const [isResetting, setIsResetting] = useState<boolean>(false)
  const [resetDialogOpen, setResetDialogOpen] = useState<boolean>(false)

  // Notification State
  const [notification, setNotification] = useState<{
    type: "success" | "error"
    message: string
  } | null>(null)

  // Feedback for tag click
  const [copiedTag, setCopiedTag] = useState<string | null>(null)

  // Textarea Ref for cursor position insertion
  const textareaRef = useRef<HTMLTextAreaElement>(null)

  // Auto-dismiss notification after 5 seconds
  useEffect(() => {
    if (notification) {
      const timer = setTimeout(() => {
        setNotification(null)
      }, 5000)
      return () => clearTimeout(timer)
    }
  }, [notification])

  // Fetch AI Prompt Configuration
  const fetchSettings = async () => {
    setIsLoading(true)
    setNotification(null)
    try {
      const res = await apiClient.get<{ status: string; data: ApiResponseData }>(
        "/admin/ai-prompts"
      )
      if (res.data?.data) {
        const data = res.data.data
        setNotesPrompt(data.notes_prompt || "")
        setDefaultNotesPrompt(data.default_notes_prompt || "")
        setQuizQuestionCount(data.quiz_question_count || 10)
        setInitialPrompt(data.notes_prompt || "")
        setInitialQuizCount(data.quiz_question_count || 10)
        if (data.allowed_placeholders && data.allowed_placeholders.length > 0) {
          setAllowedPlaceholders(data.allowed_placeholders)
        }
      }
    } catch (err: unknown) {
      const errorMsg =
        err instanceof Error
          ? err.message
          : "Failed to load AI Prompt configuration. Please check your network connection."
      setNotification({
        type: "error",
        message: errorMsg,
      })
    } finally {
      setIsLoading(false)
    }
  }

  useEffect(() => {
    fetchSettings()
  }, [])

  // Insert dynamic placeholder tag at cursor position
  const handleInsertTag = (tag: string) => {
    const textarea = textareaRef.current
    if (!textarea) {
      setNotesPrompt((prev) => prev + " " + tag)
      return
    }

    const start = textarea.selectionStart ?? notesPrompt.length
    const end = textarea.selectionEnd ?? notesPrompt.length
    const updated =
      notesPrompt.substring(0, start) + tag + notesPrompt.substring(end)

    setNotesPrompt(updated)
    setCopiedTag(tag)
    setTimeout(() => setCopiedTag(null), 1500)

    // Restore focus and position cursor right after inserted tag
    setTimeout(() => {
      textarea.focus()
      const newCursorPos = start + tag.length
      textarea.setSelectionRange(newCursorPos, newCursorPos)
    }, 10)
  }

  // Save changes
  const handleSaveChanges = async () => {
    if (!notesPrompt.trim()) {
      setNotification({
        type: "error",
        message: "Chapter notes prompt cannot be empty.",
      })
      return
    }

    if (notesPrompt.trim().length < 50) {
      setNotification({
        type: "error",
        message: "Chapter notes prompt must be at least 50 characters long.",
      })
      return
    }

    if (quizQuestionCount < 3 || quizQuestionCount > 30) {
      setNotification({
        type: "error",
        message: "Quiz question count must be between 3 and 30.",
      })
      return
    }

    setIsSaving(true)
    setNotification(null)

    try {
      const res = await apiClient.put<{
        status: string
        message?: string
        data: ApiResponseData
      }>("/admin/ai-prompts", {
        notes_prompt: notesPrompt,
        quiz_question_count: quizQuestionCount,
      })

      if (res.data?.data) {
        setInitialPrompt(res.data.data.notes_prompt)
        setInitialQuizCount(res.data.data.quiz_question_count)
      }

      setNotification({
        type: "success",
        message:
          res.data?.message ||
          "AI prompt template and quiz parameters saved successfully.",
      })
    } catch (err: unknown) {
      let msg = "Failed to save AI configuration."
      if (typeof err === "object" && err !== null && "response" in err) {
        const axErr = err as { response?: { data?: { message?: string } } }
        if (axErr.response?.data?.message) {
          msg = axErr.response.data.message
        }
      }
      setNotification({
        type: "error",
        message: msg,
      })
    } finally {
      setIsSaving(false)
    }
  }

  // Reset notes prompt to system default
  const handleResetToDefault = async () => {
    setIsResetting(true)
    setNotification(null)

    try {
      const res = await apiClient.post<{
        status: string
        message?: string
        data: ApiResponseData
      }>("/admin/ai-prompts/reset-notes")

      if (res.data?.data) {
        setNotesPrompt(res.data.data.notes_prompt)
        setInitialPrompt(res.data.data.notes_prompt)
      } else if (defaultNotesPrompt) {
        setNotesPrompt(defaultNotesPrompt)
        setInitialPrompt(defaultNotesPrompt)
      }

      setResetDialogOpen(false)
      setNotification({
        type: "success",
        message:
          res.data?.message ||
          "Chapter revision notes prompt reset to system default.",
      })
    } catch (err: unknown) {
      let msg = "Failed to reset notes prompt."
      if (typeof err === "object" && err !== null && "response" in err) {
        const axErr = err as { response?: { data?: { message?: string } } }
        if (axErr.response?.data?.message) {
          msg = axErr.response.data.message
        }
      }
      setNotification({
        type: "error",
        message: msg,
      })
    } finally {
      setIsResetting(false)
    }
  }

  const isDirty =
    notesPrompt !== initialPrompt || quizQuestionCount !== initialQuizCount

  return (
    <div className="flex-1 space-y-6 p-4 md:p-8 max-w-7xl mx-auto">
      {/* Header Section */}
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-5">
        <div>
          <div className="flex items-center gap-2 mb-1">
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              Academic Curriculum (LMS)
            </span>
            <span className="text-slate-400">/</span>
            <span className="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
              AI Settings
            </span>
          </div>
          <div className="flex items-center gap-3">
            <h1 className="text-2xl md:text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
              AI Prompts & Settings
            </h1>
          </div>
          <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Configure the system prompt instructions and parameters powering
            Gemini AI notes synthesis and chapter quiz drills.
          </p>
        </div>

        {/* Action Controls in Header */}
        <div className="flex items-center gap-3">
          <Link href="/settings/audit-logs">
            <Button
              variant="outline"
              size="sm"
              className="text-xs font-semibold gap-1.5 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
            >
              <History className="h-3.5 w-3.5 text-indigo-500" />
              <span>Audit Trail</span>
            </Button>
          </Link>
          {isDirty && (
            <Badge
              variant="outline"
              className="bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30 text-xs px-2.5 py-1"
            >
              Unsaved changes
            </Badge>
          )}
          <Button
            onClick={handleSaveChanges}
            disabled={isLoading || isSaving || !isDirty}
            className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm font-medium transition-all"
          >
            {isSaving ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Saving...
              </>
            ) : (
              <>
                <Save className="mr-2 h-4 w-4" />
                Save All Changes
              </>
            )}
          </Button>
        </div>
      </div>


      {/* Floating or Top Alert Notification */}
      {notification && (
        <div
          className={`flex items-center justify-between p-4 rounded-lg border text-sm transition-all duration-200 ${
            notification.type === "success"
              ? "bg-emerald-50 text-emerald-900 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-200 dark:border-emerald-800"
              : "bg-rose-50 text-rose-900 border-rose-200 dark:bg-rose-950/40 dark:text-rose-200 dark:border-rose-800"
          }`}
        >
          <div className="flex items-center gap-3">
            {notification.type === "success" ? (
              <CheckCircle2 className="h-5 w-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
            ) : (
              <AlertCircle className="h-5 w-5 text-rose-600 dark:text-rose-400 shrink-0" />
            )}
            <span className="font-medium">{notification.message}</span>
          </div>
          <button
            onClick={() => setNotification(null)}
            className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs font-semibold ml-4"
          >
            Dismiss
          </button>
        </div>
      )}

      {/* Initial Loading Skeleton */}
      {isLoading ? (
        <div className="flex flex-col items-center justify-center py-24 space-y-4">
          <Loader2 className="h-10 w-10 animate-spin text-indigo-600" />
          <p className="text-sm text-slate-500">Loading AI prompt settings...</p>
        </div>
      ) : (
        <div className="space-y-6">
          {/* Card 1: Chapter Notes AI Prompt */}
          <Card className="border border-slate-200 dark:border-slate-800 shadow-sm bg-white dark:bg-slate-900/60">
            <CardHeader className="pb-4">
              <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div className="space-y-1">
                  <div className="flex items-center gap-2">
                    <div className="p-1.5 rounded-md bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                      <Sparkles className="h-4 w-4" />
                    </div>
                    <CardTitle className="text-lg font-semibold">
                      Chapter Revision Notes Prompt
                    </CardTitle>
                  </div>
                  <CardDescription>
                    Configure the prompt Gemini AI uses when reading chapter
                    PDFs and synthesizing structured revision notes for high
                    school students.
                  </CardDescription>
                </div>

                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => setResetDialogOpen(true)}
                  className="text-slate-600 dark:text-slate-300 hover:text-rose-600 dark:hover:text-rose-400 hover:border-rose-300 dark:hover:border-rose-800 transition-colors"
                >
                  <RotateCcw className="mr-1.5 h-3.5 w-3.5" />
                  Reset to Default
                </Button>
              </div>
            </CardHeader>

            <CardContent className="space-y-4">
              {/* Placeholders helper toolbar */}
              <div className="bg-slate-50 dark:bg-slate-950/40 p-3.5 rounded-lg border border-slate-200 dark:border-slate-800/80">
                <div className="flex flex-wrap items-center justify-between gap-2 mb-2">
                  <div className="flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                    <Tag className="h-3.5 w-3.5 text-indigo-500" />
                    <span>Available Dynamic Tags (click tag to insert at cursor):</span>
                  </div>
                  <span className="text-[11px] text-slate-500 dark:text-slate-400">
                    Substituted at generation time
                  </span>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  {allowedPlaceholders.map((placeholder) => {
                    const isPresent = notesPrompt.includes(placeholder)
                    const isJustCopied = copiedTag === placeholder

                    return (
                      <button
                        key={placeholder}
                        type="button"
                        onClick={() => handleInsertTag(placeholder)}
                        className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-mono font-medium transition-all ${
                          isJustCopied
                            ? "bg-emerald-600 text-white ring-2 ring-emerald-400 shadow-sm"
                            : isPresent
                            ? "bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800 hover:dark:bg-indigo-900/60"
                            : "bg-slate-200/70 text-slate-700 border border-slate-300 hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 hover:dark:bg-slate-700"
                        }`}
                        title={`Click to insert ${placeholder} into prompt textarea`}
                      >
                        {isJustCopied ? (
                          <Check className="h-3.5 w-3.5" />
                        ) : (
                          <Plus className="h-3 w-3 opacity-60" />
                        )}
                        <span>{placeholder}</span>
                        {isPresent && (
                          <span className="ml-1 w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-indigo-400 inline-block" />
                        )}
                      </button>
                    )
                  })}
                </div>
              </div>

              {/* Textarea */}
              <div className="space-y-1.5">
                <div className="flex items-center justify-between text-xs text-slate-500">
                  <Label
                    htmlFor="notes-prompt-input"
                    className="font-medium text-slate-700 dark:text-slate-300"
                  >
                    System Prompt Template
                  </Label>
                  <span className="font-mono text-[11px]">
                    {notesPrompt.length} characters
                  </span>
                </div>

                <Textarea
                  id="notes-prompt-input"
                  ref={textareaRef}
                  value={notesPrompt}
                  onChange={(e) => setNotesPrompt(e.target.value)}
                  rows={15}
                  placeholder="Enter the system prompt instructions for Gemini AI notes generation..."
                  className="font-mono text-xs leading-relaxed border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-950/80 focus-visible:ring-indigo-500 resize-y p-3.5"
                />
              </div>
            </CardContent>

            <CardFooter className="bg-slate-50/50 dark:bg-slate-950/30 border-t border-slate-100 dark:border-slate-800/60 text-xs text-slate-500 dark:text-slate-400 py-3 px-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
              <div className="flex items-center gap-1.5">
                <FileText className="h-3.5 w-3.5 text-slate-400" />
                <span>
                  Tip: Include structured headings like Key Concepts, Worked
                  Examples, and Practice Questions.
                </span>
              </div>
              <span className="text-[11px] text-slate-400">
                Auto-saved when clicking Save All Changes
              </span>
            </CardFooter>
          </Card>

          {/* Card 2: Quiz Generation Settings */}
          <Card className="border border-slate-200 dark:border-slate-800 shadow-sm bg-white dark:bg-slate-900/60">
            <CardHeader className="pb-4">
              <div className="space-y-1">
                <div className="flex items-center gap-2">
                  <div className="p-1.5 rounded-md bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                    <SlidersHorizontal className="h-4 w-4" />
                  </div>
                  <CardTitle className="text-lg font-semibold">
                    Chapter Quiz Generation Settings
                  </CardTitle>
                </div>
                <CardDescription>
                  Set the target question volume synthesized per chapter drill.
                </CardDescription>
              </div>
            </CardHeader>

            <CardContent className="space-y-6">
              {/* Security / Schema Protection Callout */}
              <div className="flex items-start gap-3 p-3.5 rounded-lg bg-blue-50/70 border border-blue-200 text-blue-900 dark:bg-blue-950/30 dark:border-blue-900/50 dark:text-blue-200 text-xs leading-relaxed">
                <Lock className="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5 shrink-0" />
                <div>
                  <p className="font-semibold text-blue-950 dark:text-blue-100 mb-0.5">
                    Strict Exam Format & JSON Schema Rules are Locked
                  </p>
                  <p className="text-blue-800 dark:text-blue-300">
                    For curriculum accuracy, Ethiopian Grade 9-12 exam
                    compliance, and error-free JSON schema parsing, quiz prompt
                    formatting instructions remain permanently locked. You can
                    freely dial the number of multiple-choice questions
                    generated per chapter.
                  </p>
                </div>
              </div>

              {/* Number Input & Controls */}
              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <Label
                    htmlFor="quiz-count-input"
                    className="text-sm font-medium text-slate-800 dark:text-slate-200"
                  >
                    Number of Multiple-Choice Questions per Chapter Quiz
                  </Label>
                  <Badge
                    variant="outline"
                    className="font-mono text-xs px-2.5 py-0.5 bg-slate-100 dark:bg-slate-800"
                  >
                    {quizQuestionCount} Questions
                  </Badge>
                </div>

                <div className="flex flex-col sm:flex-row sm:items-center gap-3">
                  {/* Stepper Input */}
                  <div className="flex items-center max-w-[180px]">
                    <Button
                      type="button"
                      variant="outline"
                      size="icon"
                      onClick={() =>
                        setQuizQuestionCount((prev) => Math.max(3, prev - 1))
                      }
                      disabled={quizQuestionCount <= 3}
                      className="rounded-r-none border-r-0 h-10 w-10 text-slate-600 dark:text-slate-300"
                    >
                      <Minus className="h-4 w-4" />
                    </Button>
                    <Input
                      id="quiz-count-input"
                      type="number"
                      min={3}
                      max={30}
                      value={quizQuestionCount}
                      onChange={(e) => {
                        const val = parseInt(e.target.value, 10)
                        if (!isNaN(val)) {
                          setQuizQuestionCount(Math.max(3, Math.min(30, val)))
                        }
                      }}
                      className="rounded-none text-center font-mono font-bold h-10 border-slate-300 dark:border-slate-800"
                    />
                    <Button
                      type="button"
                      variant="outline"
                      size="icon"
                      onClick={() =>
                        setQuizQuestionCount((prev) => Math.min(30, prev + 1))
                      }
                      disabled={quizQuestionCount >= 30}
                      className="rounded-l-none border-l-0 h-10 w-10 text-slate-600 dark:text-slate-300"
                    >
                      <Plus className="h-4 w-4" />
                    </Button>
                  </div>

                  {/* Preset Buttons */}
                  <div className="flex flex-wrap items-center gap-1.5">
                    <span className="text-xs text-slate-400 mr-1">Presets:</span>
                    {[5, 10, 15, 20].map((preset) => (
                      <Button
                        key={preset}
                        type="button"
                        variant={
                          quizQuestionCount === preset ? "default" : "outline"
                        }
                        size="sm"
                        onClick={() => setQuizQuestionCount(preset)}
                        className={`text-xs h-8 px-2.5 font-mono ${
                          quizQuestionCount === preset
                            ? "bg-indigo-600 hover:bg-indigo-700 text-white"
                            : "text-slate-600 dark:text-slate-300"
                        }`}
                      >
                        {preset} Qs {preset === 10 && "(Default)"}
                      </Button>
                    ))}
                  </div>
                </div>

                <p className="text-xs text-slate-500 dark:text-slate-400">
                  Allowed range: 3 to 30 multiple-choice questions. Recommended:
                  10 questions for balanced chapter coverage.
                </p>
              </div>
            </CardContent>

            <CardFooter className="bg-slate-50/50 dark:bg-slate-950/30 border-t border-slate-100 dark:border-slate-800/60 py-3 px-6 flex items-center justify-between">
              <span className="text-xs text-slate-500 flex items-center gap-1.5">
                <HelpCircle className="h-3.5 w-3.5 text-slate-400" />
                Applicable when generating chapter quizzes from study notes or PDFs.
              </span>
            </CardFooter>
          </Card>

          {/* Sticky or Bottom Action Bar */}
          <div className="flex items-center justify-between pt-2 pb-6">
            <Button
              type="button"
              variant="outline"
              onClick={fetchSettings}
              disabled={isLoading || isSaving}
              className="text-slate-600 dark:text-slate-300"
            >
              Discard Changes
            </Button>

            <div className="flex items-center gap-3">
              <Button
                onClick={handleSaveChanges}
                disabled={isLoading || isSaving || !isDirty}
                className="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 shadow-sm"
              >
                {isSaving ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Saving Changes...
                  </>
                ) : (
                  <>
                    <Save className="mr-2 h-4 w-4" />
                    Save All Changes
                  </>
                )}
              </Button>
            </div>
          </div>
        </div>
      )}

      {/* Confirmation Dialog for Reset to Default */}
      <Dialog open={resetDialogOpen} onOpenChange={setResetDialogOpen}>
        <DialogContent className="sm:max-w-[500px]">
          <DialogHeader>
            <div className="flex items-center gap-2 text-rose-600 dark:text-rose-400 mb-1">
              <RotateCcw className="h-5 w-5" />
              <DialogTitle>Reset Notes Prompt Template?</DialogTitle>
            </div>
            <DialogDescription className="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
              Are you sure you want to reset the chapter notes prompt to the
              system default? Any custom instructions, formatting rules, or
              specialized curriculum guidelines you entered will be replaced by
              the original Ethiopian National Curriculum educator prompt.
            </DialogDescription>
          </DialogHeader>

          <div className="p-3.5 my-2 rounded-md bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-mono text-slate-600 dark:text-slate-300 max-h-36 overflow-y-auto leading-relaxed">
            {defaultNotesPrompt || "Loading default prompt..."}
          </div>

          <DialogFooter className="gap-2 sm:gap-0 mt-3">
            <Button
              type="button"
              variant="outline"
              onClick={() => setResetDialogOpen(false)}
              disabled={isResetting}
            >
              Cancel
            </Button>
            <Button
              type="button"
              variant="destructive"
              onClick={handleResetToDefault}
              disabled={isResetting}
              className="bg-rose-600 hover:bg-rose-700 text-white"
            >
              {isResetting ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Resetting...
                </>
              ) : (
                "Yes, Reset to Default"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
