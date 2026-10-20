"use client"

import React, { useState, useEffect, useRef } from "react"
import { apiClient } from "@/lib/api-client"
import { ChapterNote } from "@/types/curriculum"
import { MarkdownRenderer } from "./markdown-renderer"
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
import { Badge } from "@/components/ui/badge"
import {
  Bold,
  Italic,
  Heading2,
  Heading3,
  List,
  ListOrdered,
  Quote,
  Code,
  Table,
  Eye,
  Edit3,
  Loader2,
  AlertCircle,
  Sparkles,
  User,
} from "lucide-react"

interface NoteEditorModalProps {
  isOpen: boolean
  onClose: () => void
  onSaved: (savedNote: ChapterNote) => void
  noteToEdit?: ChapterNote | null
  note?: ChapterNote | null
  chapterId: number
  chapterTitle: string
  gradeId?: number
  subjectId?: number
  defaultType?: "teacher" | "ai"
}

export function NoteEditorModal({
  isOpen,
  onClose,
  onSaved,
  noteToEdit,
  note,
  chapterId,
  chapterTitle,
  gradeId,
  subjectId,
  defaultType,
}: NoteEditorModalProps) {
  const targetNote = noteToEdit ?? note ?? null
  const [title, setTitle] = useState("")
  const [content, setContent] = useState("")
  const [isAiGenerated, setIsAiGenerated] = useState<boolean>(false)
  const [activeTab, setActiveTab] = useState<"write" | "preview">("write")
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const textareaRef = useRef<HTMLTextAreaElement>(null)

  useEffect(() => {
    if (isOpen) {
      if (targetNote) {
        setTitle(targetNote.title || "")
        setContent(targetNote.content || "")
        setIsAiGenerated(Boolean(targetNote.is_ai_generated))
      } else {
        const isAi = defaultType === "ai"
        setIsAiGenerated(isAi)
        setTitle(isAi ? `${chapterTitle} Notes` : `${chapterTitle} Teacher Notes`)
        setContent("")
      }
      setActiveTab("write")
      setError(null)
    }
  }, [isOpen, targetNote, chapterTitle, defaultType])

  const insertSnippet = (before: string, after: string = "", defaultPlaceholder = "text") => {
    const textarea = textareaRef.current
    if (!textarea) return

    const start = textarea.selectionStart
    const end = textarea.selectionEnd
    const selectedText = content.substring(start, end) || defaultPlaceholder
    const replacement = before + selectedText + after

    const newContent = content.substring(0, start) + replacement + content.substring(end)
    setContent(newContent)

    setTimeout(() => {
      textarea.focus()
      textarea.setSelectionRange(start + before.length, start + before.length + selectedText.length)
    }, 50)
  }

  const wordCount = content.trim() ? content.trim().split(/\s+/).length : 0
  const charCount = content.length

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!title.trim()) {
      setError("Please enter a note title.")
      return
    }
    if (!content.trim()) {
      setError("Please provide note content or formula summary.")
      return
    }

    setIsSubmitting(true)
    setError(null)

    try {
      if (targetNote && targetNote.id > 0) {
        // Update existing note live in MySQL
        const res = await apiClient.put(`/admin/notes/${targetNote.id}`, {
          title: title.trim(),
          content: content.trim(),
        })
        const updated = res.data?.data || res.data
        onSaved({
          ...targetNote,
          title: updated.title || title,
          content: updated.content || content,
          word_count: wordCount,
          updated_at: updated.updated_at || new Date().toISOString(),
        })
      } else {
        // Create new note live in MySQL
        const res = await apiClient.post(`/admin/chapters/${chapterId}/notes`, {
          title: title.trim(),
          content: content.trim(),
          is_ai_generated: isAiGenerated,
        })
        const created = res.data?.data || res.data
        onSaved({
          id: created.id,
          chapter_id: chapterId,
          chapter_title: chapterTitle,
          title: created.title || title,
          content: created.content || content,
          is_ai_generated: Boolean(created.is_ai_generated ?? isAiGenerated),
          word_count: wordCount,
          created_at: created.created_at || new Date().toISOString(),
          updated_at: created.updated_at || new Date().toISOString(),
        })
      }
      onClose()
    } catch (err: any) {
      console.error("Save note error:", err)
      setError(
        err.response?.data?.message ||
          (err.response?.data?.errors
            ? Object.values(err.response.data.errors).flat().join(", ")
            : "Failed to save note. Please check the fields and try again.")
      )
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-h-[90vh] max-w-4xl overflow-hidden p-0 sm:rounded-2xl">
        <form onSubmit={handleSubmit} className="flex h-full max-h-[90vh] flex-col">
          {/* Header */}
          <DialogHeader className="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <div className="flex items-center justify-between">
              <div>
                <DialogTitle className="text-lg font-bold text-slate-900 dark:text-slate-100">
                  {noteToEdit ? "Edit Chapter Note" : "Write Chapter Note"}
                </DialogTitle>
                <DialogDescription className="text-xs text-slate-500">
                  Chapter: <span className="font-semibold text-slate-700 dark:text-slate-300">{chapterTitle}</span>
                </DialogDescription>
              </div>

              {/* View Switcher: Write vs Preview */}
              <div className="flex items-center rounded-lg border border-slate-200 bg-slate-100 p-0.5 dark:border-slate-800 dark:bg-slate-900">
                <button
                  type="button"
                  onClick={() => setActiveTab("write")}
                  className={`flex items-center gap-1.5 rounded-md px-3 py-1 text-xs font-medium transition ${
                    activeTab === "write"
                      ? "bg-white text-slate-900 shadow-xs dark:bg-slate-800 dark:text-slate-100"
                      : "text-slate-500 hover:text-slate-900 dark:hover:text-slate-200"
                  }`}
                >
                  <Edit3 className="h-3.5 w-3.5" />
                  Write
                </button>
                <button
                  type="button"
                  onClick={() => setActiveTab("preview")}
                  className={`flex items-center gap-1.5 rounded-md px-3 py-1 text-xs font-medium transition ${
                    activeTab === "preview"
                      ? "bg-white text-slate-900 shadow-xs dark:bg-slate-800 dark:text-slate-100"
                      : "text-slate-500 hover:text-slate-900 dark:hover:text-slate-200"
                  }`}
                >
                  <Eye className="h-3.5 w-3.5" />
                  Live Preview
                </button>
              </div>
            </div>
          </DialogHeader>

          {/* Body */}
          <div className="flex-1 space-y-4 overflow-y-auto p-6">
            {error && (
              <div className="flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50/80 p-3.5 text-xs text-rose-800 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-300">
                <AlertCircle className="mt-0.5 h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400" />
                <span>{error}</span>
              </div>
            )}

            {/* Note Type Indicator / Selector */}
            {targetNote ? (
              <div className="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/80 px-3.5 py-2.5 text-xs dark:border-slate-800 dark:bg-slate-900/50">
                <span className="font-semibold text-slate-600 dark:text-slate-400">Note Type:</span>
                {isAiGenerated ? (
                  <Badge className="gap-1 border-purple-300 bg-purple-50 text-purple-700 dark:border-purple-900 dark:bg-purple-950/40 dark:text-purple-300 font-semibold">
                    <Sparkles className="h-3 w-3" />
                    ⚡ AI Generated Note
                  </Badge>
                ) : (
                  <Badge className="gap-1 border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300 font-semibold">
                    <User className="h-3 w-3" />
                    👤 Teacher Authored Note
                  </Badge>
                )}
              </div>
            ) : (
              <div className="space-y-1.5">
                <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Select Note Type to Create
                </Label>
                <div className="grid grid-cols-2 gap-3">
                  <button
                    type="button"
                    onClick={() => {
                      setIsAiGenerated(false)
                      if (!title || title.includes("AI Notes") || title.endsWith("Notes")) {
                        setTitle(`${chapterTitle} Teacher Notes`)
                      }
                    }}
                    className={`flex items-center justify-center gap-2 rounded-xl border p-2.5 text-xs font-semibold transition ${
                      !isAiGenerated
                        ? "border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-600 dark:bg-blue-950/40 dark:text-blue-300 shadow-xs"
                        : "border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400"
                    }`}
                  >
                    <User className="h-4 w-4" />
                    <span>👤 Teacher Note</span>
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      setIsAiGenerated(true)
                      if (!title || title.includes("Teacher Notes")) {
                        setTitle(`${chapterTitle} Notes`)
                      }
                    }}
                    className={`flex items-center justify-center gap-2 rounded-xl border p-2.5 text-xs font-semibold transition ${
                      isAiGenerated
                        ? "border-purple-500 bg-purple-50 text-purple-700 dark:border-purple-600 dark:bg-purple-950/40 dark:text-purple-300 shadow-xs"
                        : "border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400"
                    }`}
                  >
                    <Sparkles className="h-4 w-4" />
                    <span>⚡ AI Note</span>
                  </button>
                </div>
              </div>
            )}

            {/* Note Title */}
            <div className="space-y-1.5">
              <Label htmlFor="note-title" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Note Title <span className="text-rose-500">*</span>
              </Label>
              <Input
                id="note-title"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                placeholder="e.g. Chapter Summary & Core Equations"
                className="h-10 text-sm font-medium"
                required
              />
            </div>

            {/* Content Editor vs Preview */}
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <Label htmlFor="note-content" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Document Content (Markdown Supported) <span className="text-rose-500">*</span>
                </Label>
                <span className="text-xs text-slate-400">
                  {wordCount.toLocaleString()} words · {charCount.toLocaleString()} chars
                </span>
              </div>

              {activeTab === "write" ? (
                <div className="rounded-xl border border-slate-200 shadow-xs dark:border-slate-800">
                  {/* Toolbar */}
                  <div className="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50/80 p-1.5 dark:border-slate-800 dark:bg-slate-900/50">
                    <button
                      type="button"
                      onClick={() => insertSnippet("## ", "", "Section Heading")}
                      title="Heading 2"
                      className="rounded p-1.5 text-slate-600 hover:bg-slate-200 dark:text-slate-400 dark:hover:bg-slate-800"
                    >
                      <Heading2 className="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      onClick={() => insertSnippet("### ", "", "Subheading")}
                      title="Heading 3"
                      className="rounded p-1.5 text-slate-600 hover:bg-slate-200 dark:text-slate-400 dark:hover:bg-slate-800"
                    >
                      <Heading3 className="h-4 w-4" />
                    </button>
                    <div className="h-4 w-[1px] bg-slate-300 dark:bg-slate-700 mx-1" />
                    <button
                      type="button"
                      onClick={() => insertSnippet("**", "**", "bold text")}
                      title="Bold"
                      className="rounded p-1.5 text-slate-600 hover:bg-slate-200 dark:text-slate-400 dark:hover:bg-slate-800"
                    >
                      <Bold className="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      onClick={() => insertSnippet("*", "*", "italic text")}
                      title="Italic"
                      className="rounded p-1.5 text-slate-600 hover:bg-slate-200 dark:text-slate-400 dark:hover:bg-slate-800"
                    >
                      <Italic className="h-4 w-4" />
                    </button>
                    <div className="h-4 w-[1px] bg-slate-300 dark:bg-slate-700 mx-1" />
                    <button
                      type="button"
                      onClick={() => insertSnippet("- ", "", "Key concept point")}
                      title="Bullet List"
                      className="rounded p-1.5 text-slate-600 hover:bg-slate-200 dark:text-slate-400 dark:hover:bg-slate-800"
                    >
                      <List className="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      onClick={() => insertSnippet("1. ", "", "First step or law")}
                      title="Numbered List"
                      className="rounded p-1.5 text-slate-600 hover:bg-slate-200 dark:text-slate-400 dark:hover:bg-slate-800"
                    >
                      <ListOrdered className="h-4 w-4" />
                    </button>
                    <div className="h-4 w-[1px] bg-slate-300 dark:bg-slate-700 mx-1" />
                    <button
                      type="button"
                      onClick={() => insertSnippet("> ", "", "Essential exam definition")}
                      title="Blockquote / Definition"
                      className="rounded p-1.5 text-slate-600 hover:bg-slate-200 dark:text-slate-400 dark:hover:bg-slate-800"
                    >
                      <Quote className="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      onClick={() => insertSnippet("```\n", "\n```", "Equation or code snippet")}
                      title="Code Block"
                      className="rounded p-1.5 text-slate-600 hover:bg-slate-200 dark:text-slate-400 dark:hover:bg-slate-800"
                    >
                      <Code className="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      onClick={() =>
                        insertSnippet(
                          "| Concept | Formula | Unit |\n| --- | --- | --- |\n| Velocity | v = d / t | m/s |\n"
                        )
                      }
                      title="Table Template"
                      className="rounded p-1.5 text-slate-600 hover:bg-slate-200 dark:text-slate-400 dark:hover:bg-slate-800"
                    >
                      <Table className="h-4 w-4" />
                    </button>
                  </div>

                  <Textarea
                    ref={textareaRef}
                    id="note-content"
                    value={content}
                    onChange={(e) => setContent(e.target.value)}
                    placeholder="Write or paste comprehensive chapter summary, core equations, key definitions, and exam formulas..."
                    className="min-h-[320px] resize-y rounded-b-xl rounded-t-none border-0 font-mono text-sm leading-relaxed focus-visible:ring-0"
                    required
                  />
                </div>
              ) : (
                <div className="min-h-[360px] rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-950">
                  {content.trim() ? (
                    <MarkdownRenderer content={content} />
                  ) : (
                    <p className="text-center text-xs italic text-slate-400 py-12">
                      Content is empty. Type in the Write tab to see a live formatted preview here.
                    </p>
                  )}
                </div>
              )}
            </div>
          </div>

          {/* Footer */}
          <DialogFooter className="border-t border-slate-200 bg-slate-50/50 px-6 py-3.5 dark:border-slate-800 dark:bg-slate-900/40">
            <Button type="button" variant="outline" onClick={onClose} disabled={isSubmitting}>
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting} className="min-w-[120px] gap-2">
              {isSubmitting ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Saving...
                </>
              ) : (
                "Save Note"
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
