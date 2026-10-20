"use client"

import React, { useState, useEffect } from "react"
import { useSearchParams } from "next/navigation"
import { apiClient, cachedGet } from "@/lib/api-client"
import { getCached, setCached, clearCache } from "@/lib/cache"
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
  Search,
  PlusCircle,
  FileText,
  Sparkles,
  Edit2,
  Trash2,
  RefreshCw,
  Loader2,
  AlertTriangle,
  BookOpen,
  GraduationCap,
  CheckCircle2,
  Zap,
  FolderOpen,
  Paperclip,
  Film,
  FileCheck,
  AlertCircle,
  Coins,
} from "lucide-react"
import { ChapterMediaModal } from "@/components/drawers/chapter-media-modal"

export interface LiveChapterItem {
  id: number
  subject_id: number
  subject_name: string
  grade_id: number
  grade_name: string
  chapter_number: number
  title: string
  description?: string
  pdf_path?: string | null
  ai_generated_notes?: string | null
  notes_generated_at?: string | null
  ai_notes_status: "ready" | "draft" | "not_generated"
  is_featured?: boolean
  textbooks_count?: number
  resources_count?: number
  videos_count?: number
  has_textbook?: boolean
}

interface GradeRef {
  id: number
  name: string
}

interface SubjectRef {
  id: number
  grade_id: number
  title: string
}

