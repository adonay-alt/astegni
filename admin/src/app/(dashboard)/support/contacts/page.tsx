"use client"

import React, { useState, useEffect, useCallback, useMemo } from "react"
import Link from "next/link"
import { apiClient } from "@/lib/api-client"
import { ContactMessage, ContactCounts, ContactStatus } from "@/types/contact"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import {
  Inbox,
  Mail,
  Send,
  PhoneCall,
  CheckCircle2,
  AlertCircle,
  Clock,
  Search,
  Trash2,
  RefreshCw,
  Copy,
  Check,
  Calendar,
  User,
  Sparkles,
  Loader2,
  X,
  MessageSquare,
  ShieldCheck,
  CornerDownRight,
  ExternalLink,
} from "lucide-react"

export default function SupportContactsPage() {
  // Inbox State
  const [contacts, setContacts] = useState<ContactMessage[]>([])
  const [counts, setCounts] = useState<ContactCounts>({ total: 0, unread: 0, read: 0, replied: 0 })
  const [selectedId, setSelectedId] = useState<number | null>(null)
  const [statusFilter, setStatusFilter] = useState<"all" | ContactStatus>("all")
  const [searchQuery, setSearchQuery] = useState("")
  const [isLoading, setIsLoading] = useState(true)
  const [isRefreshing, setIsRefreshing] = useState(false)

  // Reply Composer State
  const [replyText, setReplyText] = useState("")
  const [isDispatching, setIsDispatching] = useState(false)
  const [copiedEmail, setCopiedEmail] = useState(false)

  // Toast notification
  const [toast, setToast] = useState<{ type: "success" | "error"; text: string } | null>(null)

  const showToast = (type: "success" | "error", text: string) => {
    setToast({ type, text })
    setTimeout(() => {
      setToast((prev) => (prev?.text === text ? null : prev))
    }, 4500)
  }

  // 1. Fetch Contacts
  const fetchContacts = useCallback(
    async (showLoading = true) => {
      if (showLoading) setIsLoading(true)
      else setIsRefreshing(true)

      try {
        const params: Record<string, string | number> = { per_page: 50 }
        if (statusFilter !== "all") params.status = statusFilter
        if (searchQuery.trim()) params.search = searchQuery.trim()

        const res = await apiClient.get("/admin/contacts", { params })
        if (res.data?.status === "success") {
          const list: ContactMessage[] = res.data.data || []
          setContacts(list)
          if (res.data.counts) {
            setCounts(res.data.counts)
          }

          // If no item is selected or selected item is no longer in list, auto-select first
          setSelectedId((prev) => {
            if (prev && list.some((c) => c.id === prev)) return prev
            return list.length > 0 ? list[0].id : null
          })
        }
      } catch (err: any) {
        console.error("Failed to fetch contact inquiries:", err)
        showToast("error", err.response?.data?.message || "Failed to load support inquiries.")
      } finally {
        setIsLoading(false)
        setIsRefreshing(false)
      }
    },
    [statusFilter, searchQuery]
  )

  useEffect(() => {
    fetchContacts(true)
  }, [fetchContacts])

  // Active selected contact object
  const activeContact = useMemo(() => {
    return contacts.find((c) => c.id === selectedId) || null
  }, [contacts, selectedId])

  // Automatically mark active contact as read when selected
  useEffect(() => {
    if (!activeContact) return
    if (activeContact.status === "unread") {
      const contactId = activeContact.id
      apiClient
        .patch(`/admin/contacts/${contactId}/read`)
        .then(() => {
          // Update item status locally
          setContacts((prev) =>
            prev.map((item) => (item.id === contactId ? { ...item, status: "read" } : item))
          )
          // Update counts
          setCounts((prev) => ({
            ...prev,
            unread: Math.max(0, prev.unread - 1),
            read: prev.read + 1,
          }))
        })
        .catch((err) => {
          console.error("Failed to mark inquiry as read:", err)
        })
    }
  }, [activeContact])

  // Copy email to clipboard helper
  const handleCopyEmail = (email: string) => {
    navigator.clipboard.writeText(email)
    setCopiedEmail(true)
    setTimeout(() => setCopiedEmail(false), 2000)
  }

  // Quick reply template appender
  const applyTemplate = (template: string) => {
    if (!activeContact) return
    const personalized = template
      .replace("{name}", activeContact.name)
      .replace("{subject}", activeContact.subject || "your inquiry")
    setReplyText(personalized)
  }

  // 2. Dispatch Reply
  const handleDispatchReply = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!activeContact) return

    if (!replyText.trim() || replyText.trim().length < 5) {
      showToast("error", "Reply message must be at least 5 characters long.")
      return
    }

    setIsDispatching(true)

    try {
      const res = await apiClient.post(`/admin/contacts/${activeContact.id}/reply`, {
        message: replyText.trim(),
      })

      if (res.data?.status === "success") {
        const updatedItem: ContactMessage = res.data.data

        // Update contacts state
        setContacts((prev) =>
          prev.map((item) => (item.id === activeContact.id ? updatedItem : item))
        )

        // Update counts
        setCounts((prev) => {
          const wasRead = activeContact.status === "read"
          const wasUnread = activeContact.status === "unread"
          return {
            ...prev,
            unread: wasUnread ? Math.max(0, prev.unread - 1) : prev.unread,
            read: wasRead ? Math.max(0, prev.read - 1) : prev.read,
            replied: activeContact.status !== "replied" ? prev.replied + 1 : prev.replied,
          }
        })

        setReplyText("")
        showToast("success", res.data?.message || `Reply successfully dispatched to ${activeContact.email}!`)
      } else {
        showToast("error", res.data?.message || "Failed to dispatch email reply.")
      }
    } catch (err: any) {
      console.error("Failed to dispatch reply email:", err)
      const errorMsg =
        err.response?.data?.message ||
        err.response?.data?.error ||
        "Could not send email. Please check server SMTP configuration."
      showToast("error", errorMsg)
    } finally {
      setIsDispatching(false)
    }
  }

  // 3. Delete Inquiry
  const handleDeleteContact = async (id: number) => {
    if (!confirm("Are you sure you want to delete this contact inquiry permanently?")) return

    try {
      await apiClient.delete(`/admin/contacts/${id}`)
      showToast("success", "Inquiry deleted successfully.")

      // Remove from state
      setContacts((prev) => {
        const remaining = prev.filter((c) => c.id !== id)
        if (selectedId === id) {
          setSelectedId(remaining.length > 0 ? remaining[0].id : null)
        }
        return remaining
      })

      // Refresh counts
      fetchContacts(false)
    } catch (err: any) {
      console.error("Failed to delete contact:", err)
      showToast("error", err.response?.data?.message || "Failed to delete inquiry.")
    }
  }

  // Formatter for dates
  const formatDate = (dateString?: string | null) => {
    if (!dateString) return "N/A"
    const d = new Date(dateString)
    return d.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    })
  }

  const formatShortDate = (dateString?: string | null) => {
    if (!dateString) return ""
    const d = new Date(dateString)
    const now = new Date()
    const diffDays = Math.floor((now.getTime() - d.getTime()) / (1000 * 60 * 60 * 24))
    if (diffDays === 0) {
      return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
    }
    if (diffDays === 1) return "Yesterday"
    if (diffDays < 7) {
      return d.toLocaleDateString([], { weekday: "short" })
    }
    return d.toLocaleDateString([], { month: "short", day: "numeric" })
  }

  return (
    <div className="space-y-6">
      {/* Toast Notification Banner */}
      {toast && (
        <div
          className={`fixed top-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-lg shadow-xl border backdrop-blur-md transition-all animate-in fade-in slide-in-from-top-4 ${
            toast.type === "success"
              ? "bg-emerald-950/90 border-emerald-500/40 text-emerald-200"
              : "bg-rose-950/90 border-rose-500/40 text-rose-200"
          }`}
        >
          {toast.type === "success" ? (
            <CheckCircle2 className="w-5 h-5 text-emerald-400 shrink-0" />
          ) : (
            <AlertCircle className="w-5 h-5 text-rose-400 shrink-0" />
          )}
          <span className="text-sm font-medium">{toast.text}</span>
          <button
            onClick={() => setToast(null)}
            className="ml-2 text-slate-400 hover:text-white transition-colors"
          >
            <X className="w-4 h-4" />
          </button>
        </div>
      )}

      {/* Header & Controls */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2.5">
            <div className="p-2 rounded-lg bg-blue-600/10 text-blue-500 border border-blue-500/20">
              <Inbox className="w-6 h-6" />
            </div>
            <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
              Support Inbox
            </h1>
            <Badge
              variant="outline"
              className="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20 gap-1.5 px-2.5 py-0.5 text-xs font-semibold"
            >
              <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
              Live Gateway
            </Badge>
          </div>
          <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Review incoming inquiries, maintain conversation history, and dispatch authenticated email replies.
          </p>
        </div>

        <div className="flex items-center gap-2.5 flex-wrap">
          <Link href="/settings/contact">
            <Button
              variant="outline"
              size="sm"
              className="border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 gap-1.5"
            >
              <PhoneCall className="w-3.5 h-3.5 text-indigo-500" />
              ⚙️ Edit Contact Phone &amp; Email
            </Button>
          </Link>
          <Button
            variant="outline"
            size="sm"
            onClick={() => fetchContacts(false)}
            disabled={isRefreshing || isLoading}
            className="border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
          >
            <RefreshCw className={`w-4 h-4 mr-2 ${isRefreshing ? "animate-spin" : ""}`} />
            Refresh Inbox
          </Button>
        </div>
      </div>

      {/* Summary KPI Badges / Cards */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
        <Card
          onClick={() => setStatusFilter("all")}
          className={`cursor-pointer transition-all border ${
            statusFilter === "all"
              ? "border-blue-500 bg-blue-50/50 dark:bg-blue-950/20 shadow-sm"
              : "border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700"
          }`}
        >
          <CardContent className="p-4 flex items-center justify-between">
            <div>
              <p className="text-xs font-medium text-slate-500 dark:text-slate-400">Total Inquiries</p>
              <h3 className="text-xl font-bold text-slate-900 dark:text-white mt-0.5">{counts.total}</h3>
            </div>
            <div className="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300">
              <Inbox className="w-4 h-4" />
            </div>
          </CardContent>
        </Card>

        <Card
          onClick={() => setStatusFilter("unread")}
          className={`cursor-pointer transition-all border ${
            statusFilter === "unread"
              ? "border-blue-500 bg-blue-50/50 dark:bg-blue-950/20 shadow-sm"
              : "border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700"
          }`}
        >
          <CardContent className="p-4 flex items-center justify-between">
            <div>
              <p className="text-xs font-medium text-blue-600 dark:text-blue-400">Unread</p>
              <h3 className="text-xl font-bold text-blue-600 dark:text-blue-400 mt-0.5">{counts.unread}</h3>
            </div>
            <div className="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-950/60 flex items-center justify-center text-blue-600 dark:text-blue-400">
              <AlertCircle className="w-4 h-4" />
            </div>
          </CardContent>
        </Card>

        <Card
          onClick={() => setStatusFilter("read")}
          className={`cursor-pointer transition-all border ${
            statusFilter === "read"
              ? "border-amber-500 bg-amber-50/50 dark:bg-amber-950/20 shadow-sm"
              : "border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700"
          }`}
        >
          <CardContent className="p-4 flex items-center justify-between">
            <div>
              <p className="text-xs font-medium text-amber-600 dark:text-amber-400">In Review</p>
              <h3 className="text-xl font-bold text-amber-600 dark:text-amber-400 mt-0.5">{counts.read}</h3>
            </div>
            <div className="w-9 h-9 rounded-full bg-amber-100 dark:bg-amber-950/60 flex items-center justify-center text-amber-600 dark:text-amber-400">
              <Clock className="w-4 h-4" />
            </div>
          </CardContent>
        </Card>

        <Card
          onClick={() => setStatusFilter("replied")}
          className={`cursor-pointer transition-all border ${
            statusFilter === "replied"
              ? "border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20 shadow-sm"
              : "border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700"
          }`}
        >
          <CardContent className="p-4 flex items-center justify-between">
            <div>
              <p className="text-xs font-medium text-emerald-600 dark:text-emerald-400">Dispatched</p>
              <h3 className="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">{counts.replied}</h3>
            </div>
            <div className="w-9 h-9 rounded-full bg-emerald-100 dark:bg-emerald-950/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
              <CheckCircle2 className="w-4 h-4" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Main Two-Column Split Pane */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-5 min-h-[680px]">
        {/* LEFT COLUMN: Inquiry List & Filters (5 cols) */}
        <div className="lg:col-span-5 flex flex-col gap-3">
          {/* Search & Tabs Card */}
          <Card className="border-slate-200 dark:border-slate-800">
            <CardContent className="p-3.5 space-y-3">
              {/* Search Bar */}
              <div className="relative">
                <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <Input
                  type="text"
                  placeholder="Search sender, email, subject, or message..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-9 pr-8 text-xs bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800"
                />
                {searchQuery && (
                  <button
                    onClick={() => setSearchQuery("")}
                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                  >
                    <X className="w-3.5 h-3.5" />
                  </button>
                )}
              </div>

              {/* Status Filter Badges */}
              <div className="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                <button
                  type="button"
                  onClick={() => setStatusFilter("all")}
                  className={`px-3 py-1.5 rounded-full font-medium transition-colors shrink-0 ${
                    statusFilter === "all"
                      ? "bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-sm"
                      : "bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700"
                  }`}
                >
                  All ({counts.total})
                </button>
                <button
                  type="button"
                  onClick={() => setStatusFilter("unread")}
                  className={`px-3 py-1.5 rounded-full font-medium transition-colors shrink-0 flex items-center gap-1.5 ${
                    statusFilter === "unread"
                      ? "bg-blue-600 text-white shadow-sm"
                      : "bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700"
                  }`}
                >
                  <span className="w-1.5 h-1.5 rounded-full bg-blue-400" />
                  Unread ({counts.unread})
                </button>
                <button
                  type="button"
                  onClick={() => setStatusFilter("read")}
                  className={`px-3 py-1.5 rounded-full font-medium transition-colors shrink-0 ${
                    statusFilter === "read"
                      ? "bg-amber-600 text-white shadow-sm"
                      : "bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700"
                  }`}
                >
                  Read ({counts.read})
                </button>
                <button
                  type="button"
                  onClick={() => setStatusFilter("replied")}
                  className={`px-3 py-1.5 rounded-full font-medium transition-colors shrink-0 ${
                    statusFilter === "replied"
                      ? "bg-emerald-600 text-white shadow-sm"
                      : "bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700"
                  }`}
                >
                  Replied ({counts.replied})
                </button>
              </div>
            </CardContent>
          </Card>

          {/* Inquiries List View */}
          <div className="flex-1 overflow-y-auto space-y-2 max-h-[640px] pr-1">
            {isLoading ? (
              <div className="p-8 text-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl">
                <Loader2 className="w-6 h-6 animate-spin text-blue-500 mx-auto mb-2" />
                <p className="text-xs text-slate-500 dark:text-slate-400">Loading inquiries...</p>
              </div>
            ) : contacts.length === 0 ? (
              <div className="p-10 text-center bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 rounded-xl space-y-2">
                <div className="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mx-auto">
                  <Mail className="w-6 h-6" />
                </div>
                <h4 className="text-sm font-semibold text-slate-700 dark:text-slate-200">No inquiries found</h4>
                <p className="text-xs text-slate-500 dark:text-slate-400 max-w-xs mx-auto">
                  {searchQuery
                    ? `No messages match your search "${searchQuery}".`
                    : `No inquiries match the current filter.`}
                </p>
              </div>
            ) : (
              contacts.map((item) => {
                const isSelected = item.id === selectedId
                const isUnread = item.status === "unread"
                const isReplied = item.status === "replied"

                return (
                  <div
                    key={item.id}
                    onClick={() => setSelectedId(item.id)}
                    className={`group relative p-4 rounded-xl border cursor-pointer transition-all text-left ${
                      isSelected
                        ? "bg-blue-50/70 dark:bg-blue-950/30 border-blue-500/60 shadow-sm ring-1 ring-blue-500/20"
                        : isUnread
                        ? "bg-white dark:bg-slate-900 border-blue-200 dark:border-blue-900/40 hover:border-blue-300 dark:hover:border-blue-800"
                        : "bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700"
                    }`}
                  >
                    <div className="flex items-start justify-between gap-2 mb-1.5">
                      <div className="flex items-center gap-2 min-w-0">
                        {isUnread && (
                          <span
                            className="w-2.5 h-2.5 rounded-full bg-blue-600 ring-4 ring-blue-100 dark:ring-blue-950 shrink-0"
                            title="Unread inquiry"
                          />
                        )}
                        <h4
                          className={`text-sm truncate ${
                            isUnread
                              ? "font-bold text-slate-900 dark:text-white"
                              : "font-semibold text-slate-800 dark:text-slate-200"
                          }`}
                        >
                          {item.name}
                        </h4>
                      </div>

                      <span className="text-[11px] font-medium text-slate-400 shrink-0">
                        {formatShortDate(item.created_at)}
                      </span>
                    </div>

                    <p
                      className={`text-xs truncate mb-1.5 ${
                        isUnread
                          ? "font-semibold text-slate-800 dark:text-slate-100"
                          : "font-medium text-slate-600 dark:text-slate-300"
                      }`}
                    >
                      {item.subject || "General Inquiry"}
                    </p>

                    <p className="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed">
                      {item.message}
                    </p>

                    <div className="mt-2.5 flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800/60">
                      <span className="text-[11px] text-slate-400 font-mono truncate max-w-[170px]">
                        {item.email}
                      </span>

                      {isReplied ? (
                        <Badge
                          variant="outline"
                          className="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30 text-[10px] py-0 px-2 font-medium flex items-center gap-1"
                        >
                          <Check className="w-2.5 h-2.5" />
                          Replied
                        </Badge>
                      ) : isUnread ? (
                        <Badge
                          variant="outline"
                          className="bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/30 text-[10px] py-0 px-2 font-medium"
                        >
                          Unread
                        </Badge>
                      ) : (
                        <Badge
                          variant="outline"
                          className="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700 text-[10px] py-0 px-2 font-medium"
                        >
                          Read
                        </Badge>
                      )}
                    </div>
                  </div>
                )
              })
            )}
          </div>
        </div>

        {/* RIGHT COLUMN: Reading & Reply Workspace (7 cols) */}
        <div className="lg:col-span-7 flex flex-col">
          {activeContact ? (
            <Card className="border-slate-200 dark:border-slate-800 flex-1 flex flex-col shadow-sm">
              {/* Header Bar */}
              <CardHeader className="p-5 pb-4 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/40 dark:bg-slate-900/40">
                <div className="min-w-0">
                  <div className="flex items-center gap-2.5 flex-wrap">
                    <h2 className="text-lg font-bold text-slate-900 dark:text-white truncate">
                      {activeContact.subject || "General Inquiry"}
                    </h2>
                    {activeContact.status === "replied" ? (
                      <Badge className="bg-emerald-600 text-white gap-1 text-xs">
                        <Check className="w-3 h-3" /> Replied
                      </Badge>
                    ) : activeContact.status === "unread" ? (
                      <Badge className="bg-blue-600 text-white text-xs">Unread</Badge>
                    ) : (
                      <Badge variant="outline" className="text-slate-600 dark:text-slate-400 text-xs">
                        Read
                      </Badge>
                    )}
                  </div>
                  <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Inquiry Reference #{activeContact.id} &bull; Received on {formatDate(activeContact.created_at)}
                  </p>
                </div>

                <div className="flex items-center gap-2 shrink-0">
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => handleDeleteContact(activeContact.id)}
                    className="text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors h-8 px-2.5 text-xs"
                  >
                    <Trash2 className="w-3.5 h-3.5 mr-1" />
                    Delete
                  </Button>
                </div>
              </CardHeader>

              {/* Inquiry Content & Sender Details */}
              <CardContent className="p-6 space-y-6 flex-1 overflow-y-auto">
                {/* Sender Profile Box */}
                <div className="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-900/80 rounded-xl border border-slate-200/80 dark:border-slate-800">
                  <div className="flex items-center gap-3 min-w-0">
                    <div className="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold flex items-center justify-center text-sm shadow-sm shrink-0">
                      {activeContact.name.slice(0, 2).toUpperCase()}
                    </div>
                    <div className="min-w-0">
                      <h4 className="text-sm font-semibold text-slate-900 dark:text-white truncate">
                        {activeContact.name}
                      </h4>
                      <div className="flex items-center gap-2 mt-0.5">
                        <span className="text-xs text-slate-500 dark:text-slate-400 font-mono truncate">
                          {activeContact.email}
                        </span>
                        <button
                          type="button"
                          onClick={() => handleCopyEmail(activeContact.email)}
                          className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                          title="Copy email address"
                        >
                          {copiedEmail ? (
                            <Check className="w-3.5 h-3.5 text-emerald-500" />
                          ) : (
                            <Copy className="w-3.5 h-3.5" />
                          )}
                        </button>
                        <a
                          href={`mailto:${activeContact.email}?subject=Re: ${encodeURIComponent(
                            activeContact.subject || "Asteghi Support"
                          )}`}
                          className="text-slate-400 hover:text-blue-500 transition-colors"
                          title="Open mail client"
                        >
                          <ExternalLink className="w-3.5 h-3.5" />
                        </a>
                      </div>
                    </div>
                  </div>

                  <div className="text-right hidden sm:block">
                    <p className="text-[11px] text-slate-400 font-medium">Channel</p>
                    <p className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                      Web Contact Form
                    </p>
                  </div>
                </div>

                {/* Original Message Card */}
                <div className="space-y-2">
                  <div className="flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                    <MessageSquare className="w-4 h-4 text-blue-500" />
                    Original Inquiry Message
                  </div>
                  <div className="p-4 rounded-xl bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 text-sm text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-wrap font-sans">
                    {activeContact.message}
                  </div>
                </div>

                {/* Dispatched Reply Box (If already replied) */}
                {activeContact.reply_message && (
                  <div className="p-5 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-500/30 space-y-2.5">
                    <div className="flex items-center justify-between flex-wrap gap-2">
                      <div className="flex items-center gap-2 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                        <CheckCircle2 className="w-4 h-4 text-emerald-500" />
                        Official Reply Dispatched to {activeContact.email}
                      </div>
                      <span className="text-[11px] font-medium text-emerald-600 dark:text-emerald-400">
                        Sent on {formatDate(activeContact.replied_at)}
                      </span>
                    </div>

                    <div className="p-3.5 rounded-lg bg-white dark:bg-slate-900 border border-emerald-500/20 text-xs text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-wrap">
                      {activeContact.reply_message}
                    </div>

                    <p className="text-[11px] text-emerald-600 dark:text-emerald-400 italic">
                      &bull; Dispatched via SMTP Mail Gateway from info@asteghi.com
                    </p>
                  </div>
                )}

                {/* Reply Composer Form */}
                <form onSubmit={handleDispatchReply} className="space-y-3 pt-2">
                  <div className="flex items-center justify-between flex-wrap gap-2">
                    <div className="flex items-center gap-1.5 text-xs font-semibold text-slate-800 dark:text-slate-200">
                      <Mail className="w-4 h-4 text-indigo-500" />
                      {activeContact.reply_message ? "Send Another Response" : "Compose Official Response"}
                    </div>

                    {/* Quick Response Templates */}
                    <div className="flex items-center gap-1.5 flex-wrap">
                      <span className="text-[11px] text-slate-400 font-medium">Quick Templates:</span>
                      <button
                        type="button"
                        onClick={() =>
                          applyTemplate(
                            "Hello {name},\n\nThank you for reaching out to Asteghi Support regarding {subject}.\n\nYour account subscription has been verified and updated. You can now access all premium resources and exam questions without restriction.\n\nPlease log out and log back in if you encounter any caching issues.\n\nBest regards,\nAsteghi Support Team"
                          )
                        }
                        className="px-2 py-0.5 rounded text-[10px] bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors"
                      >
                        Subscription Verified
                      </button>
                      <button
                        type="button"
                        onClick={() =>
                          applyTemplate(
                            "Hello {name},\n\nThank you for reaching out regarding {subject}.\n\nWe have reviewed the payment details you provided and activated your account. Your transaction receipt has been verified.\n\nEnjoy your learning journey on Asteghi!\n\nBest regards,\nAsteghi Support Team"
                          )
                        }
                        className="px-2 py-0.5 rounded text-[10px] bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors"
                      >
                        Payment Confirmed
                      </button>
                      <button
                        type="button"
                        onClick={() =>
                          applyTemplate(
                            "Hello {name},\n\nThank you for your inquiry regarding {subject}.\n\nCould you please provide additional details or a screenshot so our technical team can assist you more effectively?\n\nLooking forward to your response.\n\nBest regards,\nAsteghi Support Team"
                          )
                        }
                        className="px-2 py-0.5 rounded text-[10px] bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors"
                      >
                        Need Details
                      </button>
                    </div>
                  </div>

                  <div className="relative">
                    <Textarea
                      placeholder={`Type your official response to ${activeContact.name}...\nThis will be dispatched directly to their email inbox (${activeContact.email}).`}
                      value={replyText}
                      onChange={(e) => setReplyText(e.target.value)}
                      rows={6}
                      className="resize-y text-xs leading-relaxed bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 focus:border-indigo-500 rounded-xl"
                    />
                  </div>

                  <div className="flex items-center justify-between flex-wrap gap-3 pt-1">
                    <p className="text-[11px] text-slate-400 flex items-center gap-1">
                      <ShieldCheck className="w-3.5 h-3.5 text-blue-500" />
                      Recipient: <span className="font-mono text-slate-600 dark:text-slate-300">{activeContact.email}</span>
                    </p>

                    <div className="flex items-center gap-2">
                      {replyText && (
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          onClick={() => setReplyText("")}
                          disabled={isDispatching}
                          className="h-8 text-xs text-slate-500"
                        >
                          Clear
                        </Button>
                      )}

                      <Button
                        type="submit"
                        disabled={isDispatching || !replyText.trim()}
                        className="h-9 px-4 text-xs font-semibold bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-md shadow-blue-500/20 gap-2 transition-all"
                      >
                        {isDispatching ? (
                          <>
                            <Loader2 className="w-4 h-4 animate-spin" />
                            Dispatching Email...
                          </>
                        ) : (
                          <>
                            <Send className="w-3.5 h-3.5" />
                            Dispatch Official Reply
                          </>
                        )}
                      </Button>
                    </div>
                  </div>
                </form>
              </CardContent>
            </Card>
          ) : (
            <Card className="border-slate-200 dark:border-slate-800 flex-1 flex items-center justify-center p-12 text-center bg-slate-50/50 dark:bg-slate-900/30">
              <div className="max-w-sm space-y-3">
                <div className="w-14 h-14 rounded-2xl bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto shadow-inner">
                  <Mail className="w-7 h-7" />
                </div>
                <h3 className="text-base font-bold text-slate-800 dark:text-slate-100">
                  Select an Inquiry to Respond
                </h3>
                <p className="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                  Choose a contact inquiry from the left pane to view the sender details, read the full message, and dispatch an official email response.
                </p>
              </div>
            </Card>
          )}
        </div>
      </div>
    </div>
  )
}
