"use client"

import React, { useState } from "react"
import { Chapter } from "@/types/curriculum"
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription,
} from "@/components/ui/sheet"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { Separator } from "@/components/ui/separator"
import {
  Sparkles,
  FileText,
  UploadCloud,
  CheckCircle,
  Clock,
  Loader2,
  ExternalLink,
  BookOpen,
  Zap,
} from "lucide-react"

interface ChapterEditorModalProps {
  chapter: Chapter | null
  open: boolean
  onOpenChange: (open: boolean) => void
  onSave: (updatedChapter: Chapter) => void
}

export function ChapterEditorModal({
  chapter,
  open,
  onOpenChange,
  onSave,
}: ChapterEditorModalProps) {
  const [formData, setFormData] = useState<Chapter | null>(null)
  const [isGeneratingAi, setIsGeneratingAi] = useState(false)
  const [uploadProgress, setUploadProgress] = useState<number | null>(null)
  const [aiSuccessNotice, setAiSuccessNotice] = useState(false)

  // Sync state when chapter prop changes
  React.useEffect(() => {
    if (chapter) {
      setFormData({ ...chapter })
    }
  }, [chapter])

  if (!formData) return null

  const handleGenerateAi = () => {
    setIsGeneratingAi(true)
    setAiSuccessNotice(false)

    // Simulate AI synthesis pipeline job
    setTimeout(() => {
      setFormData((prev) =>
        prev
          ? {
              ...prev,
              ai_notes_status: "ready",
              ai_notes_word_count: 3850,
              ai_last_generated_at: new Date().toISOString(),
            }
          : null
      )
      setIsGeneratingAi(false)
      setAiSuccessNotice(true)
      setTimeout(() => setAiSuccessNotice(false), 5000)
    }, 1800)
  }

  const handlePdfUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (!file) return

    setUploadProgress(10)
    const interval = setInterval(() => {
      setUploadProgress((prev) => {
        if (prev === null || prev >= 100) {
          clearInterval(interval)
          setFormData((f) =>
            f
              ? {
                  ...f,
                  textbook_pdf_url: `https://storage.asteghi.com/textbooks/${file.name}`,
                  textbook_size_mb: Math.round((file.size / 1024 / 1024) * 10) / 10,
                }
              : null
          )
          return null
        }
        return prev + 30
      })
    }, 200)
  }

  const handleSave = (e: React.FormEvent) => {
    e.preventDefault()
    if (formData) {
      onSave(formData)
      onOpenChange(false)
    }
  }

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent side="right" className="w-full sm:max-w-lg overflow-y-auto p-6">
        <SheetHeader className="space-y-1 pb-4 text-left">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold uppercase tracking-wider text-slate-400">
              Chapter #{formData.chapter_number} Editor
            </span>
            <Badge
              variant={
                formData.ai_notes_status === "ready"
                  ? "success"
                  : formData.ai_notes_status === "draft"
                  ? "warning"
                  : "outline"
              }
              className="capitalize text-xs"
            >
              AI: {formData.ai_notes_status.replace("_", " ")}
            </Badge>
          </div>
          <SheetTitle className="text-xl font-bold text-slate-900 dark:text-slate-100">
            {formData.title}
          </SheetTitle>
          <SheetDescription className="text-xs text-slate-500">
            {formData.grade_name} • {formData.subject_name}
          </SheetDescription>
        </SheetHeader>

        <form onSubmit={handleSave} className="space-y-6 pt-2">
          {/* Metadata Section */}
          <div className="space-y-3">
            <div className="space-y-1.5">
              <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Chapter Title
              </Label>
              <Input
                required
                value={formData.title}
                onChange={(e) =>
                  setFormData({ ...formData, title: e.target.value })
                }
                className="text-xs border-slate-200 dark:border-slate-800"
              />
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Chapter Number
                </Label>
                <Input
                  type="number"
                  min={1}
                  required
                  value={formData.chapter_number}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      chapter_number: parseInt(e.target.value) || 1,
                    })
                  }
                  className="text-xs border-slate-200 dark:border-slate-800"
                />
              </div>

              <div className="space-y-1.5">
                <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Quiz Questions
                </Label>
                <Input
                  type="number"
                  min={0}
                  value={formData.quiz_questions_count}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      quiz_questions_count: parseInt(e.target.value) || 0,
                    })
                  }
                  className="text-xs border-slate-200 dark:border-slate-800"
                />
              </div>
            </div>

            <div className="space-y-1.5">
              <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Chapter Syllabus Description
              </Label>
              <textarea
                rows={3}
                value={formData.description}
                onChange={(e) =>
                  setFormData({ ...formData, description: e.target.value })
                }
                className="w-full rounded-md border border-slate-200 bg-white p-2.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
              />
            </div>

            <label className="flex items-center gap-2 cursor-pointer text-xs text-slate-700 dark:text-slate-300 pt-1">
              <input
                type="checkbox"
                checked={formData.is_featured || false}
                onChange={(e) =>
                  setFormData({ ...formData, is_featured: e.target.checked })
                }
                className="h-3.5 w-3.5 rounded border-slate-300 text-blue-600"
              />
              <span>Mark as Featured Sample Lesson on Mobile App</span>
            </label>
          </div>

          <Separator />

          {/* Textbook PDF File Section */}
          <div className="space-y-3">
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center justify-between">
              <span>Textbook PDF Asset</span>
              {formData.textbook_pdf_url && (
                <span className="text-[10px] text-emerald-600 font-semibold flex items-center gap-1">
                  <CheckCircle className="h-3 w-3" />
                  PDF Linked ({formData.textbook_size_mb} MB)
                </span>
              )}
            </h4>

            {formData.textbook_pdf_url ? (
              <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs dark:border-slate-800 dark:bg-slate-900/40">
                <div className="flex items-center gap-2">
                  <FileText className="h-4 w-4 text-blue-600" />
                  <span className="font-semibold text-slate-800 line-clamp-1 dark:text-slate-200">
                    {formData.textbook_pdf_url.split("/").pop()}
                  </span>
                </div>
                <Button
                  size="sm"
                  type="button"
                  variant="ghost"
                  onClick={() =>
                    window.alert(`Streaming PDF from: ${formData.textbook_pdf_url}`)
                  }
                  className="h-7 text-xs text-blue-600"
                >
                  Stream <ExternalLink className="ml-1 h-3 w-3" />
                </Button>
              </div>
            ) : (
              <div className="rounded-xl border border-dashed border-slate-300 p-6 text-center text-xs dark:border-slate-700">
                <UploadCloud className="mx-auto h-8 w-8 text-slate-400 mb-2" />
                <p className="font-semibold text-slate-700 dark:text-slate-300">
                  No Textbook PDF uploaded
                </p>
                <p className="text-[11px] text-slate-400 mb-3">
                  Upload a chapter slice to enable PDF streaming and AI synthesis.
                </p>
                <label className="inline-flex cursor-pointer items-center rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900">
                  <span>Choose PDF File...</span>
                  <input
                    type="file"
                    accept=".pdf"
                    onChange={handlePdfUpload}
                    className="sr-only"
                  />
                </label>
              </div>
            )}

            {uploadProgress !== null && (
              <div className="space-y-1">
                <div className="flex justify-between text-[11px] text-slate-500">
                  <span>Uploading to S3 storage...</span>
                  <span>{uploadProgress}%</span>
                </div>
                <div className="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                  <div
                    className="h-full bg-blue-600 transition-all"
                    style={{ width: `${uploadProgress}%` }}
                  />
                </div>
              </div>
            )}
          </div>

          <Separator />

          {/* AI Notes Synthesis Pipeline Box */}
          <div className="rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-50/50 to-purple-50/30 p-4 text-xs dark:border-indigo-950 dark:bg-indigo-950/20 space-y-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-1.5 font-bold text-indigo-950 dark:text-indigo-200">
                <Sparkles className="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                <span>AI Synthesis Engine</span>
              </div>
              <Badge
                variant={formData.ai_notes_status === "ready" ? "success" : "outline"}
                className="text-[10px]"
              >
                {formData.ai_notes_status === "ready"
                  ? "✓ Synthesis Active"
                  : "Needs Generation"}
              </Badge>
            </div>

            <p className="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
              Extracts core equations, generates high-yield exam summary notes, and synthesizes chapter quiz questions directly from the textbook PDF.
            </p>

            {formData.ai_last_generated_at && (
              <div className="flex items-center justify-between text-[10px] text-slate-500 pt-1">
                <span>Word Count: {formData.ai_notes_word_count?.toLocaleString()} words</span>
                <span>
                  Last Generated:{" "}
                  {new Date(formData.ai_last_generated_at).toLocaleDateString()}
                </span>
              </div>
            )}

            {aiSuccessNotice && (
              <div className="flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 p-2 text-[11px] text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300">
                <CheckCircle className="h-3.5 w-3.5 text-emerald-600" />
                <span>AI notes & formula sheets successfully generated and stored!</span>
              </div>
            )}

            <Button
              type="button"
              disabled={isGeneratingAi}
              onClick={handleGenerateAi}
              className="w-full bg-gradient-to-r from-indigo-600 to-purple-600 font-semibold text-white shadow-md shadow-indigo-500/20 hover:from-indigo-500 hover:to-purple-500"
            >
              {isGeneratingAi ? (
                <>
                  <Loader2 className="mr-2 h-3.5 w-3.5 animate-spin" />
                  Synthesizing Chapter Formulas & Notes...
                </>
              ) : (
                <>
                  <Zap className="mr-1.5 h-3.5 w-3.5" />
                  ⚡ Trigger AI Notes Generation
                </>
              )}
            </Button>
          </div>

          <div className="flex gap-2 pt-2">
            <Button
              type="submit"
              className="flex-1 bg-slate-900 text-xs font-semibold text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900"
            >
              Save Chapter Changes
            </Button>
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
              className="text-xs"
            >
              Cancel
            </Button>
          </div>
        </form>
      </SheetContent>
    </Sheet>
  )
}