export default function ChaptersPage() {
  const searchParams = useSearchParams()
  const initialSubjectQuery = searchParams.get("subject") || ""
  const initialSubjectIdQuery = searchParams.get("subject_id") || "all"

  const cachedChapters = getCached<LiveChapterItem[]>("admin_chapters_list")
  const cachedGrades = getCached<GradeRef[]>("admin_grades_list")
  const cachedSubjects = getCached<SubjectRef[]>("admin_subjects_ref_list")

  const [chapters, setChapters] = useState<LiveChapterItem[]>(cachedChapters || [])
  const [gradesList, setGradesList] = useState<GradeRef[]>(cachedGrades || [])
  const [subjectsList, setSubjectsList] = useState<SubjectRef[]>(cachedSubjects || [])
  const [isLoading, setIsLoading] = useState<boolean>(!cachedChapters)
  const [error, setError] = useState<string | null>(null)
  const [dataSource, setDataSource] = useState<"loading" | "live" | "error">(cachedChapters ? "live" : "loading")
  const [fetchTrigger, setFetchTrigger] = useState(0)

  // Filters
  const [searchQuery, setSearchQuery] = useState("")
  const [subjectFilter, setSubjectFilter] = useState(initialSubjectIdQuery)
  const [gradeFilter, setGradeFilter] = useState("all")

  // Create / Edit Modal State
  const [dialogOpen, setDialogOpen] = useState(false)
  const [editingChapter, setEditingChapter] = useState<LiveChapterItem | null>(null)
  const [formGradeId, setFormGradeId] = useState<number>(1)
  const [formSubjectId, setFormSubjectId] = useState<number>(1)
  const [formChapterNumber, setFormChapterNumber] = useState<number>(1)
  const [formTitle, setFormTitle] = useState("")
  const [formDescription, setFormDescription] = useState("")
  const [isSubmitting, setIsSubmitting] = useState(false)

  // Delete Dialog State
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)
  const [chapterToDelete, setChapterToDelete] = useState<LiveChapterItem | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)
  const [deleteError, setDeleteError] = useState<string | null>(null)

  // AI Trigger State
  const [generatingAiId, setGeneratingAiId] = useState<number | null>(null)

  // Chapter Media Modal State
  const [mediaModalChapter, setMediaModalChapter] = useState<LiveChapterItem | null>(null)
  const [mediaModalOpen, setMediaModalOpen] = useState(false)
  const [mediaModalTab, setMediaModalTab] = useState<"textbook" | "resources" | "videos">("textbook")

  const openMediaModal = (ch: LiveChapterItem, tab: "textbook" | "resources" | "videos" = "textbook") => {
    setMediaModalChapter(ch)
    setMediaModalTab(tab)
    setMediaModalOpen(true)
  }

  const handleMediaUpdated = (
    chapterId: number,
    mediaCounts: {
      textbooks_count: number
      resources_count: number
      videos_count: number
      has_textbook: boolean
    }
  ) => {
    setChapters((prev) => {
      const updated = prev.map((c) =>
        c.id === chapterId
          ? {
              ...c,
              textbooks_count: mediaCounts.textbooks_count,
              resources_count: mediaCounts.resources_count,
              videos_count: mediaCounts.videos_count,
              has_textbook: mediaCounts.has_textbook,
            }
          : c
      )
      setCached("admin_chapters_list", updated)
      return updated
    })
  }

  useEffect(() => {
    let isMounted = true
    const hasCache = !!getCached("admin_chapters_list")
    if (!hasCache) {
      setIsLoading(true)
      setDataSource("loading")
    }
    setError(null)

    Promise.all([
      cachedGet("/grades").catch(() => []),
      cachedGet("/admin/subjects").catch(() => cachedGet("/subjects").catch(() => [])),
      cachedGet("/admin/chapters").catch(() => []),
    ])
      .then(([gradesData, subjectsData, chaptersData]) => {
        if (!isMounted) return

        const rawGrades = Array.isArray(gradesData)
          ? gradesData
          : gradesData?.data || []
        const gradesMap = new Map<number, string>()
        const gList: GradeRef[] = []

        rawGrades.forEach((g: any) => {
          gradesMap.set(g.id, g.name)
          gList.push({ id: g.id, name: g.name })
        })
        gList.sort((a, b) => {
          const numA = parseInt(a.name.replace(/\D/g, "") || "0", 10)
          const numB = parseInt(b.name.replace(/\D/g, "") || "0", 10)
          return numA - numB
        })
        setGradesList(gList)
        setCached("admin_grades_list", gList)

        const rawSubjects = Array.isArray(subjectsData)
          ? subjectsData
          : subjectsData?.data || []
        const sList: SubjectRef[] = []
        const extractedChapters: LiveChapterItem[] = []

        rawSubjects.forEach((s: any) => {
          sList.push({ id: s.id, grade_id: s.grade_id, title: s.title })
        })

        // Check if live chapters were fetched directly from /admin/chapters
        const directChapters = Array.isArray(chaptersData?.data)
          ? chaptersData.data
          : Array.isArray(chaptersData)
          ? chaptersData
          : []

        if (directChapters.length > 0) {
          directChapters.forEach((ch: any) => {
            const gradeName = ch.grade?.name || gradesMap.get(ch.grade_id) || `Grade ${ch.grade_id}`
            const subjectName = ch.subject?.title || sList.find((s) => s.id === ch.subject_id)?.title || `Subject ${ch.subject_id}`
            const hasNotes = Boolean(ch.has_ai_notes || (ch.ai_generated_notes && ch.ai_generated_notes.trim()) || ch.notes_generated_at)
            const tbCount = Number(ch.textbooks_count || (ch.pdf_path ? 1 : 0))
            const resCount = Number(ch.resources_count || 0)
            const vidCount = Number(ch.videos_count || 0)

            extractedChapters.push({
              id: Number(ch.id),
              subject_id: Number(ch.subject_id),
              subject_name: String(subjectName),
              grade_id: Number(ch.grade_id),
              grade_name: gradeName,
              chapter_number: Number(ch.chapter_number) || 1,
              title: String(ch.title || "Chapter"),
              description: typeof ch.description === "string" ? ch.description : "",
              pdf_path: ch.pdf_path || null,
              ai_generated_notes: ch.ai_generated_notes || null,
              notes_generated_at: ch.notes_generated_at || null,
              ai_notes_status: hasNotes ? "ready" : "not_generated",
              is_featured: Boolean(ch.is_featured),
              textbooks_count: tbCount,
              resources_count: resCount,
              videos_count: vidCount,
              has_textbook: tbCount > 0 || Boolean(ch.pdf_path),
            })
          })
        } else {
          // Fallback extraction from subjects.chapters
          rawSubjects.forEach((s: any) => {
            const gradeName = gradesMap.get(s.grade_id) || `Grade ${s.grade_id}`
            if (Array.isArray(s.chapters)) {
              s.chapters.forEach((ch: any) => {
                const hasNotes = Boolean(ch.has_ai_notes || (ch.ai_generated_notes && ch.ai_generated_notes.trim()) || ch.notes_generated_at)
                const tbCount = Number(ch.textbooks_count || (ch.pdf_path ? 1 : 0))
                const resCount = Number(ch.resources_count || 0)
                const vidCount = Number(ch.videos_count || 0)

                extractedChapters.push({
                  id: Number(ch.id),
                  subject_id: Number(s.id),
                  subject_name: String(s.title),
                  grade_id: Number(s.grade_id),
                  grade_name: gradeName,
                  chapter_number: Number(ch.chapter_number) || 1,
                  title: String(ch.title || "Chapter"),
                  description: typeof ch.description === "string" ? ch.description : "",
                  pdf_path: ch.pdf_path || null,
                  ai_generated_notes: ch.ai_generated_notes || null,
                  notes_generated_at: ch.notes_generated_at || null,
                  ai_notes_status: hasNotes ? "ready" : "not_generated",
                  is_featured: Boolean(ch.is_featured),
                  textbooks_count: tbCount,
                  resources_count: resCount,
                  videos_count: vidCount,
                  has_textbook: tbCount > 0 || Boolean(ch.pdf_path),
                })
              })
            }
          })
        }

        // Sort subjects by title
        sList.sort((a, b) => a.title.localeCompare(b.title))
        setSubjectsList(sList)
        setCached("admin_subjects_ref_list", sList)

        if (sList.length > 0) {
          setFormGradeId(sList[0].grade_id)
          setFormSubjectId(sList[0].id)
        }

        // Sort chapters by grade, then subject, then chapter number
        extractedChapters.sort((a, b) => {
          if (a.grade_name !== b.grade_name) {
            return a.grade_name.localeCompare(b.grade_name, undefined, { numeric: true })
          }
          if (a.subject_name !== b.subject_name) {
            return a.subject_name.localeCompare(b.subject_name)
          }
          return a.chapter_number - b.chapter_number
        })

        setChapters(extractedChapters)
        setCached("admin_chapters_list", extractedChapters)
        setDataSource("live")
      })
      .catch((err) => {
        if (!isMounted) return
        console.error("Failed to load live chapters:", err)
        setError(
          `Failed to reach https://apiv2.asteghi.com/api/subjects: ${
            err.response?.status ? `HTTP ${err.response.status}` : err.message
          }`
        )
        setDataSource("error")
      })
      .finally(() => {
        if (isMounted) setIsLoading(false)
      })

    return () => {
      isMounted = false
    }
  }, [fetchTrigger])

  const openCreateDialog = () => {
    setEditingChapter(null)
    setFormTitle("")
    setFormDescription("")
    const currentSubject = subjectsList.find((s) => String(s.id) === subjectFilter)
    if (currentSubject) {
      setFormGradeId(currentSubject.grade_id)
      setFormSubjectId(currentSubject.id)
      const existingChCount = chapters.filter((c) => c.subject_id === currentSubject.id).length
      setFormChapterNumber(existingChCount + 1)
    } else if (subjectsList.length > 0) {
      setFormGradeId(subjectsList[0].grade_id)
      setFormSubjectId(subjectsList[0].id)
      setFormChapterNumber(1)
    }
    setDialogOpen(true)
  }

  const openEditDialog = (ch: LiveChapterItem) => {
    setEditingChapter(ch)
    setFormGradeId(ch.grade_id)
    setFormSubjectId(ch.subject_id)
    setFormChapterNumber(ch.chapter_number)
    setFormTitle(ch.title)
    setFormDescription(ch.description || "")
    setDialogOpen(true)
  }

  const handleSaveChapter = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!formTitle.trim()) return

    setIsSubmitting(true)
    const selectedSubj = subjectsList.find((s) => s.id === formSubjectId)
    const selectedGrade = gradesList.find((g) => g.id === formGradeId)
    const subjectName = selectedSubj ? selectedSubj.title : "Subject"
    const gradeName = selectedGrade ? selectedGrade.name : `Grade ${formGradeId}`

    const payload = {
      grade_id: formGradeId,
      subject_id: formSubjectId,
      chapter_number: formChapterNumber,
      title: formTitle.trim(),
      description: formDescription.trim(),
    }

    try {
      clearCache("admin_chapters")
      if (editingChapter) {
        await apiClient.put(`/admin/chapters/${editingChapter.id}`, payload).catch(() => {})
        setChapters((prev) => {
          const updated = prev.map((c) =>
            c.id === editingChapter.id
              ? {
                  ...c,
                  title: formTitle.trim(),
                  description: formDescription.trim(),
                  chapter_number: formChapterNumber,
                  grade_id: formGradeId,
                  grade_name: gradeName,
                  subject_id: formSubjectId,
                  subject_name: subjectName,
                }
              : c
          )
          setCached("admin_chapters_list", updated)
          return updated
        })
      } else {
        const res = await apiClient.post("/admin/chapters", payload).catch(() => null)
        const newId = res?.data?.data?.id || Date.now()

        const newChapter: LiveChapterItem = {
          id: newId,
          subject_id: formSubjectId,
          subject_name: subjectName,
          grade_id: formGradeId,
          grade_name: gradeName,
          chapter_number: formChapterNumber,
          title: formTitle.trim(),
          description: formDescription.trim(),
          pdf_path: null,
          ai_generated_notes: null,
          notes_generated_at: null,
          ai_notes_status: "not_generated",
          is_featured: false,
        }

        setChapters((prev) => {
          const updated = [newChapter, ...prev]
          setCached("admin_chapters_list", updated)
          return updated
        })
      }
      setDialogOpen(false)
    } catch (err) {
      console.error("Failed to save chapter:", err)
    } finally {
      setIsSubmitting(false)
    }
  }

  const handleDeleteChapter = async () => {
    if (!chapterToDelete) return

    setIsDeleting(true)
    setDeleteError(null)
    try {
      clearCache("admin_chapters")
      await apiClient.delete(`/admin/chapters/${chapterToDelete.id}`)
      setChapters((prev) => {
        const updated = prev.filter((c) => c.id !== chapterToDelete.id)
        setCached("admin_chapters_list", updated)
        return updated
      })
      setDeleteDialogOpen(false)
      setChapterToDelete(null)
    } catch (err: any) {
      console.error("Failed to delete chapter:", err)
      const errorMsg =
        err.response?.data?.message ||
        err.response?.data?.error ||
        err.message ||
        "Failed to delete chapter. Please try again."
      setDeleteError(errorMsg)
    } finally {
      setIsDeleting(false)
    }
  }

  const handleTriggerAi = async (chapter: LiveChapterItem) => {
    setGeneratingAiId(chapter.id)
    try {
      clearCache("admin_chapters")
      await apiClient
        .post(`/admin/chapters/${chapter.id}/generate-notes`, {
          type: "standard",
          sync: true,
        })
        .catch(() => {})

      setChapters((prev) => {
        const updated = prev.map((c) =>
          c.id === chapter.id
            ? {
                ...c,
                ai_notes_status: "ready" as const,
                notes_generated_at: new Date().toISOString(),
              }
            : c
        )
        setCached("admin_chapters_list", updated)
        return updated
      })
    } catch (err) {
      console.warn("AI generation error:", err)
    } finally {
      setGeneratingAiId(null)
    }
  }

  // Filtered list
  const filteredChapters = chapters.filter((ch) => {
    if (gradeFilter !== "all" && String(ch.grade_id) !== gradeFilter) return false
    if (subjectFilter !== "all" && String(ch.subject_id) !== subjectFilter) return false

    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase()
      return (
        ch.title.toLowerCase().includes(q) ||
        ch.subject_name.toLowerCase().includes(q) ||
        ch.grade_name.toLowerCase().includes(q) ||
        (ch.description && ch.description.toLowerCase().includes(q))
      )
    }
    return true
  })

  // Filter available subjects in dialog based on selected grade
  const dialogAvailableSubjects = subjectsList.filter((s) => s.grade_id === formGradeId)

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100">
            Chapters & Content Hub
          </h1>
          <p className="text-xs text-slate-500">
            Create, edit, and organize secondary syllabus chapters and trigger AI synthesis notes.
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2.5">
          <Button
            size="sm"
            variant="outline"
            onClick={() => setFetchTrigger((prev) => prev + 1)}
            disabled={isLoading}
            className="h-8 text-xs gap-1.5"
          >
            <RefreshCw className={`h-3.5 w-3.5 ${isLoading ? "animate-spin" : ""}`} />
            Refresh
          </Button>

          <Button
            size="sm"
            onClick={openCreateDialog}
            className="bg-blue-600 font-semibold text-white shadow-md shadow-blue-600/30 hover:bg-blue-500 text-xs h-8"
          >
            <PlusCircle className="mr-1.5 h-3.5 w-3.5" />
            Add Chapter to Subject
          </Button>
        </div>
      </div>

      {/* Error Alert Banner */}
      {error && (
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-800 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200">
          <div className="flex items-center gap-2.5">
            <AlertTriangle className="h-5 w-5 shrink-0 text-red-600" />
            <div>
              <p className="font-bold">Connection Error</p>
              <p className="text-[11px] text-red-700 dark:text-red-300">{error}</p>
            </div>
          </div>
          <Button
            size="sm"
            variant="outline"
            className="h-7 text-xs border-red-300 text-red-800 hover:bg-red-100 dark:border-red-800 dark:text-red-200 dark:hover:bg-red-900"
            onClick={() => setFetchTrigger((prev) => prev + 1)}
          >
            Retry Connection
          </Button>
        </div>
      )}

      {/* Filter Toolbar */}
      <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <CardContent className="p-4">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            {/* Search Input */}
            <div className="relative flex-1 max-w-sm">
              <Search className="absolute left-3 top-2.5 h-3.5 w-3.5 text-slate-400" />
              <Input
                placeholder="Search chapter title, syllabus..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-8 text-xs border-slate-200 dark:border-slate-800"
              />
            </div>

            {/* Dropdown Filters */}
            <div className="flex items-center gap-2">
              <select
                value={gradeFilter}
                onChange={(e) => {
                  setGradeFilter(e.target.value)
                  setSubjectFilter("all")
                }}
                className="h-9 rounded-md border border-slate-200 bg-white px-2.5 text-xs text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300"
              >
                <option value="all">All Grades</option>
                {gradesList.map((g) => (
                  <option key={g.id} value={String(g.id)}>
                    {g.name}
                  </option>
                ))}
              </select>

              <select
                value={subjectFilter}
                onChange={(e) => setSubjectFilter(e.target.value)}
                className="h-9 rounded-md border border-slate-200 bg-white px-2.5 text-xs text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 max-w-[200px]"
              >
                <option value="all">All Subjects</option>
                {subjectsList
                  .filter((s) => gradeFilter === "all" || String(s.grade_id) === gradeFilter)
                  .map((s) => (
                    <option key={s.id} value={String(s.id)}>
                      {s.title}
                    </option>
                  ))}
              </select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Chapters Data Table */}
      <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs text-slate-700 dark:text-slate-300">
            <thead className="border-b border-slate-200 bg-slate-50/70 font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-900/40">
              <tr>
                <th className="p-3.5 w-16 text-center">Ch #</th>
                <th className="p-3.5">Chapter Title</th>
                <th className="p-3.5">Grade / Subject</th>
                <th className="p-3.5">Textbook</th>
                <th className="p-3.5">Resources</th>
                <th className="p-3.5">Videos</th>
                <th className="p-3.5">AI Synthesis</th>
                <th className="p-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {isLoading && chapters.length === 0 ? (
                <tr>
                  <td colSpan={8} className="py-12 text-center text-slate-400">
                    <div className="flex flex-col items-center justify-center gap-2">
                      <Loader2 className="h-6 w-6 animate-spin text-blue-500" />
                      <span className="text-xs">Loading chapters...</span>
                    </div>
                  </td>
                </tr>
              ) : filteredChapters.length === 0 ? (
                <tr>
                  <td colSpan={8} className="p-8 text-center text-slate-400">
                    No chapters match the selected criteria.
                  </td>
                </tr>
              ) : (
                filteredChapters.map((ch) => (
                  <tr
                    key={ch.id}
                    className="transition hover:bg-slate-50/70 dark:hover:bg-slate-900/40"
                  >
                    <td className="p-3.5 text-center font-bold text-slate-900 dark:text-slate-100 font-mono">
                      #{ch.chapter_number}
                    </td>
                    <td className="p-3.5">
                      <div className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                        {ch.title}
                        {ch.is_featured && (
                          <Badge variant="outline" className="text-[9px] bg-amber-50 text-amber-700 border-amber-200">
                            Featured
                          </Badge>
                        )}
                      </div>
                      {ch.description && (
                        <div className="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                          {ch.description}
                        </div>
                      )}
                    </td>
                    <td className="p-3.5">
                      <div className="font-semibold text-slate-800 dark:text-slate-200">
                        {ch.subject_name}
                      </div>
                      <div className="text-[10px] text-slate-400">
                        {ch.grade_name}
                      </div>
                    </td>

                    {/* Textbook Status */}
                    <td className="p-3.5 whitespace-nowrap">
                      {ch.has_textbook ? (
                        <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-md dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900">
                          <FileCheck className="h-3 w-3 text-emerald-600" />
                          📄 Ready
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-700 bg-amber-50 border border-amber-200/80 px-2 py-0.5 rounded-md dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900">
                          <AlertCircle className="h-3 w-3 text-amber-600" />
                          ⚠️ Missing
                        </span>
                      )}
                    </td>

                    {/* Supplementary Resources Count */}
                    <td className="p-3.5 whitespace-nowrap">
                      <button
                        type="button"
                        onClick={() => openMediaModal(ch, "resources")}
                        className="inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-800 bg-amber-50/80 border border-amber-200 hover:bg-amber-100 hover:border-amber-300 px-2.5 py-1 rounded-md transition shadow-xs dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900 cursor-pointer"
                        title="Click to view, upload, and adjust pricing for supplementary PDFs"
                      >
                        <Paperclip className="h-3 w-3 text-amber-600" />
                        <span>📎 {ch.resources_count || 0} PDFs</span>
                        <span className="text-[9px] font-normal opacity-70 underline ml-0.5">Manage</span>
                      </button>
                    </td>

                    {/* Video Lessons Count */}
                    <td className="p-3.5 whitespace-nowrap">
                      <span className="inline-flex items-center gap-1 text-[11px] font-medium text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-md dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-900">
                        <Film className="h-3 w-3 text-purple-500" />
                        🎥 {ch.videos_count || 0} Videos
                      </span>
                    </td>

                    <td className="p-3.5">
                      {ch.ai_notes_status === "ready" ? (
                        <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                          <CheckCircle2 className="h-3.5 w-3.5" /> Synthesized
                        </span>
                      ) : (
                        <Button
                          size="sm"
                          variant="outline"
                          disabled={generatingAiId === ch.id}
                          onClick={() => handleTriggerAi(ch)}
                          className="h-6 text-[10px] gap-1 px-2 border-purple-200 text-purple-700 hover:bg-purple-50 dark:border-purple-800 dark:text-purple-300"
                        >
                          {generatingAiId === ch.id ? (
                            <>
                              <Loader2 className="h-3 w-3 animate-spin" /> Synthesizing...
                            </>
                          ) : (
                            <>
                              <Zap className="h-3 w-3 text-amber-500" /> Synthesize Notes
                            </>
                          )}
                        </Button>
                      )}
                    </td>
                    <td className="p-3.5 text-right whitespace-nowrap">
                      <div className="flex items-center justify-end gap-1.5">
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => openMediaModal(ch, "resources")}
                          className="h-7 text-xs px-2.5 gap-1.5 font-semibold text-amber-800 bg-amber-50 border-amber-300 hover:bg-amber-100 hover:border-amber-400 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800 shadow-xs"
                          title="Manage Supplementary PDF Resources & Pricing (Free / Locked)"
                        >
                          <Coins className="h-3.5 w-3.5 text-amber-600" />
                          <span>💰 Resources &amp; Pricing</span>
                        </Button>
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => openMediaModal(ch, "textbook")}
                          className="h-7 text-xs px-2.5 gap-1.5 font-semibold text-blue-700 bg-blue-50/70 border-blue-200 hover:bg-blue-100 hover:border-blue-300 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-900 shadow-sm"
                          title="Manage Chapter Media & Assets (Textbook, Resources, Videos)"
                        >
                          <FolderOpen className="h-3.5 w-3.5 text-blue-600" />
                          <span>📁 All Media</span>
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => openEditDialog(ch)}
                          className="h-7 w-7 p-0 text-slate-500 hover:text-slate-900 dark:hover:text-slate-100"
                          title="Edit Chapter"
                        >
                          <Edit2 className="h-3.5 w-3.5" />
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => {
                            setChapterToDelete(ch)
                            setDeleteError(null)
                            setDeleteDialogOpen(true)
                          }}
                          className="h-7 w-7 p-0 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                          title="Delete Chapter"
                        >
                          <Trash2 className="h-3.5 w-3.5" />
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Add / Edit Chapter Dialog */}
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>
              {editingChapter ? "Edit Curriculum Chapter" : "Add Chapter to Subject"}
            </DialogTitle>
            <DialogDescription>
              {editingChapter
                ? "Update chapter number, title, or syllabus details."
                : "Create a new sequential chapter under an official curriculum subject."}
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleSaveChapter} className="space-y-4 pt-2">
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Target Grade
                </Label>
                <select
                  value={formGradeId}
                  onChange={(e) => {
                    const nextGradeId = Number(e.target.value)
                    setFormGradeId(nextGradeId)
                    const avail = subjectsList.filter((s) => s.grade_id === nextGradeId)
                    if (avail.length > 0) {
                      setFormSubjectId(avail[0].id)
                    }
                  }}
                  className="w-full h-9 rounded-md border border-slate-200 bg-white p-2 text-xs text-slate-900 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
                >
                  {gradesList.map((g) => (
                    <option key={g.id} value={g.id}>
                      {g.name}
                    </option>
                  ))}
                </select>
              </div>

              <div className="space-y-1.5">
                <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Target Subject
                </Label>
                <select
                  value={formSubjectId}
                  onChange={(e) => setFormSubjectId(Number(e.target.value))}
                  className="w-full h-9 rounded-md border border-slate-200 bg-white p-2 text-xs text-slate-900 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
                >
                  {dialogAvailableSubjects.map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.title}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="grid grid-cols-3 gap-3">
              <div className="space-y-1.5 col-span-1">
                <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Chapter #
                </Label>
                <Input
                  type="number"
                  min={1}
                  required
                  value={formChapterNumber}
                  onChange={(e) => setFormChapterNumber(parseInt(e.target.value) || 1)}
                  className="text-xs font-mono"
                />
              </div>

              <div className="space-y-1.5 col-span-2">
                <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Chapter Title
                </Label>
                <Input
                  required
                  placeholder="e.g. Chemical Bonding"
                  value={formTitle}
                  onChange={(e) => setFormTitle(e.target.value)}
                  className="text-xs"
                />
              </div>
            </div>

            <div className="space-y-1.5">
              <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Syllabus Overview / Description
              </Label>
              <textarea
                rows={3}
                placeholder="Key learning outcomes and concepts covered..."
                value={formDescription}
                onChange={(e) => setFormDescription(e.target.value)}
                className="w-full rounded-md border border-slate-200 bg-white p-2 text-xs text-slate-900 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
              />
            </div>

            <DialogFooter className="pt-2">
              <Button
                type="button"
                variant="outline"
                onClick={() => setDialogOpen(false)}
                className="text-xs"
              >
                Cancel
              </Button>
              <Button
                type="submit"
                disabled={isSubmitting}
                className="bg-blue-600 text-xs font-semibold text-white hover:bg-blue-500"
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" /> Saving...
                  </>
                ) : editingChapter ? (
                  "Update Chapter"
                ) : (
                  "Add Chapter"
                )}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation Dialog */}
      <Dialog
        open={deleteDialogOpen}
        onOpenChange={(open) => {
          setDeleteDialogOpen(open)
          if (!open) {
            setDeleteError(null)
          }
        }}
      >
        <DialogContent className="sm:max-w-sm">
          <DialogHeader>
            <DialogTitle className="text-rose-600">Delete Chapter?</DialogTitle>
            <DialogDescription>
              Are you sure you want to delete <strong>Chapter #{chapterToDelete?.chapter_number}: {chapterToDelete?.title}</strong> ({chapterToDelete?.subject_name})? This will permanently delete the chapter and all associated notes, quizzes, textbooks, and resources.
            </DialogDescription>
          </DialogHeader>

          {deleteError && (
            <div className="rounded-md bg-rose-50 border border-rose-200 p-2.5 text-xs text-rose-700 flex items-start gap-2 my-2">
              <AlertTriangle className="h-4 w-4 shrink-0 text-rose-600 mt-0.5" />
              <span>{deleteError}</span>
            </div>
          )}

          <DialogFooter className="pt-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => {
                setDeleteDialogOpen(false)
                setDeleteError(null)
              }}
              disabled={isDeleting}
              className="text-xs"
            >
              Cancel
            </Button>
            <Button
              variant="destructive"
              size="sm"
              onClick={handleDeleteChapter}
              disabled={isDeleting}
              className="text-xs gap-1.5"
            >
              {isDeleting ? (
                <>
                  <Loader2 className="h-3.5 w-3.5 animate-spin" /> Deleting...
                </>
              ) : (
                "Confirm Delete"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Chapter Media & Asset Manager Drawer Modal */}
      <ChapterMediaModal
        chapter={mediaModalChapter}
        open={mediaModalOpen}
        onOpenChange={setMediaModalOpen}
        initialTab={mediaModalTab}
        onMediaUpdated={handleMediaUpdated}
      />
    </div>
  )
}
