"use client"

import React, { useState, useEffect, useCallback } from "react"
import { PlatformReview, ReviewStatus } from "@/types/moderation"
import { apiClient } from "@/lib/api-client"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import {
  CheckCircle,
  XCircle,
  Star,
  MessageSquareQuote,
  Clock,
  ThumbsUp,
  RotateCcw,
  Trash2,
  RefreshCw,
  AlertCircle,
  Loader2,
} from "lucide-react"

export default function PlatformReviewsPage() {
  const [reviews, setReviews] = useState<PlatformReview[]>([])
  const [loading, setLoading] = useState<boolean>(true)
  const [error, setError] = useState<string | null>(null)
  const [activeTab, setActiveTab] = useState<string>("pending")
  const [actionLoadingId, setActionLoadingId] = useState<number | null>(null)
  const [actionType, setActionType] = useState<string | null>(null)
  const [notification, setNotification] = useState<{
    type: "success" | "error"
    message: string
  } | null>(null)

  const fetchReviews = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const response = await apiClient.get("/admin/platform-reviews")
      if (response.data && response.data.status === "success") {
        setReviews(response.data.data || [])
      } else if (Array.isArray(response.data)) {
        setReviews(response.data)
      } else {
        setReviews([])
      }
    } catch (err: any) {
      console.error("Failed to load platform reviews:", err)
      setError(
        err.response?.data?.message ||
          "Failed to load platform reviews from backend. Please verify your connection."
      )
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    fetchReviews()
  }, [fetchReviews])

  const showNotification = (type: "success" | "error", message: string) => {
    setNotification({ type, message })
    setTimeout(() => {
      setNotification(null)
    }, 4000)
  }

  const updateStatus = async (reviewId: number, nextStatus: ReviewStatus) => {
    setActionLoadingId(reviewId)
    setActionType(`status-${nextStatus}`)
    try {
      const response = await apiClient.patch(
        `/admin/platform-reviews/${reviewId}/status`,
        { status: nextStatus }
      )
      if (response.data && response.data.status === "success") {
        setReviews((prev) =>
          prev.map((r) =>
            r.id === reviewId ? { ...r, status: nextStatus } : r
          )
        )
        showNotification(
          "success",
          `Testimonial successfully marked as ${nextStatus}.`
        )
      }
    } catch (err: any) {
      console.error("Failed to update status:", err)
      showNotification(
        "error",
        err.response?.data?.message || "Failed to update testimonial status."
      )
    } finally {
      setActionLoadingId(null)
      setActionType(null)
    }
  }

  const deleteReview = async (reviewId: number) => {
    if (
      !window.confirm(
        "Are you sure you want to permanently delete this testimonial? This action cannot be undone."
      )
    ) {
      return
    }

    setActionLoadingId(reviewId)
    setActionType("delete")
    try {
      const response = await apiClient.delete(
        `/admin/platform-reviews/${reviewId}`
      )
      if (response.data && response.data.status === "success") {
        setReviews((prev) => prev.filter((r) => r.id !== reviewId))
        showNotification("success", "Testimonial permanently deleted.")
      }
    } catch (err: any) {
      console.error("Failed to delete review:", err)
      showNotification(
        "error",
        err.response?.data?.message || "Failed to delete testimonial."
      )
    } finally {
      setActionLoadingId(null)
      setActionType(null)
    }
  }

  const countPending = reviews.filter((r) => r.status === "pending").length
  const countApproved = reviews.filter((r) => r.status === "approved").length
  const countRejected = reviews.filter((r) => r.status === "rejected").length

  const filteredReviews = reviews.filter((r) => {
    if (activeTab === "all") return true
    return r.status === activeTab
  })

  const getReviewerName = (review: PlatformReview): string => {
    if (review.user?.first_name || review.user?.last_name) {
      return `${review.user.first_name || ""} ${review.user.last_name || ""}`.trim()
    }
    return review.student_name || `User #${review.user_id}`
  }

  const getReviewerSubtitle = (review: PlatformReview): string => {
    if (review.user?.student_profile?.grade?.name) {
      return review.user.student_profile.grade.name
    }
    if (review.grade_name) {
      return review.grade_name
    }
    if (review.user?.email) {
      return review.user.email
    }
    return "Student"
  }

  const getReviewerAvatar = (review: PlatformReview): string => {
    return (
      review.user?.photo_url ||
      review.user?.profile_photo_url ||
      review.student_avatar ||
      ""
    )
  }

  const getInitials = (name: string): string => {
    return (
      name
        .split(" ")
        .filter(Boolean)
        .map((n) => n[0])
        .slice(0, 2)
        .join("")
        .toUpperCase() || "U"
    )
  }

  const getComment = (review: PlatformReview): string => {
    return review.comment || review.review_text || "(No comment provided)"
  }

  const renderStars = (rating: number) => {
    return (
      <div className="flex items-center gap-0.5">
        {[1, 2, 3, 4, 5].map((s) => (
          <Star
            key={s}
            className={`h-3.5 w-3.5 ${
              s <= rating
                ? "fill-amber-400 text-amber-400"
                : "text-slate-200 dark:text-slate-700"
            }`}
          />
        ))}
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100">
            Platform Testimonials Moderation
          </h1>
          <p className="text-xs text-slate-500">
            Review live student feedback and moderate testimonials showcased on the public landing page.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={fetchReviews}
            disabled={loading}
            className="h-8 gap-1.5 text-xs text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
          >
            <RefreshCw className={`h-3.5 w-3.5 ${loading ? "animate-spin" : ""}`} />
            Refresh
          </Button>
        </div>
      </div>

      {/* Notification Toast Banner */}
      {notification && (
        <div
          className={`flex items-center gap-2 rounded-lg border px-4 py-2.5 text-xs font-medium transition ${
            notification.type === "success"
              ? "border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300"
              : "border-red-200 bg-red-50 text-red-800 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300"
          }`}
        >
          {notification.type === "success" ? (
            <CheckCircle className="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
          ) : (
            <AlertCircle className="h-4 w-4 shrink-0 text-red-600 dark:text-red-400" />
          )}
          <span>{notification.message}</span>
        </div>
      )}

      {/* Error Alert */}
      {error && (
        <div className="flex items-center justify-between rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-800 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
          <div className="flex items-center gap-2">
            <AlertCircle className="h-4 w-4 shrink-0 text-red-600" />
            <span>{error}</span>
          </div>
          <Button
            size="sm"
            variant="outline"
            onClick={fetchReviews}
            className="h-7 text-xs border-red-300 text-red-700 hover:bg-red-100 dark:border-red-800 dark:text-red-300"
          >
            Retry
          </Button>
        </div>
      )}

      {/* Status Tabs */}
      <div className="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3 dark:border-slate-800">
        <button
          type="button"
          onClick={() => setActiveTab("pending")}
          className={`flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition ${
            activeTab === "pending"
              ? "bg-amber-500 text-white shadow-sm"
              : "text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-900"
          }`}
        >
          <span>Pending Review</span>
          <span
            className={`rounded-full px-1.5 py-0.2 text-[10px] ${
              activeTab === "pending"
                ? "bg-white text-amber-600 font-bold"
                : "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"
            }`}
          >
            {countPending}
          </span>
        </button>

        <button
          type="button"
          onClick={() => setActiveTab("approved")}
          className={`flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition ${
            activeTab === "approved"
              ? "bg-emerald-600 text-white shadow-sm"
              : "text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-900"
          }`}
        >
          <span>Approved Showcase</span>
          <span
            className={`rounded-full px-1.5 py-0.2 text-[10px] ${
              activeTab === "approved"
                ? "bg-white text-emerald-700 font-bold"
                : "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
            }`}
          >
            {countApproved}
          </span>
        </button>

        <button
          type="button"
          onClick={() => setActiveTab("rejected")}
          className={`flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition ${
            activeTab === "rejected"
              ? "bg-red-600 text-white shadow-sm"
              : "text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-900"
          }`}
        >
          <span>Rejected</span>
          <span
            className={`rounded-full px-1.5 py-0.2 text-[10px] ${
              activeTab === "rejected"
                ? "bg-white text-red-700 font-bold"
                : "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300"
            }`}
          >
            {countRejected}
          </span>
        </button>

        <button
          type="button"
          onClick={() => setActiveTab("all")}
          className={`flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition ${
            activeTab === "all"
              ? "bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 shadow-sm"
              : "text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-900"
          }`}
        >
          <span>All</span>
          <span
            className={`rounded-full px-1.5 py-0.2 text-[10px] ${
              activeTab === "all"
                ? "bg-slate-700 text-white dark:bg-slate-300 dark:text-slate-900 font-bold"
                : "bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
            }`}
          >
            {reviews.length}
          </span>
        </button>
      </div>

      {/* Loading Skeletons */}
      {loading && reviews.length === 0 ? (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {[1, 2, 3].map((i) => (
            <div
              key={i}
              className="animate-pulse rounded-xl border border-slate-200 bg-white p-5 space-y-4 dark:border-slate-800 dark:bg-slate-950"
            >
              <div className="flex items-center gap-3">
                <div className="h-10 w-10 rounded-full bg-slate-200 dark:bg-slate-800" />
                <div className="flex-1 space-y-2">
                  <div className="h-3 w-28 rounded bg-slate-200 dark:bg-slate-800" />
                  <div className="h-2.5 w-16 rounded bg-slate-200 dark:bg-slate-800" />
                </div>
              </div>
              <div className="h-16 rounded bg-slate-100 dark:bg-slate-900" />
              <div className="h-8 rounded bg-slate-200 dark:bg-slate-800" />
            </div>
          ))}
        </div>
      ) : filteredReviews.length === 0 ? (
        /* Empty State */
        <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 p-12 text-center dark:border-slate-800">
          <div className="mb-3 rounded-full bg-slate-100 p-3 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
            <MessageSquareQuote className="h-6 w-6" />
          </div>
          <h3 className="text-sm font-semibold text-slate-800 dark:text-slate-200">
            {activeTab === "pending"
              ? "No testimonials pending moderation"
              : activeTab === "approved"
              ? "No testimonials currently approved for showcase"
              : activeTab === "rejected"
              ? "No rejected testimonials"
              : "No testimonials recorded in the database"}
          </h3>
          <p className="mt-1 max-w-sm text-xs text-slate-500">
            {activeTab === "pending"
              ? "All submitted student testimonials have been reviewed."
              : "Student feedback submitted from the platform will appear here."}
          </p>
          <Button
            variant="outline"
            size="sm"
            onClick={fetchReviews}
            className="mt-4 h-8 gap-1.5 text-xs"
          >
            <RefreshCw className="h-3.5 w-3.5" />
            Check for New Reviews
          </Button>
        </div>
      ) : (
        /* Reviews Grid */
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {filteredReviews.map((review) => {
            const reviewerName = getReviewerName(review)
            const reviewerSubtitle = getReviewerSubtitle(review)
            const reviewerAvatar = getReviewerAvatar(review)
            const initials = getInitials(reviewerName)
            const comment = getComment(review)
            const isItemUpdating = actionLoadingId === review.id

            return (
              <Card
                key={review.id}
                className="flex flex-col justify-between border-slate-200 bg-white shadow-sm transition hover:shadow dark:border-slate-800 dark:bg-slate-950"
              >
                <CardHeader className="pb-3">
                  <div className="flex items-start justify-between gap-2">
                    <div className="flex items-center gap-2.5 min-w-0">
                      <Avatar className="h-9 w-9 shrink-0 border border-slate-200 dark:border-slate-700">
                        {reviewerAvatar && (
                          <AvatarImage src={reviewerAvatar} alt={reviewerName} />
                        )}
                        <AvatarFallback className="bg-slate-800 text-xs font-bold text-white">
                          {initials}
                        </AvatarFallback>
                      </Avatar>
                      <div className="min-w-0 flex-1">
                        <CardTitle className="truncate text-xs font-bold text-slate-900 dark:text-slate-100">
                          {reviewerName}
                        </CardTitle>
                        <CardDescription className="truncate text-[11px] text-slate-500">
                          {reviewerSubtitle}
                        </CardDescription>
                      </div>
                    </div>

                    <div className="flex items-center gap-1.5 shrink-0">
                      <Badge
                        variant={
                          review.status === "approved"
                            ? "success"
                            : review.status === "pending"
                            ? "warning"
                            : "destructive"
                        }
                        className="text-[10px] capitalize"
                      >
                        {review.status}
                      </Badge>

                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => deleteReview(review.id)}
                        disabled={isItemUpdating}
                        title="Delete permanently"
                        className="h-6 w-6 text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40"
                      >
                        {isItemUpdating && actionType === "delete" ? (
                          <Loader2 className="h-3.5 w-3.5 animate-spin text-red-600" />
                        ) : (
                          <Trash2 className="h-3.5 w-3.5" />
                        )}
                      </Button>
                    </div>
                  </div>
                </CardHeader>

                <CardContent className="flex-1 space-y-3 pt-0">
                  <div className="flex items-center justify-between">
                    {renderStars(review.rating)}
                    <span className="text-[10px] text-slate-400 flex items-center gap-1">
                      <Clock className="h-3 w-3" />
                      {review.created_at
                        ? new Date(review.created_at).toLocaleDateString(undefined, {
                            year: "numeric",
                            month: "short",
                            day: "numeric",
                          })
                        : "Recent"}
                    </span>
                  </div>

                  <p className="rounded-lg bg-slate-50 p-3 text-xs text-slate-700 leading-relaxed italic dark:bg-slate-900/50 dark:text-slate-300">
                    &ldquo;{comment}&rdquo;
                  </p>
                </CardContent>

                {/* Action Buttons */}
                <div className="border-t border-slate-100 p-3 dark:border-slate-800">
                  {review.status === "pending" && (
                    <div className="flex items-center gap-2">
                      <Button
                        size="sm"
                        disabled={isItemUpdating}
                        onClick={() => updateStatus(review.id, "approved")}
                        className="flex-1 h-8 bg-emerald-600 text-xs font-semibold text-white hover:bg-emerald-500"
                      >
                        {isItemUpdating && actionType === "status-approved" ? (
                          <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                        ) : (
                          <CheckCircle className="mr-1.5 h-3.5 w-3.5" />
                        )}
                        Approve
                      </Button>

                      <Button
                        size="sm"
                        variant="outline"
                        disabled={isItemUpdating}
                        onClick={() => updateStatus(review.id, "rejected")}
                        className="flex-1 h-8 border-red-200 text-xs text-red-600 hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950/40"
                      >
                        {isItemUpdating && actionType === "status-rejected" ? (
                          <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                        ) : (
                          <XCircle className="mr-1.5 h-3.5 w-3.5" />
                        )}
                        Reject
                      </Button>
                    </div>
                  )}

                  {review.status === "approved" && (
                    <div className="flex items-center justify-between gap-2">
                      <span className="text-[11px] font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                        <ThumbsUp className="h-3.5 w-3.5 shrink-0" />
                        Showcased on Landing Page
                      </span>

                      <div className="flex items-center gap-1">
                        <Button
                          size="sm"
                          variant="outline"
                          disabled={isItemUpdating}
                          onClick={() => updateStatus(review.id, "pending")}
                          title="Move back to Pending"
                          className="h-7 px-2 text-[11px] text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-900"
                        >
                          <RotateCcw className="h-3 w-3 mr-1" />
                          To Pending
                        </Button>

                        <Button
                          size="sm"
                          variant="outline"
                          disabled={isItemUpdating}
                          onClick={() => updateStatus(review.id, "rejected")}
                          title="Reject testimonial"
                          className="h-7 px-2 text-[11px] border-red-200 text-red-600 hover:bg-red-50 dark:border-red-900/60 dark:text-red-400"
                        >
                          <XCircle className="h-3 w-3 mr-1" />
                          Reject
                        </Button>
                      </div>
                    </div>
                  )}

                  {review.status === "rejected" && (
                    <div className="flex items-center justify-between gap-2">
                      <span className="text-[11px] font-medium text-red-500 dark:text-red-400 flex items-center gap-1">
                        <XCircle className="h-3.5 w-3.5 shrink-0" />
                        Rejected / Hidden
                      </span>

                      <div className="flex items-center gap-1">
                        <Button
                          size="sm"
                          variant="outline"
                          disabled={isItemUpdating}
                          onClick={() => updateStatus(review.id, "pending")}
                          title="Reset to pending"
                          className="h-7 px-2 text-[11px] text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-900"
                        >
                          <RotateCcw className="h-3 w-3 mr-1" />
                          To Pending
                        </Button>

                        <Button
                          size="sm"
                          disabled={isItemUpdating}
                          onClick={() => updateStatus(review.id, "approved")}
                          className="h-7 px-2 text-[11px] bg-emerald-600 text-white hover:bg-emerald-500"
                        >
                          <CheckCircle className="h-3 w-3 mr-1" />
                          Approve
                        </Button>
                      </div>
                    </div>
                  )}
                </div>
              </Card>
            )
          })}
        </div>
      )}
    </div>
  )
}
