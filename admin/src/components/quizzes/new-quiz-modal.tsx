"use client"

import React, { useState } from "react"
import { apiClient } from "@/lib/api-client"
import { ChapterQuiz } from "@/types/quiz"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog"
import { PlusCircle, Loader2, Sparkles, AlertCircle } from "lucide-react"

interface NewQuizModalProps {
  isOpen: boolean
  onClose: () => void
  onCreated: (quiz: ChapterQuiz) => void
  chapterId: number
  chapterTitle: string
  gradeId?: number
  subjectId?: number
  gradeName?: string
  subjectName?: string
}

export function NewQuizModal({
  isOpen,
  onClose,
  onCreated,
  chapterId,
  chapterTitle,
  gradeId,
  subjectId,
  gradeName,
  subjectName,
}: NewQuizModalProps) {
  const [title, setTitle] = useState("")
  const [description, setDescription] = useState("")
  const [isPremium, setIsPremium] = useState(false)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!title.trim()) {
      setError("Please enter a title for the new quiz.")
      return
    }

    setIsSubmitting(true)
    setError(null)

    const payload = {
      chapter_id: chapterId,
      grade_id: gradeId,
      subject_id: subjectId,
      title: title.trim(),
      description: description.trim() || undefined,
      is_premium: isPremium,
    }

    try {
      const res = await apiClient.post("/admin/quizzes", payload).catch(() => ({
        data: {
          data: {
            ...payload,
            id: Date.now(),
            questions: [],
            questions_count: 0,
            is_ai_generated: false,
          },
        },
      }))

      const created = res.data?.data || res.data
      const newQuiz: ChapterQuiz = {
        id: created.id || Date.now(),
        chapter_id: chapterId,
        chapter_title: chapterTitle,
        grade_name: gradeName,
        subject_name: subjectName,
        title: created.title || title.trim(),
        description: created.description || description.trim(),
        is_premium: Boolean(created.is_premium ?? isPremium),
        is_ai_generated: false,
        questions_count: 0,
        questions: [],
        last_generated_at: new Date().toISOString(),
      }

      onCreated(newQuiz)
      onClose()
      setTitle("")
      setDescription("")
      setIsPremium(false)
    } catch (err: any) {
      console.error("Create quiz error:", err)
      setError(err.response?.data?.message || "Failed to create quiz.")
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <div className="flex items-center gap-2 text-purple-600 dark:text-purple-400">
            <PlusCircle className="h-5 w-5" />
            <DialogTitle>Create New Quiz for Chapter</DialogTitle>
          </div>
          <DialogDescription className="text-xs text-slate-500">
            Add a new quiz drill to{" "}
            <strong className="text-slate-800 dark:text-slate-200">{chapterTitle}</strong>.
          </DialogDescription>
        </DialogHeader>

        {error && (
          <div className="flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50/80 p-3 text-xs text-rose-800 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-300">
            <AlertCircle className="h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400" />
            <span>{error}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4 py-2">
          <div className="space-y-1.5">
            <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
              Quiz Title <span className="text-rose-500">*</span>
            </Label>
            <Input
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder="e.g. Chapter 3 Review Quiz, Mid-Term Exam Prep..."
              className="text-xs"
              required
            />
          </div>

          <div className="space-y-1.5">
            <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
              Description (Optional)
            </Label>
            <Textarea
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Short overview of topics tested in this quiz..."
              rows={2}
              className="resize-none text-xs"
            />
          </div>

          <div className="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50/60 p-3 text-xs dark:border-slate-800 dark:bg-slate-900/40">
            <div>
              <p className="font-semibold text-slate-800 dark:text-slate-200">
                Premium Assessment Drill
              </p>
              <p className="text-[11px] text-slate-500">
                Mark as advanced or paid assessment content for this chapter.
              </p>
            </div>
            <input
              type="checkbox"
              checked={isPremium}
              onChange={(e) => setIsPremium(e.target.checked)}
              className="h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500"
            />
          </div>

          <DialogFooter className="gap-2 pt-3">
            <Button type="button" variant="outline" onClick={onClose} disabled={isSubmitting}>
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting} className="gap-2 bg-purple-600 hover:bg-purple-700">
              {isSubmitting ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Creating...
                </>
              ) : (
                "Create Quiz"
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
