"use client"

import React, { useState, useEffect, useMemo } from "react"
import { apiClient, cachedGet } from "@/lib/api-client"
import { getCached, setCached, clearCache } from "@/lib/cache"
import { ChapterNote } from "@/types/curriculum"
import { MarkdownRenderer } from "@/components/notes/markdown-renderer"
import { NoteEditorModal } from "@/components/notes/note-editor-modal"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { Card, CardContent } from "@/components/ui/card"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog"
import {
  BookOpen,
  GraduationCap,
  Layers,
  Sparkles,
  FileText,
  PlusCircle,
  Edit2,
  Trash2,
  RefreshCw,
  Search,
  CheckCircle2,
  Clock,
  AlertTriangle,
  Loader2,
  Copy,
  Check,
  ChevronRight,
  BookMarked,
  FileCheck2,
  SlidersHorizontal,
  Eye,
  EyeOff,
  User,
  Globe,
} from "lucide-react"

interface GradeItem {
  id: number
  name: string
}

interface SubjectItem {
  id: number
  grade_id: number
  grade_name?: string
  title: string
}

interface ChapterItem {
  id: number
  subject_id: number
  grade_id?: number
  chapter_number: number
  title: string
  ai_generated_notes?: string | null
  notes_generated_at?: string | null
  ai_notes_status?: "ready" | "draft" | "not_generated"
  notes_count?: number
}

interface NotesMetadataCache {
  grades: GradeItem[]
  subjects: SubjectItem[]
  chapters: ChapterItem[]
}

