"use client"

import React, { useState, useEffect, useCallback, useTransition } from "react"
import { AdminAuditLog, AuditLogPagination } from "@/types/settings"
import { apiClient } from "@/lib/api-client"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import {
  History,
  Search,
  Code2,
  Clock,
  Filter,
  Fingerprint,
  Globe,
  FileCode,
  User,
  RefreshCw,
  Loader2,
  AlertCircle,
  Trash2,
  Sparkles,
  ChevronLeft,
  ChevronRight,
  CheckCircle2,
  ShieldAlert,
} from "lucide-react"

export function parseUserAgent(ua?: string | null) {
  if (!ua || ua === 'System / Console') {
    return { browser: 'System Service', os: 'Server', device: 'Automated Task' };
  }

  let browser = 'Unknown Browser';
  if (ua.includes('Edg/')) browser = 'Microsoft Edge';
  else if (ua.includes('Chrome/')) browser = 'Google Chrome';
  else if (ua.includes('Safari/') && !ua.includes('Chrome')) browser = 'Apple Safari';
  else if (ua.includes('Firefox/')) browser = 'Mozilla Firefox';

  let os = 'Unknown OS';
  if (ua.includes('Windows NT 10.0')) os = 'Windows 10/11';
  else if (ua.includes('Macintosh') || ua.includes('Mac OS')) os = 'macOS';
  else if (ua.includes('Android')) os = 'Android';
  else if (ua.includes('iPhone') || ua.includes('iPad')) os = 'iOS';
  else if (ua.includes('Linux')) os = 'Linux';

  const isMobile = ua.includes('Mobile') || ua.includes('Android') || ua.includes('iPhone');

  return {
    browser,
    os,
    device: isMobile ? 'Mobile' : 'Desktop',
  };
}

export const DEFAULT_ACTION_GROUPS: { category: string; actions: string[] }[] = [
  {
    category: "Resources & Pricing",
    actions: [
      "resource.uploaded",
      "resource.updated",
      "resource.price_updated",
      "resource.price_removed",
      "resource.deleted",
    ],
  },
  {
    category: "Chapters & Curriculum",
    actions: [
      "chapter.created",
      "chapter.updated",
      "chapter.deleted",
      "chapter.ai_notes_regenerated",
      "stream.created",
      "stream.updated",
      "stream.deleted",
      "subject.created",
      "subject.updated",
      "subject.deleted",
    ],
  },
  {
    category: "Media & Textbooks",
    actions: [
      "textbook.saved",
      "textbook.updated",
      "textbook.deleted",
      "video.created",
      "video.updated",
      "video.deleted",
    ],
  },
  {
    category: "Notes & AI Synthesis",
    actions: [
      "note.created",
      "note.updated",
      "note.deleted",
      "note.activated",
      "note.ai_generated",
      "note.visibility_toggled",
    ],
  },
  {
    category: "AI Prompts & Settings",
    actions: [
      "ai_settings.updated",
      "ai_settings.reset_notes",
    ],
  },
  {
    category: "Quizzes & Questions",
    actions: [
      "quiz.created",
      "quiz.updated",
      "quiz.deleted",
      "quiz.ai_generated",
      "quiz_question.created",
      "quiz_question.updated",
      "quiz_question.deleted",
      "quiz_questions.bulk_deleted",
      "quiz_questions.all_deleted",
      "quiz_questions.batch_created",
    ],
  },
  {
    category: "National Entrance Exams",
    actions: [
      "national_exam.created",
      "national_exam.updated",
      "national_exam.deleted",
      "exam_year.created",
      "exam_year.updated",
      "exam_year.deleted",
      "exam_subject.created",
      "exam_subject.updated",
      "exam_subject.deleted",
      "exam_question.created",
      "exam_question.updated",
      "exam_question.deleted",
      "exam_questions.batch_imported",
    ],
  },
  {
    category: "CMS Pages & FAQs",
    actions: [
      "cms_page.created",
      "cms_page.updated",
      "cms_page.deleted",
      "cms_page.published",
      "cms_page.unpublished",
      "cms_section.created",
      "cms_section.updated",
      "cms_section.deleted",
      "cms_sections.reordered",
      "faq.created",
      "faq.updated",
      "faq.deleted",
      "faq.reordered",
      "faq.status_toggled",
    ],
  },
  {
    category: "Announcements & Updates",
    actions: [
      "latest_update.created",
      "latest_update.updated",
      "latest_update.deleted",
    ],
  },
  {
    category: "Staff & Access Control",
    actions: [
      "staff.created",
      "staff.updated",
      "staff.promoted",
      "staff.role_updated",
      "staff.activated",
      "staff.deactivated",
      "staff.revoked",
      "staff.deleted",
      "user.impersonated",
    ],
  },
  {
    category: "Tutors & Moderation",
    actions: [
      "tutor.approved",
      "tutor.rejected",
      "tutor_review.approved",
      "tutor_review.rejected",
    ],
  },
  {
    category: "Platform Reviews",
    actions: [
      "platform_review.approved",
      "platform_review.rejected",
      "platform_review.pending",
      "platform_review.deleted",
    ],
  },
  {
    category: "Audit Ledger Maintenance",
    actions: [
      "audit_log.deleted",
      "audit_log.pruned",
    ],
  },
]

