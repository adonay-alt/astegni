"use client"

import React, { useState, useEffect, useMemo, useRef } from "react"
import { apiClient } from "@/lib/api-client"
import { ChapterResource } from "@/types/curriculum"
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
  FileText,
  Plus,
  Coins,
  Lock,
  Unlock,
  ExternalLink,
  Edit2,
  Trash2,
  Search,
  RefreshCw,
  Loader2,
  CheckCircle2,
  AlertCircle,
  Paperclip,
  UploadCloud,
  FileCheck,
  GraduationCap,
  BookOpen,
  Filter,
} from "lucide-react"

interface GradeItem {
  id: number
  name: string
}

interface SubjectItem {
  id: number
  grade_id: number
  name?: string
  title?: string
}

interface ChapterItem {
  id: number
  subject_id: number
  chapter_number?: number
  title: string
}

export default function ResourcesPricingHubPage() {
  const [resources, setResources] = useState<ChapterResource[]>([])
  const [grades, setGrades] = useState<GradeItem[]>([])
  const [subjects, setSubjects] = useState<SubjectItem[]>([])
  const [chapters, setChapters] = useState<ChapterItem[]>([])

  const [isLoading, setIsLoading] = useState(true)
  const [searchQuery, setSearchQuery] = useState("")
  const [selectedGradeId, setSelectedGradeId] = useState<string>("all")
  const [selectedSubjectId, setSelectedSubjectId] = useState<string>("all")
  const [pricingFilter, setPricingFilter] = useState<"all" | "paid" | "free">("all")

  // Toast / Feedback State
  const [feedbackNotice, setFeedbackNotice] = useState<{
    type: "success" | "error"
    message: string
  } | null>(null)

  // Upload New Resource State
  const [showUploadModal, setShowUploadModal] = useState(false)
  const [uploadGradeId, setUploadGradeId] = useState<string>("")
  const [uploadSubjectId, setUploadSubjectId] = useState<string>("")
  const [uploadChapterId, setUploadChapterId] = useState<string>("")
  const [uploadTitle, setUploadTitle] = useState("")
  const [uploadDesc, setUploadDesc] = useState("")
  const [uploadPrice, setUploadPrice] = useState("50.00")
  const [uploadFile, setUploadFile] = useState<File | null>(null)
  const [isUploading, setIsUploading] = useState(false)
  const [isUploadDragOver, setIsUploadDragOver] = useState(false)
  const uploadFileInputRef = useRef<HTMLInputElement | null>(null)

  // Edit Resource Metadata State
  const [editingResource, setEditingResource] = useState<ChapterResource | null>(null)
  const [editTitle, setEditTitle] = useState("")
  const [editDesc, setEditDesc] = useState("")
  const [isSavingEdit, setIsSavingEdit] = useState(false)

  // Dedicated Edit Price (UPDATE) State
  const [pricingResource, setPricingResource] = useState<ChapterResource | null>(null)
  const [pricingNewPrice, setPricingNewPrice] = useState("50.00")
  const [isSavingPrice, setIsSavingPrice] = useState(false)

  // Dedicated Remove Price (DELETE / Make Free) State
  const [removePriceResource, setRemovePriceResource] = useState<ChapterResource | null>(null)
  const [isRemovingPrice, setIsRemovingPrice] = useState(false)

  // Delete Resource File State
  const [deleteResourceItem, setDeleteResourceItem] = useState<ChapterResource | null>(null)
  const [isDeletingResource, setIsDeletingResource] = useState(false)

  const showToast = (type: "success" | "error", message: string) => {
    setFeedbackNotice({ type, message })
    setTimeout(() => setFeedbackNotice(null), 4500)
  }

  // Load initial resources and metadata
  const fetchData = async () => {
    setIsLoading(true)
    try {
      const [resData, gradesData, subjectsData, chaptersData] = await Promise.all([
        apiClient.get("/admin/resources").then((r) => r.data?.data || []).catch(() => []),
        apiClient.get("/grades").then((r) => r.data?.data || []).catch(() => []),
        apiClient.get("/subjects").then((r) => r.data?.data || []).catch(() => []),
        apiClient.get("/admin/chapters").then((r) => r.data?.data || []).catch(() => []),
      ])
      setResources(resData)
      setGrades(gradesData)
      setSubjects(subjectsData)
      setChapters(chaptersData)
    } catch (err) {
      console.error("Failed to load resources hub data:", err)
      showToast("error", "Failed to load resource catalog.")
    } finally {
      setIsLoading(false)
    }
  }

  useEffect(() => {
    fetchData()
  }, [])

  // Filtered subjects for upload modal based on selected grade
  const availableUploadSubjects = useMemo(() => {
    if (!uploadGradeId) return []
    return subjects.filter((s) => String(s.grade_id) === String(uploadGradeId))
  }, [uploadGradeId, subjects])

  // Filtered chapters for upload modal based on selected subject
  const availableUploadChapters = useMemo(() => {
    if (!uploadSubjectId) return []
    return chapters.filter((c) => String(c.subject_id) === String(uploadSubjectId))
  }, [uploadSubjectId, chapters])

  // Cascading filter subjects for main table
  const filteredTableSubjects = useMemo(() => {
    if (selectedGradeId === "all") return subjects
    return subjects.filter((s) => String(s.grade_id) === String(selectedGradeId))
  }, [selectedGradeId, subjects])

  // Filtered resources list for table display
  const filteredResources = useMemo(() => {
    return resources.filter((res) => {
      // Grade filter
      if (selectedGradeId !== "all" && res.grade_id && String(res.grade_id) !== String(selectedGradeId)) {
        return false
      }
      // Subject filter
      if (selectedSubjectId !== "all" && res.subject_id && String(res.subject_id) !== String(selectedSubjectId)) {
        return false
      }
      // Pricing filter
      const priceNum = Number(res.price) || 0
      if (pricingFilter === "paid" && priceNum <= 0) return false
      if (pricingFilter === "free" && priceNum > 0) return false

      // Search query
      if (searchQuery.trim()) {
        const query = searchQuery.toLowerCase()
        const titleMatch = res.title?.toLowerCase().includes(query)
        const descMatch = res.description?.toLowerCase().includes(query)
        const chapterMatch = res.chapter?.title?.toLowerCase().includes(query)
        const subjectMatch = (res.subject?.name || res.subject?.title)?.toLowerCase().includes(query)
        if (!titleMatch && !descMatch && !chapterMatch && !subjectMatch) return false
      }

      return true
    })
  }, [resources, selectedGradeId, selectedSubjectId, pricingFilter, searchQuery])

  // Stats
  const stats = useMemo(() => {
    const total = resources.length
    const paid = resources.filter((r) => Number(r.price) > 0).length
    const free = total - paid
    return { total, paid, free }
  }, [resources])

  // =========================================================================
  // HANDLERS: CREATE (Upload New Resource)
  // =========================================================================
  const handleUploadSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!uploadChapterId) {
      showToast("error", "Please select a target chapter.")
      return
    }
    if (!uploadTitle.trim()) {
      showToast("error", "Please enter a resource title.")
      return
    }
    if (!uploadFile) {
      showToast("error", "Please attach a PDF document.")
      return
    }

    setIsUploading(true)
    const formData = new FormData()
    formData.append("file", uploadFile)
    formData.append("title", uploadTitle.trim())
    if (uploadDesc.trim()) formData.append("description", uploadDesc.trim())
    const finalPrice = parseFloat(uploadPrice) || 0
    formData.append("price", String(finalPrice))

    try {
      const res = await apiClient.post(`/admin/chapters/${uploadChapterId}/resources`, formData, {
        headers: { "Content-Type": "multipart/form-data" },
      })
      const newResource: ChapterResource = res.data?.data
      setResources((prev) => [newResource, ...prev])
      setShowUploadModal(false)
      setUploadTitle("")
      setUploadDesc("")
      setUploadFile(null)
      setUploadPrice("50.00")
      showToast("success", "Supplementary PDF uploaded successfully with pricing rule!")
    } catch (err: any) {
      console.error("Resource upload failed:", err)
      showToast("error", err.response?.data?.message || "Failed to upload resource.")
    } finally {
      setIsUploading(false)
    }
  }

  // =========================================================================
  // HANDLERS: UPDATE Price
  // =========================================================================
  const handleOpenPriceModal = (res: ChapterResource) => {
    setPricingResource(res)
    const currentPrice = res.price !== undefined && res.price !== null ? Number(res.price) : 50.0
    setPricingNewPrice(currentPrice > 0 ? String(currentPrice) : "0")
  }

  const handleUpdatePrice = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!pricingResource) return

    const parsedPrice = parseFloat(pricingNewPrice)
    if (isNaN(parsedPrice) || parsedPrice < 0) {
      showToast("error", "Please enter a valid price (0 or greater).")
      return
    }

    setIsSavingPrice(true)
    try {
      const res = await apiClient.patch(`/admin/resources/${pricingResource.id}/price`, {
        price: parsedPrice,
      })

      const updatedRes: ChapterResource = res.data?.data || {
        ...pricingResource,
        price: parsedPrice,
        is_locked: parsedPrice > 0,
      }

      setResources((prev) => prev.map((item) => (item.id === updatedRes.id ? updatedRes : item)))
      setPricingResource(null)
      showToast("success", res.data?.message || `Resource price updated to ${parsedPrice.toFixed(2)} ETB.`)
    } catch (err: any) {
      console.error("Failed to update resource price:", err)
      showToast("error", err.response?.data?.message || "Failed to update resource price.")
    } finally {
      setIsSavingPrice(false)
    }
  }

  // =========================================================================
  // HANDLERS: DELETE Price (Make Free)
  // =========================================================================
  const handleRemovePrice = async () => {
    if (!removePriceResource) return

    setIsRemovingPrice(true)
    try {
      const res = await apiClient.delete(`/admin/resources/${removePriceResource.id}/price`)

      const updatedRes: ChapterResource = res.data?.data || {
        ...removePriceResource,
        price: 0,
        is_locked: false,
      }

      setResources((prev) => prev.map((item) => (item.id === updatedRes.id ? updatedRes : item)))
      setRemovePriceResource(null)
      showToast("success", res.data?.message || "Price removed. This resource is now completely FREE for all students.")
    } catch (err: any) {
      console.error("Failed to remove price:", err)
      showToast("error", err.response?.data?.message || "Failed to remove price.")
    } finally {
      setIsRemovingPrice(false)
    }
  }

  // =========================================================================
  // HANDLERS: EDIT Details (Title & Description)
  // =========================================================================
  const handleSaveEdit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!editingResource || !editTitle.trim()) return

    setIsSavingEdit(true)
    try {
      const res = await apiClient.put(`/admin/resources/${editingResource.id}`, {
        title: editTitle.trim(),
        description: editDesc.trim() || null,
      })

      const updatedRes: ChapterResource = res.data?.data || {
        ...editingResource,
        title: editTitle.trim(),
        description: editDesc.trim() || null,
      }

      setResources((prev) => prev.map((r) => (r.id === updatedRes.id ? updatedRes : r)))
      setEditingResource(null)
      showToast("success", "Resource title and description updated.")
    } catch (err: any) {
      console.error("Failed to edit resource:", err)
      showToast("error", err.response?.data?.message || "Failed to update resource.")
    } finally {
      setIsSavingEdit(false)
    }
  }

  // =========================================================================
  // HANDLERS: DELETE Resource File
  // =========================================================================
  const handleDeleteResourceFile = async () => {
    if (!deleteResourceItem) return

    setIsDeletingResource(true)
    try {
      await apiClient.delete(`/admin/resources/${deleteResourceItem.id}`)
      setResources((prev) => prev.filter((r) => r.id !== deleteResourceItem.id))
      setDeleteResourceItem(null)
      showToast("success", "Resource permanently deleted from database and storage.")
    } catch (err: any) {
      console.error("Failed to delete resource:", err)
      showToast("error", err.response?.data?.message || "Failed to delete resource.")
    } finally {
      setIsDeletingResource(false)
    }
  }

  return (
    <div className="space-y-6 pb-12">
      {/* Toast Feedback */}
      {feedbackNotice && (
        <div
          className={`fixed bottom-6 right-6 z-50 flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-xl text-xs font-semibold animate-in slide-in-from-bottom-3 duration-300 ${
            feedbackNotice.type === "success"
              ? "bg-emerald-600 text-white"
              : "bg-rose-600 text-white"
          }`}
        >
          {feedbackNotice.type === "success" ? (
            <CheckCircle2 className="h-4 w-4 shrink-0" />
          ) : (
            <AlertCircle className="h-4 w-4 shrink-0" />
          )}
          <span>{feedbackNotice.message}</span>
        </div>
      )}

      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div className="flex items-center gap-2.5">
            <div className="p-2 rounded-xl bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 shadow-xs">
              <Coins className="h-5 w-5" />
            </div>
            <div>
              <h1 className="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                Chapter Resources &amp; Pricing Hub
              </h1>
              <p className="text-xs text-slate-500 mt-0.5">
                Manage supplementary PDF worksheets, formula sheets, paywalls, and student pricing.
              </p>
            </div>
          </div>
        </div>

        <div className="flex items-center gap-2">
          <Button
            size="sm"
            variant="outline"
            onClick={fetchData}
            disabled={isLoading}
            className="text-xs h-9 gap-1.5"
          >
            <RefreshCw className={`h-3.5 w-3.5 ${isLoading ? "animate-spin" : ""}`} />
            Refresh
          </Button>

          <Button
            size="sm"
            onClick={() => {
              setUploadGradeId(grades[0]?.id ? String(grades[0].id) : "")
              setUploadSubjectId("")
              setUploadChapterId("")
              setUploadTitle("")
              setUploadDesc("")
              setUploadFile(null)
              setUploadPrice("50.00")
              setShowUploadModal(true)
            }}
            className="bg-blue-600 hover:bg-blue-500 text-white text-xs h-9 gap-1.5 font-bold shadow-sm"
          >
            <Plus className="h-4 w-4" />
            Upload New Resource
          </Button>
        </div>
      </div>

      {/* Metric Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <Card className="rounded-xl border-slate-200 dark:border-slate-800 shadow-xs">
          <CardContent className="p-4 flex items-center gap-3">
            <div className="p-2.5 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
              <Paperclip className="h-5 w-5" />
            </div>
            <div>
              <p className="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                Total Resources
              </p>
              <h3 className="text-xl font-bold text-slate-900 dark:text-slate-100 mt-0.5">
                {stats.total}
              </h3>
            </div>
          </CardContent>
        </Card>

        <Card className="rounded-xl border-slate-200 dark:border-slate-800 shadow-xs">
          <CardContent className="p-4 flex items-center gap-3">
            <div className="p-2.5 rounded-lg bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
              <Lock className="h-5 w-5" />
            </div>
            <div>
              <p className="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                Paid / Locked (Paywall)
              </p>
              <h3 className="text-xl font-bold text-amber-700 dark:text-amber-400 mt-0.5">
                {stats.paid}
              </h3>
            </div>
          </CardContent>
        </Card>

        <Card className="rounded-xl border-slate-200 dark:border-slate-800 shadow-xs">
          <CardContent className="p-4 flex items-center gap-3">
            <div className="p-2.5 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
              <Unlock className="h-5 w-5" />
            </div>
            <div>
              <p className="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                Free Access (0 ETB)
              </p>
              <h3 className="text-xl font-bold text-emerald-700 dark:text-emerald-400 mt-0.5">
                {stats.free}
              </h3>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Filter Bar */}
      <Card className="rounded-xl border-slate-200 dark:border-slate-800 shadow-xs bg-slate-50/50 dark:bg-slate-900/40">
        <CardContent className="p-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            {/* Search */}
            <div className="relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
              <Input
                placeholder="Search resources by title or chapter..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-9 text-xs h-9 bg-white dark:bg-slate-950"
              />
            </div>

            {/* Grade Filter */}
            <div>
              <select
                value={selectedGradeId}
                onChange={(e) => {
                  setSelectedGradeId(e.target.value)
                  setSelectedSubjectId("all")
                }}
                className="w-full h-9 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-xs px-3 font-semibold text-slate-700 dark:text-slate-300"
              >
                <option value="all">All Grades</option>
                {grades.map((g) => (
                  <option key={g.id} value={g.id}>
                    {g.name}
                  </option>
                ))}
              </select>
            </div>

            {/* Subject Filter */}
            <div>
              <select
                value={selectedSubjectId}
                onChange={(e) => setSelectedSubjectId(e.target.value)}
                className="w-full h-9 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-xs px-3 font-semibold text-slate-700 dark:text-slate-300"
              >
                <option value="all">All Subjects</option>
                {filteredTableSubjects.map((s) => (
                  <option key={s.id} value={s.id}>
                    {s.title || s.name}
                  </option>
                ))}
              </select>
            </div>

            {/* Pricing Tier Filter */}
            <div>
              <select
                value={pricingFilter}
                onChange={(e: any) => setPricingFilter(e.target.value)}
                className="w-full h-9 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-xs px-3 font-semibold text-slate-700 dark:text-slate-300"
              >
                <option value="all">All Pricing Tiers</option>
                <option value="paid">🔒 Locked Only (Paid)</option>
                <option value="free">🔓 Free Access Only</option>
              </select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Resources Table */}
      <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              <tr>
                <th className="p-3.5">Resource Title &amp; Details</th>
                <th className="p-3.5">Curriculum Context</th>
                <th className="p-3.5">Pricing &amp; Paywall</th>
                <th className="p-3.5 text-right">Administrative Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {isLoading ? (
                <tr>
                  <td colSpan={4} className="p-12 text-center text-slate-400">
                    <Loader2 className="h-6 w-6 animate-spin mx-auto mb-2 text-blue-500" />
                    Loading chapter resources...
                  </td>
                </tr>
              ) : filteredResources.length === 0 ? (
                <tr>
                  <td colSpan={4} className="p-12 text-center">
                    <Paperclip className="h-8 w-8 text-slate-400 mx-auto mb-2" />
                    <h4 className="text-xs font-bold text-slate-700 dark:text-slate-300">
                      No Supplementary Resources Found
                    </h4>
                    <p className="text-[11px] text-slate-500 mt-1 max-w-sm mx-auto mb-3">
                      No resources match the selected criteria. Upload a PDF attachment or adjust your filters.
                    </p>
                    <Button
                      size="sm"
                      onClick={() => setShowUploadModal(true)}
                      className="text-xs h-8 gap-1.5 font-semibold bg-blue-600 text-white"
                    >
                      <Plus className="h-3.5 w-3.5" />
                      Upload First Resource
                    </Button>
                  </td>
                </tr>
              ) : (
                filteredResources.map((res) => (
                  <tr
                    key={res.id}
                    className="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition"
                  >
                    {/* Resource Details */}
                    <td className="p-3.5 align-middle">
                      <div className="flex items-start gap-3">
                        <div className="p-2 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400 shrink-0">
                          <FileText className="h-4 w-4" />
                        </div>
                        <div className="min-w-0">
                          <h5 className="font-bold text-slate-900 dark:text-slate-100 truncate text-xs">
                            {res.title}
                          </h5>
                          {res.description && (
                            <p className="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                              {res.description}
                            </p>
                          )}
                          <div className="flex items-center gap-2 mt-1 text-[10px] text-slate-400">
                            <span className="uppercase font-semibold">{res.file_type || "PDF"}</span>
                            {res.file_size_mb && (
                              <>
                                <span>•</span>
                                <span className="font-mono">{res.file_size_mb} MB</span>
                              </>
                            )}
                            {res.created_at && (
                              <>
                                <span>•</span>
                                <span>{new Date(res.created_at).toLocaleDateString()}</span>
                              </>
                            )}
                          </div>
                        </div>
                      </div>
                    </td>

                    {/* Context */}
                    <td className="p-3.5 align-middle whitespace-nowrap">
                      <div className="font-semibold text-slate-800 dark:text-slate-200">
                        {res.chapter?.title || `Chapter #${res.chapter_id}`}
                      </div>
                      <div className="text-[10px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                        <span>{res.subject?.name || res.subject?.title || "Subject"}</span>
                        <span>•</span>
                        <span>{res.grade?.name || "Grade"}</span>
                      </div>
                    </td>

                    {/* Pricing Badge (READ) */}
                    <td className="p-3.5 align-middle whitespace-nowrap">
                      {Number(res.price) > 0 ? (
                        <Badge
                          variant="outline"
                          className="bg-amber-50 text-amber-800 border-amber-300 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800 text-[10px] px-2.5 py-1 gap-1.5 font-bold"
                        >
                          <Lock className="h-3 w-3" />
                          Locked ({Number(res.price).toFixed(2)} ETB)
                        </Badge>
                      ) : (
                        <Badge
                          variant="outline"
                          className="bg-emerald-50 text-emerald-800 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 text-[10px] px-2.5 py-1 gap-1.5 font-bold"
                        >
                          <Unlock className="h-3 w-3" />
                          Free Access (0.00 ETB)
                        </Badge>
                      )}
                    </td>

                    {/* Actions */}
                    <td className="p-3.5 align-middle text-right whitespace-nowrap">
                      <div className="flex items-center justify-end gap-1.5">
                        <Button
                          size="sm"
                          variant="outline"
                          className="h-7 text-xs px-2.5 gap-1"
                          onClick={() => window.open(res.file_url || res.file_path, "_blank")}
                        >
                          <ExternalLink className="h-3 w-3" />
                          View
                        </Button>

                        {/* 💰 UPDATE Price */}
                        <Button
                          size="sm"
                          variant="outline"
                          className="h-7 text-xs px-2.5 gap-1 border-amber-300 bg-amber-50/60 hover:bg-amber-100 text-amber-800 dark:border-amber-800/60 dark:bg-amber-950/30 dark:hover:bg-amber-950/60 dark:text-amber-300 font-semibold"
                          onClick={() => handleOpenPriceModal(res)}
                          title="Adjust Price"
                        >
                          <Coins className="h-3.5 w-3.5 text-amber-600 dark:text-amber-400" />
                          <span>Edit Price</span>
                        </Button>

                        {/* 🔓 DELETE Price (Make Free) */}
                        {Number(res.price) > 0 && (
                          <Button
                            size="sm"
                            variant="outline"
                            className="h-7 text-xs px-2.5 gap-1 border-emerald-300 bg-emerald-50/60 hover:bg-emerald-100 text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/30 dark:hover:bg-emerald-950/60 dark:text-emerald-300 font-semibold"
                            onClick={() => setRemovePriceResource(res)}
                            title="Remove price and unlock for all students"
                          >
                            <Unlock className="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                            <span>Make Free</span>
                          </Button>
                        )}

                        {/* Edit metadata */}
                        <Button
                          size="sm"
                          variant="ghost"
                          className="h-7 w-7 p-0 text-slate-500 hover:text-slate-900 dark:hover:text-slate-100"
                          onClick={() => {
                            setEditingResource(res)
                            setEditTitle(res.title)
                            setEditDesc(res.description || "")
                          }}
                          title="Edit Details"
                        >
                          <Edit2 className="h-3.5 w-3.5" />
                        </Button>

                        {/* Delete file */}
                        <Button
                          size="sm"
                          variant="ghost"
                          className="h-7 w-7 p-0 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                          onClick={() => setDeleteResourceItem(res)}
                          title="Delete File"
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

      {/* ========================================================================= */}
      {/* DIALOG 1: CREATE (Upload New Resource)                                    */}
      {/* ========================================================================= */}
      <Dialog open={showUploadModal} onOpenChange={setShowUploadModal}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2 text-slate-900 dark:text-slate-100">
              <UploadCloud className="h-5 w-5 text-blue-600" />
              Upload Chapter Supplementary Resource
            </DialogTitle>
            <DialogDescription className="text-xs">
              Upload worksheets, formula cheatsheets, or revision summaries and configure paywall pricing.
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleUploadSubmit} className="space-y-4 pt-2">
            {/* Hierarchy Selectors */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
              <div className="space-y-1">
                <Label className="text-[11px] font-semibold text-slate-700 dark:text-slate-300">
                  Grade
                </Label>
                <select
                  value={uploadGradeId}
                  onChange={(e) => {
                    setUploadGradeId(e.target.value)
                    setUploadSubjectId("")
                    setUploadChapterId("")
                  }}
                  className="w-full h-8 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-xs px-2 font-medium"
                >
                  <option value="">Select Grade</option>
                  {grades.map((g) => (
                    <option key={g.id} value={g.id}>
                      {g.name}
                    </option>
                  ))}
                </select>
              </div>

              <div className="space-y-1">
                <Label className="text-[11px] font-semibold text-slate-700 dark:text-slate-300">
                  Subject
                </Label>
                <select
                  value={uploadSubjectId}
                  disabled={!uploadGradeId}
                  onChange={(e) => {
                    setUploadSubjectId(e.target.value)
                    setUploadChapterId("")
                  }}
                  className="w-full h-8 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-xs px-2 font-medium disabled:opacity-50"
                >
                  <option value="">Select Subject</option>
                  {availableUploadSubjects.map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.title || s.name}
                    </option>
                  ))}
                </select>
              </div>

              <div className="space-y-1">
                <Label className="text-[11px] font-semibold text-slate-700 dark:text-slate-300">
                  Chapter
                </Label>
                <select
                  value={uploadChapterId}
                  disabled={!uploadSubjectId}
                  onChange={(e) => setUploadChapterId(e.target.value)}
                  className="w-full h-8 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-xs px-2 font-medium disabled:opacity-50"
                >
                  <option value="">Select Chapter</option>
                  {availableUploadChapters.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.title}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            {/* Title */}
            <div className="space-y-1.5">
              <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Resource Title
              </Label>
              <Input
                required
                placeholder="e.g. Physics Chapter 1 Formula Sheet &amp; Solved Exercises"
                value={uploadTitle}
                onChange={(e) => setUploadTitle(e.target.value)}
                className="text-xs"
              />
            </div>

            {/* Description */}
            <div className="space-y-1.5">
              <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Description (Optional)
              </Label>
              <Input
                placeholder="e.g. Comprehensive guide with 50+ solved problems."
                value={uploadDesc}
                onChange={(e) => setUploadDesc(e.target.value)}
                className="text-xs"
              />
            </div>

            {/* Pricing Selector (CREATE) */}
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Resource Pricing (ETB)
                </Label>
                <div className="flex items-center gap-1">
                  {[
                    { label: "Free (0 ETB)", value: "0" },
                    { label: "25 ETB", value: "25" },
                    { label: "50 ETB", value: "50" },
                    { label: "100 ETB", value: "100" },
                  ].map((preset) => {
                    const isSelected = String(parseFloat(uploadPrice) || 0) === String(parseFloat(preset.value))
                    return (
                      <button
                        key={preset.value}
                        type="button"
                        onClick={() => setUploadPrice(preset.value)}
                        className={`px-2 py-0.5 rounded text-[10px] font-semibold transition ${
                          isSelected
                            ? preset.value === "0"
                              ? "bg-emerald-600 text-white shadow-xs"
                              : "bg-amber-600 text-white shadow-xs"
                            : "bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300"
                        }`}
                      >
                        {preset.label}
                      </button>
                    )
                  })}
                </div>
              </div>

              <div className="relative">
                <Input
                  type="number"
                  min="0"
                  step="5"
                  required
                  placeholder="50.00"
                  value={uploadPrice}
                  onChange={(e) => setUploadPrice(e.target.value)}
                  className="pr-14 text-xs font-semibold"
                />
                <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400">
                  ETB
                </span>
              </div>
              <p className="text-[11px] text-slate-500">
                Initial unlock fee. Setting to 0 ETB makes this document completely free for all students.
              </p>
            </div>

            {/* Dropzone */}
            <div
              onDragOver={(e) => {
                e.preventDefault()
                setIsUploadDragOver(true)
              }}
              onDragLeave={() => setIsUploadDragOver(false)}
              onDrop={(e) => {
                e.preventDefault()
                setIsUploadDragOver(false)
                const files = e.dataTransfer.files
                if (files && files[0]) {
                  setUploadFile(files[0])
                }
              }}
              onClick={() => uploadFileInputRef.current?.click()}
              className={`border-2 border-dashed rounded-xl p-5 text-center cursor-pointer transition ${
                isUploadDragOver
                  ? "border-blue-500 bg-blue-50/50"
                  : uploadFile
                  ? "border-emerald-400 bg-emerald-50/40"
                  : "border-slate-300 dark:border-slate-700 hover:border-slate-400"
              }`}
            >
              <input
                type="file"
                ref={uploadFileInputRef}
                accept=".pdf,application/pdf"
                className="hidden"
                onChange={(e) => {
                  const files = e.target.files
                  if (files && files[0]) {
                    setUploadFile(files[0])
                  }
                }}
              />

              {uploadFile ? (
                <div className="flex items-center justify-center gap-2 text-xs font-bold text-slate-800 dark:text-slate-200">
                  <FileCheck className="h-4 w-4 text-emerald-600" />
                  <span>{uploadFile.name}</span>
                  <span className="font-mono text-[10px] text-slate-500">
                    ({(uploadFile.size / 1024 / 1024).toFixed(2)} MB)
                  </span>
                </div>
              ) : (
                <div className="flex flex-col items-center gap-1.5">
                  <UploadCloud className="h-5 w-5 text-blue-500" />
                  <p className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                    Click to browse or drop PDF attachment
                  </p>
                  <p className="text-[10px] text-slate-400">PDF files up to 50MB</p>
                </div>
              )}
            </div>

            <DialogFooter className="pt-2">
              <Button
                type="button"
                variant="outline"
                onClick={() => setShowUploadModal(false)}
                className="text-xs"
              >
                Cancel
              </Button>
              <Button
                type="submit"
                disabled={!uploadChapterId || !uploadTitle.trim() || !uploadFile || isUploading}
                className="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold gap-1.5"
              >
                {isUploading ? (
                  <>
                    <Loader2 className="h-3.5 w-3.5 animate-spin" /> Uploading...
                  </>
                ) : (
                  "Upload Resource"
                )}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* ========================================================================= */}
      {/* DIALOG 2: UPDATE Price                                                    */}
      {/* ========================================================================= */}
      <Dialog
        open={Boolean(pricingResource)}
        onOpenChange={(open) => {
          if (!open) setPricingResource(null)
        }}
      >
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2 text-slate-900 dark:text-slate-100">
              <Coins className="h-5 w-5 text-amber-600" />
              Adjust Resource Price
            </DialogTitle>
            <DialogDescription className="text-xs">
              Update student unlock fee for &ldquo;<strong>{pricingResource?.title}</strong>&rdquo;.
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleUpdatePrice} className="space-y-4 pt-2">
            <div className="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs">
              <span className="text-slate-500">Current Setting:</span>
              <div className="font-bold">
                {Number(pricingResource?.price) > 0 ? (
                  <span className="text-amber-600 dark:text-amber-400 font-mono">
                    {Number(pricingResource?.price).toFixed(2)} ETB (Paid)
                  </span>
                ) : (
                  <span className="text-emerald-600 dark:text-emerald-400 font-semibold">
                    Free Access (0.00 ETB)
                  </span>
                )}
              </div>
            </div>

            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  New Price (ETB)
                </Label>
                <div className="flex items-center gap-1">
                  {[
                    { label: "Free (0 ETB)", value: "0" },
                    { label: "25 ETB", value: "25" },
                    { label: "50 ETB", value: "50" },
                    { label: "100 ETB", value: "100" },
                  ].map((preset) => {
                    const isSelected = String(parseFloat(pricingNewPrice) || 0) === String(parseFloat(preset.value))
                    return (
                      <button
                        key={preset.value}
                        type="button"
                        onClick={() => setPricingNewPrice(preset.value)}
                        className={`px-2 py-0.5 rounded text-[10px] font-semibold transition ${
                          isSelected
                            ? preset.value === "0"
                              ? "bg-emerald-600 text-white"
                              : "bg-amber-600 text-white"
                            : "bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300"
                        }`}
                      >
                        {preset.label}
                      </button>
                    )
                  })}
                </div>
              </div>

              <div className="relative">
                <Input
                  type="number"
                  min="0"
                  step="5"
                  required
                  value={pricingNewPrice}
                  onChange={(e) => setPricingNewPrice(e.target.value)}
                  className="pr-14 text-xs font-semibold"
                  placeholder="0.00"
                />
                <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400">
                  ETB
                </span>
              </div>
              <p className="text-[11px] text-slate-500">
                Dispatches PATCH /api/admin/resources/{pricingResource?.id}/price and updates cache immediately.
              </p>
            </div>

            <DialogFooter className="pt-2">
              <Button
                type="button"
                variant="outline"
                onClick={() => setPricingResource(null)}
                className="text-xs"
              >
                Cancel
              </Button>
              <Button
                type="submit"
                disabled={isSavingPrice || !pricingNewPrice.trim()}
                className="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold gap-1.5"
              >
                {isSavingPrice ? (
                  <>
                    <Loader2 className="h-3.5 w-3.5 animate-spin" /> Saving Price...
                  </>
                ) : (
                  "Save Price"
                )}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* ========================================================================= */}
      {/* DIALOG 3: DELETE Price (Make Free Confirmation)                           */}
      {/* ========================================================================= */}
      <Dialog
        open={Boolean(removePriceResource)}
        onOpenChange={(open) => {
          if (!open) setRemovePriceResource(null)
        }}
      >
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2 text-emerald-600 dark:text-emerald-400">
              <Unlock className="h-5 w-5" />
              Remove Price &amp; Make Free for All Students?
            </DialogTitle>
            <DialogDescription className="text-xs text-slate-600 dark:text-slate-400">
              Are you sure you want to remove the price requirement from{" "}
              <strong>&ldquo;{removePriceResource?.title}&rdquo;</strong>?
            </DialogDescription>
          </DialogHeader>

          <div className="rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 p-3 text-xs space-y-1">
            <p className="font-semibold text-emerald-900 dark:text-emerald-200 flex items-center gap-1.5">
              <CheckCircle2 className="h-4 w-4 text-emerald-600" />
              Instant Free Access
            </p>
            <p className="text-emerald-700 dark:text-emerald-300 text-[11px]">
              This will reset <code>price = 0.00</code>. All students will be able to access and view this PDF without purchasing.
            </p>
            <p className="text-slate-500 dark:text-slate-400 text-[10px] mt-1">
              Note: The resource file will NOT be deleted from storage.
            </p>
          </div>

          <DialogFooter className="pt-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => setRemovePriceResource(null)}
              className="text-xs"
            >
              Cancel
            </Button>
            <Button
              type="button"
              disabled={isRemovingPrice}
              onClick={handleRemovePrice}
              className="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold gap-1.5"
            >
              {isRemovingPrice ? (
                <>
                  <Loader2 className="h-3.5 w-3.5 animate-spin" /> Unlocking...
                </>
              ) : (
                <>
                  <Unlock className="h-3.5 w-3.5" />
                  Make Completely Free
                </>
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ========================================================================= */}
      {/* DIALOG 4: EDIT Details (Title & Description)                              */}
      {/* ========================================================================= */}
      <Dialog
        open={Boolean(editingResource)}
        onOpenChange={(open) => {
          if (!open) setEditingResource(null)
        }}
      >
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Edit Resource Details</DialogTitle>
            <DialogDescription>Update title or description notes.</DialogDescription>
          </DialogHeader>

          <form onSubmit={handleSaveEdit} className="space-y-4 pt-2">
            <div className="space-y-1.5">
              <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Resource Title
              </Label>
              <Input
                required
                value={editTitle}
                onChange={(e) => setEditTitle(e.target.value)}
                className="text-xs"
              />
            </div>

            <div className="space-y-1.5">
              <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Description (Optional)
              </Label>
              <Input
                value={editDesc}
                onChange={(e) => setEditDesc(e.target.value)}
                className="text-xs"
              />
            </div>

            <DialogFooter className="pt-2">
              <Button
                type="button"
                variant="outline"
                onClick={() => setEditingResource(null)}
                className="text-xs"
              >
                Cancel
              </Button>
              <Button
                type="submit"
                disabled={isSavingEdit || !editTitle.trim()}
                className="bg-blue-600 text-xs font-bold text-white hover:bg-blue-500"
              >
                {isSavingEdit ? "Saving..." : "Save Changes"}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* ========================================================================= */}
      {/* DIALOG 5: DELETE Resource File Confirmation                               */}
      {/* ========================================================================= */}
      <Dialog
        open={Boolean(deleteResourceItem)}
        onOpenChange={(open) => {
          if (!open) setDeleteResourceItem(null)
        }}
      >
        <DialogContent className="sm:max-w-sm">
          <DialogHeader>
            <DialogTitle className="text-rose-600 flex items-center gap-2">
              <Trash2 className="h-5 w-5" />
              Delete Resource File?
            </DialogTitle>
            <DialogDescription className="text-xs text-slate-600 dark:text-slate-400">
              Are you sure you want to permanently delete{" "}
              <strong>&ldquo;{deleteResourceItem?.title}&rdquo;</strong>?
              <span className="block mt-1 text-rose-600 font-semibold">
                This will delete the file from storage and database.
              </span>
            </DialogDescription>
          </DialogHeader>

          <DialogFooter className="pt-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => setDeleteResourceItem(null)}
              className="text-xs"
            >
              Cancel
            </Button>
            <Button
              type="button"
              disabled={isDeletingResource}
              onClick={handleDeleteResourceFile}
              className="bg-rose-600 hover:bg-rose-500 text-xs font-bold text-white"
            >
              {isDeletingResource ? "Deleting..." : "Confirm Delete"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
