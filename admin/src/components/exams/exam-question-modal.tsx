"use client"

import React, { useState, useEffect } from "react"
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { ExamQuestion, OptionKey } from "@/types/exam"
import { apiClient } from "@/lib/api-client"
import {
  HelpCircle,
  Image as ImageIcon,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Sparkles,
  Layers,
} from "lucide-react"

interface ExamQuestionModalProps {
  isOpen: boolean
  onClose: () => void
  onSave: (savedQuestion: ExamQuestion) => void
  questionToEdit?: ExamQuestion | null
  examYearId: number
  subjectId: number
  subjectName?: string
  nextQuestionNumber?: number
}

export function ExamQuestionModal({
  isOpen,
  onClose,
  onSave,
  questionToEdit,
  examYearId,
  subjectId,
  subjectName = "Subject",
  nextQuestionNumber = 1,
}: ExamQuestionModalProps) {
  const isEditing = !!questionToEdit

  const [questionNumber, setQuestionNumber] = useState<number>(nextQuestionNumber)
  const [questionText, setQuestionText] = useState("")
  const [diagramUrl, setDiagramUrl] = useState("")
  const [optA, setOptA] = useState("")
  const [optB, setOptB] = useState("")
  const [optC, setOptC] = useState("")
  const [optD, setOptD] = useState("")
  const [correctAnswer, setCorrectAnswer] = useState<OptionKey>("A")
  const [explanation, setExplanation] = useState("")

  const [isSubmitting, setIsSubmitting] = useState(false)
  const [errorMsg, setErrorMsg] = useState<string | null>(null)

  useEffect(() => {
    if (questionToEdit) {
      setQuestionNumber(questionToEdit.question_number)
      setQuestionText(questionToEdit.question_text)
      setDiagramUrl(questionToEdit.diagram_url || "")

      const rawOpts = questionToEdit.options as any
      if (Array.isArray(rawOpts)) {
        const findText = (k: string) => rawOpts.find((o) => o.key === k)?.text || ""
        setOptA(findText("A"))
        setOptB(findText("B"))
        setOptC(findText("C"))
        setOptD(findText("D"))
      } else if (rawOpts && typeof rawOpts === "object") {
        setOptA(rawOpts.A || "")
        setOptB(rawOpts.B || "")
        setOptC(rawOpts.C || "")
        setOptD(rawOpts.D || "")
      }

      setCorrectAnswer(questionToEdit.correct_answer || "A")
      setExplanation(questionToEdit.explanation || "")
    } else {
      setQuestionNumber(nextQuestionNumber)
      setQuestionText("")
      setDiagramUrl("")
      setOptA("")
      setOptB("")
      setOptC("")
      setOptD("")
      setCorrectAnswer("A")
      setExplanation("")
    }
    setErrorMsg(null)
  }, [questionToEdit, nextQuestionNumber, isOpen])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!questionText.trim()) {
      setErrorMsg("Please enter the question text/problem statement.")
      return
    }
    if (!optA.trim() || !optB.trim() || !optC.trim() || !optD.trim()) {
      setErrorMsg("All 4 options (A, B, C, D) must be provided.")
      return
    }

    setIsSubmitting(true)
    setErrorMsg(null)

    const payload = {
      exam_year_id: examYearId,
      subject_id: subjectId,
      question_number: Number(questionNumber) || 1,
      question_text: questionText.trim(),
      diagram_url: diagramUrl.trim() || null,
      options: {
        A: optA.trim(),
        B: optB.trim(),
        C: optC.trim(),
        D: optD.trim(),
      },
      correct_answer: correctAnswer,
      explanation: explanation.trim(),
    }

    try {
      let saved: ExamQuestion | null = null
      if (isEditing && questionToEdit) {
        try {
          const res = await apiClient.put(`/admin/national-exams/questions/${questionToEdit.id}`, payload)
          if (res?.data?.data) {
            saved = res.data.data
          }
        } catch {
          // Fallback to internal route below
        }

        if (!saved) {
          const localRes = await fetch(`/api/admin/national-exams/questions/${questionToEdit.id}`, {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
          })
          if (localRes.ok) {
            const localData = await localRes.json()
            if (localData?.data) {
              saved = localData.data
            }
          }
        }
      } else {
        try {
          const res = await apiClient.post("/admin/national-exams/questions", payload)
          if (res?.data?.data) {
            saved = res.data.data
          }
        } catch {
          // Fallback to internal route below
        }

        if (!saved) {
          const localRes = await fetch("/api/admin/national-exams/questions", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
          })
          if (localRes.ok) {
            const localData = await localRes.json()
            if (localData?.data) {
              saved = localData.data
            }
          }
        }
      }

      if (!saved) {
        throw new Error("Unable to save question to backend database.")
      }

      onSave(saved)
      onClose()
    } catch (err: any) {
      console.error("Save question error:", err)
      setErrorMsg(err.response?.data?.message || err.message || "Failed to save question.")
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto sm:rounded-2xl p-6 bg-white text-slate-900 border border-slate-200 shadow-xl">
        <DialogHeader className="border-b border-slate-100 pb-4">
          <div className="flex items-center justify-between pr-6">
            <div className="flex items-center gap-2.5">
              <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <HelpCircle className="h-5 w-5" />
              </div>
              <div>
                <DialogTitle className="text-base font-bold text-slate-900">
                  {isEditing ? `Edit Question #${questionNumber}` : "Add Question to Exam"}
                </DialogTitle>
                <p className="text-xs text-slate-500">
                  Target Subject: <span className="font-semibold text-slate-800">{subjectName}</span>
                </p>
              </div>
            </div>
            <Badge variant="outline" className="text-xs font-mono border-slate-300 text-slate-700 bg-slate-50">
              Q#{questionNumber}
            </Badge>
          </div>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4 pt-2">
          {errorMsg && (
            <div className="flex items-center gap-2 rounded-xl bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800">
              <AlertCircle className="h-4 w-4 shrink-0 text-rose-600" />
              <span>{errorMsg}</span>
            </div>
          )}

          {/* Row 1: Question Number & Diagram URL */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <Label className="text-xs font-semibold text-slate-700">
                Question Number
              </Label>
              <Input
                type="number"
                min={1}
                value={questionNumber}
                onChange={(e) => setQuestionNumber(parseInt(e.target.value, 10) || 1)}
                className="mt-1 font-mono text-sm bg-white border-slate-300 text-slate-900"
                required
              />
            </div>

            <div className="sm:col-span-2">
              <Label className="text-xs font-semibold text-slate-700">
                Diagram / Illustration Image URL (Optional)
              </Label>
              <div className="relative mt-1">
                <Input
                  type="url"
                  placeholder="https://example.com/circuit-diagram.png"
                  value={diagramUrl}
                  onChange={(e) => setDiagramUrl(e.target.value)}
                  className="pl-8 text-xs font-mono bg-white border-slate-300 text-slate-900"
                />
                <ImageIcon className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" />
              </div>
            </div>
          </div>

          {/* Diagram Preview if provided */}
          {diagramUrl && (
            <div className="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                src={diagramUrl}
                alt="Preview"
                className="h-12 w-16 object-cover rounded-lg border border-slate-300"
                onError={(e) => {
                  ;(e.target as HTMLElement).style.display = "none"
                }}
              />
              <span className="text-[11px] text-slate-600">
                Live Diagram Preview (Will be displayed above choices)
              </span>
            </div>
          )}

          {/* Problem Statement */}
          <div>
            <Label className="text-xs font-semibold text-slate-700">
              Problem Statement / Question Text (Supports formulas & formatting)
            </Label>
            <textarea
              rows={3}
              value={questionText}
              onChange={(e) => setQuestionText(e.target.value)}
              placeholder="e.g. In an isolated thermodynamics system, calculate the work done when pressure changes from P1 to P2..."
              className="mt-1 w-full rounded-xl border border-slate-300 bg-white p-3 text-xs text-slate-900 focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500 shadow-xs"
              required
            />
          </div>

          {/* 4 Choices Grid (A, B, C, D) */}
          <div>
            <div className="flex items-center justify-between pb-1.5">
              <Label className="text-xs font-semibold text-slate-700">
                Answer Choices (A, B, C, D) & Correct Key Selector
              </Label>
              <span className="text-[11px] font-medium text-emerald-700">
                Click radio pill to set the correct answer
              </span>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              {(["A", "B", "C", "D"] as OptionKey[]).map((key) => {
                const isSelected = correctAnswer === key
                const val = key === "A" ? optA : key === "B" ? optB : key === "C" ? optC : optD
                const setVal = key === "A" ? setOptA : key === "B" ? setOptB : key === "C" ? setOptC : setOptD

                return (
                  <div
                    key={key}
                    className={`relative rounded-xl border p-3 transition ${
                      isSelected
                        ? "border-emerald-500 bg-emerald-50/70 shadow-xs ring-1 ring-emerald-400"
                        : "border-slate-200 bg-white hover:border-slate-300"
                    }`}
                  >
                    <div className="flex items-center justify-between pb-1.5">
                      <span
                        className={`flex h-6 w-6 items-center justify-center rounded-md font-bold text-xs ${
                          isSelected
                            ? "bg-emerald-600 text-white"
                            : "bg-slate-100 text-slate-700"
                        }`}
                      >
                        {key}
                      </span>

                      <button
                        type="button"
                        onClick={() => setCorrectAnswer(key)}
                        className={`flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full transition ${
                          isSelected
                            ? "bg-emerald-600 text-white shadow-xs"
                            : "bg-slate-100 text-slate-600 hover:bg-slate-200"
                        }`}
                      >
                        <CheckCircle2 className="h-3 w-3" />
                        {isSelected ? "Correct Answer" : "Set Correct"}
                      </button>
                    </div>

                    <Input
                      type="text"
                      placeholder={`Choice ${key} text...`}
                      value={val}
                      onChange={(e) => setVal(e.target.value)}
                      className="text-xs bg-white border-slate-300 text-slate-900 mt-1"
                      required
                    />
                  </div>
                )
              })}
            </div>
          </div>

          {/* Explanation / Derivation */}
          <div>
            <div className="flex items-center gap-1.5 pb-1">
              <Sparkles className="h-3.5 w-3.5 text-amber-500" />
              <Label className="text-xs font-semibold text-slate-700">
                Step-by-Step Derivation & Explanation Box
              </Label>
            </div>
            <textarea
              rows={3}
              value={explanation}
              onChange={(e) => setExplanation(e.target.value)}
              placeholder="Step 1: Write down given values...\nStep 2: Apply formula E = mc^2...\nStep 3: Option B is verified."
              className="mt-1 w-full rounded-xl border border-slate-300 bg-white p-3 text-xs text-slate-900 focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500 shadow-xs"
            />
          </div>

          <DialogFooter className="border-t border-slate-100 pt-4">
            <Button type="button" variant="outline" onClick={onClose} disabled={isSubmitting} size="sm">
              Cancel
            </Button>
            <Button
              type="submit"
              disabled={isSubmitting}
              size="sm"
              className="bg-emerald-600 text-white hover:bg-emerald-700 font-semibold gap-1.5 shadow-md shadow-emerald-600/20"
            >
              {isSubmitting ? (
                <>
                  <Loader2 className="h-3.5 w-3.5 animate-spin" />
                  Saving Question...
                </>
              ) : (
                <>
                  <CheckCircle2 className="h-3.5 w-3.5" />
                  {isEditing ? "Update Question" : "Save Question to Bank"}
                </>
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
