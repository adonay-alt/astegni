"use client"

import React, { useState, useEffect } from "react"
import Link from "next/link"
import { apiClient } from "@/lib/api-client"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  Layers,
  GraduationCap,
  ArrowRight,
  BookOpen,
  Sparkles,
  AlertTriangle,
  RefreshCw,
  Loader2,
} from "lucide-react"

interface LiveSubjectSummary {
  id: number
  grade_id: number
  stream_id?: number | null
  title: string
  description?: string
}

interface LiveGrade {
  id: number
  name: string
  type: number // 0 = National Exam only, 1 = Learning only, 2 = Both
  subjects?: LiveSubjectSummary[]
}

export default function GradesPage() {
  const [grades, setGrades] = useState<LiveGrade[]>([])
  const [isLoading, setIsLoading] = useState<boolean>(true)
  const [error, setError] = useState<string | null>(null)
  const [dataSource, setDataSource] = useState<"loading" | "live" | "error">("loading")
  const [fetchTrigger, setFetchTrigger] = useState(0)

  useEffect(() => {
    let isMounted = true
    setIsLoading(true)
    setError(null)
    setDataSource("loading")

    apiClient
      .get("/grades")
      .then((res) => {
        if (!isMounted) return
        const rawData = Array.isArray(res.data)
          ? res.data
          : res.data?.data || []

        // Sort by natural grade number: Grade 9, 10, 11, 12, 8
        const sorted = [...rawData].sort((a: LiveGrade, b: LiveGrade) => {
          const numA = parseInt(a.name.replace(/\D/g, "") || "0", 10)
          const numB = parseInt(b.name.replace(/\D/g, "") || "0", 10)
          return numA - numB
        })

        setGrades(sorted)
        setDataSource("live")
      })
      .catch((err) => {
        if (!isMounted) return
        console.error("Failed to load live grades:", err)
        setError(
          `Failed to reach https://apiv2.asteghi.com/api/grades: ${
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

  const getGradeTypeBadge = (type: number) => {
    switch (type) {
      case 1:
        return (
          <Badge
            variant="outline"
            className="text-[10px] font-medium bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700"
          >
            Curriculum LMS
          </Badge>
        )
      case 2:
        return (
          <Badge
            variant="outline"
            className="text-[10px] font-medium bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700"
          >
            LMS + National Exam
          </Badge>
        )
      case 0:
        return (
          <Badge
            variant="outline"
            className="text-[10px] font-medium bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700"
          >
            National Exam Archive
          </Badge>
        )
      default:
        return null
    }
  }

  const hasStreams = (grade: LiveGrade) => {
    const nameLower = grade.name.toLowerCase()
    return nameLower.includes("11") || nameLower.includes("12")
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100">
            Grades & Academic Streams
          </h1>
          <p className="text-xs text-slate-500">
            Ethiopian national secondary cohorts and academic streams.
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

      {/* Loading Skeleton */}
      {isLoading && grades.length === 0 && (
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {[1, 2, 3, 4].map((i) => (
            <Card key={i} className="animate-pulse border-slate-200 dark:border-slate-800 p-6 space-y-4">
              <div className="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-800" />
              <div className="h-4 w-24 bg-slate-100 dark:bg-slate-800 rounded" />
              <div className="h-8 w-full bg-slate-50 dark:bg-slate-900 rounded" />
            </Card>
          ))}
        </div>
      )}

      {/* Grade Cards Grid */}
      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        {grades.map((grade) => {
          const subjectCount = grade.subjects?.length || 0
          const gradeNumber = grade.name.replace(/\D/g, "")
          const isStreamingGrade = hasStreams(grade)

          return (
            <Card
              key={grade.id}
              className="flex flex-col justify-between border-slate-200 bg-white shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-950"
            >
              <CardHeader className="pb-3">
                <div className="flex items-center justify-between">
                  <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 font-bold text-base shadow-xs">
                    G{gradeNumber || grade.id}
                  </div>
                  {getGradeTypeBadge(grade.type)}
                </div>
                <CardTitle className="text-lg font-bold text-slate-900 dark:text-slate-100 pt-2">
                  {grade.name}
                </CardTitle>
                <CardDescription className="text-xs text-slate-500">
                  {subjectCount} secondary subjects enrolled in active curriculum syllabus
                </CardDescription>
              </CardHeader>

              <CardContent className="space-y-4 pt-0">
                {/* Streams breakdown */}
                <div className="space-y-1.5 rounded-lg bg-slate-50 p-3 text-xs dark:bg-slate-900/40">
                  <span className="font-semibold text-slate-700 dark:text-slate-300">
                    {isStreamingGrade ? "Academic Preparatory Streams:" : "Cohort Track:"}
                  </span>
                  {isStreamingGrade ? (
                    <div className="flex flex-wrap gap-1 pt-1">
                      <Badge
                        variant="outline"
                        className="text-[10px] bg-white text-blue-700 border-blue-200 dark:bg-slate-900 dark:border-blue-800 dark:text-blue-300"
                      >
                        Natural Science
                      </Badge>
                      <Badge
                        variant="outline"
                        className="text-[10px] bg-white text-purple-700 border-purple-200 dark:bg-slate-900 dark:border-purple-800 dark:text-purple-300"
                      >
                        Social Science
                      </Badge>
                    </div>
                  ) : (
                    <p className="text-[11px] text-slate-500 pt-0.5">
                      Unified general secondary curriculum (all subjects common to all students).
                    </p>
                  )}
                </div>

                <Link href={`/curriculum/subjects?grade_id=${grade.id}`}>
                  <Button
                    size="sm"
                    variant="outline"
                    className="w-full text-xs border-slate-200 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900"
                  >
                    View {subjectCount} Subjects <ArrowRight className="ml-1 h-3.5 w-3.5" />
                  </Button>
                </Link>
              </CardContent>
            </Card>
          )
        })}
      </div>

      {/* Preparatory Stream Deep Dive */}
      <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <CardHeader>
          <CardTitle className="text-base font-bold text-slate-900 dark:text-slate-100">
            Secondary Stream Architecture Details
          </CardTitle>
          <CardDescription className="text-xs text-slate-500">
            Stream allocation logic governing Ethiopian university entrance prerequisites for Grade 11 & Grade 12
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="rounded-xl border border-blue-100 bg-blue-50/40 p-4 dark:border-blue-950 dark:bg-blue-950/20 space-y-2">
              <div className="flex items-center justify-between">
                <h3 className="font-bold text-sm text-blue-900 dark:text-blue-200">
                  Natural Science Stream
                </h3>
                <Badge variant="outline" className="bg-white text-blue-700 border-blue-200 dark:bg-slate-900">
                  Engineering / Health Sciences
                </Badge>
              </div>
              <p className="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Core disciplines include Advanced Calculus, Mechanics, Electromagnetism, Organic Chemistry, Cellular Biology, Agriculture, and Technical Drawing.
              </p>
            </div>

            <div className="rounded-xl border border-purple-100 bg-purple-50/40 p-4 dark:border-purple-950 dark:bg-purple-950/20 space-y-2">
              <div className="flex items-center justify-between">
                <h3 className="font-bold text-sm text-purple-900 dark:text-purple-200">
                  Social Science Stream
                </h3>
                <Badge variant="outline" className="bg-white text-purple-700 border-purple-200 dark:bg-slate-900">
                  Business / Humanities / Law
                </Badge>
              </div>
              <p className="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Core disciplines include Micro/Macro Economics, Physical & Human Geography, World & Ethiopian History, General Business, and Basic Mathematics.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
