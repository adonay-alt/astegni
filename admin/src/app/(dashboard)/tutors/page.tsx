"use client"

import React, { useState, useEffect, useCallback } from "react"
import { apiClient } from "@/lib/api-client"
import { getCached, setCached, clearCache } from "@/lib/cache"
import { TutorProfile, TutorStatus } from "@/types/tutor"
import { TutorDossierDrawer } from "@/components/drawers/tutor-dossier-drawer"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Search,
  Filter,
  CheckCircle,
  XCircle,
  Download,
  Star,
  Users,
  AlertTriangle,
  Loader2,
  RefreshCw,
  Eye,
} from "lucide-react"

function normalizeTutorProfile(raw: Record<string, unknown>): TutorProfile {
  let subjects: string[] = []
  if (Array.isArray(raw.subject_names)) {
    subjects = raw.subject_names.map(String)
  } else if (Array.isArray(raw.subjects)) {
    subjects = raw.subjects
      .map((s: unknown) => {
        if (typeof s === "string") return s
        if (typeof s === "object" && s !== null) {
          const obj = s as Record<string, unknown>
          return String(obj.title || obj.name || "")
        }
        return String(s)
      })
      .filter(Boolean)
  } else if (typeof raw.subject === "string") {
    subjects = raw.subject.split(",").map((s) => s.trim()).filter(Boolean)
  }

  let grades: string[] = []
  if (Array.isArray(raw.grade_names)) {
    grades = raw.grade_names.map(String)
  } else if (Array.isArray(raw.grades)) {
    grades = raw.grades
      .map((g: unknown) => {
        if (typeof g === "string") return g
        if (typeof g === "object" && g !== null) {
          const obj = g as Record<string, unknown>
          return String(obj.name || obj.level || "")
        }
        return String(g)
      })
      .filter(Boolean)
  } else if (typeof raw.grade === "string") {
    grades = raw.grade.split(",").map((g) => g.trim()).filter(Boolean)
  }

  const rawStatus = String(raw.status || "pending").toLowerCase()
  const validStatus: TutorStatus =
    rawStatus === "approved" || rawStatus === "rejected" || rawStatus === "suspended"
      ? rawStatus
      : "pending"

  return {
    id: Number(raw.id) || 0,
    user_id: Number(raw.user_id) || Number(raw.id) || 0,
    name: String(raw.name || (raw.user as any)?.name || `${(raw.user as any)?.first_name || ""} ${(raw.user as any)?.last_name || ""}`.trim() || "Tutor Applicant"),
    email: String(raw.email || (raw.user as any)?.email || ""),
    phone: String(raw.phone || (raw.user as any)?.phone || "—"),
    city: String(raw.city || "Addis Ababa"),
    gender: (raw.gender === "female" || raw.gender === "male" ? raw.gender : "other") as "male" | "female" | "other",
    status: validStatus,
    rating: Number(raw.average_rating ?? raw.rating ?? 0),
    total_reviews: Number(raw.review_count ?? raw.total_reviews ?? 0),
    subjects: subjects.length > 0 ? subjects : ["General Academic"],
    grades: grades.length > 0 ? grades : ["High School"],
    bio: typeof raw.bio === "string" ? raw.bio : undefined,
    avatar_url:
      typeof raw.image === "string"
        ? raw.image
        : typeof raw.avatar_url === "string"
        ? raw.avatar_url
        : typeof (raw.user as any)?.profile_photo_url === "string"
        ? (raw.user as any).profile_photo_url
        : undefined,
    documents: Array.isArray(raw.documents) ? (raw.documents as any) : [],
    rejection_reason: typeof raw.rejection_reason === "string" ? raw.rejection_reason : undefined,
    created_at: String(raw.created_at || new Date().toISOString()),
  }
}

