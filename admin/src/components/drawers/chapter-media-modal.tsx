"use client"

import React, { useState, useEffect, useRef } from "react"
import { apiClient } from "@/lib/api-client"
import { ChapterTextbook, ChapterResource, ChapterVideo } from "@/types/curriculum"
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription,
} from "@/components/ui/sheet"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import {
  BookOpen,
  FileText,
  Video,
  UploadCloud,
  Trash2,
  ExternalLink,
  Edit2,
  Plus,
  Play,
  Clock,
  Loader2,
  RefreshCw,
  CheckCircle2,
  AlertCircle,
  Paperclip,
  FileCheck,
  Film,
  X,
  Lock,
  Unlock,
  Coins,
} from "lucide-react"

export interface ChapterMediaModalProps {
  chapter: {
    id: number
    title: string
    grade_name?: string
    subject_name?: string
    chapter_number?: number
    textbooks_count?: number
    resources_count?: number
    videos_count?: number
    has_textbook?: boolean
  } | null
  open: boolean
  onOpenChange: (open: boolean) => void
  initialTab?: TabType
  onMediaUpdated?: (
    chapterId: number,
    mediaCounts: {
      textbooks_count: number
      resources_count: number
      videos_count: number
      has_textbook: boolean
    }
  ) => void
}

type TabType = "textbook" | "resources" | "videos"