export default function AuditLogsPage() {
  const [logs, setLogs] = useState<AdminAuditLog[]>([])
  const [loading, setLoading] = useState<boolean>(true)
  const [error, setError] = useState<string | null>(null)
  const [successMessage, setSuccessMessage] = useState<string | null>(null)

  // Filters & Pagination
  const [searchQuery, setSearchQuery] = useState<string>("")
  const [selectedAction, setSelectedAction] = useState<string>("all")
  const [currentPage, setCurrentPage] = useState<number>(1)
  const [actionGroups, setActionGroups] = useState<{ category: string; actions: string[] }[]>(DEFAULT_ACTION_GROUPS)
  const [availableActions, setAvailableActions] = useState<string[]>([])
  const [pagination, setPagination] = useState<AuditLogPagination>({
    total: 0,
    current_page: 1,
    last_page: 1,
    per_page: 25,
  })

  // Inspector & Actions
  const [inspectedLog, setInspectedLog] = useState<AdminAuditLog | null>(null)
  const [logToDelete, setLogToDelete] = useState<AdminAuditLog | null>(null)
  const [isDeleting, setIsDeleting] = useState<boolean>(false)

  // Pruning
  const [pruneModalOpen, setPruneModalOpen] = useState<boolean>(false)
  const [pruneDays, setPruneDays] = useState<number>(90)
  const [isPruning, setIsPruning] = useState<boolean>(false)

  const [, startTransition] = useTransition()

  // Fetch audit logs live from MySQL database via backend API
  const fetchLogs = useCallback(
    async (page = currentPage, action = selectedAction, search = searchQuery) => {
      setLoading(true)
      setError(null)
      try {
        const params: Record<string, any> = {
          page,
          per_page: 25,
        }

        if (action && action !== "all") {
          params.action = action
        }

        if (search && search.trim() !== "") {
          params.search = search.trim()
        }

        const response = await apiClient.get("/admin/audit-logs", { params })

        if (response.data && response.data.status === "success") {
          setLogs(response.data.data || [])
          if (
            response.data.action_groups &&
            Array.isArray(response.data.action_groups) &&
            response.data.action_groups.length > 0
          ) {
            setActionGroups(response.data.action_groups)
          }
          const actions = response.data.available_actions || response.data.actions || []
          if (Array.isArray(actions) && actions.length > 0) {
            setAvailableActions(actions)
          }
          if (response.data.pagination) {
            setPagination(response.data.pagination)
            setCurrentPage(response.data.pagination.current_page)
          }
        } else if (Array.isArray(response.data)) {
          setLogs(response.data)
        }
      } catch (err: any) {
        console.error("Audit log fetch error:", err)
        setError(
          err.response?.data?.message ||
            "Failed to connect to the audit trail ledger. Please verify backend connection."
        )
      } finally {
        setLoading(false)
      }
    },
    [currentPage, selectedAction, searchQuery]
  )

  // Initial load
  useEffect(() => {
    fetchLogs(1, selectedAction, searchQuery)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  // Auto-dismiss success notification
  useEffect(() => {
    if (successMessage) {
      const timer = setTimeout(() => setSuccessMessage(null), 6000)
      return () => clearTimeout(timer)
    }
  }, [successMessage])

  // Debounced search / filter trigger
  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const val = e.target.value
    setSearchQuery(val)
    startTransition(() => {
      fetchLogs(1, selectedAction, val)
    })
  }

  const handleActionFilterChange = (newAction: string) => {
    setSelectedAction(newAction)
    fetchLogs(1, newAction, searchQuery)
  }

  const handlePageChange = (newPage: number) => {
    if (newPage < 1 || newPage > pagination.last_page) return
    setCurrentPage(newPage)
    fetchLogs(newPage, selectedAction, searchQuery)
  }

  // Delete a single audit log entry
  const handleDeleteLog = async () => {
    if (!logToDelete) return
    setIsDeleting(true)
    setError(null)
    try {
      const response = await apiClient.delete(`/admin/audit-logs/${logToDelete.id}`)
      if (response.data && response.data.status === "success") {
        setSuccessMessage(`Audit log entry #${logToDelete.id} successfully removed.`)
        if (inspectedLog?.id === logToDelete.id) {
          setInspectedLog(null)
        }
        setLogToDelete(null)
        fetchLogs(currentPage, selectedAction, searchQuery)
      }
    } catch (err: any) {
      setError(err.response?.data?.message || "Failed to remove audit log entry.")
    } finally {
      setIsDeleting(false)
    }
  }

  // Prune audit logs older than specified cutoff days
  const handlePruneLogs = async () => {
    setIsPruning(true)
    setError(null)
    try {
      const response = await apiClient.post("/admin/audit-logs/prune", { days: pruneDays })
      if (response.data && response.data.status === "success") {
        setSuccessMessage(
          response.data.message || `Pruned audit logs older than ${pruneDays} days.`
        )
        setPruneModalOpen(false)
        fetchLogs(1, selectedAction, searchQuery)
      }
    } catch (err: any) {
      setError(err.response?.data?.message || "Failed to prune audit logs.")
    } finally {
      setIsPruning(false)
    }
  }

  const getActionBadge = (action: string) => {
    return (
      <Badge className="bg-orange-500/15 text-orange-700 border-orange-200 dark:bg-orange-950/40 dark:text-orange-300 dark:border-orange-900 font-mono text-[11px] font-medium">
        {action}
      </Badge>
    )
  }

  return (
    <div className="space-y-8">
      {/* Header Banner */}
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 sm:text-3xl">
            Immutable Administrative Audit Trail
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
            Live, tamper-evident record of administrative mutations, curriculum updates, and credential approvals directly from MySQL.
          </p>
        </div>

        <div className="flex items-center gap-2">
          {/* Prune Logs Button */}
          <Button
            variant="outline"
            size="sm"
            onClick={() => setPruneModalOpen(true)}
            className="h-8 gap-1.5 text-xs font-medium text-amber-700 border-amber-300 bg-amber-50 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300"
          >
            <Sparkles className="h-3.5 w-3.5" />
            Prune Old Logs
          </Button>

          {/* Refresh Button */}
          <Button
            variant="outline"
            size="sm"
            onClick={() => fetchLogs(currentPage, selectedAction, searchQuery)}
            disabled={loading}
            className="h-8 gap-1.5 text-xs text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
          >
            <RefreshCw className={`h-3.5 w-3.5 ${loading ? "animate-spin" : ""}`} />
            Refresh
          </Button>
        </div>
      </div>

      {/* Success Notification Alert */}
      {successMessage && (
        <div className="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
          <div className="flex items-center gap-2">
            <CheckCircle2 className="h-4 w-4 shrink-0 text-emerald-600" />
            <span>{successMessage}</span>
          </div>
          <Button
            size="sm"
            variant="ghost"
            onClick={() => setSuccessMessage(null)}
            className="h-6 text-xs text-emerald-700 hover:bg-emerald-100"
          >
            Dismiss
          </Button>
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
            onClick={() => fetchLogs(currentPage, selectedAction, searchQuery)}
            className="h-7 text-xs border-red-300 text-red-700 hover:bg-red-100 dark:border-red-800 dark:text-red-300"
          >
            Retry
          </Button>
        </div>
      )}

      {/* Filter and Search Bar */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="relative w-full sm:w-96">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" />
          <Input
            placeholder="Search by admin name, email, IP, action, entity #..."
            className="pl-9 text-xs"
            value={searchQuery}
            onChange={handleSearchChange}
          />
        </div>

        <div className="flex items-center gap-2">
          <Filter className="h-4 w-4 text-slate-400 shrink-0" />
          <select
            className="rounded-md border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm transition-colors focus:border-blue-500 focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300 max-w-[260px] sm:max-w-xs"
            value={selectedAction}
            onChange={(e) => handleActionFilterChange(e.target.value)}
          >
            <option value="all">All Action Types ({pagination.total})</option>
            {actionGroups.map((group) => (
              <optgroup key={group.category} label={group.category}>
                {group.actions.map((action) => (
                  <option key={action} value={action}>
                    {action}
                  </option>
                ))}
              </optgroup>
            ))}
          </select>
        </div>
      </div>

      {/* Audit Log Table */}
      <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle className="text-base font-bold text-slate-950 dark:text-slate-50">
                Audit Events ({pagination.total})
              </CardTitle>
              <CardDescription className="text-xs text-slate-500">
                Live write-only ledger displaying staff operations, credential changes, and entity mutations
              </CardDescription>
            </div>
            <span className="text-[11px] font-mono text-slate-400">
              Retention: 365 Days
            </span>
          </div>
        </CardHeader>
        <CardContent>
          {loading && logs.length === 0 ? (
            <div className="flex h-48 flex-col items-center justify-center space-y-2 text-slate-400">
              <Loader2 className="h-6 w-6 animate-spin text-slate-500" />
              <span className="text-xs font-medium">Fetching immutable audit logs from database...</span>
            </div>
          ) : logs.length === 0 ? (
            <div className="flex h-48 flex-col items-center justify-center space-y-2 text-slate-400">
              <History className="h-8 w-8 text-slate-300 dark:text-slate-600" />
              <span className="text-xs font-medium">
                {searchQuery || selectedAction !== "all"
                  ? "No audit events match your current filter criteria."
                  : "No audit records currently stored in the backend ledger."}
              </span>
              {(selectedAction !== "all" || searchQuery) && (
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => {
                    setSelectedAction("all")
                    setSearchQuery("")
                    fetchLogs(1, "all", "")
                  }}
                  className="text-xs text-blue-600"
                >
                  Reset all filters
                </Button>
              )}
            </div>
          ) : (
            <div className="space-y-4">
              <div className="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800">
                <table className="w-full text-left text-xs">
                  <thead className="border-b border-slate-200 bg-slate-50 font-semibold uppercase text-slate-500 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-400">
                    <tr>
                      <th className="px-4 py-3">Timestamp</th>
                      <th className="px-4 py-3">Admin Personnel</th>
                      <th className="px-4 py-3">Action Event</th>
                      <th className="px-4 py-3">Target Entity</th>
                      <th className="px-4 py-3">IP Address</th>
                      <th className="px-4 py-3 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 dark:divide-slate-800/80">
                    {logs.map((log) => (
                      <tr
                        key={log.id}
                        className="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-900/30"
                      >
                        <td className="px-4 py-3.5 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                          <div className="flex flex-col gap-0.5">
                            <span className="flex items-center gap-1.5 font-mono text-[11px] text-slate-800 dark:text-slate-200">
                              <Clock className="h-3 w-3 text-slate-400" />
                              {log.created_at
                                ? new Date(log.created_at).toLocaleString("en-US", {
                                    month: "short",
                                    day: "numeric",
                                    year: "numeric",
                                    hour: "numeric",
                                    minute: "2-digit",
                                    second: "2-digit",
                                    hour12: true,
                                  })
                                : "Recent"}
                            </span>
                            {log.time_ago && (
                              <span className="text-[10px] text-blue-600 dark:text-blue-400 font-medium pl-4.5">
                                {log.time_ago}
                              </span>
                            )}
                          </div>
                        </td>
                        <td className="px-4 py-3.5">
                          <div className="flex items-center gap-2">
                            <div className="flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                              {(log.admin_name || "AD").slice(0, 2).toUpperCase()}
                            </div>
                            <div>
                              <div className="font-bold text-slate-900 dark:text-slate-100">
                                {log.admin_name || "Administrator"}
                              </div>
                              <div className="text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                                {log.admin_email}
                              </div>
                            </div>
                          </div>
                        </td>
                        <td className="px-4 py-3.5">{getActionBadge(log.action)}</td>
                        <td className="px-4 py-3.5">
                          <div className="font-mono text-[11px] text-slate-700 dark:text-slate-300">
                            {log.auditable_type ? log.auditable_type.split("\\").pop() : "Entity"} #{log.auditable_id}
                          </div>
                          <div className="text-[10px] text-slate-400 truncate max-w-[160px]">
                            {log.auditable_type_full || log.auditable_type}
                          </div>
                        </td>
                        <td className="px-4 py-3.5 text-slate-500 dark:text-slate-400 font-mono text-[11px]">
                          {log.ip_address || "127.0.0.1"}
                        </td>
                        <td className="px-4 py-3.5 text-right">
                          <div className="flex items-center justify-end gap-1.5">
                            <Button
                              size="sm"
                              variant="outline"
                              className="h-7 text-xs font-semibold gap-1 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-900 hover:bg-blue-50 dark:hover:bg-blue-950/50"
                              onClick={() => setInspectedLog(log)}
                            >
                              <Code2 className="h-3.5 w-3.5" />
                              Inspect Payload
                            </Button>
                            <Button
                              size="sm"
                              variant="ghost"
                              className="h-7 w-7 p-0 text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50"
                              onClick={() => setLogToDelete(log)}
                              title="Delete log entry"
                            >
                              <Trash2 className="h-3.5 w-3.5" />
                            </Button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              {/* Pagination Controls */}
              <div className="flex flex-col items-center justify-between gap-3 sm:flex-row text-xs text-slate-500">
                <div>
                  Showing{" "}
                  <span className="font-semibold text-slate-900 dark:text-slate-100">
                    {(pagination.current_page - 1) * pagination.per_page + (logs.length > 0 ? 1 : 0)}
                  </span>{" "}
                  to{" "}
                  <span className="font-semibold text-slate-900 dark:text-slate-100">
                    {Math.min(pagination.current_page * pagination.per_page, pagination.total)}
                  </span>{" "}
                  of{" "}
                  <span className="font-semibold text-slate-900 dark:text-slate-100">
                    {pagination.total}
                  </span>{" "}
                  audit events
                </div>

                <div className="flex items-center gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    className="h-8 px-2 text-xs"
                    disabled={pagination.current_page <= 1 || loading}
                    onClick={() => handlePageChange(pagination.current_page - 1)}
                  >
                    <ChevronLeft className="h-3.5 w-3.5 mr-1" />
                    Previous
                  </Button>
                  <span className="px-2 text-xs font-medium text-slate-700 dark:text-slate-300">
                    Page {pagination.current_page} of {Math.max(1, pagination.last_page)}
                  </span>
                  <Button
                    variant="outline"
                    size="sm"
                    className="h-8 px-2 text-xs"
                    disabled={pagination.current_page >= pagination.last_page || loading}
                    onClick={() => handlePageChange(pagination.current_page + 1)}
                  >
                    Next
                    <ChevronRight className="h-3.5 w-3.5 ml-1" />
                  </Button>
                </div>
              </div>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Sliding JSON Diff Inspector Sheet */}
      <Sheet open={!!inspectedLog} onOpenChange={(open) => !open && setInspectedLog(null)}>
        <SheetContent className="sm:max-w-xl w-full overflow-y-auto">
          <SheetHeader className="border-b border-slate-200 pb-4 dark:border-slate-800">
            <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">
              <Code2 className="h-4 w-4" />
              Audit Payload Inspector
            </div>
            <SheetTitle className="text-lg font-bold">
              Event #{inspectedLog?.id}: {inspectedLog?.action}
            </SheetTitle>
            <SheetDescription className="text-xs text-slate-500">
              Before and after state snapshots recorded at transaction commit time.
            </SheetDescription>
          </SheetHeader>

          {inspectedLog && (
            <div className="space-y-6 pt-6 text-xs">
              {/* Event Metadata Grid */}
              <div className="grid grid-cols-2 gap-3 rounded-lg border border-slate-200 bg-slate-50/50 p-3.5 dark:border-slate-800 dark:bg-slate-900/30">
                <div>
                  <span className="text-[10px] uppercase font-bold text-slate-400 flex items-center gap-1">
                    <User className="h-3 w-3" />
                    Admin User
                  </span>
                  <div className="font-bold text-slate-900 dark:text-slate-100 mt-0.5">
                    {inspectedLog.admin_name}
                  </div>
                  <div className="text-[11px] text-slate-500 font-mono">
                    {inspectedLog.admin_email}
                  </div>
                </div>

                <div>
                  <p className="text-xs text-slate-400 font-medium">TIMESTAMP</p>
                  <div className="flex items-center gap-2 mt-0.5">
                    <span className="text-sm font-semibold text-slate-200">
                      {new Date(inspectedLog.created_at).toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: true,
                      })}
                    </span>
                    <Badge variant="outline" className="text-[10px] text-blue-400 border-blue-500/20">
                      {inspectedLog.time_ago || 'EAT (UTC+3)'}
                    </Badge>
                  </div>
                </div>

                <div>
                  <span className="text-[10px] uppercase font-bold text-slate-400 flex items-center gap-1">
                    <Globe className="h-3 w-3" />
                    IP Address
                  </span>
                  <div className="font-mono text-slate-800 dark:text-slate-200 mt-0.5">
                    {inspectedLog.ip_address}
                  </div>
                </div>

                <div>
                  <span className="text-[10px] uppercase font-bold text-slate-400 flex items-center gap-1">
                    <Fingerprint className="h-3 w-3" />
                    Target Entity
                  </span>
                  <div className="font-mono text-slate-800 dark:text-slate-200 mt-0.5">
                    {inspectedLog.auditable_type ? inspectedLog.auditable_type.split("\\").pop() : "Entity"} #{inspectedLog.auditable_id}
                  </div>
                </div>

                <div className="col-span-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                  <p className="text-xs text-slate-400 font-medium mb-1.5">DEVICE & BROWSER</p>
                  {(() => {
                    const ua = parseUserAgent(inspectedLog.user_agent);
                    return (
                      <div className="space-y-2">
                        <div className="flex flex-wrap items-center gap-2">
                          <Badge className="bg-blue-500/10 text-blue-400 border-blue-500/20">
                            🌐 {ua.browser}
                          </Badge>
                          <Badge className="bg-purple-500/10 text-purple-400 border-purple-500/20">
                            💻 {ua.os}
                          </Badge>
                          <Badge className="bg-slate-800 text-slate-300 border-slate-700">
                            🖥️ {ua.device}
                          </Badge>
                        </div>
                        
                        {/* Collapsible raw string */}
                        <details className="text-[11px] text-slate-500 cursor-pointer">
                          <summary className="hover:text-slate-400">View raw technical user-agent</summary>
                          <p className="mt-1 font-mono text-[10px] break-all bg-slate-950 p-2 rounded border border-slate-800">
                            {inspectedLog.user_agent}
                          </p>
                        </details>
                      </div>
                    );
                  })()}
                </div>
              </div>

              {/* Before vs After JSON Diffs */}
              <div className="space-y-4">
                <div className="space-y-1.5">
                  <div className="flex items-center justify-between">
                    <span className="font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                      <FileCode className="h-3.5 w-3.5" />
                      Previous State (old_values)
                    </span>
                    {inspectedLog.old_values === null && (
                      <span className="text-[10px] font-semibold text-slate-400">
                        (Initial Creation / None)
                      </span>
                    )}
                  </div>
                  <pre className="rounded-lg border border-rose-200 bg-rose-950/20 p-3.5 font-mono text-[11px] text-rose-700 dark:border-rose-900/40 dark:text-rose-300 overflow-x-auto max-h-48">
                    {inspectedLog.old_values
                      ? JSON.stringify(inspectedLog.old_values, null, 2)
                      : "// No previous state recorded for this event"}
                  </pre>
                </div>

                <div className="space-y-1.5">
                  <div className="flex items-center justify-between">
                    <span className="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                      <FileCode className="h-3.5 w-3.5" />
                      Committed State (new_values)
                    </span>
                    <span className="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                      (Verified)
                    </span>
                  </div>
                  <pre className="rounded-lg border border-emerald-200 bg-emerald-950/20 p-3.5 font-mono text-[11px] text-emerald-700 dark:border-emerald-900/40 dark:text-emerald-300 overflow-x-auto max-h-48">
                    {inspectedLog.new_values
                      ? JSON.stringify(inspectedLog.new_values, null, 2)
                      : "// No values recorded"}
                  </pre>
                </div>
              </div>

              {/* Drawer Actions */}
              <div className="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-800">
                <Button
                  variant="outline"
                  size="sm"
                  className="text-xs text-red-600 border-red-200 hover:bg-red-50 dark:border-red-900/60 dark:hover:bg-red-950/50"
                  onClick={() => setLogToDelete(inspectedLog)}
                >
                  <Trash2 className="h-3.5 w-3.5 mr-1 text-red-600" />
                  Delete Log Entry
                </Button>

                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setInspectedLog(null)}
                >
                  Close Inspector
                </Button>
              </div>
            </div>
          )}
        </SheetContent>
      </Sheet>

      {/* Delete Confirmation Dialog */}
      <Dialog open={!!logToDelete} onOpenChange={(open) => !open && setLogToDelete(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2 text-rose-600">
              <ShieldAlert className="h-5 w-5" />
              Delete Audit Log Entry
            </DialogTitle>
            <DialogDescription className="text-xs text-slate-500 pt-2">
              Are you sure you want to delete audit event{" "}
              <strong>#{logToDelete?.id} ({logToDelete?.action})</strong>? This action is permanent and cannot be reversed.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter className="gap-2 sm:gap-0">
            <Button
              variant="outline"
              size="sm"
              onClick={() => setLogToDelete(null)}
              disabled={isDeleting}
            >
              Cancel
            </Button>
            <Button
              variant="destructive"
              size="sm"
              onClick={handleDeleteLog}
              disabled={isDeleting}
              className="gap-1.5"
            >
              {isDeleting ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : <Trash2 className="h-3.5 w-3.5" />}
              Confirm Deletion
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Prune Old Logs Dialog */}
      <Dialog open={pruneModalOpen} onOpenChange={setPruneModalOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2 text-amber-600 dark:text-amber-400">
              <Sparkles className="h-5 w-5" />
              Prune Historical Audit Logs
            </DialogTitle>
            <DialogDescription className="text-xs text-slate-500 pt-2">
              Pruning permanently removes older audit log entries from the database to optimize table indexing and storage space.
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4 py-2 text-xs">
            <div className="space-y-2">
              <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Retention Cutoff Period:
              </label>
              <select
                className="w-full rounded-md border border-slate-200 bg-white p-2 text-xs font-medium text-slate-800 shadow-sm dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200"
                value={pruneDays}
                onChange={(e) => setPruneDays(Number(e.target.value))}
              >
                <option value={30}>Older than 30 Days (1 Month)</option>
                <option value={60}>Older than 60 Days (2 Months)</option>
                <option value={90}>Older than 90 Days (Quarterly Default)</option>
                <option value={180}>Older than 180 Days (Half Year)</option>
                <option value={365}>Older than 365 Days (1 Year)</option>
              </select>
            </div>

            <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-[11px] text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200">
              <strong>Caution:</strong> All audit entries created prior to{" "}
              {new Date(Date.now() - pruneDays * 86400 * 1000).toLocaleDateString()} will be permanently erased.
            </div>
          </div>

          <DialogFooter className="gap-2 sm:gap-0">
            <Button
              variant="outline"
              size="sm"
              onClick={() => setPruneModalOpen(false)}
              disabled={isPruning}
            >
              Cancel
            </Button>
            <Button
              variant="default"
              size="sm"
              onClick={handlePruneLogs}
              disabled={isPruning}
              className="bg-amber-600 hover:bg-amber-700 text-white gap-1.5"
            >
              {isPruning ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : <Sparkles className="h-3.5 w-3.5" />}
              Prune Logs Now
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