export default function TutorsPage() {
  const cachedTutors = getCached<TutorProfile[]>("admin_tutors_list")
  const cachedStats = getCached<{ total: number; pending: number; approved: number; rejected: number }>("admin_tutors_stats")

  const [tutors, setTutors] = useState<TutorProfile[]>(cachedTutors || [])
  const [stats, setStats] = useState(
    cachedStats || {
      total: 0,
      pending: 0,
      approved: 0,
      rejected: 0,
    }
  )
  const [isLoading, setIsLoading] = useState<boolean>(!cachedTutors)
  const [error, setError] = useState<string | null>(null)
  const [fetchError, setFetchError] = useState<string | null>(null)
  const [fetchTrigger, setFetchTrigger] = useState<number>(0)

  const [selectedTutor, setSelectedTutor] = useState<TutorProfile | null>(null)
  const [drawerOpen, setDrawerOpen] = useState(false)

  // Filters
  const [activeTab, setActiveTab] = useState<string>("all")
  const [searchQuery, setSearchQuery] = useState<string>("")
  const [gradeFilter, setGradeFilter] = useState<string>("all")
  const [subjectFilter, setSubjectFilter] = useState<string>("all")
  const [selectedIds, setSelectedIds] = useState<number[]>([])

  // Dynamic filter options derived from live data
  const [availableGrades, setAvailableGrades] = useState<string[]>([])
  const [availableSubjects, setAvailableSubjects] = useState<string[]>([])

  const fetchTutors = useCallback(async (isSilent = false) => {
    if (!isSilent && !getCached("admin_tutors_list")) {
      setIsLoading(true)
    }
    setError(null)
    setFetchError(null)

    try {
      const params: Record<string, string> = {}
      if (activeTab !== "all") params.status = activeTab
      if (searchQuery.trim()) params.q = searchQuery.trim()

      const res = await apiClient.get("/admin/tutors", { params })
      const liveData: Record<string, unknown>[] = Array.isArray(res.data?.data)
        ? res.data.data
        : Array.isArray(res.data?.tutors)
        ? res.data.tutors
        : Array.isArray(res.data)
        ? res.data
        : []

      const backendCounts = res.data?.counts || res.data?.stats
      let newStats = { total: 0, pending: 0, approved: 0, rejected: 0 }
      if (backendCounts) {
        newStats = {
          total: Number(backendCounts.total ?? backendCounts.all ?? 0),
          pending: Number(backendCounts.pending ?? 0),
          approved: Number(backendCounts.approved ?? 0),
          rejected: Number(backendCounts.rejected ?? 0),
        }
      } else {
        newStats = {
          total: liveData.length,
          pending: liveData.filter((t: any) => String(t.status || "").toLowerCase() === "pending").length,
          approved: liveData.filter((t: any) => String(t.status || "").toLowerCase() === "approved").length,
          rejected: liveData.filter((t: any) => String(t.status || "").toLowerCase() === "rejected").length,
        }
      }
      setStats(newStats)

      const normalized = liveData.map((item: Record<string, unknown>) => normalizeTutorProfile(item))
      setTutors(normalized)

      if (activeTab === "all" && !searchQuery.trim()) {
        setCached("admin_tutors_list", normalized)
        setCached("admin_tutors_stats", newStats)
      }

      // Dynamic filter options
      const allGrades = Array.from(new Set(normalized.flatMap((t) => t.grades))).sort()
      const allSubjects = Array.from(new Set(normalized.flatMap((t) => t.subjects))).sort()
      setAvailableGrades(allGrades)
      setAvailableSubjects(allSubjects)
    } catch (err: any) {
      console.error("Failed to load tutors:", err)
      const errorMsg =
        err.response?.data?.message ||
        (err.response?.status ? `HTTP ${err.response.status}: Failed to load tutor applicants` : err.message) ||
        "Failed to reach backend tutors API"
      setError(
        `Failed to reach backend tutors API: ${
          err.response?.data?.message || (err.response?.status ? `HTTP ${err.response.status}` : err.message)
        }`
      )
      setFetchError(errorMsg)
      if (!getCached("admin_tutors_list")) {
        setTutors([])
      }
    } finally {
      setIsLoading(false)
    }
  }, [activeTab, searchQuery])

  useEffect(() => {
    const hasCache = !!getCached("admin_tutors_list") && activeTab === "all" && !searchQuery.trim()
    const handler = setTimeout(() => {
      fetchTutors(hasCache)
    }, 250)
    return () => clearTimeout(handler)
  }, [fetchTutors, fetchTrigger])

  // Client-side grade and subject refinement
  const filteredTutors = tutors.filter((tutor) => {
    if (gradeFilter !== "all" && !tutor.grades.includes(gradeFilter)) {
      return false
    }
    if (subjectFilter !== "all" && !tutor.subjects.includes(subjectFilter)) {
      return false
    }
    return true
  })

  const handleSelectAll = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.checked) {
      setSelectedIds(filteredTutors.map((t) => t.id))
    } else {
      setSelectedIds([])
    }
  }

  const handleSelectOne = (id: number) => {
    setSelectedIds((prev) => (prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]))
  }

  const handleStatusChange = (tutorId: number, newStatus: TutorStatus, reason?: string) => {
    clearCache("admin_tutors")
    setTutors((prev) =>
      prev.map((t) =>
        t.id === tutorId
          ? {
              ...t,
              status: newStatus,
              rejection_reason: reason || t.rejection_reason,
            }
          : t
      )
    )
    setFetchTrigger((prev) => prev + 1)
  }

  const handleDelete = (tutorId: number) => {
    clearCache("admin_tutors")
    setTutors((prev) => prev.filter((t) => t.id !== tutorId))
    setSelectedIds((prev) => prev.filter((id) => id !== tutorId))
  }

  const handleBulkApprove = async () => {
    const idsToApprove = [...selectedIds]
    if (idsToApprove.length === 0) return

    clearCache("admin_tutors")
    setTutors((prev) =>
      prev.map((t) => (idsToApprove.includes(t.id) ? { ...t, status: "approved" } : t))
    )
    setSelectedIds([])

    try {
      await apiClient.patch("/admin/tutors/bulk-status", {
        ids: idsToApprove,
        status: "approved",
      })
      setFetchTrigger((prev) => prev + 1)
    } catch (e) {
      console.error("Failed to persist bulk approve:", e)
    }
  }

  const handleBulkReject = async () => {
    const idsToReject = [...selectedIds]
    if (idsToReject.length === 0) return

    clearCache("admin_tutors")
    const reason = "Inappropriate picture in tutor profile"
    const feedback =
      "Your application was rejected because your uploaded profile picture does not meet platform guidelines (must be a clear, professional headshot). Please update your photo and re-apply."

    setTutors((prev) =>
      prev.map((t) =>
        idsToReject.includes(t.id) ? { ...t, status: "rejected", rejection_reason: reason } : t
      )
    )
    setSelectedIds([])

    try {
      await apiClient.patch("/admin/tutors/bulk-status", {
        ids: idsToReject,
        status: "rejected",
        reason,
        feedback,
      })
      setFetchTrigger((prev) => prev + 1)
    } catch (e) {
      console.error("Failed to persist bulk reject:", e)
    }
  }

  const getStatusBadge = (status: TutorStatus) => {
    switch (status) {
      case "approved":
        return <Badge variant="success">Approved</Badge>
      case "pending":
        return <Badge variant="warning">Pending Review</Badge>
      case "rejected":
        return <Badge variant="destructive">Rejected</Badge>
      case "suspended":
        return <Badge variant="destructive">Suspended</Badge>
      default:
        return <Badge variant="outline">{status}</Badge>
    }
  }

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100">
            Tutor Verification & Moderation
          </h1>
          <p className="text-xs text-slate-500">
            Validate educator credentials, inspect profile photos, and moderate applications with live database authority.
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2.5">
          <Button
            size="sm"
            variant="outline"
            onClick={() => setFetchTrigger((prev) => prev + 1)}
            disabled={isLoading}
            className="h-8 text-xs gap-1.5 cursor-pointer"
          >
            <RefreshCw className={`h-3.5 w-3.5 ${isLoading ? "animate-spin text-blue-600" : ""}`} />
            Refresh
          </Button>
        </div>
      </div>

      {/* Error Alert Banner */}
      {error && (
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-800 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200 shadow-xs">
          <div className="flex items-center gap-2.5">
            <AlertTriangle className="h-5 w-5 shrink-0 text-red-600" />
            <div>
              <p className="font-bold">Backend Synchronization Warning</p>
              <p className="text-[11px] text-red-700 dark:text-red-300">{error}</p>
            </div>
          </div>
          <Button
            size="sm"
            variant="outline"
            className="h-7 text-xs border-red-300 text-red-800 hover:bg-red-100 dark:border-red-800 dark:text-red-200 cursor-pointer"
            onClick={() => setFetchTrigger((prev) => prev + 1)}
          >
            Retry Connection
          </Button>
        </div>
      )}

      {/* Tab Selectors reflecting live database counts */}
      <div className="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3 dark:border-slate-800">
        <button
          type="button"
          onClick={() => setActiveTab("all")}
          className={`rounded-lg px-3.5 py-1.5 text-xs font-semibold transition cursor-pointer ${
            activeTab === "all"
              ? "bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 shadow-sm"
              : "text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-900"
          }`}
        >
          All Applicants ({stats.total})
        </button>

        <button
          type="button"
          onClick={() => setActiveTab("pending")}
          className={`rounded-lg px-3.5 py-1.5 text-xs font-semibold transition cursor-pointer ${
            activeTab === "pending"
              ? "bg-amber-500 text-white shadow-sm"
              : "text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-900"
          }`}
        >
          Pending Verification ({stats.pending})
        </button>

        <button
          type="button"
          onClick={() => setActiveTab("approved")}
          className={`rounded-lg px-3.5 py-1.5 text-xs font-semibold transition cursor-pointer ${
            activeTab === "approved"
              ? "bg-emerald-600 text-white shadow-sm"
              : "text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-900"
          }`}
        >
          Approved ({stats.approved})
        </button>

        <button
          type="button"
          onClick={() => setActiveTab("rejected")}
          className={`rounded-lg px-3.5 py-1.5 text-xs font-semibold transition cursor-pointer ${
            activeTab === "rejected"
              ? "bg-rose-600 text-white shadow-sm"
              : "text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-900"
          }`}
        >
          Rejected ({stats.rejected})
        </button>
      </div>

      {/* KPI Cards Summary */}
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <Card className="border-slate-200 shadow-sm dark:border-slate-800">
          <CardHeader className="pb-1">
            <CardTitle className="text-xs uppercase text-slate-500 font-semibold">Total Applicants</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.total}</div>
          </CardContent>
        </Card>
        <Card className="border-amber-200 bg-amber-50/20 shadow-sm dark:border-amber-900/50">
          <CardHeader className="pb-1">
            <CardTitle className="text-xs uppercase text-amber-700 font-semibold dark:text-amber-400">Needs Review</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-amber-700 dark:text-amber-400">{stats.pending}</div>
          </CardContent>
        </Card>
        <Card className="border-emerald-200 bg-emerald-50/20 shadow-sm dark:border-emerald-900/50">
          <CardHeader className="pb-1">
            <CardTitle className="text-xs uppercase text-emerald-700 font-semibold dark:text-emerald-400">Approved Tutors</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-emerald-700 dark:text-emerald-400">{stats.approved}</div>
          </CardContent>
        </Card>
        <Card className="border-rose-200 bg-rose-50/20 shadow-sm dark:border-rose-900/50">
          <CardHeader className="pb-1">
            <CardTitle className="text-xs uppercase text-rose-700 font-semibold dark:text-rose-400">Rejected</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-rose-700 dark:text-rose-400">{stats.rejected}</div>
          </CardContent>
        </Card>
      </div>

      {/* Search and Filters Toolbar */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
          <Input
            placeholder="Search name, phone, or city..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="pl-9 text-xs"
          />
        </div>

        <div className="flex flex-wrap items-center gap-2">
          {availableGrades.length > 0 && (
            <select
              value={gradeFilter}
              onChange={(e) => setGradeFilter(e.target.value)}
              className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs dark:border-slate-800 dark:bg-slate-900"
            >
              <option value="all">All Grades</option>
              {availableGrades.map((grade) => (
                <option key={grade} value={grade}>
                  {grade}
                </option>
              ))}
            </select>
          )}

          {availableSubjects.length > 0 && (
            <select
              value={subjectFilter}
              onChange={(e) => setSubjectFilter(e.target.value)}
              className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs dark:border-slate-800 dark:bg-slate-900"
            >
              <option value="all">All Subjects</option>
              {availableSubjects.map((sub) => (
                <option key={sub} value={sub}>
                  {sub}
                </option>
              ))}
            </select>
          )}
        </div>
      </div>

      {/* Bulk Action Bar */}
      {selectedIds.length > 0 && (
        <div className="flex items-center justify-between rounded-xl bg-slate-900 px-4 py-2.5 text-xs text-white dark:bg-slate-100 dark:text-slate-900">
          <span className="font-semibold">{selectedIds.length} candidate(s) selected</span>
          <div className="flex items-center gap-2">
            <Button
              size="sm"
              variant="outline"
              onClick={handleBulkApprove}
              className="h-7 border-emerald-500 bg-emerald-600 text-white hover:bg-emerald-700 text-xs"
            >
              <CheckCircle className="mr-1 h-3.5 w-3.5" /> Approve Selected
            </Button>
            <Button
              size="sm"
              variant="outline"
              onClick={handleBulkReject}
              className="h-7 border-rose-500 bg-rose-600 text-white hover:bg-rose-700 text-xs"
            >
              <XCircle className="mr-1 h-3.5 w-3.5" /> Reject Selected
            </Button>
          </div>
        </div>
      )}

      {/* Network / 403 Error Alert Banner */}
      {fetchError && (
        <div className="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-sm font-medium text-red-400">
          ⚠️ {fetchError}
        </div>
      )}

      {/* Main Table / Skeletons */}
      <div className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/40 text-slate-500">
              <tr>
                <th className="p-3 w-10">
                  <input
                    type="checkbox"
                    onChange={handleSelectAll}
                    checked={selectedIds.length > 0 && selectedIds.length === filteredTutors.length}
                    className="rounded border-slate-300"
                  />
                </th>
                <th className="p-3 font-semibold">Tutor Applicant</th>
                <th className="p-3 font-semibold">Subjects</th>
                <th className="p-3 font-semibold">Grade Coverage</th>
                <th className="p-3 font-semibold">Location</th>
                <th className="p-3 font-semibold">Status</th>
                <th className="p-3 font-semibold text-right">Actions</th>
              </tr>
            </thead>

            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {isLoading ? (
                // Pulsing Skeletons
                Array.from({ length: 4 }).map((_, i) => (
                  <tr key={i} className="animate-pulse">
                    <td className="p-3">
                      <div className="h-4 w-4 bg-slate-200 rounded dark:bg-slate-800" />
                    </td>
                    <td className="p-3">
                      <div className="flex items-center gap-3">
                        <div className="h-9 w-9 rounded-full bg-slate-200 dark:bg-slate-800" />
                        <div className="space-y-1.5">
                          <div className="h-3 w-28 bg-slate-200 rounded dark:bg-slate-800" />
                          <div className="h-2.5 w-36 bg-slate-200 rounded dark:bg-slate-800" />
                        </div>
                      </div>
                    </td>
                    <td className="p-3">
                      <div className="h-3 w-24 bg-slate-200 rounded dark:bg-slate-800" />
                    </td>
                    <td className="p-3">
                      <div className="h-3 w-20 bg-slate-200 rounded dark:bg-slate-800" />
                    </td>
                    <td className="p-3">
                      <div className="h-3 w-16 bg-slate-200 rounded dark:bg-slate-800" />
                    </td>
                    <td className="p-3">
                      <div className="h-5 w-16 bg-slate-200 rounded-full dark:bg-slate-800" />
                    </td>
                    <td className="p-3 text-right">
                      <div className="h-7 w-20 bg-slate-200 rounded ml-auto dark:bg-slate-800" />
                    </td>
                  </tr>
                ))
              ) : filteredTutors.length === 0 ? (
                <tr>
                  <td colSpan={7} className="p-8 text-center text-slate-400">
                    <Users className="mx-auto h-8 w-8 mb-2 opacity-50" />
                    <p className="font-semibold">No tutor applicants match your filters.</p>
                    <p className="text-[11px] mt-0.5">Try modifying your search or switching tabs.</p>
                  </td>
                </tr>
              ) : (
                filteredTutors.map((tutor) => (
                  <tr
                    key={tutor.id}
                    className="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition cursor-pointer"
                    onClick={() => {
                      setSelectedTutor(tutor)
                      setDrawerOpen(true)
                    }}
                  >
                    <td className="p-3" onClick={(e) => e.stopPropagation()}>
                      <input
                        type="checkbox"
                        checked={selectedIds.includes(tutor.id)}
                        onChange={() => handleSelectOne(tutor.id)}
                        className="rounded border-slate-300"
                      />
                    </td>

                    <td className="p-3">
                      <div className="flex items-center gap-3">
                        <Avatar className="h-9 w-9 border border-slate-200 dark:border-slate-700">
                          <AvatarImage src={tutor.avatar_url} />
                          <AvatarFallback className="font-bold text-xs bg-slate-100 dark:bg-slate-800">
                            {tutor.name
                              .split(" ")
                              .map((n) => n[0])
                              .join("")
                              .slice(0, 2)
                              .toUpperCase()}
                          </AvatarFallback>
                        </Avatar>
                        <div>
                          <div className="font-bold text-slate-900 dark:text-slate-100">{tutor.name}</div>
                          <div className="text-[11px] text-slate-500">{tutor.email || tutor.phone}</div>
                        </div>
                      </div>
                    </td>

                    <td className="p-3">
                      <div className="flex flex-wrap gap-1 max-w-[200px]">
                        {tutor.subjects.slice(0, 2).map((s, idx) => (
                          <Badge key={idx} variant="outline" className="text-[10px] font-medium py-0">
                            {s}
                          </Badge>
                        ))}
                        {tutor.subjects.length > 2 && (
                          <span className="text-[10px] text-slate-400 self-center">
                            +{tutor.subjects.length - 2}
                          </span>
                        )}
                      </div>
                    </td>

                    <td className="p-3">
                      <div className="flex flex-wrap gap-1 max-w-[160px]">
                        {tutor.grades.slice(0, 2).map((g, idx) => (
                          <Badge key={idx} variant="secondary" className="text-[10px] font-medium py-0">
                            {g}
                          </Badge>
                        ))}
                        {tutor.grades.length > 2 && (
                          <span className="text-[10px] text-slate-400 self-center">
                            +{tutor.grades.length - 2}
                          </span>
                        )}
                      </div>
                    </td>

                    <td className="p-3 text-slate-600 dark:text-slate-400 font-medium">
                      {tutor.city}
                    </td>

                    <td className="p-3">
                      {getStatusBadge(tutor.status)}
                    </td>

                    <td className="p-3 text-right" onClick={(e) => e.stopPropagation()}>
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => {
                          setSelectedTutor(tutor)
                          setDrawerOpen(true)
                        }}
                        className="h-7 text-xs font-semibold gap-1.5 cursor-pointer"
                      >
                        <Eye className="h-3 w-3" />
                        Inspect
                      </Button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Tutor Dossier Drawer */}
      <TutorDossierDrawer
        tutor={selectedTutor}
        open={drawerOpen}
        onOpenChange={setDrawerOpen}
        onStatusChange={handleStatusChange}
        onDelete={handleDelete}
      />
    </div>
  )
}