export function ChapterMediaModal({
  chapter,
  open,
  onOpenChange,
  initialTab = "textbook",
  onMediaUpdated,
}: ChapterMediaModalProps) {
  const [activeTab, setActiveTab] = useState<TabType>(initialTab)

  useEffect(() => {
    if (open && initialTab) {
      setActiveTab(initialTab)
    }
  }, [open, initialTab])
  const [isLoading, setIsLoading] = useState(false)
  const [feedbackNotice, setFeedbackNotice] = useState<{
    type: "success" | "error"
    message: string
  } | null>(null)

  // Data states
  const [textbook, setTextbook] = useState<ChapterTextbook | null>(null)
  const [resources, setResources] = useState<ChapterResource[]>([])
  const [videos, setVideos] = useState<ChapterVideo[]>([])

  // Textbook Upload / Replace State
  const [isReplacingTextbook, setIsReplacingTextbook] = useState(false)
  const [textbookFile, setTextbookFile] = useState<File | null>(null)
  const [textbookTitle, setTextbookTitle] = useState("")
  const [textbookUploadProgress, setTextbookUploadProgress] = useState<number | null>(null)
  const [isUploadingTextbook, setIsUploadingTextbook] = useState(false)
  const [isTextbookDragOver, setIsTextbookDragOver] = useState(false)

  // Resource Upload / Edit State
  const [resourceFile, setResourceFile] = useState<File | null>(null)
  const [resourceTitle, setResourceTitle] = useState("")
  const [resourceDesc, setResourceDesc] = useState("")
  const [resourceIsPaid, setResourceIsPaid] = useState(true)
  const [resourcePrice, setResourcePrice] = useState("50.00")
  const [isUploadingResource, setIsUploadingResource] = useState(false)
  const [resourceUploadProgress, setResourceUploadProgress] = useState<number | null>(null)
  const [isResourceDragOver, setIsResourceDragOver] = useState(false)
  const [showAddResourceDialog, setShowAddResourceDialog] = useState(false)
  const [editingResource, setEditingResource] = useState<ChapterResource | null>(null)
  const [editResourceTitle, setEditResourceTitle] = useState("")
  const [editResourceDesc, setEditResourceDesc] = useState("")
  const [editResourceIsPaid, setEditResourceIsPaid] = useState(true)
  const [editResourcePrice, setEditResourcePrice] = useState("50.00")
  const [isSavingResourceEdit, setIsSavingResourceEdit] = useState(false)

  // Dedicated Resource Pricing State (UPDATE Price)
  const [pricingResource, setPricingResource] = useState<ChapterResource | null>(null)
  const [pricingNewPrice, setPricingNewPrice] = useState("50.00")
  const [isSavingPrice, setIsSavingPrice] = useState(false)

  // Dedicated Remove Price (Make Free) State (DELETE Price)
  const [removePriceResource, setRemovePriceResource] = useState<ChapterResource | null>(null)
  const [isRemovingPrice, setIsRemovingPrice] = useState(false)

  // Video Add / Edit / Preview State
  const [showAddVideoDialog, setShowAddVideoDialog] = useState(false)
  const [videoTitle, setVideoTitle] = useState("")
  const [videoUrl, setVideoUrl] = useState("")
  const [videoDuration, setVideoDuration] = useState("")
  const [videoDesc, setVideoDesc] = useState("")
  const [isSubmittingVideo, setIsSubmittingVideo] = useState(false)
  const [editingVideo, setEditingVideo] = useState<ChapterVideo | null>(null)
  const [activePlayingVideoId, setActivePlayingVideoId] = useState<number | null>(null)

  // Delete Confirmation State
  const [deleteConfirmState, setDeleteConfirmState] = useState<{
    type: "textbook" | "resource" | "video"
    id?: number
    title?: string
  } | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)

  // File input refs
  const textbookFileInputRef = useRef<HTMLInputElement | null>(null)
  const resourceFileInputRef = useRef<HTMLInputElement | null>(null)

  // Notify parent component about current counts
  const emitCounts = (
    tb: ChapterTextbook | null,
    resList: ChapterResource[],
    vidList: ChapterVideo[]
  ) => {
    if (chapter && onMediaUpdated) {
      onMediaUpdated(chapter.id, {
        textbooks_count: tb ? 1 : 0,
        resources_count: resList.length,
        videos_count: vidList.length,
        has_textbook: Boolean(tb),
      })
    }
  }

  const showToast = (type: "success" | "error", message: string) => {
    setFeedbackNotice({ type, message })
    setTimeout(() => {
      setFeedbackNotice(null)
    }, 4500)
  }

  // Fetch all media when chapter changes or modal opens
  useEffect(() => {
    if (!open || !chapter?.id) return

    let isMounted = true
    setIsLoading(true)
    setIsReplacingTextbook(false)
    setTextbookFile(null)
    setResourceFile(null)
    setResourceTitle("")
    setResourceDesc("")
    setResourceIsPaid(true)
    setResourcePrice("50.00")
    setTextbookTitle(`${chapter.title} Official Textbook`)

    Promise.all([
      apiClient.get(`/admin/chapters/${chapter.id}/textbook`).catch(() => ({ data: { data: null } })),
      apiClient.get(`/admin/chapters/${chapter.id}/resources`).catch(() => ({ data: { data: [] } })),
      apiClient.get(`/admin/chapters/${chapter.id}/videos`).catch(() => ({ data: { data: [] } })),
    ])
      .then(([tbRes, resRes, vidRes]) => {
        if (!isMounted) return

        const tbData: ChapterTextbook | null = tbRes.data?.data || null
        const resData: ChapterResource[] = Array.isArray(resRes.data?.data)
          ? resRes.data.data
          : Array.isArray(resRes.data)
          ? resRes.data
          : []
        const vidData: ChapterVideo[] = Array.isArray(vidRes.data?.data)
          ? vidRes.data.data
          : Array.isArray(vidRes.data)
          ? vidRes.data
          : []

        setTextbook(tbData)
        setResources(resData)
        setVideos(vidData)
        emitCounts(tbData, resData, vidData)
      })
      .catch((err) => {
        console.error("Failed to load chapter media:", err)
        if (isMounted) {
          showToast("error", "Failed to load chapter assets from backend.")
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false)
      })

    return () => {
      isMounted = false
    }
  }, [open, chapter?.id])

  // Platform and ID helper for videos
  const parseVideoPlatform = (url: string) => {
    const trimmed = url.trim().toLowerCase()
    if (trimmed.includes("youtube.com") || trimmed.includes("youtu.be")) return "youtube"
    if (trimmed.includes("vimeo.com")) return "vimeo"
    if (trimmed.endsWith(".mp4") || trimmed.endsWith(".mov") || trimmed.endsWith(".webm"))
      return "direct"
    return "other"
  }

  const getYouTubeVideoId = (url: string): string | null => {
    const regExp = /(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/
    const match = url.match(regExp)
    return match ? match[1] : null
  }

  const getVimeoVideoId = (url: string): string | null => {
    const regExp = /(?:vimeo\.com\/)(\d+)/
    const match = url.match(regExp)
    return match ? match[1] : null
  }

  // =========================================================================
  // TAB 1: TEXTBOOK ACTIONS
  // =========================================================================
  const handleUploadTextbook = async (e?: React.FormEvent) => {
    if (e) e.preventDefault()
    if (!chapter?.id) return
    if (!textbookFile) {
      showToast("error", "Please select a PDF textbook file to upload.")
      return
    }

    if (textbookFile.size > 52428800) {
      showToast("error", "File is too large. Maximum permitted PDF size is 50MB.")
      return
    }

    setIsUploadingTextbook(true)
    setTextbookUploadProgress(10)

    const formData = new FormData()
    formData.append("file", textbookFile)
    if (textbookTitle.trim()) {
      formData.append("title", textbookTitle.trim())
    }

    try {
      const res = await apiClient.post(
        `/admin/chapters/${chapter.id}/textbook`,
        formData,
        {
          headers: { "Content-Type": "multipart/form-data" },
          onUploadProgress: (progressEvent) => {
            if (progressEvent.total) {
              const pct = Math.round((progressEvent.loaded * 100) / progressEvent.total)
              setTextbookUploadProgress(pct)
            }
          },
        }
      )

      const savedTb: ChapterTextbook = res.data?.data
      setTextbook(savedTb)
      setIsReplacingTextbook(false)
      setTextbookFile(null)
      setTextbookUploadProgress(null)
      emitCounts(savedTb, resources, videos)
      showToast("success", "Textbook PDF successfully saved and curriculum cache cleared!")
    } catch (err: any) {
      console.error("Textbook upload failed:", err)
      const msg =
        err.response?.data?.message ||
        err.response?.data?.error ||
        "Failed to upload textbook PDF."
      showToast("error", msg)
    } finally {
      setIsUploadingTextbook(false)
    }
  }

  const handleDeleteTextbook = async () => {
    if (!chapter?.id) return
    setIsDeleting(true)

    try {
      await apiClient.delete(`/admin/chapters/${chapter.id}/textbook`)
      setTextbook(null)
      setIsReplacingTextbook(false)
      setTextbookFile(null)
      setDeleteConfirmState(null)
      emitCounts(null, resources, videos)
      showToast("success", "Textbook removed and file deleted from storage.")
    } catch (err: any) {
      console.error("Failed to delete textbook:", err)
      showToast("error", "Failed to delete textbook.")
    } finally {
      setIsDeleting(false)
    }
  }

  // =========================================================================
  // TAB 2: RESOURCE ACTIONS
  // =========================================================================
  const handleUploadResource = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!chapter?.id) return
    if (!resourceFile) {
      showToast("error", "Please select a supplementary PDF file.")
      return
    }
    if (!resourceTitle.trim()) {
      showToast("error", "Please enter a title for the resource.")
      return
    }

    if (resourceFile.size > 52428800) {
      showToast("error", "File is too large. Maximum PDF size is 50MB.")
      return
    }

    setIsUploadingResource(true)
    setResourceUploadProgress(15)

    const formData = new FormData()
    formData.append("file", resourceFile)
    formData.append("title", resourceTitle.trim())
    if (resourceDesc.trim()) {
      formData.append("description", resourceDesc.trim())
    }
    const finalPrice = resourceIsPaid ? parseFloat(resourcePrice) || 50.0 : 0
    formData.append("price", String(finalPrice))

    try {
      const res = await apiClient.post(
        `/admin/chapters/${chapter.id}/resources`,
        formData,
        {
          headers: { "Content-Type": "multipart/form-data" },
          onUploadProgress: (progressEvent) => {
            if (progressEvent.total) {
              const pct = Math.round((progressEvent.loaded * 100) / progressEvent.total)
              setResourceUploadProgress(pct)
            }
          },
        }
      )

      const newRes: ChapterResource = res.data?.data
      const updatedList = [newRes, ...resources]
      setResources(updatedList)
      setResourceFile(null)
      setResourceTitle("")
      setResourceDesc("")
      setResourceIsPaid(true)
      setResourcePrice("50.00")
      setShowAddResourceDialog(false)
      setResourceUploadProgress(null)
      emitCounts(textbook, updatedList, videos)
      showToast("success", "Supplementary PDF resource uploaded successfully!")
    } catch (err: any) {
      console.error("Resource upload failed:", err)
      showToast(
        "error",
        err.response?.data?.message || "Failed to upload supplementary resource."
      )
    } finally {
      setIsUploadingResource(false)
    }
  }

  const handleUpdateResource = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!editingResource) return
    if (!editResourceTitle.trim()) return

    setIsSavingResourceEdit(true)
    const finalPrice = editResourceIsPaid ? parseFloat(editResourcePrice) || 50.0 : 0
    try {
      const res = await apiClient.put(`/admin/resources/${editingResource.id}`, {
        title: editResourceTitle.trim(),
        description: editResourceDesc.trim() || null,
        price: finalPrice,
      })

      const updatedRes: ChapterResource = res.data?.data || {
        ...editingResource,
        title: editResourceTitle.trim(),
        description: editResourceDesc.trim() || null,
        price: finalPrice,
      }

      setResources((prev) =>
        prev.map((item) => (item.id === updatedRes.id ? updatedRes : item))
      )
      setEditingResource(null)
      showToast("success", "Resource title, description, and pricing updated.")
    } catch (err) {
      console.error("Failed to update resource:", err)
      showToast("error", "Failed to update resource.")
    } finally {
      setIsSavingResourceEdit(false)
    }
  }

  // Dedicated Price Editing Handlers
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

      setResources((prev) =>
        prev.map((item) => (item.id === updatedRes.id ? updatedRes : item))
      )
      setPricingResource(null)
      showToast(
        "success",
        res.data?.message || `Resource price updated to ${parsedPrice.toFixed(2)} ETB.`
      )
    } catch (err: any) {
      console.error("Failed to update resource price:", err)
      showToast(
        "error",
        err.response?.data?.message || "Failed to update resource price."
      )
    } finally {
      setIsSavingPrice(false)
    }
  }

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

      setResources((prev) =>
        prev.map((item) => (item.id === updatedRes.id ? updatedRes : item))
      )
      setRemovePriceResource(null)
      showToast(
        "success",
        res.data?.message || "Price removed. This resource is now completely FREE for all students."
      )
    } catch (err: any) {
      console.error("Failed to remove resource price:", err)
      showToast(
        "error",
        err.response?.data?.message || "Failed to remove price."
      )
    } finally {
      setIsRemovingPrice(false)
    }
  }

  const handleDeleteResource = async (resourceId: number) => {
    setIsDeleting(true)
    try {
      await apiClient.delete(`/admin/resources/${resourceId}`)
      const updatedList = resources.filter((r) => r.id !== resourceId)
      setResources(updatedList)
      setDeleteConfirmState(null)
      emitCounts(textbook, updatedList, videos)
      showToast("success", "Resource removed and storage file deleted.")
    } catch (err) {
      console.error("Failed to delete resource:", err)
      showToast("error", "Failed to delete resource.")
    } finally {
      setIsDeleting(false)
    }
  }

  // =========================================================================
  // TAB 3: VIDEO ACTIONS
  // =========================================================================
  const handleSaveVideo = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!chapter?.id) return
    if (!videoTitle.trim() || !videoUrl.trim()) {
      showToast("error", "Please provide both video title and URL.")
      return
    }

    setIsSubmittingVideo(true)
    const payload = {
      title: videoTitle.trim(),
      video_url: videoUrl.trim(),
      duration: videoDuration.trim() || null,
      description: videoDesc.trim() || null,
    }

    try {
      if (editingVideo) {
        const res = await apiClient.put(`/admin/videos/${editingVideo.id}`, payload)
        const updatedVid: ChapterVideo = res.data?.data || {
          ...editingVideo,
          ...payload,
        }
        setVideos((prev) =>
          prev.map((v) => (v.id === updatedVid.id ? updatedVid : v))
        )
        showToast("success", "Video lesson details updated.")
      } else {
        const res = await apiClient.post(`/admin/chapters/${chapter.id}/videos`, payload)
        const newVid: ChapterVideo = res.data?.data
        const updatedList = [newVid, ...videos]
        setVideos(updatedList)
        emitCounts(textbook, resources, updatedList)
        showToast("success", "Video lesson added to chapter.")
      }

      setShowAddVideoDialog(false)
      setEditingVideo(null)
      setVideoTitle("")
      setVideoUrl("")
      setVideoDuration("")
      setVideoDesc("")
    } catch (err: any) {
      console.error("Failed to save video:", err)
      showToast(
        "error",
        err.response?.data?.message || "Failed to save video lesson."
      )
    } finally {
      setIsSubmittingVideo(false)
    }
  }

  const handleDeleteVideo = async (videoId: number) => {
    setIsDeleting(true)
    try {
      await apiClient.delete(`/admin/videos/${videoId}`)
      const updatedList = videos.filter((v) => v.id !== videoId)
      setVideos(updatedList)
      setDeleteConfirmState(null)
      if (activePlayingVideoId === videoId) {
        setActivePlayingVideoId(null)
      }
      emitCounts(textbook, resources, updatedList)
      showToast("success", "Video lesson removed.")
    } catch (err) {
      console.error("Failed to delete video:", err)
      showToast("error", "Failed to delete video.")
    } finally {
      setIsDeleting(false)
    }
  }

  const openAddVideoDialog = () => {
    setEditingVideo(null)
    setVideoTitle("")
    setVideoUrl("")
    setVideoDuration("")
    setVideoDesc("")
    setShowAddVideoDialog(true)
  }

  const openEditVideoDialog = (vid: ChapterVideo) => {
    setEditingVideo(vid)
    setVideoTitle(vid.title)
    setVideoUrl(vid.video_url)
    setVideoDuration(vid.duration || "")
    setVideoDesc(vid.description || "")
    setShowAddVideoDialog(true)
  }

  if (!chapter) return null

  // Live parsed video thumbnail preview
  const livePreviewYtId = getYouTubeVideoId(videoUrl)
  const livePlatform = parseVideoPlatform(videoUrl)

  return (
    <>
      <Sheet open={open} onOpenChange={onOpenChange}>
        <SheetContent
          side="right"
          className="w-full sm:max-w-2xl overflow-y-auto p-0 flex flex-col bg-slate-50 dark:bg-slate-950 border-l border-slate-200 dark:border-slate-800"
        >
          {/* Header */}
          <div className="p-5 sm:p-6 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800">
            <div className="flex items-center justify-between gap-3">
              <span className="inline-flex items-center gap-1.5 rounded-full bg-blue-500/10 px-2.5 py-0.5 text-[11px] font-bold text-blue-600 dark:text-blue-400">
                <BookOpen className="h-3.5 w-3.5" />
                Chapter Media & Asset Hub
              </span>
              <Badge variant="outline" className="text-xs font-mono font-bold">
                Ch #{chapter.chapter_number || 1}
              </Badge>
            </div>

            <SheetTitle className="text-xl font-black tracking-tight text-slate-900 dark:text-slate-100 mt-2">
              {chapter.title}
            </SheetTitle>

            <SheetDescription className="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-2">
              <span>{chapter.grade_name || "Grade"}</span>
              <span>•</span>
              <span className="font-medium text-slate-700 dark:text-slate-300">
                {chapter.subject_name || "Subject"}
              </span>
            </SheetDescription>

            {/* Quick Status Pill Counters */}
            <div className="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-slate-100 dark:border-slate-800/80 text-xs">
              <span
                className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-semibold border ${
                  textbook
                    ? "bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900"
                    : "bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900"
                }`}
              >
                {textbook ? (
                  <>
                    <FileCheck className="h-3.5 w-3.5" /> Textbook Ready
                  </>
                ) : (
                  <>
                    <AlertCircle className="h-3.5 w-3.5" /> Textbook Missing
                  </>
                )}
              </span>

              <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                <Paperclip className="h-3.5 w-3.5" /> {resources.length} Supplementary{" "}
                {resources.length === 1 ? "PDF" : "PDFs"}
              </span>

              <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-medium bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-900">
                <Film className="h-3.5 w-3.5" /> {videos.length} Video{" "}
                {videos.length === 1 ? "Lesson" : "Lessons"}
              </span>
            </div>

            {/* Tab Navigation */}
            <div className="grid grid-cols-3 gap-1 p-1 bg-slate-100 dark:bg-slate-800/60 rounded-xl mt-4">
              <button
                type="button"
                onClick={() => setActiveTab("textbook")}
                className={`flex items-center justify-center gap-1.5 py-2 px-3 text-xs font-semibold rounded-lg transition-all ${
                  activeTab === "textbook"
                    ? "bg-white text-blue-600 shadow-sm dark:bg-slate-900 dark:text-blue-400"
                    : "text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
                }`}
              >
                <BookOpen className="h-3.5 w-3.5" />
                <span>Textbook PDF</span>
                {textbook && (
                  <span className="h-1.5 w-1.5 rounded-full bg-emerald-500 ml-0.5" />
                )}
              </button>

              <button
                type="button"
                onClick={() => setActiveTab("resources")}
                className={`flex items-center justify-center gap-1.5 py-2 px-3 text-xs font-semibold rounded-lg transition-all ${
                  activeTab === "resources"
                    ? "bg-white text-blue-600 shadow-sm dark:bg-slate-900 dark:text-blue-400"
                    : "text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
                }`}
              >
                <Paperclip className="h-3.5 w-3.5" />
                <span>Resources</span>
                <span className="ml-1 rounded-full bg-slate-200 dark:bg-slate-700 px-1.5 py-0.2 text-[10px] font-bold">
                  {resources.length}
                </span>
              </button>

              <button
                type="button"
                onClick={() => setActiveTab("videos")}
                className={`flex items-center justify-center gap-1.5 py-2 px-3 text-xs font-semibold rounded-lg transition-all ${
                  activeTab === "videos"
                    ? "bg-white text-blue-600 shadow-sm dark:bg-slate-900 dark:text-blue-400"
                    : "text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
                }`}
              >
                <Video className="h-3.5 w-3.5" />
                <span>Video Lessons</span>
                <span className="ml-1 rounded-full bg-slate-200 dark:bg-slate-700 px-1.5 py-0.2 text-[10px] font-bold">
                  {videos.length}
                </span>
              </button>
            </div>
          </div>

          {/* Toast Notification Banner */}
          {feedbackNotice && (
            <div
              className={`mx-5 sm:mx-6 mt-4 p-3 rounded-lg flex items-center gap-2.5 text-xs font-semibold border ${
                feedbackNotice.type === "success"
                  ? "bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-900"
                  : "bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-900"
              }`}
            >
              {feedbackNotice.type === "success" ? (
                <CheckCircle2 className="h-4 w-4 shrink-0 text-emerald-600" />
              ) : (
                <AlertCircle className="h-4 w-4 shrink-0 text-rose-600" />
              )}
              <span className="flex-1">{feedbackNotice.message}</span>
              <button
                type="button"
                onClick={() => setFeedbackNotice(null)}
                className="text-slate-400 hover:text-slate-600"
              >
                <X className="h-3.5 w-3.5" />
              </button>
            </div>
          )}

          {/* Main Body */}
          <div className="flex-1 p-5 sm:p-6 space-y-6">
            {isLoading ? (
              <div className="py-16 flex flex-col items-center justify-center gap-3 text-slate-400">
                <Loader2 className="h-7 w-7 animate-spin text-blue-500" />
                <span className="text-xs">Loading chapter media assets...</span>
              </div>
            ) : (
              <>
                {/* ============================================================== */}
                {/* TAB 1: PRIMARY TEXTBOOK PDF                                     */}
                {/* ============================================================== */}
                {activeTab === "textbook" && (
                  <div className="space-y-5">
                    {textbook && !isReplacingTextbook ? (
                      /* Textbook Exists Card */
                      <div className="rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50/70 to-white dark:from-emerald-950/20 dark:to-slate-900 dark:border-emerald-900/60 p-5 shadow-sm space-y-4">
                        <div className="flex items-start gap-3.5">
                          <div className="p-3 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-900/60">
                            <FileText className="h-7 w-7" />
                          </div>
                          <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2">
                              <Badge className="bg-emerald-600 hover:bg-emerald-600 text-[10px] text-white">
                                Active Primary Textbook
                              </Badge>
                              {textbook.file_size_mb && (
                                <span className="text-xs font-mono font-semibold text-slate-500">
                                  {textbook.file_size_mb} MB
                                </span>
                              )}
                            </div>
                            <h4 className="text-base font-bold text-slate-900 dark:text-slate-100 mt-1 truncate">
                              {textbook.title}
                            </h4>
                            <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1 font-mono text-[11px]">
                              {textbook.file_path || textbook.file_url}
                            </p>
                            {textbook.created_at && (
                              <p className="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                                <Clock className="h-3 w-3" />
                                Uploaded on{" "}
                                {new Date(textbook.created_at).toLocaleDateString(undefined, {
                                  year: "numeric",
                                  month: "short",
                                  day: "numeric",
                                })}
                              </p>
                            )}
                          </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex flex-wrap items-center gap-2 pt-2 border-t border-emerald-100 dark:border-emerald-900/40">
                          <Button
                            size="sm"
                            className="bg-emerald-600 hover:bg-emerald-500 text-white text-xs h-8 gap-1.5 font-semibold"
                            onClick={() => window.open(textbook.file_url, "_blank")}
                          >
                            <ExternalLink className="h-3.5 w-3.5" />
                            View / Preview PDF
                          </Button>

                          <Button
                            size="sm"
                            variant="outline"
                            className="text-xs h-8 gap-1.5 border-slate-300 dark:border-slate-700"
                            onClick={() => {
                              setIsReplacingTextbook(true)
                              setTextbookFile(null)
                              setTextbookTitle(textbook.title)
                            }}
                          >
                            <RefreshCw className="h-3.5 w-3.5" />
                            Replace PDF File
                          </Button>

                          <Button
                            size="sm"
                            variant="ghost"
                            className="text-xs h-8 gap-1.5 text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/30 ml-auto"
                            onClick={() =>
                              setDeleteConfirmState({
                                type: "textbook",
                                title: textbook.title,
                              })
                            }
                          >
                            <Trash2 className="h-3.5 w-3.5" />
                            Delete Textbook
                          </Button>
                        </div>
                      </div>
                    ) : (
                      /* Drag & Drop Upload Zone */
                      <form
                        onSubmit={handleUploadTextbook}
                        className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm space-y-4"
                      >
                        <div className="flex items-center justify-between">
                          <div>
                            <h4 className="text-sm font-bold text-slate-900 dark:text-slate-100">
                              {isReplacingTextbook
                                ? "Replace Primary Textbook PDF"
                                : "Upload Official Chapter Textbook"}
                            </h4>
                            <p className="text-xs text-slate-500">
                              Accepts official curriculum PDF (Max 50 MB).
                            </p>
                          </div>
                          {isReplacingTextbook && (
                            <Button
                              type="button"
                              variant="ghost"
                              size="sm"
                              className="text-xs h-7"
                              onClick={() => setIsReplacingTextbook(false)}
                            >
                              Cancel Replace
                            </Button>
                          )}
                        </div>

                        {/* Dropzone */}
                        <div
                          onDragOver={(e) => {
                            e.preventDefault()
                            setIsTextbookDragOver(true)
                          }}
                          onDragLeave={() => setIsTextbookDragOver(false)}
                          onDrop={(e) => {
                            e.preventDefault()
                            setIsTextbookDragOver(false)
                            const files = e.dataTransfer.files
                            if (files && files[0]) {
                              if (files[0].type === "application/pdf" || files[0].name.endsWith(".pdf")) {
                                setTextbookFile(files[0])
                              } else {
                                showToast("error", "Only PDF files are supported for textbooks.")
                              }
                            }
                          }}
                          onClick={() => textbookFileInputRef.current?.click()}
                          className={`border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition-all ${
                            isTextbookDragOver
                              ? "border-blue-500 bg-blue-50/50 dark:bg-blue-950/20"
                              : textbookFile
                              ? "border-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/20"
                              : "border-slate-300 dark:border-slate-700 hover:border-slate-400 bg-slate-50/50 dark:bg-slate-900/50"
                          }`}
                        >
                          <input
                            type="file"
                            ref={textbookFileInputRef}
                            accept=".pdf,application/pdf"
                            className="hidden"
                            onChange={(e) => {
                              const files = e.target.files
                              if (files && files[0]) {
                                setTextbookFile(files[0])
                              }
                            }}
                          />

                          {textbookFile ? (
                            <div className="flex flex-col items-center gap-2">
                              <div className="p-3 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/60 dark:text-emerald-300">
                                <FileCheck className="h-6 w-6" />
                              </div>
                              <div>
                                <p className="text-xs font-bold text-slate-900 dark:text-slate-100">
                                  {textbookFile.name}
                                </p>
                                <p className="text-[11px] font-mono text-slate-500">
                                  {(textbookFile.size / 1024 / 1024).toFixed(2)} MB • Ready to upload
                                </p>
                              </div>
                              <span className="text-[10px] text-blue-600 hover:underline mt-1">
                                Click or drop another file to change
                              </span>
                            </div>
                          ) : (
                            <div className="flex flex-col items-center gap-2">
                              <div className="p-3 rounded-full bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                                <UploadCloud className="h-6 w-6" />
                              </div>
                              <div>
                                <p className="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                  Click to browse or drag & drop textbook PDF
                                </p>
                                <p className="text-[11px] text-slate-400">
                                  Standard PDF up to 50MB
                                </p>
                              </div>
                            </div>
                          )}
                        </div>

                        {/* Title input */}
                        <div className="space-y-1.5">
                          <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Textbook Title
                          </Label>
                          <Input
                            placeholder="e.g. Chapter 1 Official Textbook"
                            value={textbookTitle}
                            onChange={(e) => setTextbookTitle(e.target.value)}
                            className="text-xs"
                          />
                        </div>

                        {/* Progress Bar */}
                        {textbookUploadProgress !== null && (
                          <div className="space-y-1">
                            <div className="flex justify-between text-[11px] text-slate-500">
                              <span>Uploading to server...</span>
                              <span className="font-mono font-bold">
                                {textbookUploadProgress}%
                              </span>
                            </div>
                            <div className="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                              <div
                                className="h-full bg-blue-600 transition-all duration-300"
                                style={{ width: `${textbookUploadProgress}%` }}
                              />
                            </div>
                          </div>
                        )}

                        <Button
                          type="submit"
                          disabled={!textbookFile || isUploadingTextbook}
                          className="w-full bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold h-9 gap-1.5"
                        >
                          {isUploadingTextbook ? (
                            <>
                              <Loader2 className="h-4 w-4 animate-spin" />
                              Uploading ({textbookUploadProgress || 0}%)...
                            </>
                          ) : (
                            <>
                              <UploadCloud className="h-4 w-4" />
                              Save & Deploy Textbook PDF
                            </>
                          )}
                        </Button>
                      </form>
                    )}
                  </div>
                )}

                {/* ============================================================== */}
                {/* TAB 2: SUPPLEMENTARY PDF RESOURCES                              */}
                {/* ============================================================== */}
                {activeTab === "resources" && (
                  <div className="space-y-4">
                    <div className="flex items-center justify-between">
                      <div>
                        <h4 className="text-sm font-bold text-slate-900 dark:text-slate-100">
                          Supplementary Attachments
                        </h4>
                        <p className="text-xs text-slate-500">
                          Worksheets, formula cheatsheets, and summaries for students.
                        </p>
                      </div>
                      <Button
                        size="sm"
                        onClick={() => {
                          setResourceFile(null)
                          setResourceTitle("")
                          setResourceDesc("")
                          setShowAddResourceDialog(true)
                        }}
                        className="bg-blue-600 hover:bg-blue-500 text-white text-xs h-8 gap-1.5 font-semibold"
                      >
                        <Plus className="h-3.5 w-3.5" />
                        Upload New PDF
                      </Button>
                    </div>

                    {resources.length === 0 ? (
                      <div className="rounded-xl border border-dashed border-slate-300 dark:border-slate-800 p-8 text-center bg-white dark:bg-slate-900/50">
                        <Paperclip className="h-8 w-8 mx-auto text-slate-400 mb-2" />
                        <h5 className="text-xs font-bold text-slate-700 dark:text-slate-300">
                          No Supplementary PDFs Yet
                        </h5>
                        <p className="text-[11px] text-slate-500 max-w-sm mx-auto mt-1 mb-3">
                          Upload formula cheat sheets, past exam papers, or homework attachments for this chapter.
                        </p>
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => setShowAddResourceDialog(true)}
                          className="text-xs h-8 gap-1.5"
                        >
                          <Plus className="h-3.5 w-3.5" />
                          Add First Resource
                        </Button>
                      </div>
                    ) : (
                      <div className="space-y-2.5">
                        {resources.map((res) => (
                          <div
                            key={res.id}
                            className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-slate-300 dark:hover:border-slate-700 transition"
                          >
                            <div className="flex items-start gap-3 min-w-0">
                              <div className="p-2.5 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400 shrink-0">
                                <FileText className="h-5 w-5" />
                              </div>
                              <div className="min-w-0">
                                <h5 className="text-xs font-bold text-slate-900 dark:text-slate-100 truncate">
                                  {res.title}
                                </h5>
                                {res.description && (
                                  <p className="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                                    {res.description}
                                  </p>
                                )}
                                <div className="flex flex-wrap items-center gap-2 mt-1.5 text-[10px]">
                                  {Number(res.price) > 0 ? (
                                    <Badge
                                      variant="outline"
                                      className="bg-amber-50 text-amber-700 border-amber-300 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60 text-[10px] px-2 py-0.5 gap-1 font-semibold"
                                    >
                                      <Lock className="h-2.5 w-2.5" />
                                      Locked ({Number(res.price).toFixed(2)} ETB)
                                    </Badge>
                                  ) : (
                                    <Badge
                                      variant="outline"
                                      className="bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60 text-[10px] px-2 py-0.5 gap-1 font-semibold"
                                    >
                                      <Unlock className="h-2.5 w-2.5" />
                                      Free Access (0.00 ETB)
                                    </Badge>
                                  )}
                                  <span className="text-slate-400 uppercase font-semibold">
                                    {res.file_type || "PDF"}
                                  </span>
                                  {res.file_size_mb && (
                                    <>
                                      <span className="text-slate-300 dark:text-slate-600">•</span>
                                      <span className="font-mono text-slate-400">{res.file_size_mb} MB</span>
                                    </>
                                  )}
                                  {res.created_at && (
                                    <>
                                      <span className="text-slate-300 dark:text-slate-600">•</span>
                                      <span className="text-slate-400">
                                        {new Date(res.created_at).toLocaleDateString()}
                                      </span>
                                    </>
                                  )}
                                </div>
                              </div>
                            </div>

                            {/* Actions */}
                            <div className="flex flex-wrap items-center gap-1.5 self-end sm:self-center shrink-0">
                              <Button
                                size="sm"
                                variant="outline"
                                className="h-7 text-xs px-2.5 gap-1"
                                onClick={() =>
                                  window.open(res.file_url || res.file_path, "_blank")
                                }
                              >
                                <ExternalLink className="h-3 w-3" />
                                View
                              </Button>

                              {/* 💰 Edit Price Button */}
                              <Button
                                size="sm"
                                variant="outline"
                                className="h-7 text-xs px-2.5 gap-1 border-amber-300 bg-amber-50/60 hover:bg-amber-100 text-amber-800 dark:border-amber-800/60 dark:bg-amber-950/30 dark:hover:bg-amber-950/60 dark:text-amber-300 font-semibold"
                                onClick={() => handleOpenPriceModal(res)}
                                title="Update Price"
                              >
                                <Coins className="h-3.5 w-3.5 text-amber-600 dark:text-amber-400" />
                                <span>Edit Price</span>
                              </Button>

                              {/* 🔓 Remove Price (Make Free) Button */}
                              {Number(res.price) > 0 && (
                                <Button
                                  size="sm"
                                  variant="outline"
                                  className="h-7 text-xs px-2.5 gap-1 border-emerald-300 bg-emerald-50/60 hover:bg-emerald-100 text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/30 dark:hover:bg-emerald-950/60 dark:text-emerald-300 font-semibold"
                                  onClick={() => setRemovePriceResource(res)}
                                  title="Remove price and make free for all students"
                                >
                                  <Unlock className="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                                  <span>Make Free</span>
                                </Button>
                              )}

                              <Button
                                size="sm"
                                variant="ghost"
                                className="h-7 w-7 p-0 text-slate-500 hover:text-slate-900 dark:hover:text-slate-100"
                                onClick={() => {
                                  setEditingResource(res)
                                  setEditResourceTitle(res.title)
                                  setEditResourceDesc(res.description || "")
                                  const priceNum = res.price !== undefined && res.price !== null ? Number(res.price) : 50
                                  setEditResourceIsPaid(priceNum > 0)
                                  setEditResourcePrice(
                                    res.price !== undefined && res.price !== null
                                      ? String(res.price)
                                      : "50.00"
                                  )
                                }}
                                title="Edit Resource Details"
                              >
                                <Edit2 className="h-3.5 w-3.5" />
                              </Button>

                              <Button
                                size="sm"
                                variant="ghost"
                                className="h-7 w-7 p-0 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                                onClick={() =>
                                  setDeleteConfirmState({
                                    type: "resource",
                                    id: res.id,
                                    title: res.title,
                                  })
                                }
                                title="Delete Resource"
                              >
                                <Trash2 className="h-3.5 w-3.5" />
                              </Button>
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                )}

                {/* ============================================================== */}
                {/* TAB 3: VIDEO LESSONS & URLS                                     */}
                {/* ============================================================== */}
                {activeTab === "videos" && (
                  <div className="space-y-4">
                    <div className="flex items-center justify-between">
                      <div>
                        <h4 className="text-sm font-bold text-slate-900 dark:text-slate-100">
                          Video Lessons & Lectures
                        </h4>
                        <p className="text-xs text-slate-500">
                          Curated video links from YouTube, Vimeo, or direct MP4 stream.
                        </p>
                      </div>
                      <Button
                        size="sm"
                        onClick={openAddVideoDialog}
                        className="bg-blue-600 hover:bg-blue-500 text-white text-xs h-8 gap-1.5 font-semibold"
                      >
                        <Plus className="h-3.5 w-3.5" />
                        Add Video Lesson
                      </Button>
                    </div>

                    {videos.length === 0 ? (
                      <div className="rounded-xl border border-dashed border-slate-300 dark:border-slate-800 p-8 text-center bg-white dark:bg-slate-900/50">
                        <Video className="h-8 w-8 mx-auto text-slate-400 mb-2" />
                        <h5 className="text-xs font-bold text-slate-700 dark:text-slate-300">
                          No Video Lessons Added
                        </h5>
                        <p className="text-[11px] text-slate-500 max-w-sm mx-auto mt-1 mb-3">
                          Attach YouTube tutorials, teacher classroom recordings, or animated explainer videos for this chapter.
                        </p>
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={openAddVideoDialog}
                          className="text-xs h-8 gap-1.5"
                        >
                          <Plus className="h-3.5 w-3.5" />
                          Add Video URL
                        </Button>
                      </div>
                    ) : (
                      <div className="space-y-3">
                        {videos.map((vid) => {
                          const platform = vid.platform || parseVideoPlatform(vid.video_url)
                          const ytId = getYouTubeVideoId(vid.video_url)
                          const vimeoId = getVimeoVideoId(vid.video_url)
                          const isPlayingThis = activePlayingVideoId === vid.id

                          return (
                            <div
                              key={vid.id}
                              className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition"
                            >
                              <div className="p-4 flex flex-col sm:flex-row gap-3.5">
                                {/* Thumbnail / Video Preview Tile */}
                                <div className="relative w-full sm:w-40 aspect-video rounded-lg overflow-hidden bg-slate-950 shrink-0 border border-slate-200/40">
                                  {vid.thumbnail_url || ytId ? (
                                    <img
                                      src={
                                        vid.thumbnail_url ||
                                        `https://img.youtube.com/vi/${ytId}/hqdefault.jpg`
                                      }
                                      alt={vid.title}
                                      className="w-full h-full object-cover"
                                    />
                                  ) : (
                                    <div className="w-full h-full flex items-center justify-center text-slate-600">
                                      <Film className="h-8 w-8" />
                                    </div>
                                  )}

                                  {/* Play Trigger Button */}
                                  <button
                                    type="button"
                                    onClick={() =>
                                      setActivePlayingVideoId(isPlayingThis ? null : vid.id)
                                    }
                                    className="absolute inset-0 m-auto h-9 w-9 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center transition hover:scale-105 shadow-md"
                                    title={isPlayingThis ? "Close Preview" : "Play Preview"}
                                  >
                                    <Play className="h-4 w-4 fill-white ml-0.5" />
                                  </button>

                                  {vid.duration && (
                                    <span className="absolute bottom-1 right-1 bg-black/80 text-white text-[9px] font-mono font-bold px-1 rounded">
                                      {vid.duration}
                                    </span>
                                  )}
                                </div>

                                {/* Video Info */}
                                <div className="flex-1 min-w-0 flex flex-col justify-between">
                                  <div>
                                    <div className="flex items-center gap-1.5 flex-wrap">
                                      {platform === "youtube" && (
                                        <Badge className="bg-red-600 hover:bg-red-600 text-white text-[9px] px-1.5 py-0 font-bold">
                                          YouTube
                                        </Badge>
                                      )}
                                      {platform === "vimeo" && (
                                        <Badge className="bg-sky-500 hover:bg-sky-500 text-white text-[9px] px-1.5 py-0 font-bold">
                                          Vimeo
                                        </Badge>
                                      )}
                                      {platform === "direct" && (
                                        <Badge className="bg-emerald-600 hover:bg-emerald-600 text-white text-[9px] px-1.5 py-0 font-bold">
                                          Direct MP4
                                        </Badge>
                                      )}
                                      {platform === "other" && (
                                        <Badge variant="outline" className="text-[9px]">
                                          Web Link
                                        </Badge>
                                      )}
                                    </div>

                                    <h5 className="text-xs font-bold text-slate-900 dark:text-slate-100 mt-1 line-clamp-1">
                                      {vid.title}
                                    </h5>

                                    {vid.description && (
                                      <p className="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                                        {vid.description}
                                      </p>
                                    )}

                                    <p className="text-[10px] font-mono text-slate-400 mt-1 truncate">
                                      {vid.video_url}
                                    </p>
                                  </div>

                                  {/* Actions */}
                                  <div className="flex items-center gap-1.5 mt-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                    <Button
                                      size="sm"
                                      variant={isPlayingThis ? "secondary" : "outline"}
                                      className="h-7 text-xs px-2 gap-1"
                                      onClick={() =>
                                        setActivePlayingVideoId(
                                          isPlayingThis ? null : vid.id
                                        )
                                      }
                                    >
                                      <Play className="h-3 w-3" />
                                      {isPlayingThis ? "Hide Player" : "Play Preview"}
                                    </Button>

                                    <Button
                                      size="sm"
                                      variant="ghost"
                                      className="h-7 text-xs px-2 gap-1 text-slate-600 hover:text-slate-900"
                                      onClick={() => window.open(vid.video_url, "_blank")}
                                    >
                                      <ExternalLink className="h-3 w-3" />
                                      Open Link
                                    </Button>

                                    <Button
                                      size="sm"
                                      variant="ghost"
                                      className="h-7 w-7 p-0 text-slate-500 hover:text-slate-900 ml-auto"
                                      onClick={() => openEditVideoDialog(vid)}
                                      title="Edit Video"
                                    >
                                      <Edit2 className="h-3.5 w-3.5" />
                                    </Button>

                                    <Button
                                      size="sm"
                                      variant="ghost"
                                      className="h-7 w-7 p-0 text-rose-500 hover:text-rose-700 hover:bg-rose-50"
                                      onClick={() =>
                                        setDeleteConfirmState({
                                          type: "video",
                                          id: vid.id,
                                          title: vid.title,
                                        })
                                      }
                                      title="Delete Video"
                                    >
                                      <Trash2 className="h-3.5 w-3.5" />
                                    </Button>
                                  </div>
                                </div>
                              </div>

                              {/* Inline Player Embed Drawer */}
                              {isPlayingThis && (
                                <div className="border-t border-slate-200 dark:border-slate-800 bg-black p-3">
                                  <div className="relative w-full aspect-video rounded-lg overflow-hidden bg-black max-w-lg mx-auto">
                                    {ytId ? (
                                      <iframe
                                        src={`https://www.youtube-nocookie.com/embed/${ytId}?autoplay=1`}
                                        title={vid.title}
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowFullScreen
                                        className="w-full h-full border-0"
                                      />
                                    ) : vimeoId ? (
                                      <iframe
                                        src={`https://player.vimeo.com/video/${vimeoId}?autoplay=1`}
                                        title={vid.title}
                                        allow="autoplay; fullscreen; picture-in-picture"
                                        allowFullScreen
                                        className="w-full h-full border-0"
                                      />
                                    ) : (
                                      <video
                                        src={vid.video_url}
                                        controls
                                        autoPlay
                                        className="w-full h-full object-contain"
                                      />
                                    )}
                                  </div>
                                </div>
                              )}
                            </div>
                          )
                        })}
                      </div>
                    )}
                  </div>
                )}
              </>
            )}
          </div>
        </SheetContent>
      </Sheet>

      {/* ========================================================================= */}
      {/* DIALOG: UPLOAD NEW PDF RESOURCE                                            */}
      {/* ========================================================================= */}
      <Dialog open={showAddResourceDialog} onOpenChange={setShowAddResourceDialog}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Upload Supplementary PDF</DialogTitle>
            <DialogDescription>
              Attach worksheets, summaries, or formula cheatsheets for this chapter.
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleUploadResource} className="space-y-4 pt-2">
            <div className="space-y-1.5">
              <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Resource Title
              </Label>
              <Input
                required
                placeholder="e.g. Chapter 1 Formula Sheet & Review Notes"
                value={resourceTitle}
                onChange={(e) => setResourceTitle(e.target.value)}
                className="text-xs"
              />
            </div>

            <div className="space-y-1.5">
              <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Description (Optional)
              </Label>
              <Input
                placeholder="e.g. Handy summary for midterm review"
                value={resourceDesc}
                onChange={(e) => setResourceDesc(e.target.value)}
                className="text-xs"
              />
            </div>

            {/* Access Tier & Paywall Selector */}
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <Label htmlFor="resource-price" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Resource Pricing (ETB)
                </Label>
                <div className="flex items-center gap-1">
                  {[
                    { label: "Free (0 ETB)", value: "0" },
                    { label: "25 ETB", value: "25" },
                    { label: "50 ETB", value: "50" },
                    { label: "100 ETB", value: "100" },
                  ].map((preset) => {
                    const isSelected = String(parseFloat(resourcePrice) || 0) === String(parseFloat(preset.value))
                    return (
                      <button
                        key={preset.value}
                        type="button"
                        onClick={() => {
                          setResourcePrice(preset.value)
                          setResourceIsPaid(preset.value !== "0")
                        }}
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
                  id="resource-price"
                  type="number"
                  min="0"
                  step="5"
                  placeholder="50.00"
                  value={resourcePrice}
                  onChange={(e) => {
                    const val = e.target.value
                    setResourcePrice(val)
                    setResourceIsPaid(Number(val) > 0)
                  }}
                  className="pr-14 text-xs font-semibold"
                />
                <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400">
                  ETB
                </span>
              </div>
              <p className="text-[11px] text-slate-500">
                Set initial price in ETB (default 50.00). Quick presets: Free (0 ETB), 25, 50, 100 ETB.
              </p>
            </div>

            {/* Dropzone */}
            <div
              onDragOver={(e) => {
                e.preventDefault()
                setIsResourceDragOver(true)
              }}
              onDragLeave={() => setIsResourceDragOver(false)}
              onDrop={(e) => {
                e.preventDefault()
                setIsResourceDragOver(false)
                const files = e.dataTransfer.files
                if (files && files[0]) {
                  setResourceFile(files[0])
                }
              }}
              onClick={() => resourceFileInputRef.current?.click()}
              className={`border-2 border-dashed rounded-xl p-5 text-center cursor-pointer transition ${
                isResourceDragOver
                  ? "border-blue-500 bg-blue-50/50"
                  : resourceFile
                  ? "border-emerald-400 bg-emerald-50/40"
                  : "border-slate-300 dark:border-slate-700 hover:border-slate-400"
              }`}
            >
              <input
                type="file"
                ref={resourceFileInputRef}
                accept=".pdf,application/pdf"
                className="hidden"
                onChange={(e) => {
                  const files = e.target.files
                  if (files && files[0]) {
                    setResourceFile(files[0])
                  }
                }}
              />

              {resourceFile ? (
                <div className="flex items-center justify-center gap-2 text-xs font-bold text-slate-800 dark:text-slate-200">
                  <FileCheck className="h-4 w-4 text-emerald-600" />
                  <span>{resourceFile.name}</span>
                  <span className="font-mono text-[10px] text-slate-500">
                    ({(resourceFile.size / 1024 / 1024).toFixed(2)} MB)
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

            {resourceUploadProgress !== null && (
              <div className="space-y-1">
                <div className="flex justify-between text-[11px] text-slate-500">
                  <span>Uploading resource...</span>
                  <span className="font-mono font-bold">
                    {resourceUploadProgress}%
                  </span>
                </div>
                <div className="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                  <div
                    className="h-full bg-blue-600 transition-all duration-300"
                    style={{ width: `${resourceUploadProgress}%` }}
                  />
                </div>
              </div>
            )}

            <DialogFooter className="pt-2">
              <Button
                type="button"
                variant="outline"
                onClick={() => setShowAddResourceDialog(false)}
                className="text-xs"
              >
                Cancel
              </Button>
              <Button
                type="submit"
                disabled={!resourceFile || !resourceTitle.trim() || isUploadingResource}
                className="bg-blue-600 text-xs font-bold text-white hover:bg-blue-500 gap-1.5"
              >
                {isUploadingResource ? (
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
      {/* DIALOG: EDIT RESOURCE METADATA                                             */}
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

          <form onSubmit={handleUpdateResource} className="space-y-4 pt-2">
            <div className="space-y-1.5">
              <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Resource Title
              </Label>
              <Input
                required
                value={editResourceTitle}
                onChange={(e) => setEditResourceTitle(e.target.value)}
                className="text-xs"
              />
            </div>

            <div className="space-y-1.5">
              <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Description (Optional)
              </Label>
              <Input
                value={editResourceDesc}
                onChange={(e) => setEditResourceDesc(e.target.value)}
                className="text-xs"
              />
            </div>

            {/* Access Tier & Paywall Selector */}
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <Label htmlFor="edit-resource-price" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Unlock Price (ETB)
                </Label>
                <div className="flex items-center gap-1">
                  {[
                    { label: "Free (0 ETB)", value: "0" },
                    { label: "25 ETB", value: "25" },
                    { label: "50 ETB", value: "50" },
                    { label: "100 ETB", value: "100" },
                  ].map((preset) => {
                    const isSelected = String(parseFloat(editResourcePrice) || 0) === String(parseFloat(preset.value))
                    return (
                      <button
                        key={preset.value}
                        type="button"
                        onClick={() => {
                          setEditResourcePrice(preset.value)
                          setEditResourceIsPaid(preset.value !== "0")
                        }}
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
                  id="edit-resource-price"
                  type="number"
                  min="0"
                  step="5"
                  placeholder="50.00"
                  value={editResourcePrice}
                  onChange={(e) => {
                    const val = e.target.value
                    setEditResourcePrice(val)
                    setEditResourceIsPaid(Number(val) > 0)
                  }}
                  className="pr-14 text-xs font-semibold"
                />
                <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400">
                  ETB
                </span>
              </div>
              <p className="text-xs text-slate-500">
                Adjust price in ETB. Choose Free (0 ETB) to unlock for all students.
              </p>
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
                disabled={isSavingResourceEdit || !editResourceTitle.trim()}
                className="bg-blue-600 text-xs font-bold text-white hover:bg-blue-500"
              >
                {isSavingResourceEdit ? (
                  <>
                    <Loader2 className="h-3.5 w-3.5 animate-spin" /> Saving...
                  </>
                ) : (
                  "Save Changes"
                )}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* ========================================================================= */}
      {/* DIALOG: ADD / EDIT VIDEO LESSON URL                                        */}
      {/* ========================================================================= */}
      <Dialog open={showAddVideoDialog} onOpenChange={setShowAddVideoDialog}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>
              {editingVideo ? "Edit Video Lesson" : "Add Video Lesson URL"}
            </DialogTitle>
            <DialogDescription>
              Add YouTube, Vimeo, or direct MP4 lecture link with instant preview.
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleSaveVideo} className="space-y-4 pt-2">
            <div className="space-y-1.5">
              <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Video Lesson Title
              </Label>
              <Input
                required
                placeholder="e.g. Thermodynamics Part 1: Heat Transfer Explained"
                value={videoTitle}
                onChange={(e) => setVideoTitle(e.target.value)}
                className="text-xs"
              />
            </div>

            <div className="space-y-1.5">
              <div className="flex items-center justify-between">
                <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Video URL (YouTube, Vimeo, MP4)
                </Label>
                {livePlatform !== "other" && (
                  <Badge className="text-[9px] uppercase font-bold tracking-wider">
                    {livePlatform}
                  </Badge>
                )}
              </div>
              <Input
                required
                type="url"
                placeholder="https://www.youtube.com/watch?v=..."
                value={videoUrl}
                onChange={(e) => setVideoUrl(e.target.value)}
                className="text-xs font-mono"
              />
            </div>

            {/* Live Detected Thumbnail Preview */}
            {livePreviewYtId && (
              <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60 p-2.5 flex items-center gap-3">
                <img
                  src={`https://img.youtube.com/vi/${livePreviewYtId}/hqdefault.jpg`}
                  alt="YouTube Preview"
                  className="w-24 aspect-video object-cover rounded-lg"
                />
                <div className="text-[11px] min-w-0">
                  <span className="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                    <CheckCircle2 className="h-3 w-3" /> YouTube ID Verified
                  </span>
                  <p className="font-mono text-slate-400 mt-0.5 truncate">
                    ID: {livePreviewYtId}
                  </p>
                </div>
              </div>
            )}

            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Duration (Optional)
                </Label>
                <Input
                  placeholder="e.g. 14:20 min"
                  value={videoDuration}
                  onChange={(e) => setVideoDuration(e.target.value)}
                  className="text-xs"
                />
              </div>

              <div className="space-y-1.5">
                <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Description / Topic Notes
                </Label>
                <Input
                  placeholder="e.g. Section 2.3 drill"
                  value={videoDesc}
                  onChange={(e) => setVideoDesc(e.target.value)}
                  className="text-xs"
                />
              </div>
            </div>

            <DialogFooter className="pt-2">
              <Button
                type="button"
                variant="outline"
                onClick={() => setShowAddVideoDialog(false)}
                className="text-xs"
              >
                Cancel
              </Button>
              <Button
                type="submit"
                disabled={isSubmittingVideo || !videoTitle.trim() || !videoUrl.trim()}
                className="bg-blue-600 text-xs font-bold text-white hover:bg-blue-500"
              >
                {isSubmittingVideo ? (
                  <>
                    <Loader2 className="h-3.5 w-3.5 animate-spin" /> Saving...
                  </>
                ) : editingVideo ? (
                  "Update Video"
                ) : (
                  "Add Video Lesson"
                )}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* ========================================================================= */}
      {/* DIALOG: DELETE CONFIRMATION MODAL                                          */}
      {/* ========================================================================= */}
      <Dialog
        open={Boolean(deleteConfirmState)}
        onOpenChange={(open) => {
          if (!open) setDeleteConfirmState(null)
        }}
      >
        <DialogContent className="sm:max-w-sm">
          <DialogHeader>
            <DialogTitle className="text-rose-600 flex items-center gap-2">
              <AlertCircle className="h-5 w-5" />
              Delete {deleteConfirmState?.type === "textbook" ? "Textbook PDF" : deleteConfirmState?.type === "resource" ? "Resource Attachment" : "Video Lesson"}?
            </DialogTitle>
            <DialogDescription className="text-xs text-slate-600 dark:text-slate-400">
              Are you sure you want to delete{" "}
              <strong>&ldquo;{deleteConfirmState?.title || "this item"}&rdquo;</strong>?
              {deleteConfirmState?.type !== "video" && (
                <span className="block mt-1 text-rose-600 font-semibold">
                  This will permanently remove the file from storage.
                </span>
              )}
            </DialogDescription>
          </DialogHeader>

          <DialogFooter className="pt-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => setDeleteConfirmState(null)}
              className="text-xs"
            >
              Cancel
            </Button>
            <Button
              type="button"
              disabled={isDeleting}
              onClick={() => {
                if (deleteConfirmState?.type === "textbook") {
                  handleDeleteTextbook()
                } else if (deleteConfirmState?.type === "resource" && deleteConfirmState.id) {
                  handleDeleteResource(deleteConfirmState.id)
                } else if (deleteConfirmState?.type === "video" && deleteConfirmState.id) {
                  handleDeleteVideo(deleteConfirmState.id)
                }
              }}
              className="bg-rose-600 hover:bg-rose-500 text-xs font-bold text-white"
            >
              {isDeleting ? (
                <>
                  <Loader2 className="h-3.5 w-3.5 animate-spin mr-1" /> Deleting...
                </>
              ) : (
                "Confirm Delete"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ========================================================================= */}
      {/* DIALOG: EDIT RESOURCE PRICE (UPDATE)                                      */}
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
            {/* Current price readout */}
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
                <Label htmlFor="new-resource-price" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
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
                  id="new-resource-price"
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
                Dispatches PATCH /api/admin/resources/{pricingResource?.id}/price and flushes system cache.
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
      {/* DIALOG: REMOVE PRICE (MAKE FREE) CONFIRMATION                             */}
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
              Remove Price &amp; Unlock for All Students?
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
              Note: The resource file will NOT be deleted.
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
    </>
  )
}
