"use client"

import React, { useState, useEffect, useCallback } from "react"
import { apiClient } from "@/lib/api-client"
import { getCached, setCached } from "@/lib/cache"
import { TutorProfile, TutorStatus } from "@/types/tutor"
import { TutorDossierDrawer } from "@/components/drawers/tutor-dossier-drawer"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar"
import {
  Search,
  Star,
  Phone,
  Mail,
  MapPin,
  GraduationCap,
  BookOpen,
  CheckCircle2,
  AlertTriangle,
  RefreshCw,
  Eye,
  Award,
  Users,
  ShieldCheck,
} from "lucide-react"

function normalizeDirectoryTutor(raw: Record<string, unknown>): TutorProfile {
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

  return {
    id: Number(raw.id) || 0,
    user_id: Number(raw.user_id) || Number(raw.id) || 0,
    name: String(raw.name || "Verified Educator"),
    email: String(raw.email || ""),
    phone: String(raw.phone || "—"),
    city: String(raw.city || "Addis Ababa"),
    gender: (raw.gender === "female" || raw.gender === "male" ? raw.gender : "other") as "male" | "female" | "other",
    status: "approved",
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
        : undefined,
    documents: Array.isArray(raw.documents) ? (raw.documents as any) : [],
    created_at: String(raw.created_at || new Date().toISOString()),
  }
}

