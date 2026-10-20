"use client"

import React, { useState, useEffect } from "react"
import Link from "next/link"
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
  BookOpen,
  ArrowRight,
  Edit2,
  Trash2,
  RefreshCw,
  Loader2,
  AlertTriangle,
  AlertCircle,
  GraduationCap,
} from "lucide-react"

export interface LiveSubjectItem {
  id: number
  title: string
  description?: string
  grade_id: number
  grade_name: string
  stream_id?: number | null
  stream_name: string
  stream_slug: "natural" | "social" | "common"
  chapters_count: number
}

interface GradeOption {
  id: number
  name: string
}

export default function SubjectsPage() {
  const cachedSubjects = getCached<LiveSubjectItem[]>("admin_subjects_list")
  const cachedGrades = getCached<GradeOption[]>("admin_grades_options")

  const [subjects, setSubjects] = useState<LiveSubjectItem[]>(cachedSubjects || [])
  const [gradesList, setGradesList] = useState<GradeOption[]>(cachedGrades || [])
  const [isLoading, setIsLoading] = useState<boolean>(!cachedSubjects)
  const [error, setError] = useState<string | null>(null)
  const [dataSource, setDataSource] = useState<"loading" | "live" | "error">(cachedSubjects ? "live" : "loading")
  const [fetchTrigger, setFetchTrigger] = useState(0)

  // Filters
  const [searchQuery, setSearchQuery] = useState("")
  const [gradeFilter, setGradeFilter] = useState("all")
  const [streamFilter, setStreamFilter] = useState("all")

  // Add / Edit Dialog State
  const [dialogOpen, setDialogOpen] = useState(false)
  const [editingSubject, setEditingSubject] = useState<LiveSubjectItem | null>(null)
  const [subjectTitle, setSubjectTitle] = useState("")
  const [subjectDescription, setSubjectDescription] = useState("")
  const [selectedGradeId, setSelectedGradeId] = useState<number>(1)
  const [selectedStreamId, setSelectedStreamId] = useState<string>("null")
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [saveError, setSaveError] = useState<string | null>(null)

  // Delete Dialog State
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)
  const [subjectToDelete, setSubjectToDelete] = useState<LiveSubjectItem | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)
  const [deleteError, setDeleteError] = useState<string | null>(null)

  // Check if a grade is eligible for streaming (Grades 11 & 12 only)
  const isGradeStreamingEligible = (gradeId: number): boolean => {
    const gradeObj = gradesList.find((g) => g.id === gradeId)
    const name = gradeObj ? gradeObj.name : `Grade ${gradeId}`
    const num = parseInt(name.replace(/\D/g, "") || "0", 10)
    return num >= 11
  }

  useEffect(() => {
    let isMounted = true
    const hasCache = !!getCached("admin_subjects_list")
    if (!hasCache) {
      setIsLoading(true)
      setDataSource("loading")
    }
    setError(null)

    Promise.all([
      cachedGet("/grades").catch(() => []),
      cachedGet("/admin/subjects").catch(() => cachedGet("/subjects").catch(() => [])),
    ])
      .then(([gradesData, subjectsData]) => {
        if (!isMounted) return

        const rawGrades = Array.isArray(gradesData)
          ? gradesData
          : gradesData?.data || []

        const gradesMap = new Map<number, string>()
        const options: GradeOption[] = []

        rawGrades.forEach((g: any) => {
          gradesMap.set(g.id, g.name)
          options.push({ id: g.id, name: g.name })
        })

        // Sort grades naturally
        options.sort((a, b) => {
          const numA = parseInt(a.name.replace(/\D/g, "") || "0", 10)
          const numB = parseInt(b.name.replace(/\D/g, "") || "0", 10)
          return numA - numB
        })
        setGradesList(options)
        setCached("admin_grades_options", options)
        if (options.length > 0 && selectedGradeId === 1) {
          setSelectedGradeId(options[0].id)
        }

        const rawSubjects = Array.isArray(subjectsData)
          ? subjectsData
          : subjectsData?.data || []

        const normalized: LiveSubjectItem[] = []

        if (rawSubjects.length > 0) {
          rawSubjects.forEach((s: any) => {
            const gradeName = s.grade?.name || gradesMap.get(s.grade_id) || `Grade ${s.grade_id}`
            let streamSlug: "natural" | "social" | "common" = "common"
            let streamName = "Common Core"

            const streamIdNum = s.stream_id !== null && s.stream_id !== undefined ? Number(s.stream_id) : null

            if (streamIdNum === 1 || s.stream?.id === 1 || s.stream_slug === "natural") {
              streamSlug = "natural"
              streamName = "Natural Science"
            } else if (streamIdNum === 2 || s.stream?.id === 2 || s.stream_slug === "social") {
              streamSlug = "social"
              streamName = "Social Science"
            } else if (s.stream?.name) {
              const lower = s.stream.name.toLowerCase()
              if (lower.includes("natural")) {
                streamSlug = "natural"
                streamName = "Natural Science"
              } else if (lower.includes("social")) {
                streamSlug = "social"
                streamName = "Social Science"
              }
            }

            normalized.push({
              id: Number(s.id),
              title: String(s.title || s.name || "Subject"),
              description: typeof s.description === "string" ? s.description : "",
              grade_id: Number(s.grade_id),
              grade_name: gradeName,
              stream_id: streamSlug === "natural" ? 1 : streamSlug === "social" ? 2 : null,
              stream_name: streamName,
              stream_slug: streamSlug,
              chapters_count:
                typeof s.chapters_count === "number"
                  ? s.chapters_count
                  : Array.isArray(s.chapters)
                  ? s.chapters.length
                  : 0,
            })
          })
        } else if (rawGrades.length > 0) {
          // Fallback: extract subjects from grades data
          rawGrades.forEach((g: any) => {
            if (Array.isArray(g.subjects)) {
              g.subjects.forEach((s: any) => {
                let streamSlug: "natural" | "social" | "common" = "common"
                let streamName = "Common Core"
                const streamIdNum = s.stream_id !== null && s.stream_id !== undefined ? Number(s.stream_id) : null

                if (streamIdNum === 1 || s.stream?.id === 1 || s.stream_slug === "natural") {
                  streamSlug = "natural"
                  streamName = "Natural Science"
                } else if (streamIdNum === 2 || s.stream?.id === 2 || s.stream_slug === "social") {
                  streamSlug = "social"
                  streamName = "Social Science"
                }

                normalized.push({
                  id: Number(s.id),
                  title: String(s.title || s.name || "Subject"),
                  description: typeof s.description === "string" ? s.description : "",
                  grade_id: Number(g.id),
                  grade_name: g.name,
                  stream_id: streamSlug === "natural" ? 1 : streamSlug === "social" ? 2 : null,
                  stream_name: streamName,
                  stream_slug: streamSlug,
                  chapters_count: 0,
                })
              })
            }
          })
        }

        // Sort by Grade name, then title
        normalized.sort((a, b) => {
          if (a.grade_name !== b.grade_name) {
            return a.grade_name.localeCompare(b.grade_name, undefined, { numeric: true })
          }
          return a.title.localeCompare(b.title)
        })

        setSubjects(normalized)
        setCached("admin_subjects_list", normalized)
        setDataSource("live")
      })
      .catch((err) => {
        if (!isMounted) return
        console.error("Failed to load live subjects:", err)
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
    setEditingSubject(null)
    setSubjectTitle("")
    setSubjectDescription("")
    setSaveError(null)
    if (gradesList.length > 0) {
      setSelectedGradeId(gradesList[0].id)
    }
    setSelectedStreamId("null")
    setDialogOpen(true)
  }

  const openEditDialog = (subj: LiveSubjectItem) => {
    setEditingSubject(subj)
    setSubjectTitle(subj.title)
    setSubjectDescription(subj.description || "")
    setSaveError(null)
    setSelectedGradeId(subj.grade_id)
    if (subj.stream_slug === "natural" || subj.stream_id === 1) {
      setSelectedStreamId("1")
    } else if (subj.stream_slug === "social" || subj.stream_id === 2) {
      setSelectedStreamId("2")
    } else {
      setSelectedStreamId("null")
    }
    setDialogOpen(true)
  }

  const handleSaveSubject = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!subjectTitle.trim()) {
      setSaveError("Subject title cannot be empty.")
      return
    }

    setIsSubmitting(true)
    setSaveError(null)
    const isEligible = isGradeStreamingEligible(selectedGradeId)
    const streamVal = isEligible
      ? selectedStreamId === "1"
        ? 1
        : selectedStreamId === "2"
        ? 2
        : null
      : null

    const streamSlug: "natural" | "social" | "common" =
      streamVal === 1 ? "natural" : streamVal === 2 ? "social" : "common"
    const streamName =
      streamVal === 1
        ? "Natural Science"
        : streamVal === 2
        ? "Social Science"
        : "Common Core"

    const gradeObj = gradesList.find((g) => g.id === selectedGradeId)
    const gradeName = gradeObj ? gradeObj.name : `Grade ${selectedGradeId}`

    const payload = {
      grade_id: selectedGradeId,
      title: subjectTitle.trim(),
      name: subjectTitle.trim(), // Send both title and name for maximum compatibility
      description: subjectDescription.trim(),
      stream_id: streamVal,
      stream: streamSlug,
    }

    try {
      clearCache("admin_subjects")
      clearCache("admin_chapters")
      if (editingSubject) {
        // Update subject via API
        const res = await apiClient.put(`/admin/subjects/${editingSubject.id}`, payload)
        const updatedData = res.data?.data || res.data

        setSubjects((prev) => {
          const updated = prev.map((s) =>
            s.id === editingSubject.id
              ? {
                  ...s,
                  title: updatedData?.title || subjectTitle.trim(),
                  description: updatedData?.description ?? subjectDescription.trim(),
                  grade_id: updatedData?.grade_id || selectedGradeId,
                  grade_name: updatedData?.grade?.name || gradeName,
                  stream_id: updatedData?.stream_id !== undefined ? updatedData.stream_id : streamVal,
                  stream_name: updatedData?.stream?.name || streamName,
                  stream_slug: streamSlug,
                }
              : s
          )
          setCached("admin_subjects_list", updated)
          return updated
        })
      } else {
        // Create subject via API
        const res = await apiClient.post("/admin/subjects", payload)
        const createdData = res.data?.data || res.data
        const realId = createdData?.id

        if (!realId) {
          throw new Error("Subject was not created properly by the server.")
        }

        const newSubjectItem: LiveSubjectItem = {
          id: realId,
          title: createdData.title || subjectTitle.trim(),
          description: createdData.description ?? subjectDescription.trim(),
          grade_id: createdData.grade_id || selectedGradeId,
          grade_name: createdData.grade?.name || gradeName,
          stream_id: createdData.stream_id !== undefined ? createdData.stream_id : streamVal,
          stream_name: createdData.stream?.name || streamName,
          stream_slug: streamSlug,
          chapters_count: 0,
        }

        setSubjects((prev) => {
          const updated = [newSubjectItem, ...prev]
          setCached("admin_subjects_list", updated)
          return updated
        })
      }
      setDialogOpen(false)
    } catch (err: any) {
      console.error("Failed to save subject:", err)
      let errorMsg = "Failed to save subject. Please check your inputs and try again."
      if (err.response?.data?.errors) {
        const errors = err.response.data.errors
        const firstKey = Object.keys(errors)[0]
        if (Array.isArray(errors[firstKey]) && errors[firstKey][0]) {
          errorMsg = errors[firstKey][0]
        } else if (typeof errors[firstKey] === "string") {
          errorMsg = errors[firstKey]
        }
      } else if (err.response?.data?.message) {
        errorMsg = err.response.data.message
      } else if (err.message) {
        errorMsg = err.message
      }
      setSaveError(errorMsg)
    } finally {
      setIsSubmitting(false)
    }
  }

  const handleDeleteSubject = async () => {
    if (!subjectToDelete) return

    setIsDeleting(true)
    setDeleteError(null)
    try {
      clearCache("admin_subjects")
      clearCache("admin_chapters")
      await apiClient.delete(`/admin/subjects/${subjectToDelete.id}`)
      setSubjects((prev) => {
        const updated = prev.filter((s) => s.id !== subjectToDelete.id)
        setCached("admin_subjects_list", updated)
        return updated
      })
      setDeleteDialogOpen(false)
      setSubjectToDelete(null)
    } catch (err: any) {
      console.error("Failed to delete subject:", err)
      const errorMsg =
        err.response?.data?.message ||
        "Failed to delete subject. It may have dependent chapters or learning materials."
      setDeleteError(errorMsg)
    } finally {
      setIsDeleting(false)
    }
  }

  // Filtered list
  const filteredSubjects = subjects.filter((subj) => {
    if (gradeFilter !== "all" && String(subj.grade_id) !== gradeFilter) return false
    if (streamFilter !== "all") {
      if (streamFilter === "common" && subj.stream_slug !== "common" && subj.stream_id !== null) return false
      if (streamFilter === "natural" && subj.stream_slug !== "natural" && subj.stream_id !== 1) return false
      if (streamFilter === "social" && subj.stream_slug !== "social" && subj.stream_id !== 2) return false
    }

    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase()
      return (
        subj.title.toLowerCase().includes(q) ||
        subj.grade_name.toLowerCase().includes(q) ||
        subj.stream_name.toLowerCase().includes(q) ||
        (subj.description && subj.description.toLowerCase().includes(q))
      )
    }
    return true
  })

  // Dynamic Stream Counts for the selected grade
  const subjectsInSelectedGrade = subjects.filter(
    (s) => gradeFilter === "all" || String(s.grade_id) === gradeFilter
  )
  const countAll = subjectsInSelectedGrade.length
  const countNatural = subjectsInSelectedGrade.filter(
    (s) => s.stream_slug === "natural" || s.stream_id === 1
  ).length
  const countSocial = subjectsInSelectedGrade.filter(
    (s) => s.stream_slug === "social" || s.stream_id === 2
  ).length
  const countCommon = subjectsInSelectedGrade.filter(
    (s) => (s.stream_slug === "common" || !s.stream_id) && s.stream_slug !== "natural" && s.stream_slug !== "social"
  ).length

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100">
            Curriculum Subjects
          </h1>
          <p className="text-xs text-slate-500">
            Manage secondary subjects assigned to grade cohorts and academic stream specializations.
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
            Add Subject to Grade
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

      {/* Filter Bar & Quick Stream Filter Strip */}
      <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <CardContent className="p-4 space-y-3">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            {/* Search Input */}
            <div className="relative flex-1 max-w-sm">
              <Search className="absolute left-3 top-2.5 h-3.5 w-3.5 text-slate-400" />
              <Input
                placeholder="Search subject title, description..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-8 text-xs border-slate-200 dark:border-slate-800"
              />
            </div>

            {/* Grade Filter Dropdown */}
            <div className="flex items-center gap-2">
              <label className="text-xs font-medium text-slate-500 whitespace-nowrap">Grade:</label>
              <select
                value={gradeFilter}
                onChange={(e) => setGradeFilter(e.target.value)}
                className="h-9 rounded-md border border-slate-200 bg-white px-2.5 text-xs text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 font-medium"
              >
                <option value="all">All Grades</option>
                {gradesList.map((g) => (
                  <option key={g.id} value={String(g.id)}>
                    {g.name}
                  </option>
                ))}
              </select>
            </div>
          </div>

          {/* Stream Quick Filter Strip */}
          <div className="flex flex-wrap items-center gap-2 pt-1 border-t border-slate-100 dark:border-slate-800/60">
            <span className="text-[11px] font-semibold text-slate-400 mr-1 uppercase tracking-wider">Stream:</span>
            <Button
              type="button"
              size="sm"
              variant={streamFilter === "all" ? "default" : "outline"}
              onClick={() => setStreamFilter("all")}
              className={`h-7 text-xs font-semibold rounded-full px-3 ${
                streamFilter === "all"
                  ? "bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 shadow-sm"
                  : "border-slate-200 text-slate-600 hover:bg-slate-100 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-900"
              }`}
            >
              All Streams ({countAll})
            </Button>
            <Button
              type="button"
              size="sm"
              variant={streamFilter === "natural" ? "default" : "outline"}
              onClick={() => setStreamFilter("natural")}
              className={`h-7 text-xs font-semibold rounded-full px-3 gap-1.5 ${
                streamFilter === "natural"
                  ? "bg-blue-600 text-white shadow-sm shadow-blue-500/20 hover:bg-blue-700"
                  : "border-blue-200 text-blue-700 bg-blue-50/50 hover:bg-blue-100/70 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-300"
              }`}
            >
              <span>🔬 Natural Science</span>
              <span className="rounded-full bg-blue-500/20 px-1.5 py-0.2 text-[10px] font-bold">
                {countNatural}
              </span>
            </Button>
            <Button
              type="button"
              size="sm"
              variant={streamFilter === "social" ? "default" : "outline"}
              onClick={() => setStreamFilter("social")}
              className={`h-7 text-xs font-semibold rounded-full px-3 gap-1.5 ${
                streamFilter === "social"
                  ? "bg-purple-600 text-white shadow-sm shadow-purple-500/20 hover:bg-purple-700"
                  : "border-purple-200 text-purple-700 bg-purple-50/50 hover:bg-purple-100/70 dark:border-purple-900/60 dark:bg-purple-950/30 dark:text-purple-300"
              }`}
            >
              <span>🌍 Social Science</span>
              <span className="rounded-full bg-purple-500/20 px-1.5 py-0.2 text-[10px] font-bold">
                {countSocial}
              </span>
            </Button>
            <Button
              type="button"
              size="sm"
              variant={streamFilter === "common" ? "default" : "outline"}
              onClick={() => setStreamFilter("common")}
              className={`h-7 text-xs font-semibold rounded-full px-3 gap-1.5 ${
                streamFilter === "common"
                  ? "bg-slate-700 text-white shadow-sm hover:bg-slate-800"
                  : "border-slate-200 text-slate-700 bg-slate-50 hover:bg-slate-100 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300"
              }`}
            >
              <span>📘 Common Core</span>
              <span className="rounded-full bg-slate-500/20 px-1.5 py-0.2 text-[10px] font-bold">
                {countCommon}
              </span>
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Main Subjects Table */}
      <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs text-slate-700 dark:text-slate-300">
            <thead className="border-b border-slate-200 bg-slate-50/70 font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-900/40">
              <tr>
                <th className="p-3.5">Subject Title</th>
                <th className="p-3.5">Assigned Grade</th>
                <th className="p-3.5">Stream Specialization</th>
                <th className="p-3.5">Chapters Linked</th>
                <th className="p-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {isLoading && subjects.length === 0 ? (
                <tr>
                  <td colSpan={5} className="py-12 text-center text-slate-400">
                    <div className="flex flex-col items-center justify-center gap-2">
                      <Loader2 className="h-6 w-6 animate-spin text-blue-500" />
                      <span className="text-xs">Loading subjects...</span>
                    </div>
                  </td>
                </tr>
              ) : filteredSubjects.length === 0 ? (
                <tr>
                  <td colSpan={5} className="p-8 text-center text-slate-400">
                    No subjects match the selected filters.
                  </td>
                </tr>
              ) : (
                filteredSubjects.map((subj) => (
                  <tr
                    key={subj.id}
                    className="transition hover:bg-slate-50/70 dark:hover:bg-slate-900/40"
                  >
                    <td className="p-3.5">
                      <div className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <BookOpen className="h-3.5 w-3.5 text-blue-600 dark:text-blue-400 shrink-0" />
                        <span>{subj.title}</span>
                      </div>
                      {subj.description && (
                        <p className="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 mt-0.5">
                          {subj.description}
                        </p>
                      )}
                    </td>
                    <td className="p-3.5">
                      <Badge variant="outline" className="text-[10px] font-semibold">
                        {subj.grade_name}
                      </Badge>
                    </td>
                    <td className="p-3.5">
                      {subj.stream_slug === "natural" || subj.stream_id === 1 ? (
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-blue-500/10 px-2.5 py-1 text-[11px] font-semibold text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-900">
                          <span className="h-1.5 w-1.5 rounded-full bg-blue-500" />
                          🔬 Natural Science
                        </span>
                      ) : subj.stream_slug === "social" || subj.stream_id === 2 ? (
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-purple-500/10 px-2.5 py-1 text-[11px] font-semibold text-purple-700 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-900">
                          <span className="h-1.5 w-1.5 rounded-full bg-purple-500" />
                          🌍 Social Science
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-500/10 px-2.5 py-1 text-[11px] font-semibold text-slate-700 border border-slate-200 dark:bg-slate-900/40 dark:text-slate-300 dark:border-slate-800">
                          <span className="h-1.5 w-1.5 rounded-full bg-slate-400" />
                          📘 Common Core
                        </span>
                      )}
                    </td>
                    <td className="p-3.5">
                      <span className="font-semibold text-slate-800 dark:text-slate-200">
                        {subj.chapters_count} Chapters
                      </span>
                    </td>
                    <td className="p-3.5 text-right">
                      <div className="flex items-center justify-end gap-1.5">
                        <Link href={`/curriculum/chapters?subject_id=${subj.id}&subject=${encodeURIComponent(subj.title)}`}>
                          <Button
                            size="sm"
                            variant="ghost"
                            className="h-7 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400"
                          >
                            Chapters <ArrowRight className="ml-1 h-3 w-3" />
                          </Button>
                        </Link>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => openEditDialog(subj)}
                          className="h-7 w-7 p-0 text-slate-500 hover:text-slate-900 dark:hover:text-slate-100"
                          title="Edit Subject"
                        >
                          <Edit2 className="h-3.5 w-3.5" />
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => {
                            setSubjectToDelete(subj)
                            setDeleteDialogOpen(true)
                          }}
                          className="h-7 w-7 p-0 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                          title="Delete Subject"
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

      {/* Add / Edit Subject Dialog */}
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>
              {editingSubject ? "Edit Curriculum Subject" : "Add Subject to Grade"}
            </DialogTitle>
            <DialogDescription>
              {editingSubject
                ? "Update subject details, syllabus description, or assigned academic track."
                : "Create a new secondary subject assigned to an official grade cohort."}
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleSaveSubject} className="space-y-4 pt-2">
            {saveError && (
              <div className="rounded-md bg-rose-50 p-3 text-xs text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-200 dark:border-rose-900 flex items-start gap-2">
                <AlertTriangle className="h-4 w-4 shrink-0 mt-0.5 text-rose-600 dark:text-rose-400" />
                <div className="flex-1 font-medium">{saveError}</div>
              </div>
            )}

            <div className="space-y-1.5">
              <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Subject Title
              </Label>
              <Input
                required
                placeholder="e.g. Organic Chemistry"
                value={subjectTitle}
                onChange={(e) => setSubjectTitle(e.target.value)}
                className="text-xs"
              />
            </div>

            <div className="space-y-1.5">
              <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Target Grade
              </Label>
              <select
                value={selectedGradeId}
                onChange={(e) => {
                  const newGradeId = Number(e.target.value)
                  setSelectedGradeId(newGradeId)
                  if (!isGradeStreamingEligible(newGradeId)) {
                    setSelectedStreamId("null")
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

            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Stream Specialization
                </Label>
                {isGradeStreamingEligible(selectedGradeId) ? (
                  <span className="text-[10px] text-blue-600 dark:text-blue-400 font-medium">
                    Grades 11 & 12 Specialized Track
                  </span>
                ) : (
                  <span className="text-[10px] text-amber-600 dark:text-amber-400 font-medium">
                    Unified Core Curriculum
                  </span>
                )}
              </div>

              {!isGradeStreamingEligible(selectedGradeId) ? (
                <div className="rounded-lg border border-amber-200 bg-amber-50/70 p-3 text-xs text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
                  <div className="flex items-start gap-2">
                    <AlertCircle className="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" />
                    <div>
                      <p className="font-semibold">Locked to Both Streams (Common Core)</p>
                      <p className="text-[11px] mt-0.5 text-amber-700 dark:text-amber-300 leading-relaxed">
                        Stream specialization begins in Grade 11. Grades 9 & 10 follow a unified curriculum.
                      </p>
                    </div>
                  </div>
                </div>
              ) : (
                <div className="grid grid-cols-3 gap-2">
                  {/* Natural Science Card */}
                  <button
                    type="button"
                    onClick={() => setSelectedStreamId("1")}
                    className={`flex flex-col items-center justify-center p-2.5 rounded-lg border text-center transition-all ${
                      selectedStreamId === "1"
                        ? "border-blue-600 bg-blue-50/90 text-blue-700 shadow-sm ring-1 ring-blue-600 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-500"
                        : "border-slate-200 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                    }`}
                  >
                    <span className="text-base mb-1">🔬</span>
                    <span className="text-xs font-bold leading-tight">Natural</span>
                    <span className="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Science</span>
                  </button>

                  {/* Social Science Card */}
                  <button
                    type="button"
                    onClick={() => setSelectedStreamId("2")}
                    className={`flex flex-col items-center justify-center p-2.5 rounded-lg border text-center transition-all ${
                      selectedStreamId === "2"
                        ? "border-purple-600 bg-purple-50/90 text-purple-700 shadow-sm ring-1 ring-purple-600 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-500"
                        : "border-slate-200 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                    }`}
                  >
                    <span className="text-base mb-1">🌍</span>
                    <span className="text-xs font-bold leading-tight">Social</span>
                    <span className="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Science</span>
                  </button>

                  {/* Common Core Card */}
                  <button
                    type="button"
                    onClick={() => setSelectedStreamId("null")}
                    className={`flex flex-col items-center justify-center p-2.5 rounded-lg border text-center transition-all ${
                      selectedStreamId === "null"
                        ? "border-slate-800 bg-slate-100 text-slate-900 shadow-sm ring-1 ring-slate-800 dark:bg-slate-800 dark:text-slate-100 dark:border-slate-600"
                        : "border-slate-200 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                    }`}
                  >
                    <span className="text-base mb-1">📘</span>
                    <span className="text-xs font-bold leading-tight">Both</span>
                    <span className="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Common Core</span>
                  </button>
                </div>
              )}
            </div>

            <div className="space-y-1.5">
              <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Description / Syllabus Overview
              </Label>
              <textarea
                rows={3}
                placeholder="Briefly describe the curriculum scope..."
                value={subjectDescription}
                onChange={(e) => setSubjectDescription(e.target.value)}
                className="w-full rounded-md border border-slate-200 bg-white p-2 text-xs text-slate-900 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
              />
            </div>

            <DialogFooter className="pt-2">
              <Button
                type="button"
                variant="outline"
                disabled={isSubmitting}
                onClick={() => {
                  setDialogOpen(false)
                  setSaveError(null)
                }}
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
                ) : editingSubject ? (
                  "Update Subject"
                ) : (
                  "Add to Grade"
                )}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation Dialog */}
      <Dialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader>
            <DialogTitle className="text-rose-600">Delete Subject?</DialogTitle>
            <DialogDescription>
              Are you sure you want to delete <strong>{subjectToDelete?.title}</strong> ({subjectToDelete?.grade_name})? This action cannot be undone.
            </DialogDescription>
          </DialogHeader>

          {deleteError && (
            <div className="rounded-md bg-rose-50 p-3 text-xs text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-200 dark:border-rose-900 flex items-start gap-2 my-2">
              <AlertTriangle className="h-4 w-4 shrink-0 mt-0.5 text-rose-600 dark:text-rose-400" />
              <div className="flex-1 font-medium">{deleteError}</div>
            </div>
          )}

          <DialogFooter className="pt-2">
            <Button
              variant="outline"
              size="sm"
              disabled={isDeleting}
              onClick={() => {
                setDeleteDialogOpen(false)
                setDeleteError(null)
              }}
              className="text-xs"
            >
              Cancel
            </Button>
            <Button
              variant="destructive"
              size="sm"
              disabled={isDeleting}
              onClick={handleDeleteSubject}
              className="text-xs"
            >
              {isDeleting ? (
                <>
                  <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" /> Deleting...
                </>
              ) : (
                "Confirm Delete"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
