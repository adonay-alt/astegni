"use client"

import React, { useEffect, useState, useCallback, useRef } from "react"
import Link from "next/link"
import { useRouter } from "next/navigation"
import { useAuth } from "@/context/auth-context"
import { apiClient } from "@/lib/api-client"
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
} from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  StudentDistributionChart,
  GradeDistributionItem,
} from "@/components/charts/student-distribution-chart"
import {
  Users,
  MessageSquareQuote,
  UserCheck,
  BookOpen,
  Clock,
  RefreshCw,
  AlertTriangle,
  GraduationCap,
} from "lucide-react"

// ─── Types ──────────────────────────────────────────────────────────────────

interface DashboardSummaryData {
  active_learners: number
  pending_reviews_count: number
  pending_tutors_count: number
  total_subjects: number
  total_chapters: number
}

// ─── Component ───────────────────────────────────────────────────────────────

export default function DashboardPage() {
  const { user, isLoading: isAuthLoading } = useAuth()
  const router = useRouter()

  const [currentDateTime, setCurrentDateTime] = useState<string>("")
  const [summary, setSummary] = useState<DashboardSummaryData | null>(null)
  const [distribution, setDistribution] = useState<GradeDistributionItem[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [refreshKey, setRefreshKey] = useState(0)

  // 1. AUTO-REDIRECT GUARD FOR SUB-ROLES
  useEffect(() => {
    if (isAuthLoading || !user) return

    const userRole = (user.role || "").toLowerCase()

    if (userRole.includes("tutor")) {
      router.replace("/tutors")
    } else if (userRole.includes("academic") || userRole.includes("editor")) {
      router.replace("/curriculum/grades")
    } else if (userRole.includes("support")) {
      router.replace("/support/contacts")
    } else if (userRole.includes("finance") || userRole.includes("auditor")) {
      router.replace("/settings/audit-logs")
    }
  }, [user, isAuthLoading, router])

  // Live date-time ticker
  useEffect(() => {
    const update = () => {
      const now = new Date()
      setCurrentDateTime(
        now.toLocaleDateString("en-US", {
          weekday: "long",
          year: "numeric",
          month: "long",
          day: "numeric",
        }) +
          " • " +
          now.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit" })
      )
    }
    update()
    const t = setInterval(update, 60000)
    return () => clearInterval(t)
  }, [])

  // Safe fetch for both endpoints in parallel
  const fetchData = useCallback(async () => {
    setIsLoading(true)
    setError(null)

    try {
      const [summaryRes, distRes] = await Promise.all([
        apiClient.get("/admin/dashboard/summary"),
        apiClient.get("/admin/dashboard/student-distribution"),
      ])

      // Set state
      setSummary(summaryRes.data?.data || null)
      setDistribution(distRes.data?.data || [])
    } catch (err: any) {
      console.error("Dashboard data fetch error:", err)
      if (err.response?.status === 403) {
        setError("Access restricted to Super Administrators.")
      } else {
        setError(
          err.response?.data?.message ||
            err.message ||
            "Failed to load dashboard data."
        )
      }
    } finally {
      setIsLoading(false)
    }
  }, [])

  // Only invoke fetchData() if the user is authorized (user.role === 'super_admin' or user.is_admin)
  useEffect(() => {
    if (!isAuthLoading && user) {
      const role = (user.role || "").toLowerCase()
      if (role === "super_admin" || user.is_admin) {
        fetchData()
      }
    }
  }, [user, isAuthLoading, refreshKey, fetchData])

  // Derived data
  const adminFirstName = user?.name?.split(" ")[0] || "Administrator"
  const totalEnrolled  = distribution.reduce((s, d) => s + (d.total ?? 0), 0)

  return (
    <div className="space-y-6">

      {/* ── Welcome Header ─────────────────────────────────────────────────── */}
      <div className="flex flex-col justify-between gap-4 rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center">
        <div className="space-y-1.5">
          <div className="flex items-center gap-2">
            <span className="flex items-center gap-1.5 text-xs font-medium text-slate-500">
              <Clock className="h-3.5 w-3.5 text-slate-400" />
              {currentDateTime || "Loading..."}
            </span>
          </div>
          <h1 className="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">
            Welcome back, {adminFirstName}
          </h1>
          <p className="max-w-2xl text-xs font-medium leading-relaxed text-slate-600 dark:text-slate-400 sm:text-sm">
            Real-time student enrollment, pending review moderation, tutor verification, and curriculum coverage.
          </p>
        </div>
        <Button
          variant="outline"
          size="sm"
          onClick={() => setRefreshKey(k => k + 1)}
          disabled={isLoading}
          className="gap-2 border-slate-200 text-xs font-semibold hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800"
        >
          <RefreshCw className={`h-3.5 w-3.5 ${isLoading ? "animate-spin text-blue-600" : ""}`} />
          Refresh
        </Button>
      </div>

      {/* ── Error Banner ───────────────────────────────────────────────────── */}
      {error && (
        <div className="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 shadow-xs dark:border-rose-900/50 dark:bg-rose-950/20">
          <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
          <div className="flex-1 space-y-0.5">
            <h4 className="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">
              Backend Synchronization Error
            </h4>
            <p className="text-xs font-medium leading-relaxed text-rose-900 dark:text-rose-300">{error}</p>
          </div>
          <Button
            size="sm"
            variant="outline"
            onClick={() => setRefreshKey(k => k + 1)}
            className="h-8 shrink-0 border-rose-300 text-xs text-rose-700 hover:bg-rose-100 dark:border-rose-800 dark:text-rose-300"
          >
            Retry
          </Button>
        </div>
      )}

      {/* ── 4 KPI Metric Cards ─────────────────────────────────────────────── */}
      <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">

        {/* 1. Active Learners */}
        <Card className="border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              Active Learners
            </CardTitle>
            <div className="flex h-8 w-8 items-center justify-center rounded-xl border border-blue-100 bg-blue-50 text-blue-600 dark:border-blue-900 dark:bg-blue-950/50 dark:text-blue-400">
              <Users className="h-4 w-4" />
            </div>
          </CardHeader>
          <CardContent className="space-y-1">
            {isLoading ? (
              <div className="h-8 w-24 animate-pulse rounded-md bg-slate-100 dark:bg-slate-800" />
            ) : (
              <div className="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                {(summary?.active_learners ?? 0).toLocaleString()}
              </div>
            )}
            <p className="text-[11px] font-medium text-slate-500 dark:text-slate-400">
              Registered student accounts
            </p>
          </CardContent>
        </Card>

        {/* 2. Pending Platform Reviews (NEW — replaces quiz card) */}
        <Link href="/marketing/reviews" className="block">
          <Card className="border-slate-200/90 bg-white shadow-sm transition-all hover:shadow-md hover:border-indigo-200 dark:border-slate-800 dark:bg-slate-900 h-full cursor-pointer">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                Pending Reviews
              </CardTitle>
              <div className="flex h-8 w-8 items-center justify-center rounded-xl border border-indigo-100 bg-indigo-50 text-indigo-600 dark:border-indigo-900 dark:bg-indigo-950/50 dark:text-indigo-400">
                <MessageSquareQuote className="h-4 w-4" />
              </div>
            </CardHeader>
            <CardContent className="space-y-1">
              {isLoading ? (
                <div className="h-8 w-24 animate-pulse rounded-md bg-slate-100 dark:bg-slate-800" />
              ) : (
                <div className="flex items-center gap-2">
                  <span className="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    {(summary?.pending_reviews_count ?? 0).toLocaleString()}
                  </span>
                  {(summary?.pending_reviews_count ?? 0) > 0 && (
                    <Badge variant="warning" className="text-[10px] font-bold">
                      Pending Moderation
                    </Badge>
                  )}
                </div>
              )}
              <p className="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                Platform testimonials awaiting approval
              </p>
            </CardContent>
          </Card>
        </Link>

        {/* 3. Tutor Verification Queue */}
        <Link href="/tutors" className="block">
          <Card className="border-slate-200/90 bg-white shadow-sm transition-all hover:shadow-md hover:border-amber-200 dark:border-slate-800 dark:bg-slate-900 h-full cursor-pointer">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                Tutor Queue
              </CardTitle>
              <div className="flex h-8 w-8 items-center justify-center rounded-xl border border-amber-100 bg-amber-50 text-amber-600 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-400">
                <UserCheck className="h-4 w-4" />
              </div>
            </CardHeader>
            <CardContent className="space-y-1">
              {isLoading ? (
                <div className="h-8 w-24 animate-pulse rounded-md bg-slate-100 dark:bg-slate-800" />
              ) : (
                <div className="flex items-center gap-2">
                  <span className="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    {(summary?.pending_tutors_count ?? 0).toLocaleString()}
                  </span>
                  {(summary?.pending_tutors_count ?? 0) > 0 && (
                    <Badge variant="warning" className="text-[10px] font-bold">
                      Awaiting Review
                    </Badge>
                  )}
                </div>
              )}
              <p className="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                Educator credentials to verify
              </p>
            </CardContent>
          </Card>
        </Link>

        {/* 4. Curriculum Coverage */}
        <Card className="border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              Curriculum
            </CardTitle>
            <div className="flex h-8 w-8 items-center justify-center rounded-xl border border-emerald-100 bg-emerald-50 text-emerald-600 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-400">
              <BookOpen className="h-4 w-4" />
            </div>
          </CardHeader>
          <CardContent className="space-y-1">
            {isLoading ? (
              <div className="h-8 w-28 animate-pulse rounded-md bg-slate-100 dark:bg-slate-800" />
            ) : (
              <div className="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                {(summary?.total_chapters ?? 0).toLocaleString()}{" "}
                <span className="text-xs font-semibold text-slate-500">Chapters</span>
              </div>
            )}
            <p className="text-[11px] font-medium text-slate-500 dark:text-slate-400">
              {(summary?.total_subjects ?? 0).toLocaleString()} Subjects · Grades 9–12 indexed
            </p>
          </CardContent>
        </Card>
      </div>

      {/* ── Student Enrollment & Stream Distribution Chart ─────────────────── */}
      <Card className="overflow-hidden border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <CardHeader className="border-b border-slate-100 bg-slate-50/50 pb-4 dark:border-slate-800 dark:bg-slate-800/20">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-start gap-3">
              <div className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-emerald-100 bg-emerald-50 text-emerald-600 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-400">
                <GraduationCap className="h-5 w-5" />
              </div>
              <div>
                <CardTitle className="text-base font-bold tracking-tight text-slate-900 dark:text-slate-100 sm:text-lg">
                  Student Enrollment & Stream Distribution
                  <span className="ml-2 text-sm font-semibold text-slate-400">(Grades 9–12)</span>
                </CardTitle>
                <CardDescription className="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                  Live from MySQL database · 🟢 Common Core (Grades 9–10) · 🔵 Natural Science · 🟣 Social Science (Grades 11–12)
                </CardDescription>
              </div>
            </div>

            {/* Total enrolled pill */}
            <div className="shrink-0 rounded-xl border border-slate-200 bg-white px-4 py-1.5 text-center shadow-sm dark:border-slate-700 dark:bg-slate-800">
              <div className="text-base font-black leading-none text-slate-900 dark:text-slate-100">
                {isLoading ? "…" : totalEnrolled.toLocaleString()}
              </div>
              <div className="mt-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                Enrolled
              </div>
            </div>
          </div>
        </CardHeader>

        <CardContent className="p-6">
          <StudentDistributionChart data={distribution} isLoading={isLoading} />
        </CardContent>
      </Card>

    </div>
  )
}