export default function TutorsDirectoryPage() {
  const cachedTutors = getCached<TutorProfile[]>("tutors_directory_list")

  const [tutors, setTutors] = useState<TutorProfile[]>(cachedTutors || [])
  const [isLoading, setIsLoading] = useState<boolean>(!cachedTutors)
  const [error, setError] = useState<string | null>(null)
  const [fetchTrigger, setFetchTrigger] = useState(0)

  // Filters
  const [searchQuery, setSearchQuery] = useState("")
  const [subjectFilter, setSubjectFilter] = useState("all")

  // Selected tutor for drawer inspection
  const [selectedTutor, setSelectedTutor] = useState<TutorProfile | null>(null)
  const [drawerOpen, setDrawerOpen] = useState(false)

  const fetchDirectory = useCallback(async (isSilent = false) => {
    if (!isSilent && !getCached("tutors_directory_list")) {
      setIsLoading(true)
    }
    setError(null)

    try {
      // Fetch only approved verified tutors
      const res = await apiClient.get("/admin/tutors", {
        params: { status: "approved" },
      })

      const liveData: Record<string, unknown>[] = Array.isArray(res.data?.data)
        ? res.data.data
        : Array.isArray(res.data)
        ? res.data
        : []

      const normalized = liveData.map((item) => normalizeDirectoryTutor(item))
      setTutors(normalized)
      setCached("tutors_directory_list", normalized)
    } catch (err: any) {
      console.error("Failed to load tutors directory:", err)
      setError(
        `Failed to reach backend tutors directory: ${
          err.response?.data?.message || (err.response?.status ? `HTTP ${err.response.status}` : err.message)
        }`
      )
      if (!getCached("tutors_directory_list")) {
        setTutors([])
      }
    } finally {
      setIsLoading(false)
    }
  }, [])

  useEffect(() => {
    const hasCache = !!getCached("tutors_directory_list")
    fetchDirectory(hasCache)
  }, [fetchDirectory, fetchTrigger])

  // Derive unique subject filter options
  const allSubjects = Array.from(new Set(tutors.flatMap((t) => t.subjects))).sort()

  // Filtered tutors
  const filteredTutors = tutors.filter((tutor) => {
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase()
      const matches =
        tutor.name.toLowerCase().includes(q) ||
        tutor.city.toLowerCase().includes(q) ||
        tutor.phone.includes(q) ||
        tutor.subjects.some((s) => s.toLowerCase().includes(q))
      if (!matches) return false
    }

    if (subjectFilter !== "all") {
      if (!tutor.subjects.includes(subjectFilter)) return false
    }

    return true
  })

  // Directory Stats
  const totalVerified = tutors.length
  const highlyRated = tutors.filter((t) => t.rating >= 4.5).length

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-4 rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center sm:justify-between">
        <div className="space-y-1">
          <h1 className="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">
            Tutors Directory
          </h1>
          <p className="text-xs text-slate-500 max-w-2xl">
            Browse verified educators active across Grade 9–12 subjects and academic streams.
          </p>
        </div>

        <Button
          size="sm"
          variant="outline"
          onClick={() => setFetchTrigger((prev) => prev + 1)}
          disabled={isLoading}
          className="gap-1.5 border-slate-200 text-xs font-semibold hover:bg-slate-50 dark:border-slate-800 cursor-pointer"
        >
          <RefreshCw className={`h-3.5 w-3.5 ${isLoading ? "animate-spin text-blue-600" : ""}`} />
          Refresh Directory
        </Button>
      </div>

      {/* Error Alert */}
      {error && (
        <div className="flex items-center justify-between rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-200">
          <div className="flex items-center gap-2.5">
            <AlertTriangle className="h-5 w-5 shrink-0 text-rose-600" />
            <div>
              <p className="font-bold">Connection Warning</p>
              <p className="text-[11px] text-rose-700 dark:text-rose-300">{error}</p>
            </div>
          </div>
          <Button
            size="sm"
            variant="outline"
            onClick={() => setFetchTrigger((prev) => prev + 1)}
            className="h-7 text-xs border-rose-300 text-rose-800 hover:bg-rose-100 dark:border-rose-800 dark:text-rose-200 cursor-pointer"
          >
            Retry Connection
          </Button>
        </div>
      )}

      {/* Summary KPI Cards */}
      <div className="grid gap-4 sm:grid-cols-3">
        <Card className="border-slate-200 shadow-sm dark:border-slate-800">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-xs uppercase text-slate-500 font-semibold">Verified Tutors</CardTitle>
            <CheckCircle2 className="h-4 w-4 text-emerald-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-black text-slate-900 dark:text-slate-100">
              {isLoading ? (
                <div className="h-7 w-12 animate-pulse rounded bg-slate-200 dark:bg-slate-800" />
              ) : (
                totalVerified
              )}
            </div>
            <p className="text-[11px] text-slate-500 mt-0.5">Approved & active educators</p>
          </CardContent>
        </Card>

        <Card className="border-slate-200 shadow-sm dark:border-slate-800">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-xs uppercase text-slate-500 font-semibold">High-Rated Educators</CardTitle>
            <Star className="h-4 w-4 text-amber-500 fill-amber-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-black text-slate-900 dark:text-slate-100">
              {isLoading ? (
                <div className="h-7 w-12 animate-pulse rounded bg-slate-200 dark:bg-slate-800" />
              ) : (
                highlyRated
              )}
            </div>
            <p className="text-[11px] text-slate-500 mt-0.5">Rating 4.5+ stars</p>
          </CardContent>
        </Card>

        <Card className="border-slate-200 shadow-sm dark:border-slate-800">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-xs uppercase text-slate-500 font-semibold">Subject Specializations</CardTitle>
            <BookOpen className="h-4 w-4 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-black text-slate-900 dark:text-slate-100">
              {isLoading ? (
                <div className="h-7 w-12 animate-pulse rounded bg-slate-200 dark:bg-slate-800" />
              ) : (
                allSubjects.length
              )}
            </div>
            <p className="text-[11px] text-slate-500 mt-0.5">Distinct disciplines covered</p>
          </CardContent>
        </Card>
      </div>

      {/* Search and Filters */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
          <Input
            placeholder="Search educator name, subject, or city..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="pl-9 text-xs"
          />
        </div>

        {allSubjects.length > 0 && (
          <div className="flex items-center gap-2">
            <span className="text-xs text-slate-500 font-medium">Filter Subject:</span>
            <select
              value={subjectFilter}
              onChange={(e) => setSubjectFilter(e.target.value)}
              className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs dark:border-slate-800 dark:bg-slate-900"
            >
              <option value="all">All Disciplines</option>
              {allSubjects.map((sub) => (
                <option key={sub} value={sub}>
                  {sub}
                </option>
              ))}
            </select>
          </div>
        )}
      </div>

      {/* Educator Cards Grid */}
      {isLoading ? (
        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: 6 }).map((_, i) => (
            <div
              key={i}
              className="h-56 animate-pulse rounded-2xl border border-slate-200 bg-slate-100 p-5 dark:border-slate-800 dark:bg-slate-900"
            />
          ))}
        </div>
      ) : filteredTutors.length === 0 ? (
        <div className="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 py-12 text-center dark:border-slate-800">
          <Users className="h-10 w-10 text-slate-400 mb-2" />
          <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">
            No Verified Tutors Found
          </h3>
          <p className="text-xs text-slate-500 mt-1 max-w-sm">
            {searchQuery
              ? "No educators matched your search terms. Try modifying your query."
              : "There are currently no approved tutor profiles in the database."}
          </p>
        </div>
      ) : (
        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {filteredTutors.map((tutor) => (
            <Card
              key={tutor.id}
              className="border-slate-200/90 bg-white shadow-sm hover:shadow-md transition dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between"
            >
              <CardHeader className="p-5 pb-3">
                <div className="flex items-start gap-4">
                  <Avatar className="h-14 w-14 border-2 border-slate-100 dark:border-slate-700 shadow-sm shrink-0">
                    <AvatarImage src={tutor.avatar_url} />
                    <AvatarFallback className="font-bold text-sm bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                      {tutor.name
                        .split(" ")
                        .map((n) => n[0])
                        .join("")
                        .slice(0, 2)
                        .toUpperCase()}
                    </AvatarFallback>
                  </Avatar>

                  <div className="space-y-1 flex-1 min-w-0">
                    <div className="flex items-center justify-between gap-1">
                      <h3 className="font-bold text-sm text-slate-900 dark:text-slate-100 truncate">
                        {tutor.name}
                      </h3>
                      <Badge variant="success" className="text-[10px] shrink-0 font-bold">
                        Verified
                      </Badge>
                    </div>

                    <div className="flex items-center gap-1.5 text-xs text-slate-500">
                      <MapPin className="h-3 w-3 shrink-0 text-slate-400" />
                      <span className="truncate">{tutor.city}</span>
                    </div>

                    <div className="flex items-center gap-1 text-xs">
                      <Star className="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                      <span className="font-bold text-slate-900 dark:text-slate-100">
                        {tutor.rating > 0 ? tutor.rating.toFixed(1) : "Unrated"}
                      </span>
                      {tutor.total_reviews > 0 && (
                        <span className="text-[11px] text-slate-400">({tutor.total_reviews})</span>
                      )}
                    </div>
                  </div>
                </div>
              </CardHeader>

              <CardContent className="p-5 pt-0 space-y-3.5">
                {/* Subject Badges */}
                <div>
                  <div className="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                    Subjects Taught
                  </div>
                  <div className="flex flex-wrap gap-1">
                    {tutor.subjects.map((sub, idx) => (
                      <Badge
                        key={idx}
                        variant="outline"
                        className="text-[10px] font-medium border-slate-200 dark:border-slate-700"
                      >
                        {sub}
                      </Badge>
                    ))}
                  </div>
                </div>

                {/* Grade Levels */}
                <div>
                  <div className="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                    Grade Coverage
                  </div>
                  <div className="flex flex-wrap gap-1">
                    {tutor.grades.map((grade, idx) => (
                      <Badge
                        key={idx}
                        variant="secondary"
                        className="text-[10px] font-medium"
                      >
                        {grade}
                      </Badge>
                    ))}
                  </div>
                </div>

                {/* Contact Information */}
                <div className="rounded-xl border border-slate-100 bg-slate-50 p-2.5 text-xs space-y-1 dark:border-slate-800 dark:bg-slate-800/50">
                  {tutor.phone && tutor.phone !== "—" && (
                    <div className="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                      <Phone className="h-3 w-3 text-slate-400 shrink-0" />
                      <span className="font-mono text-[11px] truncate">{tutor.phone}</span>
                    </div>
                  )}
                  {tutor.email && (
                    <div className="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                      <Mail className="h-3 w-3 text-slate-400 shrink-0" />
                      <span className="truncate text-[11px]">{tutor.email}</span>
                    </div>
                  )}
                </div>

                {/* Action button */}
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => {
                    setSelectedTutor(tutor)
                    setDrawerOpen(true)
                  }}
                  className="w-full text-xs font-semibold gap-1.5 hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer"
                >
                  <Eye className="h-3.5 w-3.5" />
                  Inspect Dossier & Photo
                </Button>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {/* Dossier Drawer */}
      <TutorDossierDrawer
        tutor={selectedTutor}
        open={drawerOpen}
        onOpenChange={setDrawerOpen}
        onStatusChange={() => setFetchTrigger((prev) => prev + 1)}
        onDelete={() => setFetchTrigger((prev) => prev + 1)}
      />
    </div>
  )
}
