"use client"

import React, { useState, useEffect, useCallback } from "react"
import { apiClient, cachedGet } from "@/lib/api-client"
import { getCached, setCached, clearCache } from "@/lib/cache"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar"
import {
  Star,
  Search,
  AlertTriangle,
  RefreshCw,
  MessageSquareQuote,
  Users,
} from "lucide-react"

export interface TutorReviewRecord {
  id: number
  tutor_id: number
  tutor_name: string
  tutor_image?: string
  subject: string
  reviewer_name: string
  reviewer_email?: string
  rating: number
  review?: string
  status?: string
  created_at: string
}

export default function TutorReviewsPage() {
  const cachedReviews = getCached<TutorReviewRecord[]>("tutors_reviews_list")
  const cachedStats = getCached<{ total: number; average_rating: number; five_star_count: number }>("tutors_reviews_stats")

  const [reviews, setReviews] = useState<TutorReviewRecord[]>(cachedReviews || [])
  const [stats, setStats] = useState<{ total: number; average_rating: number; five_star_count: number }>(
    cachedStats || {
      total: 0,
      average_rating: 0,
      five_star_count: 0,
    }
  )
  const [isLoading, setIsLoading] = useState<boolean>(!cachedReviews)
  const [error, setError] = useState<string | null>(null)
  const [fetchTrigger, setFetchTrigger] = useState(0)

  // Filters
  const [searchQuery, setSearchQuery] = useState("")
  const [ratingFilter, setRatingFilter] = useState("all")

  const fetchReviews = useCallback(async (isSilent = false) => {
    if (!isSilent && !getCached("tutors_reviews_list")) {
      setIsLoading(true)
    }
    setError(null)

    try {
      const params: Record<string, string> = {}
      if (searchQuery.trim()) params.q = searchQuery.trim()
      if (ratingFilter !== "all" && ratingFilter !== "low") {
        params.rating = ratingFilter
      }

      const res = await cachedGet("/admin/tutor-reviews", params)

      if (res && (res.status === "success" || Array.isArray(res.data) || Array.isArray(res))) {
        const liveReviews = Array.isArray(res.data) ? res.data : Array.isArray(res) ? res : []
        setReviews(liveReviews)
        setCached("tutors_reviews_list", liveReviews)

        const backendStats = res.stats
        let resolvedStats: { total: number; average_rating: number; five_star_count: number }
        if (backendStats) {
          resolvedStats = {
            total: Number(backendStats.total ?? liveReviews.length),
            average_rating: Number(backendStats.average_rating ?? 0),
            five_star_count: Number(backendStats.five_star_count ?? 0),
          }
        } else {
          const tot = liveReviews.length
          const avg = tot > 0 ? liveReviews.reduce((sum: number, r: any) => sum + (Number(r.rating) || 0), 0) / tot : 0
          const five = liveReviews.filter((r: any) => Number(r.rating) === 5).length
          resolvedStats = {
            total: tot,
            average_rating: Number(avg.toFixed(1)),
            five_star_count: five,
          }
        }
        setStats(resolvedStats)
        setCached("tutors_reviews_stats", resolvedStats)
      } else {
        throw new Error(res?.message || "Failed to load tutor reviews.")
      }
    } catch (err: any) {
      console.error("Failed to load tutor reviews:", err)
      setError(
        `Failed to reach backend tutor reviews API: ${
          err.response?.data?.message || (err.response?.status ? `HTTP ${err.response.status}` : err.message)
        }`
      )
      if (!getCached("tutors_reviews_list")) {
        setReviews([])
      }
    } finally {
      setIsLoading(false)
    }
  }, [searchQuery, ratingFilter])

  useEffect(() => {
    const hasCache = !!getCached("tutors_reviews_list")
    const handler = setTimeout(() => {
      fetchReviews(hasCache)
    }, 250)
    return () => clearTimeout(handler)
  }, [fetchReviews, fetchTrigger])

  // Filtered list for 'low' (<= 2 stars) on client
  const filteredReviews = reviews.filter((item) => {
    if (ratingFilter === "low") return item.rating <= 2
    return true
  })

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-4 rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center sm:justify-between">
        <div className="space-y-1">
          <h1 className="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">
            Tutor Ratings
          </h1>
          <p className="text-xs text-slate-500 max-w-2xl">
            Audit student ratings, monitor educator evaluations, and oversee platform feedback scores.
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
          Refresh Ratings
        </Button>
      </div>

      {/* Error Alert */}
      {error && (
        <div className="flex items-center justify-between rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-200">
          <div className="flex items-center gap-2.5">
            <AlertTriangle className="h-5 w-5 shrink-0 text-rose-600" />
            <div>
              <p className="font-bold">Backend Synchronization Warning</p>
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

      {/* Summary Metrics */}
      <div className="grid gap-4 sm:grid-cols-2">
        <Card className="border-slate-200 shadow-sm dark:border-slate-800">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-xs uppercase text-slate-500 font-semibold">Total Ratings</CardTitle>
            <Users className="h-4 w-4 text-slate-400" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-black text-slate-900 dark:text-slate-100">
              {isLoading ? (
                <div className="h-7 w-12 animate-pulse rounded bg-slate-200 dark:bg-slate-800" />
              ) : (
                stats.total
              )}
            </div>
            <p className="text-[11px] text-slate-500 mt-0.5">Submitted student ratings</p>
          </CardContent>
        </Card>

        <Card className="border-slate-200 shadow-sm dark:border-slate-800">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-xs uppercase text-slate-500 font-semibold">Average Rating</CardTitle>
            <Star className="h-4 w-4 text-amber-400 fill-amber-400" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-black text-slate-900 dark:text-slate-100">
              {isLoading ? (
                <div className="h-7 w-12 animate-pulse rounded bg-slate-200 dark:bg-slate-800" />
              ) : (
                `${stats.average_rating} / 5.0`
              )}
            </div>
            <p className="text-[11px] text-slate-500 mt-0.5">{stats.five_star_count} five-star ratings</p>
          </CardContent>
        </Card>
      </div>

      {/* Toolbar Search & Star Filter */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
          <Input
            placeholder="Search by tutor, student name, or email..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="pl-9 text-xs"
          />
        </div>

        <div className="flex items-center gap-2">
          <span className="text-xs text-slate-500 font-medium">Rating:</span>
          <select
            value={ratingFilter}
            onChange={(e) => setRatingFilter(e.target.value)}
            className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs dark:border-slate-800 dark:bg-slate-900 cursor-pointer"
          >
            <option value="all">All Ratings</option>
            <option value="5">★★★★★ (5 Stars)</option>
            <option value="4">★★★★☆ (4 Stars)</option>
            <option value="3">★★★☆☆ (3 Stars)</option>
            <option value="low">★★☆☆☆ (≤ 2 Stars)</option>
          </select>
        </div>
      </div>

      {/* Ratings Audit Log Table */}
      <div className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/40 text-slate-500">
              <tr>
                <th className="p-3 font-semibold">Tutor</th>
                <th className="p-3 font-semibold">Student Reviewer</th>
                <th className="p-3 font-semibold">Score</th>
                <th className="p-3 font-semibold">Review</th>
                <th className="p-3 font-semibold">Date</th>
              </tr>
            </thead>

            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {isLoading ? (
                Array.from({ length: 4 }).map((_, i) => (
                  <tr key={i} className="animate-pulse">
                    <td className="p-3">
                      <div className="h-9 w-32 bg-slate-200 rounded dark:bg-slate-800" />
                    </td>
                    <td className="p-3">
                      <div className="h-4 w-28 bg-slate-200 rounded dark:bg-slate-800" />
                    </td>
                    <td className="p-3">
                      <div className="h-4 w-16 bg-slate-200 rounded dark:bg-slate-800" />
                    </td>
                    <td className="p-3">
                      <div className="h-4 w-32 bg-slate-200 rounded dark:bg-slate-800" />
                    </td>
                    <td className="p-3">
                      <div className="h-4 w-20 bg-slate-200 rounded dark:bg-slate-800" />
                    </td>
                  </tr>
                ))
              ) : filteredReviews.length === 0 ? (
                <tr>
                  <td colSpan={5} className="p-12 text-center text-slate-400">
                    <MessageSquareQuote className="mx-auto h-8 w-8 mb-2 opacity-50" />
                    <p className="font-semibold">No tutor ratings found in this view.</p>
                    <p className="text-[11px] mt-0.5">
                      No student ratings match the active filter criteria.
                    </p>
                  </td>
                </tr>
              ) : (
                filteredReviews.map((rev) => (
                  <tr key={rev.id} className="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                    {/* Tutor column */}
                    <td className="p-3">
                      <div className="flex items-center gap-2.5">
                        <Avatar className="h-8 w-8 border border-slate-200 dark:border-slate-700 shrink-0">
                          <AvatarImage src={rev.tutor_image} />
                          <AvatarFallback className="font-bold text-[10px]">
                            {rev.tutor_name
                              .split(" ")
                              .map((n) => n[0])
                              .join("")
                              .slice(0, 2)
                              .toUpperCase()}
                          </AvatarFallback>
                        </Avatar>
                        <div>
                          <div className="font-bold text-slate-900 dark:text-slate-100">{rev.tutor_name}</div>
                          <div className="text-[10px] text-slate-400">{rev.subject}</div>
                        </div>
                      </div>
                    </td>

                    {/* Student column */}
                    <td className="p-3">
                      <div className="font-medium text-slate-900 dark:text-slate-100">{rev.reviewer_name}</div>
                      {rev.reviewer_email && (
                        <div className="text-[10px] text-slate-400">{rev.reviewer_email}</div>
                      )}
                    </td>

                    {/* Score */}
                    <td className="p-3">
                      <div className="flex items-center gap-1">
                        <div className="flex">
                          {Array.from({ length: 5 }).map((_, idx) => (
                            <Star
                              key={idx}
                              className={`h-3 w-3 ${
                                idx < rev.rating ? "fill-amber-400 text-amber-400" : "text-slate-300 dark:text-slate-700"
                              }`}
                            />
                          ))}
                        </div>
                        <span className="font-bold text-[11px] text-slate-700 dark:text-slate-300 ml-1">
                          {rev.rating}.0
                        </span>
                      </div>
                    </td>

                    {/* Review text */}
                    <td className="p-3 max-w-xs text-slate-700 dark:text-slate-300">
                      <p className="line-clamp-2 text-xs italic">
                        {rev.review ? `"${rev.review}"` : <span className="text-slate-400 not-italic">No comment left</span>}
                      </p>
                    </td>

                    {/* Date */}
                    <td className="p-3 text-slate-500 text-[11px] whitespace-nowrap">
                      {new Date(rev.created_at).toLocaleDateString("en-US", {
                        month: "short",
                        day: "numeric",
                        year: "numeric",
                      })}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}
