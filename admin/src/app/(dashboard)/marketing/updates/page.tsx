"use client"

import React, { useState, useEffect, useCallback, useRef } from "react"
import { apiClient } from "@/lib/api-client"
import { LatestUpdateItem } from "@/types/cms"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import {
  BellRing,
  PlusCircle,
  Clock,
  Globe,
  Trash2,
  CheckCircle2,
  AlertCircle,
  Eye,
  EyeOff,
  Image as ImageIcon,
  Upload,
  RefreshCw,
  Loader2,
  Search,
  Edit3,
  Calendar,
  Sparkles,
  Layers,
  X,
  Scissors,
} from "lucide-react"
import { ImageCropperDialog } from "@/components/marketing/image-cropper-dialog"

export default function LatestUpdatesPage() {
  // Real backend data state
  const [updates, setUpdates] = useState<LatestUpdateItem[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [isRefreshing, setIsRefreshing] = useState(false)
  const [fetchError, setFetchError] = useState<string | null>(null)

  // Filters & Views
  const [searchQuery, setSearchQuery] = useState("")
  const [statusFilter, setStatusFilter] = useState<"all" | "active" | "draft">("all")
  const [languageView, setLanguageView] = useState<"en" | "am" | "both">("both")

  // Feedback Toast
  const [toast, setToast] = useState<{ type: "success" | "error"; message: string } | null>(null)
  const showToast = (type: "success" | "error", message: string) => {
    setToast({ type, message })
    setTimeout(() => {
      setToast((prev) => (prev?.message === message ? null : prev))
    }, 4500)
  }

  // Create Modal State
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [createTitleEn, setCreateTitleEn] = useState("")
  const [createTitleAm, setCreateTitleAm] = useState("")
  const [createDescEn, setCreateDescEn] = useState("")
  const [createDescAm, setCreateDescAm] = useState("")
  const [createIsActive, setCreateIsActive] = useState(true)
  const [createPublishedAt, setCreatePublishedAt] = useState("")
  const [createImageFile, setCreateImageFile] = useState<File | null>(null)
  const [createImagePreview, setCreateImagePreview] = useState<string | null>(null)
  const [createRawImageSrc, setCreateRawImageSrc] = useState<string | null>(null)
  const [isSubmittingCreate, setIsSubmittingCreate] = useState(false)
  const createFileInputRef = useRef<HTMLInputElement>(null)

  // Edit Modal State
  const [editingItem, setEditingItem] = useState<LatestUpdateItem | null>(null)
  const [editTitleEn, setEditTitleEn] = useState("")
  const [editTitleAm, setEditTitleAm] = useState("")
  const [editDescEn, setEditDescEn] = useState("")
  const [editDescAm, setEditDescAm] = useState("")
  const [editIsActive, setEditIsActive] = useState(true)
  const [editPublishedAt, setEditPublishedAt] = useState("")
  const [editImageFile, setEditImageFile] = useState<File | null>(null)
  const [editImagePreview, setEditImagePreview] = useState<string | null>(null)
  const [editRawImageSrc, setEditRawImageSrc] = useState<string | null>(null)
  const [editRemoveImage, setEditRemoveImage] = useState(false)
  const [isSubmittingEdit, setIsSubmittingEdit] = useState(false)
  const editFileInputRef = useRef<HTMLInputElement>(null)

  // Instagram-style Image Cropper State
  const [isCropperOpen, setIsCropperOpen] = useState(false)
  const [cropImageSrc, setCropImageSrc] = useState<string | null>(null)
  const [cropTarget, setCropTarget] = useState<"create" | "edit">("create")

  // Delete Modal State
  const [deletingItem, setDeletingItem] = useState<LatestUpdateItem | null>(null)
  const [isSubmittingDelete, setIsSubmittingDelete] = useState(false)

  // Quick Status Toggle Loading Tracker (store id being toggled)
  const [togglingId, setTogglingId] = useState<number | null>(null)

  // Fetch updates from live backend
  const fetchUpdates = useCallback(async (isRefresh = false) => {
    if (isRefresh) {
      setIsRefreshing(true)
    } else {
      setIsLoading(true)
    }
    setFetchError(null)

    try {
      // First attempt to get the full admin listing
      let list: LatestUpdateItem[] = []
      try {
        const adminRes = await apiClient.get<{ status: string; data: LatestUpdateItem[] }>("/admin/latest-updates")
        if (adminRes.data?.status === "success" && Array.isArray(adminRes.data.data)) {
          list = adminRes.data.data
        }
      } catch {
        // Fallback to public listing if admin endpoint is not yet routed
        const publicRes = await apiClient.get<{ status: string; data: LatestUpdateItem[] }>("/latest-updates")
        if (publicRes.data?.status === "success" && Array.isArray(publicRes.data.data)) {
          list = publicRes.data.data
        }
      }

      // If we got items but Amharic fields might be localized, optionally fetch Amharic feed to enrich
      if (list.length > 0 && !list[0].title_am) {
        try {
          const amRes = await apiClient.get<{ status: string; data: Array<{ id: number; title: string; description: string }> }>("/latest-updates?locale=am")
          if (amRes.data?.status === "success" && Array.isArray(amRes.data.data)) {
            const amMap = new Map<number, { title: string; description: string }>()
            amRes.data.data.forEach((item) => {
              amMap.set(item.id, { title: item.title, description: item.description })
            })
            list = list.map((item) => {
              const amData = amMap.get(item.id)
              return {
                ...item,
                title_am: item.title_am || amData?.title || null,
                description_am: item.description_am || amData?.description || null,
              }
            })
          }
        } catch {
          // Keep existing list if Amharic endpoint enrich fails
        }
      }

      setUpdates(list)
    } catch (err: any) {
      const errorMsg =
        err.response?.data?.message ||
        err.message ||
        "Could not load latest updates from backend API."
      setFetchError(errorMsg)
      setUpdates([])
    } finally {
      setIsLoading(false)
      setIsRefreshing(false)
    }
  }, [])

  useEffect(() => {
    fetchUpdates()
  }, [fetchUpdates])

  // Image Selection & Crop Handlers
  const handleCreateImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      const tempUrl = URL.createObjectURL(file)
      setCropImageSrc(tempUrl)
      setCreateRawImageSrc(tempUrl)
      setCropTarget("create")
      setIsCropperOpen(true)
    }
  }

  const handleEditImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      const tempUrl = URL.createObjectURL(file)
      setCropImageSrc(tempUrl)
      setEditRawImageSrc(tempUrl)
      setCropTarget("edit")
      setIsCropperOpen(true)
    }
  }

  const handleCropComplete = (croppedFile: File, previewUrl: string) => {
    if (cropTarget === "create") {
      setCreateImageFile(croppedFile)
      setCreateImagePreview(previewUrl)
    } else {
      setEditImageFile(croppedFile)
      setEditImagePreview(previewUrl)
      setEditRemoveImage(false)
    }
  }

  const handleRecrop = (target: "create" | "edit") => {
    const src =
      target === "create"
        ? createRawImageSrc || createImagePreview
        : editRawImageSrc || editImagePreview
    if (src) {
      setCropImageSrc(src)
      setCropTarget(target)
      setIsCropperOpen(true)
    }
  }

  const clearCreateImage = () => {
    setCreateImageFile(null)
    setCreateRawImageSrc(null)
    if (createImagePreview && createImagePreview.startsWith("blob:")) {
      URL.revokeObjectURL(createImagePreview)
    }
    setCreateImagePreview(null)
    if (createFileInputRef.current) {
      createFileInputRef.current.value = ""
    }
  }

  const clearEditImage = () => {
    setEditImageFile(null)
    setEditRawImageSrc(null)
    if (editImagePreview && editImagePreview.startsWith("blob:")) {
      URL.revokeObjectURL(editImagePreview)
    }
    setEditImagePreview(null)
    setEditRemoveImage(true)
    if (editFileInputRef.current) {
      editFileInputRef.current.value = ""
    }
  }

  // Open Edit Modal
  const handleOpenEdit = (item: LatestUpdateItem) => {
    setEditingItem(item)
    setEditTitleEn(item.title_en || item.title || "")
    setEditTitleAm(item.title_am || "")
    setEditDescEn(item.description_en || item.description || "")
    setEditDescAm(item.description_am || "")
    setEditIsActive(Boolean(item.is_active))
    setEditPublishedAt(
      item.published_at ? new Date(item.published_at).toISOString().slice(0, 16) : ""
    )
    setEditImageFile(null)
    setEditImagePreview(item.image_url || null)
    setEditRawImageSrc(item.image_url || null)
    setEditRemoveImage(false)
  }

  // CREATE UPDATE (POST /api/latest-updates)
  const handleCreateSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!createTitleEn.trim() || !createDescEn.trim()) {
      showToast("error", "English title and description are required.")
      return
    }

    setIsSubmittingCreate(true)
    try {
      const formData = new FormData()
      formData.append("title", createTitleEn.trim())
      formData.append("title_en", createTitleEn.trim())
      if (createTitleAm.trim()) {
        formData.append("title_am", createTitleAm.trim())
      }
      formData.append("description", createDescEn.trim())
      formData.append("description_en", createDescEn.trim())
      if (createDescAm.trim()) {
        formData.append("description_am", createDescAm.trim())
      }
      formData.append("is_active", createIsActive ? "1" : "0")
      if (createPublishedAt) {
        formData.append("published_at", new Date(createPublishedAt).toISOString())
      }
      if (createImageFile) {
        formData.append("image", createImageFile)
      }

      const res = await apiClient.post("/latest-updates", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      })

      if (res.data?.status === "success" || res.status === 201 || res.status === 200) {
        showToast("success", "Latest update published successfully!")
        setIsCreateOpen(false)
        setCreateTitleEn("")
        setCreateTitleAm("")
        setCreateDescEn("")
        setCreateDescAm("")
        setCreateIsActive(true)
        setCreatePublishedAt("")
        clearCreateImage()
        await fetchUpdates(true)
      } else {
        showToast("error", res.data?.message || "Failed to create update.")
      }
    } catch (err: any) {
      showToast("error", err.response?.data?.message || "Failed to create latest update.")
    } finally {
      setIsSubmittingCreate(false)
    }
  }

  // EDIT UPDATE (POST /api/latest-updates/{id})
  const handleEditSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!editingItem) return
    if (!editTitleEn.trim() || !editDescEn.trim()) {
      showToast("error", "English title and description are required.")
      return
    }

    setIsSubmittingEdit(true)
    try {
      const formData = new FormData()
      formData.append("title", editTitleEn.trim())
      formData.append("title_en", editTitleEn.trim())
      formData.append("title_am", editTitleAm.trim())
      formData.append("description", editDescEn.trim())
      formData.append("description_en", editDescEn.trim())
      formData.append("description_am", editDescAm.trim())
      formData.append("is_active", editIsActive ? "1" : "0")
      if (editPublishedAt) {
        formData.append("published_at", new Date(editPublishedAt).toISOString())
      }
      if (editImageFile) {
        formData.append("image", editImageFile)
      } else if (editRemoveImage) {
        formData.append("remove_image", "1")
      }

      const res = await apiClient.post(`/latest-updates/${editingItem.id}`, formData, {
        headers: { "Content-Type": "multipart/form-data" },
      })

      if (res.data?.status === "success" || res.status === 200) {
        showToast("success", "Update saved successfully!")
        setEditingItem(null)
        clearEditImage()
        await fetchUpdates(true)
      } else {
        showToast("error", res.data?.message || "Failed to update.")
      }
    } catch (err: any) {
      showToast("error", err.response?.data?.message || "Failed to save latest update.")
    } finally {
      setIsSubmittingEdit(false)
    }
  }

  // TOGGLE ACTIVE STATUS (QUICK PUBLISH / UNPUBLISH)
  const handleToggleActive = async (item: LatestUpdateItem) => {
    setTogglingId(item.id)
    try {
      const nextActive = !item.is_active
      const formData = new FormData()
      formData.append("is_active", nextActive ? "1" : "0")

      const res = await apiClient.post(`/latest-updates/${item.id}`, formData, {
        headers: { "Content-Type": "multipart/form-data" },
      })

      if (res.data?.status === "success" || res.status === 200) {
        showToast(
          "success",
          nextActive ? "Announcement published to live feeds." : "Announcement hidden (drafted)."
        )
        // Optimistic local state update
        setUpdates((prev) =>
          prev.map((u) => (u.id === item.id ? { ...u, is_active: nextActive } : u))
        )
      } else {
        showToast("error", res.data?.message || "Failed to toggle status.")
      }
    } catch (err: any) {
      showToast("error", err.response?.data?.message || "Failed to update status.")
    } finally {
      setTogglingId(null)
    }
  }

  // DELETE UPDATE (DELETE /api/latest-updates/{id})
  const handleDeleteSubmit = async () => {
    if (!deletingItem) return

    setIsSubmittingDelete(true)
    try {
      const res = await apiClient.delete(`/latest-updates/${deletingItem.id}`)
      if (res.data?.status === "success" || res.status === 200) {
        showToast("success", "Latest update removed successfully.")
        setUpdates((prev) => prev.filter((u) => u.id !== deletingItem.id))
        setDeletingItem(null)
      } else {
        showToast("error", res.data?.message || "Failed to delete update.")
      }
    } catch (err: any) {
      showToast("error", err.response?.data?.message || "Failed to delete latest update.")
    } finally {
      setIsSubmittingDelete(false)
    }
  }

  // Filter list by search query & status
  const filteredUpdates = updates.filter((item) => {
    const q = searchQuery.toLowerCase().trim()
    const matchesSearch =
      !q ||
      item.title?.toLowerCase().includes(q) ||
      item.title_en?.toLowerCase().includes(q) ||
      item.title_am?.toLowerCase().includes(q) ||
      item.description?.toLowerCase().includes(q) ||
      item.description_en?.toLowerCase().includes(q) ||
      item.description_am?.toLowerCase().includes(q)

    const matchesStatus =
      statusFilter === "all" ||
      (statusFilter === "active" && item.is_active) ||
      (statusFilter === "draft" && !item.is_active)

    return matchesSearch && matchesStatus
  })

  // KPI calculations
  const totalCount = updates.length
  const activeCount = updates.filter((u) => u.is_active).length
  const draftCount = updates.filter((u) => !u.is_active).length
  const withImageCount = updates.filter((u) => Boolean(u.image_url || u.image_path)).length

  return (
    <div className="space-y-8 pb-12">
      {/* Toast Notification */}
      {toast && (
        <div
          className={`fixed top-5 right-5 z-50 flex items-center gap-3 rounded-lg border px-4 py-3 shadow-lg transition-all animate-in slide-in-from-top-2 max-w-md ${
            toast.type === "success"
              ? "border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/90 dark:text-emerald-200"
              : "border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900 dark:bg-rose-950/90 dark:text-rose-200"
          }`}
        >
          {toast.type === "success" ? (
            <CheckCircle2 className="h-5 w-5 text-emerald-600 flex-shrink-0" />
          ) : (
            <AlertCircle className="h-5 w-5 text-rose-600 flex-shrink-0" />
          )}
          <div className="text-xs font-medium leading-relaxed">{toast.message}</div>
          <button
            type="button"
            onClick={() => setToast(null)}
            className="ml-auto text-xs font-bold opacity-60 hover:opacity-100"
          >
            ✕
          </button>
        </div>
      )}

      {/* Header Banner */}
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 sm:text-3xl">
            Latest Updates
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
            Publish platform announcements, curriculum releases, and system updates with cover images for students and tutors.
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2.5">
          <Button
            variant="outline"
            size="sm"
            onClick={() => fetchUpdates(true)}
            disabled={isRefreshing}
            className="border-slate-200 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900"
          >
            <RefreshCw className={`mr-2 h-3.5 w-3.5 ${isRefreshing ? "animate-spin text-blue-600" : ""}`} />
            Refresh
          </Button>

          <Button
            className="bg-blue-600 font-semibold text-white shadow-md shadow-blue-600/20 hover:bg-blue-500"
            onClick={() => {
              setCreateTitleEn("")
              setCreateTitleAm("")
              setCreateDescEn("")
              setCreateDescAm("")
              setCreateIsActive(true)
              setCreatePublishedAt(new Date().toISOString().slice(0, 16))
              clearCreateImage()
              setIsCreateOpen(true)
            }}
          >
            <PlusCircle className="mr-2 h-4 w-4" />
            New Update
          </Button>
        </div>
      </div>

      {/* Backend Connection Alert */}
      {fetchError && (
        <div className="flex items-center justify-between gap-3 rounded-lg border border-rose-200 bg-rose-50 p-4 text-xs font-medium text-rose-900 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-200">
          <div className="flex items-center gap-2">
            <AlertCircle className="h-5 w-5 text-rose-600 flex-shrink-0" />
            <div>
              <span className="font-bold">Backend Communication Notice:</span> {fetchError}
            </div>
          </div>
          <Button
            size="sm"
            variant="outline"
            onClick={() => fetchUpdates(false)}
            className="border-rose-300 text-rose-800 hover:bg-rose-100 dark:border-rose-800 dark:text-rose-200"
          >
            Retry
          </Button>
        </div>
      )}

      {/* KPI Overview Cards */}
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <span className="text-xs font-medium text-slate-500 dark:text-slate-400">Total Updates</span>
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                <Layers className="h-4 w-4" />
              </div>
            </div>
            <div className="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-50">
              {isLoading ? "..." : totalCount}
            </div>
            <p className="mt-0.5 text-[11px] text-slate-400">Database records</p>
          </CardContent>
        </Card>

        <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <span className="text-xs font-medium text-slate-500 dark:text-slate-400">Live in Apps</span>
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                <Eye className="h-4 w-4" />
              </div>
            </div>
            <div className="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
              {isLoading ? "..." : activeCount}
            </div>
            <p className="mt-0.5 text-[11px] text-slate-400">Visible to learners</p>
          </CardContent>
        </Card>

        <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <span className="text-xs font-medium text-slate-500 dark:text-slate-400">Drafts / Inactive</span>
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                <EyeOff className="h-4 w-4" />
              </div>
            </div>
            <div className="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">
              {isLoading ? "..." : draftCount}
            </div>
            <p className="mt-0.5 text-[11px] text-slate-400">Hidden from feed</p>
          </CardContent>
        </Card>

        <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <span className="text-xs font-medium text-slate-500 dark:text-slate-400">Cover Pictures</span>
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                <ImageIcon className="h-4 w-4" />
              </div>
            </div>
            <div className="mt-2 text-2xl font-bold text-purple-600 dark:text-purple-400">
              {isLoading ? "..." : withImageCount}
            </div>
            <p className="mt-0.5 text-[11px] text-slate-400">With visual banner</p>
          </CardContent>
        </Card>
      </div>

      {/* Control Bar: Search, Status Filter & Language Toggle */}
      <div className="flex flex-col sm:flex-row items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div className="relative w-full sm:w-80">
          <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" />
          <Input
            placeholder="Search updates by title or content..."
            className="pl-8 text-xs h-8"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
        </div>

        <div className="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
          {/* Status Filter */}
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value as any)}
            className="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 h-8"
          >
            <option value="all">All Statuses ({updates.length})</option>
            <option value="active">Live in App ({activeCount})</option>
            <option value="draft">Drafts ({draftCount})</option>
          </select>

          {/* Bilingual View Toggle */}
          <div className="inline-flex rounded-md border border-slate-200 bg-slate-100 p-0.5 text-xs dark:border-slate-800 dark:bg-slate-900">
            <button
              type="button"
              onClick={() => setLanguageView("both")}
              className={`rounded px-2.5 py-1 text-xs font-semibold transition-all ${
                languageView === "both"
                  ? "bg-white text-blue-600 shadow-sm dark:bg-slate-800 dark:text-blue-400"
                  : "text-slate-600 hover:text-slate-900 dark:text-slate-400"
              }`}
            >
              Bilingual
            </button>
            <button
              type="button"
              onClick={() => setLanguageView("en")}
              className={`rounded px-2.5 py-1 text-xs font-semibold transition-all ${
                languageView === "en"
                  ? "bg-white text-blue-600 shadow-sm dark:bg-slate-800 dark:text-blue-400"
                  : "text-slate-600 hover:text-slate-900 dark:text-slate-400"
              }`}
            >
              English
            </button>
            <button
              type="button"
              onClick={() => setLanguageView("am")}
              className={`rounded px-2.5 py-1 text-xs font-semibold transition-all ${
                languageView === "am"
                  ? "bg-white text-blue-600 shadow-sm dark:bg-slate-800 dark:text-blue-400"
                  : "text-slate-600 hover:text-slate-900 dark:text-slate-400"
              }`}
            >
              አማርኛ
            </button>
          </div>
        </div>
      </div>

      {/* Updates Cards Grid */}
      {isLoading ? (
        <div className="flex flex-col items-center justify-center py-20 text-slate-400">
          <Loader2 className="h-8 w-8 animate-spin text-blue-600 mb-2" />
          <p className="text-xs font-medium">Synchronizing latest updates with backend...</p>
        </div>
      ) : filteredUpdates.length === 0 ? (
        <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 py-16 text-center dark:border-slate-800">
          <div className="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-3">
            <BellRing className="h-6 w-6" />
          </div>
          <h3 className="text-sm font-semibold text-slate-800 dark:text-slate-200">
            {updates.length === 0 ? "No updates published yet" : "No matching updates found"}
          </h3>
          <p className="text-xs text-slate-500 max-w-sm mt-1 mb-4">
            {updates.length === 0
              ? "Click 'New Update' above to create your first announcement post with a cover image."
              : "Try adjusting your search terms or status filter."}
          </p>
          {updates.length === 0 && (
            <Button
              size="sm"
              className="bg-blue-600 text-white hover:bg-blue-500"
              onClick={() => {
                setCreateTitleEn("")
                setCreateTitleAm("")
                setCreateDescEn("")
                setCreateDescAm("")
                setCreateIsActive(true)
                setCreatePublishedAt(new Date().toISOString().slice(0, 16))
                clearCreateImage()
                setIsCreateOpen(true)
              }}
            >
              <PlusCircle className="mr-2 h-4 w-4" />
              Create First Update
            </Button>
          )}
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          {filteredUpdates.map((item) => {
            const isToggling = togglingId === item.id

            return (
              <Card
                key={item.id}
                className={`overflow-hidden border transition-all duration-200 hover:shadow-md flex flex-col justify-between ${
                  item.is_active
                    ? "border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950"
                    : "border-dashed border-slate-300 bg-slate-50/70 opacity-85 dark:border-slate-800 dark:bg-slate-900/30"
                }`}
              >
                <div>
                  {/* Visual Picture Banner */}
                  <div className="relative w-full h-44 bg-slate-100 dark:bg-slate-900 overflow-hidden group">
                    {item.image_url ? (
                      <img
                        src={item.image_url}
                        alt={item.title || "Update Cover"}
                        className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                        onError={(e) => {
                          // Fallback to placeholder graphic if remote asset is unavailable
                          e.currentTarget.style.display = "none"
                          const parent = e.currentTarget.parentElement
                          if (parent) {
                            parent.classList.add(
                              "bg-gradient-to-br",
                              "from-blue-600",
                              "to-indigo-900",
                              "flex",
                              "items-center",
                              "justify-center"
                            )
                            const placeholder = document.createElement("div")
                            placeholder.className = "text-white text-center p-4"
                            placeholder.innerHTML = `<span class="font-bold text-sm block">Asteghi Learning</span><span class="text-[11px] opacity-80">Platform Announcement</span>`
                            parent.appendChild(placeholder)
                          }
                        }}
                      />
                    ) : (
                      <div className="w-full h-full bg-gradient-to-br from-slate-700 via-slate-800 to-slate-900 flex flex-col items-center justify-center p-4 text-center">
                        <ImageIcon className="h-8 w-8 text-slate-400 mb-1 opacity-60" />
                        <span className="text-xs font-semibold text-slate-300">No Image Attached</span>
                        <span className="text-[10px] text-slate-500">Edit post to upload a picture</span>
                      </div>
                    )}

                    {/* Status Badge on top-right of image */}
                    <div className="absolute top-2.5 right-2.5">
                      <Badge
                        variant={item.is_active ? "default" : "secondary"}
                        className={`text-[10px] uppercase font-bold shadow-sm ${
                          item.is_active
                            ? "bg-emerald-600 hover:bg-emerald-600 text-white"
                            : "bg-slate-900/80 text-slate-200 dark:bg-slate-800/90"
                        }`}
                      >
                        {item.is_active ? "Live in App" : "Draft / Hidden"}
                      </Badge>
                    </div>

                    {/* ID Badge on top-left */}
                    <div className="absolute top-2.5 left-2.5">
                      <span className="rounded bg-black/60 px-2 py-0.5 text-[10px] font-mono text-white/90 backdrop-blur-sm">
                        #{item.id}
                      </span>
                    </div>
                  </div>

                  {/* Header & Meta */}
                  <CardHeader className="p-4 pb-2">
                    <div className="flex items-center gap-2 text-[11px] text-slate-400 mb-1">
                      <Calendar className="h-3 w-3" />
                      <span>
                        {item.published_at
                          ? new Date(item.published_at).toLocaleDateString("en-US", {
                              month: "short",
                              day: "numeric",
                              year: "numeric",
                            })
                          : item.created_at
                          ? new Date(item.created_at).toLocaleDateString("en-US", {
                              month: "short",
                              day: "numeric",
                              year: "numeric",
                            })
                          : "Unpublished"}
                      </span>
                    </div>

                    {/* Title Rendering Based on Language Mode */}
                    {languageView === "both" ? (
                      <div className="space-y-1">
                        <CardTitle className="text-sm font-bold text-slate-900 dark:text-slate-100 line-clamp-2">
                          {item.title_en || item.title || "Untitled Announcement"}
                        </CardTitle>
                        {item.title_am && (
                          <div className="text-xs font-semibold text-blue-700 dark:text-blue-300 line-clamp-1 font-serif">
                            {item.title_am}
                          </div>
                        )}
                      </div>
                    ) : languageView === "am" ? (
                      <CardTitle className="text-sm font-bold text-slate-900 dark:text-slate-100 line-clamp-2 font-serif">
                        {item.title_am || item.title || "ርዕስ የለውም"}
                      </CardTitle>
                    ) : (
                      <CardTitle className="text-sm font-bold text-slate-900 dark:text-slate-100 line-clamp-2">
                        {item.title_en || item.title || "Untitled Announcement"}
                      </CardTitle>
                    )}
                  </CardHeader>

                  {/* Body Content */}
                  <CardContent className="p-4 pt-1">
                    {languageView === "both" ? (
                      <div className="space-y-2">
                        <p className="text-xs text-slate-600 dark:text-slate-400 line-clamp-3 leading-relaxed">
                          {item.description_en || item.description || "No description provided."}
                        </p>
                        {item.description_am && (
                          <p className="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed border-t border-slate-100 pt-1.5 dark:border-slate-800/80 font-serif">
                            {item.description_am}
                          </p>
                        )}
                      </div>
                    ) : languageView === "am" ? (
                      <p className="text-xs text-slate-600 dark:text-slate-400 line-clamp-3 leading-relaxed font-serif">
                        {item.description_am || item.description || "ዝርዝር መግለጫ የለም።"}
                      </p>
                    ) : (
                      <p className="text-xs text-slate-600 dark:text-slate-400 line-clamp-3 leading-relaxed">
                        {item.description_en || item.description || "No description provided."}
                      </p>
                    )}
                  </CardContent>
                </div>

                {/* Footer Action Buttons */}
                <div className="flex items-center justify-between border-t border-slate-100 p-3 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/20">
                  {/* Quick Toggle Active */}
                  <Button
                    size="sm"
                    variant={item.is_active ? "outline" : "default"}
                    disabled={isToggling}
                    className={`h-7 text-xs font-semibold gap-1.5 px-2.5 ${
                      !item.is_active
                        ? "bg-emerald-600 text-white hover:bg-emerald-500"
                        : "border-slate-200 text-slate-700 dark:border-slate-800 dark:text-slate-300"
                    }`}
                    onClick={() => handleToggleActive(item)}
                  >
                    {isToggling ? (
                      <Loader2 className="h-3 w-3 animate-spin" />
                    ) : item.is_active ? (
                      <>
                        <EyeOff className="h-3 w-3 text-slate-400" />
                        Hide
                      </>
                    ) : (
                      <>
                        <Eye className="h-3 w-3" />
                        Publish
                      </>
                    )}
                  </Button>

                  <div className="flex items-center gap-1.5">
                    {/* Edit Button */}
                    <Button
                      size="sm"
                      variant="outline"
                      className="h-7 text-xs font-semibold border-slate-200 hover:bg-slate-100 dark:border-slate-800 dark:hover:bg-slate-900 gap-1"
                      onClick={() => handleOpenEdit(item)}
                    >
                      <Edit3 className="h-3 w-3 text-blue-600" />
                      Edit
                    </Button>

                    {/* Delete Button */}
                    <Button
                      size="sm"
                      variant="ghost"
                      className="h-7 w-7 p-0 text-rose-600 hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-950/40"
                      onClick={() => setDeletingItem(item)}
                    >
                      <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                  </div>
                </div>
              </Card>
            )
          })}
        </div>
      )}

      {/* ========================================================================= */}
      {/* MODAL 1: CREATE NEW UPDATE */}
      {/* ========================================================================= */}
      <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
        <DialogContent className="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2 text-base font-bold text-slate-950 dark:text-slate-50">
              <PlusCircle className="h-5 w-5 text-blue-600" />
              New Latest Update
            </DialogTitle>
            <DialogDescription className="text-xs text-slate-500">
              Compose a platform announcement with bilingual copy and an optional visual cover photo.
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleCreateSubmit} className="space-y-4 py-2">
            {/* Picture Upload Box */}
            <div className="space-y-1.5">
              <Label className="text-xs font-semibold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                <span className="flex items-center gap-1.5">
                  <ImageIcon className="h-3.5 w-3.5 text-blue-600" />
                  Update Cover Picture
                </span>
                <span className="text-[11px] font-normal text-slate-400">JPG, PNG, WebP up to 10MB</span>
              </Label>

              {createImagePreview ? (
                <div className="relative rounded-lg border border-slate-200 overflow-hidden bg-slate-900 shadow-sm dark:border-slate-800">
                  <img
                    src={createImagePreview}
                    alt="Cropped Preview"
                    className="w-full max-h-56 object-contain bg-black/40"
                  />
                  <div className="absolute top-2 right-2 flex items-center gap-1.5">
                    <Button
                      type="button"
                      size="sm"
                      onClick={() => handleRecrop("create")}
                      className="h-7 text-xs bg-black/75 hover:bg-black/90 text-white backdrop-blur-sm border border-white/20 gap-1 shadow-sm"
                    >
                      <Scissors className="h-3 w-3 text-blue-400" />
                      Re-crop / Adjust
                    </Button>
                    <Button
                      type="button"
                      size="sm"
                      variant="destructive"
                      onClick={clearCreateImage}
                      className="h-7 text-xs bg-rose-600/90 hover:bg-rose-600 text-white shadow-sm gap-1"
                    >
                      <Trash2 className="h-3 w-3" />
                      Remove
                    </Button>
                  </div>
                  <div className="p-2.5 text-xs text-slate-300 flex items-center justify-between bg-slate-950/90 backdrop-blur-sm border-t border-slate-800">
                    <span className="text-[11px] text-emerald-400 flex items-center gap-1">
                      <CheckCircle2 className="h-3.5 w-3.5" />
                      Cropped & ready for announcement banner
                    </span>
                    <button
                      type="button"
                      onClick={() => createFileInputRef.current?.click()}
                      className="text-xs text-blue-400 hover:text-blue-300 hover:underline font-medium"
                    >
                      Upload Different Image
                    </button>
                  </div>
                </div>
              ) : (
                <div
                  onClick={() => createFileInputRef.current?.click()}
                  className="cursor-pointer flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-300 p-6 hover:border-blue-500 hover:bg-blue-50/30 transition-colors dark:border-slate-800 dark:hover:border-blue-500 dark:hover:bg-blue-950/20 group"
                >
                  <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400 mb-2 group-hover:scale-105 transition-transform">
                    <Upload className="h-5 w-5" />
                  </div>
                  <p className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                    Click to upload and crop picture
                  </p>
                  <p className="text-[11px] text-slate-400 mt-0.5">
                    Interactive Instagram-style zoom & crop (16:9 Banner, 1:1 Square, 4:3)
                  </p>
                </div>
              )}

              <input
                ref={createFileInputRef}
                type="file"
                accept="image/jpeg,image/png,image/webp"
                className="hidden"
                onChange={handleCreateImageChange}
              />
            </div>

            {/* Bilingual Titles */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="create-title-en" className="text-xs font-semibold">
                  English Title <span className="text-rose-500">*</span>
                </Label>
                <Input
                  id="create-title-en"
                  placeholder="e.g. 2016 E.C. Exam Solutions Released"
                  value={createTitleEn}
                  onChange={(e) => setCreateTitleEn(e.target.value)}
                  required
                  className="text-xs"
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="create-title-am" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Amharic Title (ርዕስ በአማርኛ)
                </Label>
                <Input
                  id="create-title-am"
                  placeholder="ምሳሌ፡ የ2016 ዓ.ም የብሔራዊ ፈተና መፍትሄዎች ወጡ"
                  value={createTitleAm}
                  onChange={(e) => setCreateTitleAm(e.target.value)}
                  className="text-xs font-serif"
                />
              </div>
            </div>

            {/* Bilingual Descriptions */}
            <div className="space-y-3">
              <div className="space-y-1.5">
                <Label htmlFor="create-desc-en" className="text-xs font-semibold">
                  English Description / Body <span className="text-rose-500">*</span>
                </Label>
                <Textarea
                  id="create-desc-en"
                  rows={3}
                  placeholder="Provide complete details, step-by-step guidance, or release highlights for students..."
                  value={createDescEn}
                  onChange={(e) => setCreateDescEn(e.target.value)}
                  required
                  className="text-xs"
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="create-desc-am" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Amharic Description (ዝርዝር መግለጫ በአማርኛ)
                </Label>
                <Textarea
                  id="create-desc-am"
                  rows={3}
                  placeholder="ለተማሪዎች እና አስተማሪዎች የተዘጋጀ ዝርዝር መረጃ..."
                  value={createDescAm}
                  onChange={(e) => setCreateDescAm(e.target.value)}
                  className="text-xs font-serif"
                />
              </div>
            </div>

            {/* Publishing Controls */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 border-t border-slate-100 dark:border-slate-800">
              <div className="flex items-center gap-2 pt-2">
                <input
                  type="checkbox"
                  id="create-is-active"
                  checked={createIsActive}
                  onChange={(e) => setCreateIsActive(e.target.checked)}
                  className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                />
                <Label htmlFor="create-is-active" className="text-xs font-medium cursor-pointer">
                  Publish immediately to client app feeds
                </Label>
              </div>

              <div className="space-y-1">
                <Label htmlFor="create-published-at" className="text-[11px] font-medium text-slate-500">
                  Publication Schedule Timestamp
                </Label>
                <Input
                  id="create-published-at"
                  type="datetime-local"
                  value={createPublishedAt}
                  onChange={(e) => setCreatePublishedAt(e.target.value)}
                  className="text-xs h-8"
                />
              </div>
            </div>

            <DialogFooter className="pt-3">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setIsCreateOpen(false)}
                disabled={isSubmittingCreate}
              >
                Cancel
              </Button>
              <Button
                type="submit"
                size="sm"
                className="bg-blue-600 text-white hover:bg-blue-500 font-semibold"
                disabled={isSubmittingCreate}
              >
                {isSubmittingCreate ? (
                  <>
                    <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                    Publishing...
                  </>
                ) : (
                  <>
                    <Upload className="mr-1.5 h-3.5 w-3.5" />
                    Publish Update
                  </>
                )}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* ========================================================================= */}
      {/* MODAL 2: EDIT UPDATE */}
      {/* ========================================================================= */}
      <Dialog
        open={Boolean(editingItem)}
        onOpenChange={(open) => !open && setEditingItem(null)}
      >
        <DialogContent className="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2 text-base font-bold text-slate-950 dark:text-slate-50">
              <Edit3 className="h-5 w-5 text-blue-600" />
              Edit Latest Update #{editingItem?.id}
            </DialogTitle>
            <DialogDescription className="text-xs text-slate-500">
              Modify update copy, replace the cover picture, or toggle publication state.
            </DialogDescription>
          </DialogHeader>

          {editingItem && (
            <form onSubmit={handleEditSubmit} className="space-y-4 py-2">
              {/* Picture Upload / Preview Box */}
              <div className="space-y-1.5">
                <Label className="text-xs font-semibold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                  <span className="flex items-center gap-1.5">
                    <ImageIcon className="h-3.5 w-3.5 text-blue-600" />
                    Cover Picture
                  </span>
                  <span className="text-[11px] font-normal text-slate-400">JPG, PNG, WebP up to 10MB</span>
                </Label>

                {editImagePreview ? (
                  <div className="relative rounded-lg border border-slate-200 overflow-hidden bg-slate-900 shadow-sm dark:border-slate-800">
                    <img
                      src={editImagePreview}
                      alt="Cover Preview"
                      className="w-full max-h-56 object-contain bg-black/40"
                    />
                    <div className="absolute top-2 right-2 flex items-center gap-1.5">
                      <Button
                        type="button"
                        size="sm"
                        onClick={() => handleRecrop("edit")}
                        className="h-7 text-xs bg-black/75 hover:bg-black/90 text-white backdrop-blur-sm border border-white/20 gap-1 shadow-sm"
                      >
                        <Scissors className="h-3 w-3 text-blue-400" />
                        Re-crop / Adjust
                      </Button>
                      <Button
                        type="button"
                        size="sm"
                        variant="destructive"
                        onClick={clearEditImage}
                        className="h-7 text-xs bg-rose-600/90 hover:bg-rose-600 text-white shadow-sm gap-1"
                      >
                        <Trash2 className="h-3 w-3" />
                        Remove
                      </Button>
                    </div>
                    <div className="p-2.5 text-xs text-slate-300 flex items-center justify-between bg-slate-950/90 backdrop-blur-sm border-t border-slate-800">
                      <span className="text-[11px] text-slate-300">
                        {editImageFile ? (
                          <span className="text-emerald-400 flex items-center gap-1">
                            <CheckCircle2 className="h-3.5 w-3.5" />
                            New cropped image ready to save
                          </span>
                        ) : (
                          "Current cover picture attached"
                        )}
                      </span>
                      <button
                        type="button"
                        onClick={() => editFileInputRef.current?.click()}
                        className="text-xs text-blue-400 hover:text-blue-300 hover:underline font-medium"
                      >
                        Replace Picture
                      </button>
                    </div>
                  </div>
                ) : (
                  <div
                    onClick={() => editFileInputRef.current?.click()}
                    className="cursor-pointer flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-300 p-6 hover:border-blue-500 hover:bg-blue-50/30 transition-colors dark:border-slate-800 dark:hover:border-blue-500 dark:hover:bg-blue-950/20 group"
                  >
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400 mb-2 group-hover:scale-105 transition-transform">
                      <Upload className="h-5 w-5" />
                    </div>
                    <p className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                      Click to attach a cover picture
                    </p>
                    <p className="text-[11px] text-slate-400 mt-0.5">
                      Interactive Instagram-style zoom & crop (16:9 Banner, 1:1 Square, 4:3)
                    </p>
                  </div>
                )}

                <input
                  ref={editFileInputRef}
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  className="hidden"
                  onChange={handleEditImageChange}
                />
              </div>

              {/* Bilingual Titles */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div className="space-y-1.5">
                  <Label htmlFor="edit-title-en" className="text-xs font-semibold">
                    English Title <span className="text-rose-500">*</span>
                  </Label>
                  <Input
                    id="edit-title-en"
                    value={editTitleEn}
                    onChange={(e) => setEditTitleEn(e.target.value)}
                    required
                    className="text-xs"
                  />
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="edit-title-am" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                    Amharic Title (ርዕስ በአማርኛ)
                  </Label>
                  <Input
                    id="edit-title-am"
                    value={editTitleAm}
                    onChange={(e) => setEditTitleAm(e.target.value)}
                    className="text-xs font-serif"
                  />
                </div>
              </div>

              {/* Bilingual Descriptions */}
              <div className="space-y-3">
                <div className="space-y-1.5">
                  <Label htmlFor="edit-desc-en" className="text-xs font-semibold">
                    English Description / Content <span className="text-rose-500">*</span>
                  </Label>
                  <Textarea
                    id="edit-desc-en"
                    rows={3}
                    value={editDescEn}
                    onChange={(e) => setEditDescEn(e.target.value)}
                    required
                    className="text-xs"
                  />
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="edit-desc-am" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                    Amharic Description (ዝርዝር መግለጫ በአማርኛ)
                  </Label>
                  <Textarea
                    id="edit-desc-am"
                    rows={3}
                    value={editDescAm}
                    onChange={(e) => setEditDescAm(e.target.value)}
                    className="text-xs font-serif"
                  />
                </div>
              </div>

              {/* Publishing Controls */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 border-t border-slate-100 dark:border-slate-800">
                <div className="flex items-center gap-2 pt-2">
                  <input
                    type="checkbox"
                    id="edit-is-active"
                    checked={editIsActive}
                    onChange={(e) => setEditIsActive(e.target.checked)}
                    className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                  />
                  <Label htmlFor="edit-is-active" className="text-xs font-medium cursor-pointer">
                    Active / Live in Client Feed
                  </Label>
                </div>

                <div className="space-y-1">
                  <Label htmlFor="edit-published-at" className="text-[11px] font-medium text-slate-500">
                    Publication Date
                  </Label>
                  <Input
                    id="edit-published-at"
                    type="datetime-local"
                    value={editPublishedAt}
                    onChange={(e) => setEditPublishedAt(e.target.value)}
                    className="text-xs h-8"
                  />
                </div>
              </div>

              <DialogFooter className="pt-3">
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => setEditingItem(null)}
                  disabled={isSubmittingEdit}
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  size="sm"
                  className="bg-blue-600 text-white hover:bg-blue-500 font-semibold"
                  disabled={isSubmittingEdit}
                >
                  {isSubmittingEdit ? (
                    <>
                      <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                      Saving Changes...
                    </>
                  ) : (
                    "Save Changes"
                  )}
                </Button>
              </DialogFooter>
            </form>
          )}
        </DialogContent>
      </Dialog>

      {/* ========================================================================= */}
      {/* MODAL 3: DELETE CONFIRMATION */}
      {/* ========================================================================= */}
      <Dialog
        open={Boolean(deletingItem)}
        onOpenChange={(open) => !open && setDeletingItem(null)}
      >
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2 text-base font-bold text-rose-600">
              <Trash2 className="h-5 w-5 text-rose-600" />
              Delete Announcement
            </DialogTitle>
            <DialogDescription className="text-xs text-slate-500">
              Are you sure you want to permanently delete this update from the platform?
            </DialogDescription>
          </DialogHeader>

          {deletingItem && (
            <div className="space-y-3 py-2">
              <div className="rounded-lg border border-rose-200 bg-rose-50/60 p-3 text-xs text-rose-950 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-200">
                <p className="font-semibold text-sm mb-1">{deletingItem.title}</p>
                <p className="text-[11px] text-slate-600 dark:text-slate-400 line-clamp-2">
                  {deletingItem.description}
                </p>
                <p className="mt-2 text-[11px] text-rose-700 dark:text-rose-300 font-medium">
                  This action removes the database record and associated image file from storage.
                </p>
              </div>
            </div>
          )}

          <DialogFooter className="pt-2">
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => setDeletingItem(null)}
              disabled={isSubmittingDelete}
            >
              Cancel
            </Button>
            <Button
              type="button"
              size="sm"
              className="bg-rose-600 text-white hover:bg-rose-500 font-semibold"
              disabled={isSubmittingDelete}
              onClick={handleDeleteSubmit}
            >
              {isSubmittingDelete ? (
                <>
                  <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                  Deleting...
                </>
              ) : (
                "Delete Announcement"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ========================================================================= */}
      {/* MODAL 4: INSTAGRAM-STYLE IMAGE CROPPER & RESIZER */}
      {/* ========================================================================= */}
      <ImageCropperDialog
        isOpen={isCropperOpen}
        imageSrc={cropImageSrc}
        onClose={() => setIsCropperOpen(false)}
        onCropComplete={handleCropComplete}
      />
    </div>
  )
}