export default function ChapterNotesHubPage() {
  const cachedMeta = getCached<NotesMetadataCache>("admin_notes_metadata")

  // Master Data Lists
  const [gradesList, setGradesList] = useState<GradeItem[]>(cachedMeta?.grades || [])
  const [subjectsList, setSubjectsList] = useState<SubjectItem[]>(cachedMeta?.subjects || [])
  const [chaptersList, setChaptersList] = useState<ChapterItem[]>(cachedMeta?.chapters || [])
  const [isLoadingMetadata, setIsLoadingMetadata] = useState(!cachedMeta)

  // Cascading Selection State
  const [selectedGradeId, setSelectedGradeId] = useState<string>(() => {
    if (cachedMeta?.chapters && cachedMeta.chapters[0]?.grade_id) {
      return String(cachedMeta.chapters[0].grade_id)
    }
    return "all"
  })
  const [selectedSubjectId, setSelectedSubjectId] = useState<string>(() => {
    if (cachedMeta?.chapters && cachedMeta.chapters[0]?.subject_id) {
      return String(cachedMeta.chapters[0].subject_id)
    }
    return "all"
  })
  const [selectedChapterId, setSelectedChapterId] = useState<number | null>(() => {
    return cachedMeta?.chapters && cachedMeta.chapters[0] ? cachedMeta.chapters[0].id : null
  })
  const [searchQuery, setSearchQuery] = useState("")

  // Current Chapter Notes State
  const initialChapterId = cachedMeta?.chapters && cachedMeta.chapters[0] ? cachedMeta.chapters[0].id : null
  const cachedInitialNotes = initialChapterId ? getCached<ChapterNote[]>(`admin_chapter_notes_${initialChapterId}`) : null
  const [notes, setNotes] = useState<ChapterNote[]>(cachedInitialNotes || [])
  const [selectedNoteId, setSelectedNoteId] = useState<number | null>(() => {
    if (cachedInitialNotes && cachedInitialNotes.length > 0) {
      const active = cachedInitialNotes.find((n) => n.is_active) || cachedInitialNotes[0]
      return active.id
    }
    return null
  })
  const [isLoadingNote, setIsLoadingNote] = useState<boolean>(!cachedInitialNotes && !!initialChapterId)
  const [notesError, setNotesError] = useState<string | null>(null)

  // Editor Modal State
  const [isEditorOpen, setIsEditorOpen] = useState(false)
  const [noteToEdit, setNoteToEdit] = useState<ChapterNote | null>(null)
  const [editorDefaultType, setEditorDefaultType] = useState<"teacher" | "ai">("teacher")

  // AI Generation & Overwrite Confirmation Modal State
  const [isAiGenerating, setIsAiGenerating] = useState(false)
  const [overwriteModalOpen, setOverwriteModalOpen] = useState(false)
  const [aiSuccessMessage, setAiSuccessMessage] = useState<string | null>(null)
  const [aiErrorMessage, setAiErrorMessage] = useState<string | null>(null)

  // Delete Note State
  const [deleteModalOpen, setDeleteModalOpen] = useState(false)
  const [noteToDelete, setNoteToDelete] = useState<ChapterNote | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)

  // Copy Feedback
  const [isCopied, setIsCopied] = useState(false)
  const [isActivating, setIsActivating] = useState(false)

  // 1. Initial Data Fetch: Load Grades, Subjects, Chapters
  useEffect(() => {
    let isMounted = true
    const hasCache = !!getCached("admin_notes_metadata")
    if (!hasCache) {
      setIsLoadingMetadata(true)
    }

    Promise.all([
      cachedGet("/grades").catch(() => []),
      cachedGet("/admin/subjects").catch(() => cachedGet("/subjects").catch(() => [])),
      cachedGet("/admin/chapters").catch(() => []),
    ])
      .then(([gradesData, subjectsData, chaptersData]) => {
        if (!isMounted) return

        // Process Grades
        const rawGrades = Array.isArray(gradesData)
          ? gradesData
          : gradesData?.data || []
        const parsedGrades: GradeItem[] = rawGrades.map((g: any) => ({
          id: g.id,
          name: g.name || `Grade ${g.id}`,
        }))
        parsedGrades.sort((a, b) => {
          const numA = parseInt(a.name.replace(/\D/g, "") || "0", 10)
          const numB = parseInt(b.name.replace(/\D/g, "") || "0", 10)
          return numA - numB
        })
        setGradesList(parsedGrades)

        // Process Subjects
        const rawSubjects = Array.isArray(subjectsData)
          ? subjectsData
          : subjectsData?.data || []
        const parsedSubjects: SubjectItem[] = rawSubjects.map((s: any) => ({
          id: s.id,
          grade_id: s.grade_id,
          grade_name: s.grade?.name,
          title: s.title || s.name || `Subject ${s.id}`,
        }))
        setSubjectsList(parsedSubjects)

        // Process Chapters
        const rawChapters = Array.isArray(chaptersData)
          ? chaptersData
          : chaptersData?.data || []
        const parsedChapters: ChapterItem[] = rawChapters.map((c: any) => ({
          id: c.id,
          subject_id: c.subject_id,
          grade_id: c.grade_id,
          chapter_number: c.chapter_number || 1,
          title: c.title || `Chapter ${c.id}`,
          ai_generated_notes: c.ai_generated_notes,
          notes_generated_at: c.notes_generated_at,
          ai_notes_status: c.ai_generated_notes ? "ready" : "not_generated",
          notes_count: c.notes_count,
        }))
        setChaptersList(parsedChapters)
        setCached("admin_notes_metadata", {
          grades: parsedGrades,
          subjects: parsedSubjects,
          chapters: parsedChapters,
        })

        // Auto-select first chapter if not already selected
        if (parsedChapters.length > 0) {
          setSelectedChapterId((prev) => {
            if (prev) return prev
            const firstChapter = parsedChapters[0]
            if (firstChapter.grade_id) setSelectedGradeId(String(firstChapter.grade_id))
            if (firstChapter.subject_id) setSelectedSubjectId(String(firstChapter.subject_id))
            return firstChapter.id
          })
        }
      })
      .catch((err) => {
        console.error("Failed to load curriculum hierarchy:", err)
      })
      .finally(() => {
        if (isMounted) setIsLoadingMetadata(false)
      })

    return () => {
      isMounted = false
    }
  }, [])

  // 2. Filtered Subjects based on Selected Grade
  const filteredSubjects = useMemo(() => {
    if (selectedGradeId === "all") return subjectsList
    const gradeIdNum = parseInt(selectedGradeId, 10)
    return subjectsList.filter((s) => s.grade_id === gradeIdNum)
  }, [subjectsList, selectedGradeId])

  // 3. Filtered Chapters based on Selected Subject & Grade & Search
  const filteredChapters = useMemo(() => {
    if (!chaptersList || chaptersList.length === 0) return []

    return chaptersList.filter((chapter) => {
      // Grade filter
      if (selectedGradeId && selectedGradeId !== "all") {
        const gradeIdNum = parseInt(selectedGradeId, 10)
        if (chapter.grade_id && chapter.grade_id !== gradeIdNum) {
          const sub = subjectsList.find((s) => s.id === chapter.subject_id)
          if (sub && sub.grade_id !== gradeIdNum) return false
        }
      }

      // Subject filter
      if (selectedSubjectId && selectedSubjectId !== "all") {
        const matchesSubject = Number(chapter.subject_id) === Number(selectedSubjectId)
        if (!matchesSubject) return false
      }

      // Keyword search
      if (!searchQuery.trim()) return true
      const query = searchQuery.toLowerCase().trim()
      return (
        chapter.title.toLowerCase().includes(query) ||
        String(chapter.chapter_number).includes(query)
      )
    })
  }, [chaptersList, selectedGradeId, selectedSubjectId, subjectsList, searchQuery])

  // Active selected chapter object
  const activeChapter = useMemo(() => {
    return chaptersList.find((c) => c.id === selectedChapterId) || null
  }, [chaptersList, selectedChapterId])

  // Grade & Subject metadata for active chapter
  const activeGradeName = useMemo(() => {
    if (!activeChapter) return ""
    const gradeObj = gradesList.find((g) => g.id === activeChapter.grade_id)
    if (gradeObj) return gradeObj.name
    const subObj = subjectsList.find((s) => s.id === activeChapter.subject_id)
    if (subObj) {
      const g = gradesList.find((gr) => gr.id === subObj.grade_id)
      return g?.name || ""
    }
    return ""
  }, [activeChapter, gradesList, subjectsList])

  const activeSubjectName = useMemo(() => {
    if (!activeChapter) return ""
    const subObj = subjectsList.find((s) => s.id === activeChapter.subject_id)
    return subObj?.title || ""
  }, [activeChapter, subjectsList])

  // 1. Single stable fetch effect with cleanup guard (NO OTHER EFFECTS SHOULD CALL setIsLoadingNote)
  useEffect(() => {
    if (!selectedChapterId) {
      setNotes([])
      setSelectedNoteId(null)
      setIsLoadingNote(false)
      return
    }

    let isMounted = true
    const cacheKey = `admin_chapter_notes_${selectedChapterId}`
    const cachedChapterNotes = getCached<ChapterNote[]>(cacheKey)

    if (cachedChapterNotes) {
      setNotes(cachedChapterNotes)
      if (cachedChapterNotes.length > 0) {
        setSelectedNoteId((prev) => {
          if (prev && cachedChapterNotes.some((n) => n.id === prev)) return prev
          const active = cachedChapterNotes.find((n) => n.is_active) || cachedChapterNotes[0]
          return active.id
        })
      }
      setIsLoadingNote(false)
    } else {
      setIsLoadingNote(true)
    }

    apiClient
      .get(`/admin/chapters/${selectedChapterId}/notes`)
      .then((res) => {
        if (!isMounted) return
        const raw = res.data?.data ?? res.data?.notes ?? res.data ?? []
        let rawArray: any[] = []
        if (Array.isArray(raw)) {
          rawArray = raw
        } else if (raw && typeof raw === "object") {
          if (raw.teacher_note) rawArray.push(raw.teacher_note)
          if (raw.ai_note) rawArray.push(raw.ai_note)
        }
        if (rawArray.length === 0) {
          if (res.data?.teacher_note) rawArray.push(res.data.teacher_note)
          if (res.data?.ai_note) rawArray.push(res.data.ai_note)
        }

        const liveNotes: ChapterNote[] = rawArray.map((n: any) => ({
          id: Number(n?.id ?? 0),
          chapter_id: Number(n?.chapter_id ?? selectedChapterId),
          chapter_title: n?.chapter?.title || "",
          grade_name: n?.grade?.name || "",
          subject_name: n?.subject?.title || "",
          title: n?.title || "Untitled Note",
          content: typeof n?.content === "string" ? n.content : "",
          is_ai_generated: Boolean(
            Number(n?.is_ai_generated) === 1 ||
            n?.is_ai_generated === true ||
            String(n?.is_ai_generated) === "1"
          ),
          is_active: Boolean(
            Number(n?.is_active) === 1 ||
            n?.is_active === true ||
            String(n?.is_active) === "1"
          ),
          word_count: n?.content ? String(n.content).trim().split(/\s+/).filter(Boolean).length : 0,
          last_generated_at: n?.updated_at || n?.created_at,
          created_at: n?.created_at,
          updated_at: n?.updated_at,
        }))

        setNotes(liveNotes)
        setCached(cacheKey, liveNotes)

        if (liveNotes.length > 0) {
          setSelectedNoteId((prev) => {
            if (prev && liveNotes.some((n) => n.id === prev)) return prev
            const active = liveNotes.find((n) => n.is_active) || liveNotes[0]
            return active.id
          })
        } else {
          setSelectedNoteId(null)
        }
      })
      .catch((err) => {
        if (!isMounted) return
        console.error("Failed to load live notes:", err)
        if (!getCached(cacheKey)) {
          setNotes([])
          setSelectedNoteId(null)
        }
      })
      .finally(() => {
        if (isMounted) {
          setIsLoadingNote(false)
        }
      })

    return () => {
      isMounted = false
    }
  }, [selectedChapterId]) // DEPENDS ONLY ON selectedChapterId! DO NOT ADD notes OR activeNote!

  // 2. Derive activeNote synchronously without secondary effects
  const activeNote = useMemo(() => {
    if (!notes || notes.length === 0) return null
    return notes.find((n) => n.id === selectedNoteId) || notes[0] || null
  }, [notes, selectedNoteId])

  // Backward compatibility aliases
  const currentNote = activeNote
  const safeNotes = notes
  const isLoadingNotes = isLoadingNote
  const displayNote = currentNote || (safeNotes && safeNotes.length > 0 ? safeNotes[0] : null)

  // 3. Safeguard Chapter Auto-Selection (Break the Loop)
  useEffect(() => {
    if (!filteredChapters || filteredChapters.length === 0) {
      setSelectedChapterId(null)
      return
    }

    // ONLY set first chapter if selectedChapterId is null OR not in the filtered list
    const isAlreadySelected = filteredChapters.some((c) => c.id === selectedChapterId)
    if (!isAlreadySelected) {
      setSelectedChapterId(filteredChapters[0].id)
    }
  }, [filteredChapters, selectedChapterId])

  // 4. Tab Switching Handler (NO setIsLoadingNote call)
  const handleSelectNote = (noteId: number) => {
    setSelectedNoteId(noteId)
    // Do NOT call setIsLoadingNote(true) here!
  }

  // Silent notes reload helper (for modal saves, deletions, activations - NO loading spinner)
  const fetchChapterNotesSilently = async (chapterId: number) => {
    try {
      const res = await apiClient.get(`/admin/chapters/${chapterId}/notes`)
      const raw = res.data?.data ?? res.data?.notes ?? res.data ?? []
      let rawArray: any[] = []
      if (Array.isArray(raw)) {
        rawArray = raw
      } else if (raw && typeof raw === "object") {
        if (raw.teacher_note) rawArray.push(raw.teacher_note)
        if (raw.ai_note) rawArray.push(raw.ai_note)
      }
      if (rawArray.length === 0) {
        if (res.data?.teacher_note) rawArray.push(res.data.teacher_note)
        if (res.data?.ai_note) rawArray.push(res.data.ai_note)
      }
      const liveNotes: ChapterNote[] = rawArray.map((n: any) => ({
        id: Number(n?.id ?? 0),
        chapter_id: Number(n?.chapter_id ?? chapterId),
        chapter_title: n?.chapter?.title || "",
        grade_name: n?.grade?.name || "",
        subject_name: n?.subject?.title || "",
        title: n?.title || "Untitled Note",
        content: typeof n?.content === "string" ? n.content : "",
        is_ai_generated: Boolean(
          Number(n?.is_ai_generated) === 1 ||
          n?.is_ai_generated === true ||
          String(n?.is_ai_generated) === "1"
        ),
        is_active: Boolean(
          Number(n?.is_active) === 1 ||
          n?.is_active === true ||
          String(n?.is_active) === "1"
        ),
        word_count: n?.content ? String(n.content).trim().split(/\s+/).filter(Boolean).length : 0,
        last_generated_at: n?.updated_at || n?.created_at,
        created_at: n?.created_at,
        updated_at: n?.updated_at,
      }))
      setNotes(liveNotes)
      setCached(`admin_chapter_notes_${chapterId}`, liveNotes)
      if (liveNotes.length > 0) {
        setSelectedNoteId((prev) => {
          if (prev && liveNotes.some((n) => n.id === prev)) {
            return prev
          }
          const active = liveNotes.find((n) => n.is_active) || liveNotes[0]
          return active.id
        })
      } else {
        setSelectedNoteId(null)
      }
    } catch (err) {
      console.error("Failed to reload notes silently:", err)
    }
  }

  // Cascading Handlers
  const handleGradeChange = (gradeId: string) => {
    setSelectedGradeId(gradeId)
    if (gradeId !== "all") {
      const gId = parseInt(gradeId, 10)
      const validSubjects = subjectsList.filter((s) => s.grade_id === gId)
      const currentSubjectStillValid = validSubjects.some((s) => String(s.id) === selectedSubjectId)
      if (!currentSubjectStillValid) {
        setSelectedSubjectId(validSubjects.length > 0 ? String(validSubjects[0].id) : "all")
      }
    }
  }

  const handleSubjectChange = (subjectId: string) => {
    setSelectedSubjectId(subjectId)
  }

  // Note classification and visibility memos
  const activeStudentNote = useMemo(() => {
    return notes.find((n) => n && n.is_active) || null
  }, [notes])

  const aiNote = useMemo(() => {
    return safeNotes.find((n) => n && n.is_ai_generated) || null
  }, [safeNotes])

  const teacherNote = useMemo(() => {
    return safeNotes.find((n) => n && !n.is_ai_generated) || null
  }, [safeNotes])

  // AI Generation & Regeneration Action
  const triggerAiGeneration = async (isRegeneration = false) => {
    if (!activeChapter) return

    setIsAiGenerating(true)
    setAiErrorMessage(null)
    setAiSuccessMessage(null)

    try {
      clearCache(`admin_chapter_notes_${activeChapter.id}`)
      clearCache("admin_chapters")
      const res = await apiClient.post(`/admin/chapters/${activeChapter.id}/generate-notes`, {
        overwrite: true,
      })

      // Fetch fresh notes silently without spinner
      await fetchChapterNotesSilently(activeChapter.id)

      // Automatically select the new/updated note
      const generatedNoteId = res.data?.data?.id
      if (generatedNoteId) {
        setSelectedNoteId(Number(generatedNoteId))
      }

      setAiSuccessMessage("AI note updated in database.")
      setOverwriteModalOpen(false)
    } catch (err: any) {
      console.error("AI Generation Error:", err)
      setAiErrorMessage(
        err.response?.data?.message ||
          "Failed to synthesize notes with AI engine."
      )
    } finally {
      setIsAiGenerating(false)
    }
  }

  // Set as Active Note (Mutual Exclusion: Sets only this note active, deactivates all others)
  const handleActivateNote = async (noteId: number) => {
    if (!activeChapter || isActivating) return
    setIsActivating(true)
    setAiErrorMessage(null)
    setAiSuccessMessage(null)

    try {
      clearCache(`admin_chapter_notes_${activeChapter.id}`)
      await apiClient.patch(`/admin/notes/${noteId}/activate`)
      await fetchChapterNotesSilently(activeChapter.id)
      setSelectedNoteId(noteId)
      setAiSuccessMessage("Note is now active and visible to students.")
    } catch (err: any) {
      console.error("Failed to activate note:", err)
      setAiErrorMessage(
        err.response?.data?.message || "Failed to set note as active."
      )
    } finally {
      setIsActivating(false)
    }
  }

  // Independent Visibility Toggle (Legacy support)
  const handleToggleVisibility = async (noteId: number, currentActive: boolean) => {
    if (!activeChapter || isActivating) return
    setIsActivating(true)
    setAiErrorMessage(null)
    setAiSuccessMessage(null)

    try {
      clearCache(`admin_chapter_notes_${activeChapter.id}`)
      const res = await apiClient.patch(`/admin/notes/${noteId}/toggle-visibility`, {
        is_active: !currentActive,
      })

      // In-place silent refresh without spinner takeover
      await fetchChapterNotesSilently(activeChapter.id)
      setSelectedNoteId(noteId)

      setAiSuccessMessage(
        res.data?.message || (!currentActive ? "Note is now visible to students." : "Note is now hidden from students.")
      )
    } catch (err: any) {
      console.error("Failed to toggle note visibility:", err)
      setAiErrorMessage(
        err.response?.data?.message || "Failed to update note visibility."
      )
    } finally {
      setIsActivating(false)
    }
  }

  // Open Note Editor with currently active or selected note
  const handleOpenEdit = () => {
    const target = currentNote || (safeNotes && safeNotes.length > 0 ? safeNotes[0] : null)
    if (!target) return
    setNoteToEdit(target)
    setIsEditorOpen(true)
  }

  // Delete Action
  const handleDeleteNote = async () => {
    if (!noteToDelete || !activeChapter) return
    setIsDeleting(true)

    try {
      clearCache(`admin_chapter_notes_${activeChapter.id}`)
      clearCache("admin_chapters")
      if (noteToDelete.id > 0) {
        await apiClient.delete(`/admin/notes/${noteToDelete.id}`)
      }
      await fetchChapterNotesSilently(activeChapter.id)
      setDeleteModalOpen(false)
      setNoteToDelete(null)
    } catch (err: any) {
      console.error("Delete note error:", err)
      alert("Failed to delete note. " + (err.response?.data?.message || ""))
    } finally {
      setIsDeleting(false)
    }
  }

  // Copy full document content
  const handleCopyNote = () => {
    if (!currentNote?.content) return
    navigator.clipboard.writeText(currentNote.content)
    setIsCopied(true)
    setTimeout(() => setIsCopied(false), 2000)
  }

  // Metrics Calculation
  const totalChapters = chaptersList.length
  const coveredChapters = chaptersList.filter(
    (c) => Boolean(c.ai_generated_notes && c.ai_generated_notes.trim()) || (c.notes_count && c.notes_count > 0)
  ).length
  const coveragePercent = totalChapters > 0 ? Math.round((coveredChapters / totalChapters) * 100) : 0

  return (
    <div className="space-y-6 pb-16">
      {/* 1. Header & Overview Stats */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
              Chapter Notes Hub
            </h1>
          </div>
          <p className="mt-1 text-sm text-slate-500">
            Browse textbook summaries, AI study guides, and teacher notes across secondary curriculum.
          </p>
        </div>

        {/* Global Action: Add Note */}
        {activeChapter && (
          <div className="flex items-center gap-2">
            <Button
              onClick={() => {
                setEditorDefaultType(teacherNote ? "ai" : "teacher")
                setNoteToEdit(null)
                setIsEditorOpen(true)
              }}
              className="gap-2 shadow-sm"
            >
              <PlusCircle className="h-4 w-4" />
              Write Note
            </Button>
          </div>
        )}
      </div>

      {/* KPI Stats Bar */}
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <Card className="border-slate-200 bg-white/80 shadow-xs dark:border-slate-800 dark:bg-slate-900/60">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <p className="text-xs font-semibold text-slate-500">Total Chapters</p>
              <BookOpen className="h-4 w-4 text-slate-400" />
            </div>
            <p className="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">
              {totalChapters}
            </p>
          </CardContent>
        </Card>

        <Card className="border-slate-200 bg-white/80 shadow-xs dark:border-slate-800 dark:bg-slate-900/60">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <p className="text-xs font-semibold text-slate-500">Notes Coverage</p>
              <FileCheck2 className="h-4 w-4 text-emerald-500" />
            </div>
            <div className="mt-2 flex items-baseline gap-2">
              <p className="text-2xl font-bold text-slate-900 dark:text-slate-100">
                {coveragePercent}%
              </p>
              <span className="text-xs text-slate-400">
                ({coveredChapters}/{totalChapters})
              </span>
            </div>
          </CardContent>
        </Card>

        <Card className="border-slate-200 bg-white/80 shadow-xs dark:border-slate-800 dark:bg-slate-900/60">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <p className="text-xs font-semibold text-slate-500">Current Note Length</p>
              <FileText className="h-4 w-4 text-blue-500" />
            </div>
            <p className="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">
              {currentNote?.word_count ? `${currentNote.word_count.toLocaleString()} words` : "—"}
            </p>
          </CardContent>
        </Card>

        <Card className="border-slate-200 bg-white/80 shadow-xs dark:border-slate-800 dark:bg-slate-900/60">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <p className="text-xs font-semibold text-slate-500">Selected Chapter</p>
              <GraduationCap className="h-4 w-4 text-purple-500" />
            </div>
            <p className="mt-2 truncate text-sm font-bold text-slate-900 dark:text-slate-100" title={activeChapter?.title}>
              {activeChapter ? `Ch ${activeChapter.chapter_number}: ${activeChapter.title}` : "None Selected"}
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Success / Error Alerts */}
      {aiSuccessMessage && (
        <div className="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50/80 p-3.5 text-xs text-emerald-800 shadow-xs dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300">
          <div className="flex items-center gap-2">
            <CheckCircle2 className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
            <span>{aiSuccessMessage}</span>
          </div>
          <button onClick={() => setAiSuccessMessage(null)} className="text-emerald-700 hover:text-emerald-900">
            ×
          </button>
        </div>
      )}

      {aiErrorMessage && (
        <div className="flex items-center justify-between rounded-xl border border-rose-200 bg-rose-50/80 p-3.5 text-xs text-rose-800 shadow-xs dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-300">
          <div className="flex items-center gap-2">
            <AlertTriangle className="h-4 w-4 text-rose-600 dark:text-rose-400" />
            <span>{aiErrorMessage}</span>
          </div>
          <button onClick={() => setAiErrorMessage(null)} className="text-rose-700 hover:text-rose-900">
            ×
          </button>
        </div>
      )}

      {/* 2. Cascading Hierarchy Filters (Grade -> Subject -> Chapter) */}
      <Card className="border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <CardContent className="p-4 sm:p-5">
          <div className="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-end">
            {/* Step 1: Grade Select */}
            <div className="space-y-1.5 md:col-span-3">
              <Label className="flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                <Layers className="h-3.5 w-3.5 text-blue-500" />
                1. Select Grade
              </Label>
              <select
                value={selectedGradeId}
                onChange={(e) => handleGradeChange(e.target.value)}
                disabled={isLoadingMetadata}
                className="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-800 shadow-xs focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
              >
                <option value="all">All Secondary Grades</option>
                {gradesList.map((g) => (
                  <option key={g.id} value={String(g.id)}>
                    {g.name}
                  </option>
                ))}
              </select>
            </div>

            {/* Step 2: Subject Select */}
            <div className="space-y-1.5 md:col-span-4">
              <Label className="flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                <BookOpen className="h-3.5 w-3.5 text-purple-500" />
                2. Select Subject
              </Label>
              <select
                value={selectedSubjectId}
                onChange={(e) => handleSubjectChange(e.target.value)}
                disabled={isLoadingMetadata}
                className="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-800 shadow-xs focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
              >
                <option value="all">All Subjects ({filteredSubjects.length})</option>
                {filteredSubjects.map((s) => (
                  <option key={s.id} value={String(s.id)}>
                    {s.title} {s.grade_name ? `(${s.grade_name})` : ""}
                  </option>
                ))}
              </select>
            </div>

            {/* Step 3: Quick Filter Search */}
            <div className="space-y-1.5 md:col-span-5">
              <Label className="flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                <Search className="h-3.5 w-3.5 text-emerald-500" />
                Search Chapters or Keywords
              </Label>
              <div className="relative">
                <Search className="absolute left-3 top-3 h-4 w-4 text-slate-400" />
                <Input
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Filter chapters by title, number, or topic..."
                  className="h-10 pl-9 text-xs"
                />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* 3. Main Workspace: Chapters Sidebar + Document Reader Canvas */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
        {/* Left Column: Chapters Navigation List */}
        <div className="space-y-3 lg:col-span-4">
          <div className="flex items-center justify-between px-1">
            <span className="text-xs font-bold uppercase tracking-wider text-slate-500">
              Chapters Catalog ({filteredChapters.length})
            </span>
            <span className="text-xs text-slate-400">Select to inspect</span>
          </div>

          <div className="max-h-[680px] space-y-2 overflow-y-auto pr-1">
            {filteredChapters.length === 0 ? (
              <div className="rounded-xl border border-dashed border-slate-200 p-8 text-center text-xs text-slate-400 dark:border-slate-800">
                No chapters match the chosen filters.
              </div>
            ) : (
              filteredChapters.map((chapter) => {
                const isSelected = chapter.id === selectedChapterId
                const hasNotes =
                  Boolean(chapter.ai_generated_notes && chapter.ai_generated_notes.trim()) ||
                  (chapter.notes_count && chapter.notes_count > 0) ||
                  (isSelected && notes.length > 0)

                return (
                  <button
                    key={chapter.id}
                    onClick={() => setSelectedChapterId(chapter.id)}
                    className={`group flex w-full items-start gap-3 rounded-xl border p-3.5 text-left transition ${
                      isSelected
                        ? "border-blue-500 bg-blue-50/70 shadow-xs dark:border-blue-600 dark:bg-blue-950/30"
                        : "border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/70 dark:border-slate-800 dark:bg-slate-900 dark:hover:bg-slate-800/50"
                    }`}
                  >
                    <div
                      className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold ${
                        isSelected
                          ? "bg-blue-600 text-white"
                          : "bg-slate-100 text-slate-700 group-hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300"
                      }`}
                    >
                      {chapter.chapter_number}
                    </div>

                    <div className="min-w-0 flex-1">
                      <p
                        className={`truncate text-xs font-semibold ${
                          isSelected ? "text-blue-950 dark:text-blue-200" : "text-slate-900 dark:text-slate-100"
                        }`}
                      >
                        {chapter.title}
                      </p>

                      <div className="mt-1.5 flex items-center gap-2">
                        {hasNotes ? (
                          <Badge
                            variant="secondary"
                            className="h-4 gap-1 px-1.5 text-[10px] font-medium text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-300"
                          >
                            <CheckCircle2 className="h-2.5 w-2.5" />
                            Notes Ready
                          </Badge>
                        ) : (
                          <Badge
                            variant="outline"
                            className="h-4 px-1.5 text-[10px] font-medium text-slate-400 dark:text-slate-500"
                          >
                            No Notes
                          </Badge>
                        )}

                        {chapter.ai_generated_notes && (
                          <span className="flex items-center gap-0.5 text-[10px] text-purple-600 dark:text-purple-400">
                            <Sparkles className="h-2.5 w-2.5" />
                            AI
                          </span>
                        )}
                      </div>
                    </div>

                    <ChevronRight
                      className={`h-4 w-4 shrink-0 transition ${
                        isSelected
                          ? "text-blue-600 dark:text-blue-400"
                          : "text-slate-300 group-hover:translate-x-0.5 dark:text-slate-600"
                      }`}
                    />
                  </button>
                )
              })
            )}
          </div>
        </div>

        {/* Right Column: Document Canvas & Action Toolbar */}
        <div className="space-y-4 lg:col-span-8">
          {activeChapter ? (
            <Card className="overflow-hidden border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
              {/* Canvas Header & Context Bar */}
              <div className="border-b border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900/60 sm:p-5">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                  <div>
                    <div className="flex items-center gap-2 text-xs font-medium text-slate-500">
                      <span>{activeGradeName || "Secondary"}</span>
                      <span>›</span>
                      <span>{activeSubjectName || "Subject"}</span>
                      <span>›</span>
                      <span className="font-semibold text-slate-800 dark:text-slate-200">
                        Chapter {activeChapter.chapter_number}
                      </span>
                    </div>
                    <h2 className="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">
                      {activeChapter.title}
                    </h2>
                  </div>

                  {/* Actions Toolbar */}
                  <div className="flex flex-wrap items-center gap-2">
                    {safeNotes.length > 0 ? (
                      <>
                        {/* Regenerate AI note */}
                        {aiNote ? (
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => triggerAiGeneration(true)}
                            disabled={isAiGenerating}
                            className="h-8 gap-1.5 text-xs text-purple-700 hover:bg-purple-50 dark:text-purple-300 dark:hover:bg-purple-950/40"
                          >
                            {isAiGenerating ? (
                              <>
                                <Loader2 className="h-3.5 w-3.5 animate-spin text-purple-600" />
                                ⚡ Generating Full AI Note...
                              </>
                            ) : (
                              <>
                                <RefreshCw className="h-3.5 w-3.5" />
                                ⚡ Regenerate AI Note
                              </>
                            )}
                          </Button>
                        ) : (
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => triggerAiGeneration(false)}
                            disabled={isAiGenerating}
                            className="h-8 gap-1.5 text-xs text-purple-700 hover:bg-purple-50 dark:text-purple-300 dark:hover:bg-purple-950/40"
                          >
                            {isAiGenerating ? (
                              <>
                                <Loader2 className="h-3.5 w-3.5 animate-spin text-purple-600" />
                                ⚡ Generating Full AI Note...
                              </>
                            ) : (
                              <>
                                <Sparkles className="h-3.5 w-3.5" />
                                ⚡ Generate AI Note
                              </>
                            )}
                          </Button>
                        )}

                        {/* Edit active note */}
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={handleOpenEdit}
                          disabled={!currentNote && (!safeNotes || safeNotes.length === 0)}
                          className="flex h-8 items-center gap-1.5 text-xs"
                        >
                          <Edit2 className="h-3.5 w-3.5" />
                          <span>Edit</span>
                        </Button>

                        {/* Copy markdown */}
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={handleCopyNote}
                          className="h-8 gap-1.5 text-xs"
                        >
                          {isCopied ? (
                            <>
                              <Check className="h-3.5 w-3.5 text-emerald-500" />
                              Copied
                            </>
                          ) : (
                            <>
                              <Copy className="h-3.5 w-3.5" />
                              Copy
                            </>
                          )}
                        </Button>

                        {/* Delete note */}
                        {currentNote && currentNote.id > 0 && (
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                              setNoteToDelete(currentNote)
                              setDeleteModalOpen(true)
                            }}
                            className="h-8 text-xs text-rose-600 hover:bg-rose-50 hover:text-rose-700 dark:text-rose-400 dark:hover:bg-rose-950/40"
                          >
                            <Trash2 className="h-3.5 w-3.5" />
                          </Button>
                        )}
                      </>
                    ) : (
                      <Button
                        size="sm"
                        onClick={() => triggerAiGeneration(false)}
                        disabled={isAiGenerating}
                        className="h-8 gap-1.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-xs text-white shadow-xs hover:from-blue-700 hover:to-indigo-700"
                      >
                        {isAiGenerating ? (
                          <>
                            <Loader2 className="h-3.5 w-3.5 animate-spin" />
                            ⚡ Generating Full AI Note...
                          </>
                        ) : (
                          <>
                            <Sparkles className="h-3.5 w-3.5" />
                            Generate with AI
                          </>
                        )}
                      </Button>
                    )}
                  </div>
                </div>

                {/* Informative Banner: Live Student Visibility */}
                {safeNotes.length > 0 && (
                  <div
                    className={`mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border px-3.5 py-2 text-xs shadow-xs ${
                      aiNote || teacherNote?.is_active
                        ? "border-emerald-200 bg-emerald-50/80 text-emerald-950 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-200"
                        : "border-amber-200 bg-amber-50/80 text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200"
                    }`}
                  >
                    <div className="flex items-center gap-2">
                      {aiNote || teacherNote?.is_active ? (
                        <Globe className="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                      ) : (
                        <AlertTriangle className="h-4 w-4 text-amber-600 dark:text-amber-400 shrink-0" />
                      )}
                      <span>
                        {aiNote && teacherNote ? (
                          teacherNote.is_active ? (
                            <>
                              <span className="font-semibold">🌐 Live Student Visibility:</span> Students can view both{" "}
                              <strong className="font-bold">⚡ AI Study Guide</strong> AND{" "}
                              <strong className="font-bold">👤 Teacher Note</strong>
                            </>
                          ) : (
                            <>
                              <span className="font-semibold">🌐 Live Student Visibility:</span>{" "}
                              <strong className="font-bold">⚡ AI Study Guide</strong> is live. 👤 Teacher Note is hidden (Draft Mode).
                            </>
                          )
                        ) : aiNote ? (
                          <>
                            <span className="font-semibold">🌐 Live Student Visibility:</span> Students can view{" "}
                            <strong className="font-bold">⚡ AI Study Guide</strong>
                          </>
                        ) : teacherNote?.is_active ? (
                          <>
                            <span className="font-semibold">🌐 Live Student Visibility:</span> Students can view{" "}
                            <strong className="font-bold">👤 Teacher Note</strong>
                          </>
                        ) : (
                          <>
                            <span className="font-semibold">⚠️ Student Visibility:</span> Teacher note currently hidden from students (Draft Mode)
                          </>
                        )}
                      </span>
                    </div>
                    {aiNote || teacherNote?.is_active ? (
                      <Badge className="bg-emerald-600 text-white text-[10px] font-semibold">
                        Live in Student App
                      </Badge>
                    ) : (
                      <Badge variant="outline" className="border-amber-300 text-amber-700 bg-amber-100/50 text-[10px] font-semibold dark:border-amber-800 dark:text-amber-300">
                        Draft Mode
                      </Badge>
                    )}
                  </div>
                )}

                {/* Clean Two-Tab Structure: [ ⚡ AI Note ] and [ 👤 Teacher Note ] */}
                {safeNotes.length > 0 && (
                  <div className="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200/80 pt-3 dark:border-slate-800">
                    <div className="flex flex-wrap items-center gap-2">
                      {/* Tab 1: [ ⚡ AI Note ] */}
                      {aiNote ? (
                        <button
                          type="button"
                          onClick={() => handleSelectNote(aiNote.id)}
                          className={`flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold transition shadow-xs ${
                            displayNote?.id === aiNote.id
                              ? "bg-white text-slate-950 shadow-sm ring-1 ring-slate-300 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700"
                              : "text-slate-600 hover:bg-slate-200/60 dark:text-slate-400 dark:hover:bg-slate-800"
                          }`}
                        >
                          <span className="flex items-center gap-1 text-purple-600 dark:text-purple-400">
                            <Sparkles className="h-3.5 w-3.5" />
                            ⚡ AI Note
                          </span>
                        </button>
                      ) : (
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => triggerAiGeneration(false)}
                          disabled={isAiGenerating}
                          className="h-8 gap-1.5 border-dashed text-xs text-purple-700 hover:bg-purple-50 dark:text-purple-300 dark:hover:bg-purple-950/40"
                        >
                          {isAiGenerating ? (
                            <>
                              <Loader2 className="h-3.5 w-3.5 animate-spin" />
                              ⚡ Generating...
                            </>
                          ) : (
                            <>
                              <Sparkles className="h-3.5 w-3.5" />
                              ⚡ Generate AI Note
                            </>
                          )}
                        </Button>
                      )}

                      {/* Tab 2: [ 👤 Teacher Note ] */}
                      {teacherNote ? (
                        <button
                          type="button"
                          onClick={() => handleSelectNote(teacherNote.id)}
                          className={`flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold transition shadow-xs ${
                            displayNote?.id === teacherNote.id
                              ? "bg-white text-slate-950 shadow-sm ring-1 ring-slate-300 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700"
                              : "text-slate-600 hover:bg-slate-200/60 dark:text-slate-400 dark:hover:bg-slate-800"
                          }`}
                        >
                          <span className="flex items-center gap-1 text-blue-600 dark:text-blue-400">
                            <User className="h-3.5 w-3.5" />
                            👤 Teacher Note
                          </span>
                          {teacherNote.is_active ? (
                            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-1.5 py-0.5 text-[9px] font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                              <span className="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                              🟢 Visible
                            </span>
                          ) : (
                            <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-1.5 py-0.5 text-[9px] font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                              ⚪ Hidden
                            </span>
                          )}
                        </button>
                      ) : (
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => {
                            setEditorDefaultType("teacher")
                            setNoteToEdit(null)
                            setIsEditorOpen(true)
                          }}
                          className="h-8 gap-1.5 border-dashed text-xs text-blue-700 hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-blue-950/40"
                        >
                          <PlusCircle className="h-3.5 w-3.5" />
                          ➕ Write Teacher Note
                        </Button>
                      )}
                    </div>

                    {/* Quick action to add complementary note if missing */}
                    <div className="flex items-center gap-1.5">
                      {!aiNote && (
                        <Button
                          size="sm"
                          variant="ghost"
                          disabled={isAiGenerating}
                          onClick={() => triggerAiGeneration(false)}
                          className="h-7 gap-1 text-[11px] text-purple-700 hover:bg-purple-50 dark:text-purple-300"
                        >
                          <Sparkles className="h-3 w-3" />
                          + Generate AI Note
                        </Button>
                      )}
                      {!teacherNote && (
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => {
                            setEditorDefaultType("teacher")
                            setNoteToEdit(null)
                            setIsEditorOpen(true)
                          }}
                          className="h-7 gap-1 text-[11px] text-slate-700 hover:bg-slate-100 dark:text-slate-300"
                        >
                          <PlusCircle className="h-3 w-3" />
                          + Write Teacher Note
                        </Button>
                      )}
                    </div>
                  </div>
                )}
              </div>

              {/* Document Reading Canvas */}
              <CardContent className="p-6 sm:p-8">
                {/* 1. HIGHEST PRIORITY: If notes exist, ALWAYS render the note immediately! */}
                {safeNotes && safeNotes.length > 0 && displayNote ? (
                  <div className="mx-auto max-w-3xl space-y-6">
                    {/* Optional small non-intrusive indicator if re-fetching in background */}
                    {isLoadingNotes && (
                      <div className="flex items-center gap-2 text-xs text-blue-500 font-medium">
                        <Loader2 className="h-3.5 w-3.5 animate-spin" />
                        <span>Updating note...</span>
                      </div>
                    )}
                    {/* Note Document Header */}
                    <div className="border-b border-slate-100 pb-5 dark:border-slate-800/80">
                      <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                          <div className="flex items-center gap-2">
                            <h3 className="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                              {displayNote.title}
                            </h3>
                            {displayNote.is_ai_generated ? (
                              <Badge className="gap-1 bg-purple-500/10 text-purple-600 border border-purple-500/20 dark:text-purple-300">
                                ⚡ AI Generated
                              </Badge>
                            ) : (
                              <Badge className="gap-1 bg-blue-500/10 text-blue-600 border border-blue-500/20 dark:text-blue-300">
                                👤 Teacher Authored
                              </Badge>
                            )}
                          </div>

                          {displayNote.last_generated_at && (
                            <div className="mt-1.5 flex items-center gap-1.5 text-xs text-slate-400">
                              <Clock className="h-3 w-3" />
                              <span>Last updated: {new Date(displayNote.last_generated_at).toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" })}</span>
                            </div>
                          )}
                        </div>

                        {/* Visibility Status Badge & Independent Toggle Action (Teacher Note Only) */}
                        <div className="flex flex-wrap items-center gap-2.5">
                          {!displayNote.is_ai_generated ? (
                            displayNote.is_active ? (
                              <div className="flex items-center gap-2">
                                <Badge className="gap-1.5 border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 font-semibold shadow-xs">
                                  <span className="h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
                                  🟢 Visible to Students
                                </Badge>
                                <Button
                                  size="sm"
                                  variant="outline"
                                  disabled={isActivating}
                                  onClick={() => handleToggleVisibility(displayNote.id, Boolean(displayNote.is_active))}
                                  className="h-7 gap-1.5 border-amber-300 bg-amber-50/70 text-xs font-semibold text-amber-800 hover:bg-amber-100 hover:text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300 shadow-xs"
                                >
                                  {isActivating ? (
                                    <Loader2 className="h-3.5 w-3.5 animate-spin" />
                                  ) : (
                                    <EyeOff className="h-3.5 w-3.5 text-amber-600" />
                                  )}
                                  <span>🙈 Hide from Students</span>
                                </Button>
                              </div>
                            ) : (
                              <div className="flex items-center gap-2">
                                <Badge variant="outline" className="gap-1.5 border-slate-300 text-slate-500 dark:border-slate-700 dark:text-slate-400 font-medium">
                                  <span className="h-2 w-2 rounded-full bg-slate-400" />
                                  ⚪ Hidden from Students
                                </Badge>
                                <Button
                                  size="sm"
                                  disabled={isActivating}
                                  onClick={() => handleToggleVisibility(displayNote.id, Boolean(displayNote.is_active))}
                                  className="h-7 gap-1.5 bg-emerald-600 text-xs font-semibold text-white hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-700 shadow-xs"
                                >
                                  {isActivating ? (
                                    <Loader2 className="h-3.5 w-3.5 animate-spin" />
                                  ) : (
                                    <Eye className="h-3.5 w-3.5" />
                                  )}
                                  <span>👁️ Make Visible to Students</span>
                                </Button>
                              </div>
                            )
                          ) : (
                            <div className="flex items-center gap-2">
                              <Badge className="gap-1.5 border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-900/50 dark:bg-purple-950/40 dark:text-purple-300 font-semibold shadow-xs">
                                <Sparkles className="h-3 w-3" />
                                Always Visible to Students
                              </Badge>
                            </div>
                          )}

                          <span className="text-xs text-slate-400">
                            {displayNote.word_count ? `${displayNote.word_count.toLocaleString()} words` : ""}
                          </span>
                        </div>
                      </div>
                    </div>

                    {/* Markdown Document Content with Typography */}
                    <div className="min-h-[400px]">
                      <MarkdownRenderer content={displayNote.content || ""} />
                    </div>
                  </div>
                ) : isLoadingNotes ? (
                  /* 2. SECOND PRIORITY: Only show spinner if we have ZERO notes and are actively loading */
                  <div className="flex flex-col items-center justify-center py-24 text-slate-400">
                    <Loader2 className="mb-3 h-8 w-8 animate-spin text-blue-500" />
                    <p className="text-sm font-medium">Loading chapter notes...</p>
                  </div>
                ) : (
                  /* Empty State: No Notes for this Chapter */
                  <div className="flex flex-col items-center justify-center py-16 text-center">
                    <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400">
                      <BookMarked className="h-8 w-8" />
                    </div>
                    <h3 className="mt-4 text-base font-bold text-slate-900 dark:text-slate-100">
                      No Notes Available for this Chapter
                    </h3>
                    <p className="mt-1.5 max-w-sm text-xs text-slate-500">
                      Synthesize high-yield exam summary notes and formula sheets directly from the textbook PDF using AI synthesis, or author notes manually.
                    </p>

                    <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
                      <Button
                        onClick={() => triggerAiGeneration(false)}
                        disabled={isAiGenerating}
                        className="gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 shadow-sm"
                      >
                        {isAiGenerating ? (
                          <>
                            <Loader2 className="h-4 w-4 animate-spin" />
                            ⚡ Generating Full AI Note...
                          </>
                        ) : (
                          <>
                            <Sparkles className="h-4 w-4" />
                            ⚡ Generate with AI
                          </>
                        )}
                      </Button>

                      <Button
                        variant="outline"
                        onClick={() => {
                          setNoteToEdit(null)
                          setIsEditorOpen(true)
                        }}
                        className="gap-2"
                      >
                        <Edit2 className="h-4 w-4" />
                        Write Manually
                      </Button>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          ) : (
            /* Empty State: No Chapter Selected */
            <Card className="border-dashed border-slate-200 p-12 text-center text-slate-400 dark:border-slate-800">
              <GraduationCap className="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" />
              <h3 className="mt-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                Select a Chapter to Inspect Notes
              </h3>
              <p className="mt-1 text-xs text-slate-400">
                Choose a Grade and Subject above, then select a chapter from the list.
              </p>
            </Card>
          )}
        </div>
      </div>

      {/* 4. Manual Note Editor Modal */}
      {isEditorOpen && activeChapter && (
        <NoteEditorModal
          isOpen={isEditorOpen}
          onClose={() => {
            setIsEditorOpen(false)
            setNoteToEdit(null)
          }}
          chapterId={activeChapter.id}
          chapterTitle={activeChapter.title}
          gradeId={activeChapter.grade_id}
          subjectId={activeChapter.subject_id}
          note={noteToEdit}
          noteToEdit={noteToEdit}
          defaultType={editorDefaultType}
          onSaved={async (savedNote) => {
            setIsEditorOpen(false)
            setNoteToEdit(null)
            if (activeChapter) {
              await fetchChapterNotesSilently(activeChapter.id)
            }
          }}
        />
      )}

      {/* 5. AI Overwrite Confirmation Guard Modal */}
      <Dialog open={overwriteModalOpen} onOpenChange={setOverwriteModalOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <div className="flex items-center gap-2 text-amber-600 dark:text-amber-400">
              <AlertTriangle className="h-5 w-5" />
              <DialogTitle>Regenerate AI Note?</DialogTitle>
            </div>
            <DialogDescription className="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
              An AI note already exists for <strong className="text-slate-900 dark:text-slate-200">{activeChapter?.title}</strong>. 
              Triggering regeneration will overwrite the existing AI note with clean, newly synthesized curriculum content and set it as visible to students.
            </DialogDescription>
          </DialogHeader>

          <DialogFooter className="mt-4 gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => setOverwriteModalOpen(false)}
              disabled={isAiGenerating}
            >
              Cancel
            </Button>
            <Button
              type="button"
              onClick={() => triggerAiGeneration(true)}
              disabled={isAiGenerating}
              className="gap-2 bg-amber-600 text-white hover:bg-amber-700 dark:bg-amber-700 dark:hover:bg-amber-800"
            >
              {isAiGenerating ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  ⚡ Generating Full AI Note...
                </>
              ) : (
                "Yes, Overwrite & Regenerate"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* 6. Delete Note Confirmation Modal */}
      <Dialog open={deleteModalOpen} onOpenChange={setDeleteModalOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <div className="flex items-center gap-2 text-rose-600 dark:text-rose-400">
              <Trash2 className="h-5 w-5" />
              <DialogTitle>Delete Chapter Note</DialogTitle>
            </div>
            <DialogDescription className="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
              Are you sure you want to permanently delete{" "}
              <strong className="text-slate-900 dark:text-slate-200">{noteToDelete?.title}</strong>? This action cannot be undone.
            </DialogDescription>
          </DialogHeader>

          <DialogFooter className="mt-4 gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => setDeleteModalOpen(false)}
              disabled={isDeleting}
            >
              Cancel
            </Button>
            <Button
              type="button"
              variant="destructive"
              onClick={handleDeleteNote}
              disabled={isDeleting}
              className="gap-2"
            >
              {isDeleting ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Deleting...
                </>
              ) : (
                "Delete Note"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}

