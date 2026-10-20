"use client"

import React, { useState, useEffect } from "react"
import { apiClient } from "@/lib/api-client"
import { OptionKey, QuizQuestion } from "@/types/quiz"
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
import {
  CheckCircle2,
  AlertCircle,
  HelpCircle,
  Loader2,
  Lightbulb,
} from "lucide-react"

interface QuestionEditorModalProps {
  isOpen: boolean
  onClose: () => void
  onSaved: (savedQuestion: QuizQuestion) => void
  questionToEdit?: QuizQuestion | null
  chapterId: number
  chapterTitle: string
  quizId?: number
  nextQuestionNumber?: number
}

export function QuestionEditorModal({
  isOpen,
  onClose,
  onSaved,
  questionToEdit,
  chapterId,
  chapterTitle,
  quizId,
  nextQuestionNumber = 1,
}: QuestionEditorModalProps) {
  const [questionText, setQuestionText] = useState("")
  const [options, setOptions] = useState<{ A: string; B: string; C: string; D: string }>({
    A: "",
    B: "",
    C: "",
    D: "",
  })
  const [correctAnswer, setCorrectAnswer] = useState<OptionKey>("A")
  const [explanation, setExplanation] = useState("")
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (isOpen) {
      if (questionToEdit) {
        setQuestionText(questionToEdit.question_text)
        setOptions({
          A: questionToEdit.options.A || "",
          B: questionToEdit.options.B || "",
          C: questionToEdit.options.C || "",
          D: questionToEdit.options.D || "",
        })
        setCorrectAnswer(questionToEdit.correct_answer || "A")
        setExplanation(questionToEdit.explanation || "")
      } else {
        setQuestionText("")
        setOptions({ A: "", B: "", C: "", D: "" })
        setCorrectAnswer("A")
        setExplanation("")
      }
      setError(null)
    }
  }, [isOpen, questionToEdit])

  const handleOptionChange = (key: OptionKey, value: string) => {
    setOptions((prev) => ({ ...prev, [key]: value }))
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    // Validation
    if (!questionText.trim()) {
      setError("Please write the question text / problem statement.")
      return
    }
    if (
      !options.A.trim() ||
      !options.B.trim() ||
      !options.C.trim() ||
      !options.D.trim()
    ) {
      setError("All four choices (Options A, B, C, D) are required.")
      return
    }
    if (!explanation.trim()) {
      setError("Please provide a solution rationale / formula derivation in the explanation.")
      return
    }

    setIsSubmitting(true)
    setError(null)

    const payload = {
      question_text: questionText.trim(),
      question: questionText.trim(),
      options: {
        A: options.A.trim(),
        B: options.B.trim(),
        C: options.C.trim(),
        D: options.D.trim(),
      },
      option_a: options.A.trim(),
      option_b: options.B.trim(),
      option_c: options.C.trim(),
      option_d: options.D.trim(),
      correct_answer: correctAnswer,
      explanation: explanation.trim(),
    }

    try {
      if (questionToEdit) {
        // Update existing question
        const res = await apiClient.put(`/admin/quiz-questions/${questionToEdit.id}`, payload)
        const updated = res.data?.data || res.data || {}

        onSaved({
          ...questionToEdit,
          question_text: updated.question_text || updated.question || payload.question_text,
          options: updated.options || {
            A: updated.option_a || payload.option_a,
            B: updated.option_b || payload.option_b,
            C: updated.option_c || payload.option_c,
            D: updated.option_d || payload.option_d,
          },
          correct_answer: (updated.correct_answer || correctAnswer) as OptionKey,
          explanation: updated.explanation || payload.explanation,
          updated_at: new Date().toISOString(),
        })
      } else {
        // Create new question (via quiz_id or auto-create chapter quiz)
        let targetQuizId = quizId
        if (!targetQuizId) {
          const createQuizRes = await apiClient.post(`/admin/chapters/${chapterId}/quizzes`, {
            title: `${chapterTitle} Quiz Drill`,
          })
          targetQuizId = createQuizRes.data?.data?.id
        }

        const res = await apiClient.post(`/admin/quizzes/${targetQuizId}/questions`, payload)
        const created = res.data?.data || res.data || {}

        onSaved({
          id: created.id || Date.now(),
          quiz_id: targetQuizId || created.quiz_id || chapterId,
          question_number: nextQuestionNumber,
          question_text: created.question_text || created.question || payload.question_text,
          options: created.options || {
            A: created.option_a || payload.option_a,
            B: created.option_b || payload.option_b,
            C: created.option_c || payload.option_c,
            D: created.option_d || payload.option_d,
          },
          correct_answer: (created.correct_answer || correctAnswer) as OptionKey,
          explanation: created.explanation || payload.explanation,
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        })
      }

      onClose()
    } catch (err: any) {
      console.error("Save quiz question error:", err)
      setError(
        err.response?.data?.message ||
          (err.response?.data?.errors
            ? Object.values(err.response.data.errors).flat().join(", ")
            : "Failed to save question. Please check your inputs and database connection.")
      )
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-h-[90vh] max-w-3xl overflow-hidden p-0 sm:rounded-2xl">
        <form onSubmit={handleSubmit} className="flex h-full max-h-[90vh] flex-col">
          {/* Modal Header */}
          <DialogHeader className="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <div className="flex items-center justify-between">
              <div>
                <DialogTitle className="flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-100">
                  <HelpCircle className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                  {questionToEdit ? `Edit Question #${questionToEdit.question_number}` : "Add Question to Quiz"}
                </DialogTitle>
                <DialogDescription className="text-xs text-slate-500">
                  Chapter: <span className="font-semibold text-slate-700 dark:text-slate-300">{chapterTitle}</span>
                </DialogDescription>
              </div>
            </div>
          </DialogHeader>

          {/* Form Fields Body */}
          <div className="flex-1 space-y-5 overflow-y-auto p-6">
            {error && (
              <div className="flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50/80 p-3.5 text-xs text-rose-800 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-300">
                <AlertCircle className="mt-0.5 h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400" />
                <span>{error}</span>
              </div>
            )}

            {/* 1. Problem Statement */}
            <div className="space-y-1.5">
              <Label htmlFor="question-text" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Question Text / Problem Statement <span className="text-rose-500">*</span>
              </Label>
              <Textarea
                id="question-text"
                value={questionText}
                onChange={(e) => setQuestionText(e.target.value)}
                placeholder="Enter the complete question prompt, formula context, or scenario..."
                className="min-h-[95px] resize-y text-sm font-medium leading-relaxed"
                required
              />
            </div>

            {/* 2. Four Choices Grid */}
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Multiple Choice Options (A, B, C, D) <span className="text-rose-500">*</span>
                </Label>
                <span className="text-[11px] text-slate-400">
                  Select which option is correct below
                </span>
              </div>

              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                {(["A", "B", "C", "D"] as OptionKey[]).map((key) => {
                  const isSelected = correctAnswer === key
                  return (
                    <div
                      key={key}
                      className={`relative rounded-xl border p-3 transition ${
                        isSelected
                          ? "border-emerald-500 bg-emerald-50/50 shadow-xs dark:border-emerald-600 dark:bg-emerald-950/20"
                          : "border-slate-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900"
                      }`}
                    >
                      <div className="mb-1.5 flex items-center justify-between">
                        <span
                          className={`flex h-6 w-6 items-center justify-center rounded-md text-xs font-bold ${
                            isSelected
                              ? "bg-emerald-600 text-white"
                              : "bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                          }`}
                        >
                          {key}
                        </span>

                        <button
                          type="button"
                          onClick={() => setCorrectAnswer(key)}
                          className={`flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-semibold transition ${
                            isSelected
                              ? "bg-emerald-600 text-white"
                              : "text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                          }`}
                        >
                          {isSelected ? (
                            <>
                              <CheckCircle2 className="h-3 w-3" />
                              Correct Answer
                            </>
                          ) : (
                            "Mark Correct"
                          )}
                        </button>
                      </div>

                      <Input
                        value={options[key]}
                        onChange={(e) => handleOptionChange(key, e.target.value)}
                        placeholder={`Option ${key} text...`}
                        className="h-9 text-xs"
                        required
                      />
                    </div>
                  )
                })}
              </div>
            </div>

            {/* 3. Correct Answer Quick Selector Bar */}
            <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/60 p-3.5 dark:border-slate-800 dark:bg-slate-900/40">
              <div>
                <p className="text-xs font-semibold text-slate-800 dark:text-slate-200">
                  Target Correct Answer Key
                </p>
                <p className="text-[11px] text-slate-500">
                  Active key: <strong className="text-emerald-600 dark:text-emerald-400">Option {correctAnswer}</strong>
                </p>
              </div>

              <div className="flex items-center gap-1.5">
                {(["A", "B", "C", "D"] as OptionKey[]).map((key) => (
                  <button
                    key={key}
                    type="button"
                    onClick={() => setCorrectAnswer(key)}
                    className={`flex h-8 w-9 items-center justify-center rounded-lg text-xs font-bold transition ${
                      correctAnswer === key
                        ? "bg-emerald-600 text-white shadow-xs"
                        : "border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                    }`}
                  >
                    {key}
                  </button>
                ))}
              </div>
            </div>

            {/* 4. Detailed Solution Explanation */}
            <div className="space-y-1.5">
              <Label htmlFor="question-explanation" className="flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                <Lightbulb className="h-3.5 w-3.5 text-amber-500" />
                Step-by-Step Explanation / Solution Derivation <span className="text-rose-500">*</span>
              </Label>
              <Textarea
                id="question-explanation"
                value={explanation}
                onChange={(e) => setExplanation(e.target.value)}
                placeholder="Explain why the selected option is correct, including relevant physics/math formulas, definitions, and common pitfall distractors..."
                className="min-h-[100px] resize-y text-xs font-mono leading-relaxed"
                required
              />
            </div>
          </div>

          {/* Modal Footer */}
          <DialogFooter className="border-t border-slate-200 bg-slate-50/50 px-6 py-3.5 dark:border-slate-800 dark:bg-slate-900/40">
            <Button type="button" variant="outline" onClick={onClose} disabled={isSubmitting}>
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting} className="min-w-[130px] gap-2">
              {isSubmitting ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Saving...
                </>
              ) : (
                "Save Question"
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
