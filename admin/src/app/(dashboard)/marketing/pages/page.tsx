"use client"

import React, { useState, useEffect, useCallback } from "react"
import { apiClient } from "@/lib/api-client"
import { CmsPage, CmsPageSection } from "@/types/cms"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
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
  DialogTrigger,
} from "@/components/ui/dialog"
import {
  FileText,
  Save,
  PlusCircle,
  Clock,
  Trash2,
  CheckCircle2,
  BookOpen,
  HelpCircle,
  Shield,
  Globe,
  Loader2,
  RefreshCw,
  Eye,
  EyeOff,
  AlertCircle,
} from "lucide-react"

export default function CmsPagesEditorPage() {
  const [pages, setPages] = useState<CmsPage[]>([])
  const [activePageId, setActivePageId] = useState<number | null>(null)
  const [activePage, setActivePage] = useState<CmsPage | null>(null)
  const [loading, setLoading] = useState(true)
  const [pageLoading, setPageLoading] = useState(false)
  const [saving, setSaving] = useState(false)
  const [saveFeedback, setSaveFeedback] = useState<string | null>(null)
  const [errorFeedback, setErrorFeedback] = useState<string | null>(null)
  const [isAddModalOpen, setIsAddModalOpen] = useState(false)

  // Add Item / Section Form State
  const [formEnTitle, setFormEnTitle] = useState("")
  const [formAmTitle, setFormAmTitle] = useState("")
  const [formEnContent, setFormEnContent] = useState("")
  const [formAmContent, setFormAmContent] = useState("")
  const [formSortOrder, setFormSortOrder] = useState<number>(1)
  const [formIsPublished, setFormIsPublished] = useState(true)

  // 1. Fetch all CMS pages list
  const fetchPages = useCallback(async () => {
    try {
      setLoading(true)
      const res = await apiClient.get("/admin/pages")
      if (res.data?.status === "success" && Array.isArray(res.data.data)) {
        const fetchedPages = res.data.data
        setPages(fetchedPages)
        if (fetchedPages.length > 0) {
          // Default to FAQ page if found, or first page
          const defaultPage = fetchedPages.find((p: CmsPage) => p.slug === "faq") || fetchedPages[0]
          setActivePageId(defaultPage.id)
        }
      }
    } catch (err) {
      console.error("Failed to load CMS pages list:", err)
      setErrorFeedback("Could not connect to CMS API. Please ensure backend server is running.")
    } finally {
      setLoading(false)
    }
  }, [])

  // 2. Fetch active page details with full sections
  const fetchPageDetails = useCallback(async (id: number) => {
    try {
      setPageLoading(true)
      const res = await apiClient.get(`/admin/pages/${id}`)
      if (res.data?.status === "success" && res.data.data) {
        setActivePage(res.data.data)
      }
    } catch (err) {
      console.error(`Failed to load details for page ${id}:`, err)
      setErrorFeedback("Failed to load page content from server.")
    } finally {
      setPageLoading(false)
    }
  }, [])

  useEffect(() => {
    fetchPages()
  }, [fetchPages])

  useEffect(() => {
    if (activePageId) {
      fetchPageDetails(activePageId)
    }
  }, [activePageId, fetchPageDetails])

  const isFaq = activePage?.slug === "faq" || activePage?.type === "faq"

  // Handle in-place content modifications
  const handleSectionChange = (
    sectionId: string | number,
    field: "question" | "question_am" | "answer" | "answer_am" | "heading" | "heading_am" | "body" | "body_am",
    value: string
  ) => {
    if (!activePage) return

    setActivePage({
      ...activePage,
      sections: activePage.sections.map((sec) =>
        sec.id === sectionId ? { ...sec, [field]: value } : sec
      ),
    })
  }

  // Toggle publish status of section
  const handleToggleStatus = async (sectionId: string | number) => {
    if (!activePage) return

    const target = activePage.sections.find((s) => s.id === sectionId)
    if (!target) return

    const newStatus = !target.is_published

    // Optimistic UI update
    setActivePage({
      ...activePage,
      sections: activePage.sections.map((s) =>
        s.id === sectionId ? { ...s, is_published: newStatus } : s
      ),
    })

    try {
      if (isFaq) {
        await apiClient.patch(`/admin/faqs/${sectionId}/toggle-status`)
      } else {
        await apiClient.put(`/admin/pages/${activePage.id}/sections/${sectionId}`, {
          is_published: newStatus,
        })
      }
      setSaveFeedback(`Status updated to ${newStatus ? "Published" : "Draft"}`)
      setTimeout(() => setSaveFeedback(null), 2000)
    } catch (err) {
      console.error("Failed to toggle status:", err)
      setErrorFeedback("Failed to update status on server.")
      // Revert on error
      setActivePage({
        ...activePage,
        sections: activePage.sections.map((s) =>
          s.id === sectionId ? { ...s, is_published: !newStatus } : s
        ),
      })
    }
  }

  // Delete section / FAQ
  const handleDeleteSection = async (sectionId: string | number) => {
    if (!activePage) return
    const itemLabel = isFaq ? "FAQ question" : "content section"
    if (!confirm(`Are you sure you want to permanently delete this ${itemLabel}?`)) return

    try {
      if (isFaq) {
        await apiClient.delete(`/admin/faqs/${sectionId}`)
      } else {
        await apiClient.delete(`/admin/pages/${activePage.id}/sections/${sectionId}`)
      }

      setActivePage({
        ...activePage,
        sections: activePage.sections.filter((s) => s.id !== sectionId),
      })

      setSaveFeedback(`Removed ${itemLabel} successfully!`)
      setTimeout(() => setSaveFeedback(null), 2500)
    } catch (err) {
      console.error("Failed to delete section:", err)
      setErrorFeedback(`Failed to delete ${itemLabel}. Please try again.`)
    }
  }

  // Create new FAQ item or Legal Section
  const handleCreateSection = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!activePage) return

    try {
      setSaving(true)

      let res
      if (isFaq) {
        res = await apiClient.post("/admin/faqs", {
          question: formEnTitle.trim(),
          question_am: formAmTitle.trim() || null,
          answer: formEnContent.trim(),
          answer_am: formAmContent.trim() || null,
          sort_order: formSortOrder,
          is_published: formIsPublished,
        })
      } else {
        res = await apiClient.post(`/admin/pages/${activePage.id}/sections`, {
          heading: formEnTitle.trim(),
          heading_am: formAmTitle.trim() || null,
          body: formEnContent.trim(),
          body_am: formAmContent.trim() || null,
          sort_order: formSortOrder,
          is_published: formIsPublished,
        })
      }

      if (res.data?.status === "success" && res.data.data) {
        setActivePage({
          ...activePage,
          sections: [...activePage.sections, res.data.data],
        })

        setIsAddModalOpen(false)
        setFormEnTitle("")
        setFormAmTitle("")
        setFormEnContent("")
        setFormAmContent("")
        setSaveFeedback(isFaq ? "New FAQ added successfully!" : "New section added successfully!")
        setTimeout(() => setSaveFeedback(null), 2500)
      }
    } catch (err) {
      console.error("Failed to add item:", err)
      setErrorFeedback("Failed to save new item. Please check all fields.")
    } finally {
      setSaving(false)
    }
  }

  // Save all modified sections in current page
  const handleSaveAllChanges = async () => {
    if (!activePage) return

    try {
      setSaving(true)
      setErrorFeedback(null)

      // Update all sections sequentially
      for (const sec of activePage.sections) {
        if (isFaq) {
          await apiClient.put(`/admin/faqs/${sec.id}`, {
            question: sec.question ?? sec.question_en ?? sec.title,
            question_am: sec.question_am ?? null,
            answer: sec.answer ?? sec.answer_en ?? sec.content,
            answer_am: sec.answer_am ?? null,
            sort_order: sec.sort_order,
            is_published: sec.is_published,
          })
        } else {
          await apiClient.put(`/admin/pages/${activePage.id}/sections/${sec.id}`, {
            heading: sec.heading ?? sec.heading_en ?? sec.title,
            heading_am: sec.heading_am ?? null,
            body: sec.body ?? sec.body_en ?? sec.content,
            body_am: sec.body_am ?? null,
            sort_order: sec.sort_order,
            is_published: sec.is_published,
          })
        }
      }

      setSaveFeedback(`All changes for "${activePage.title}" saved & live on mobile app!`)
      setTimeout(() => setSaveFeedback(null), 3500)
    } catch (err) {
      console.error("Failed to save changes:", err)
      setErrorFeedback("Error saving changes to server. Please try again.")
    } finally {
      setSaving(false)
    }
  }

  const getPageIcon = (type: string) => {
    switch (type) {
      case "legal":
        return <Shield className="h-4 w-4 text-blue-600 dark:text-blue-400" />
      case "faq":
        return <HelpCircle className="h-4 w-4 text-purple-600 dark:text-purple-400" />
      default:
        return <BookOpen className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
    }
  }

  if (loading) {
    return (
      <div className="flex h-96 items-center justify-center space-x-3 text-slate-500">
        <Loader2 className="h-6 w-6 animate-spin text-blue-600" />
        <span className="text-sm font-semibold">Loading CMS content...</span>
      </div>
    )
  }

  return (
    <div className="space-y-8">
      {/* Header Banner */}
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 sm:text-3xl">
            CMS Pages & FAQ Management
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
            Create, edit, reorder, and translate FAQs and Legal policies delivered dynamically to the mobile app.
          </p>
        </div>

        <div className="flex items-center gap-2">
          {saveFeedback && (
            <div className="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-3 py-1.5 rounded-lg border border-emerald-200 dark:border-emerald-800">
              <CheckCircle2 className="h-4 w-4" />
              {saveFeedback}
            </div>
          )}
          {errorFeedback && (
            <div className="flex items-center gap-1.5 text-xs font-semibold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/50 px-3 py-1.5 rounded-lg border border-rose-200 dark:border-rose-800">
              <AlertCircle className="h-4 w-4" />
              {errorFeedback}
            </div>
          )}
          <Button
            className="bg-emerald-600 font-semibold text-white shadow-md shadow-emerald-600/20 hover:bg-emerald-500"
            onClick={handleSaveAllChanges}
            disabled={saving || !activePage}
          >
            {saving ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Saving...
              </>
            ) : (
              <>
                <Save className="mr-2 h-4 w-4" />
                Save Live Changes
              </>
            )}
          </Button>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-4">
        {/* Left Sidebar: Page Selector */}
        <div className="space-y-3 lg:col-span-1">
          <div className="flex items-center justify-between px-1">
            <span className="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
              CMS Pages
            </span>
            <button
              onClick={() => fetchPages()}
              className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
              title="Refresh pages"
            >
              <RefreshCw className="h-3 w-3" />
            </button>
          </div>

          <div className="space-y-2">
            {pages.map((page) => {
              const isSelected = page.id === activePageId
              return (
                <button
                  key={page.id}
                  onClick={() => setActivePageId(page.id)}
                  className={`w-full text-left rounded-xl p-3.5 border transition-all ${
                    isSelected
                      ? "border-blue-500 bg-blue-50/50 text-blue-950 shadow-sm dark:border-blue-600 dark:bg-blue-950/30 dark:text-blue-100"
                      : "border-slate-200 bg-white hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:hover:bg-slate-900/50 text-slate-800 dark:text-slate-200"
                  }`}
                >
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2 font-bold text-xs">
                      {getPageIcon(page.type)}
                      <span>{page.title}</span>
                    </div>
                    <Badge variant="outline" className="text-[10px] uppercase font-mono">
                      /{page.slug}
                    </Badge>
                  </div>
                  {page.title_am && (
                    <div className="mt-1 text-[11px] text-slate-500 dark:text-slate-400 font-sans">
                      {page.title_am}
                    </div>
                  )}
                  <div className="mt-2 flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500">
                    <span>
                      {page.slug === "faq" ? "Questions" : "Sections"}: {page.sections_count || page.sections?.length || 0}
                    </span>
                    <span className="font-mono text-[10px]">
                      {page.updated_at ? new Date(page.updated_at).toLocaleDateString() : "Live"}
                    </span>
                  </div>
                </button>
              )
            })}
          </div>
        </div>

        {/* Right Section: Active Page Sections / FAQs Editor */}
        <div className="space-y-6 lg:col-span-3">
          {pageLoading || !activePage ? (
            <div className="flex h-64 items-center justify-center space-x-2 text-slate-400">
              <Loader2 className="h-5 w-5 animate-spin" />
              <span className="text-xs">Loading page details...</span>
            </div>
          ) : (
            <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
              <CardHeader className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 pb-4 dark:border-slate-800">
                <div>
                  <div className="flex items-center gap-2">
                    <CardTitle className="text-lg font-bold text-slate-950 dark:text-slate-50">
                      {activePage.title}
                    </CardTitle>
                    {activePage.title_am && (
                      <span className="text-xs text-slate-500 font-medium">
                        ({activePage.title_am})
                      </span>
                    )}
                    <Badge variant={activePage.is_published ? "default" : "secondary"} className="text-[10px] font-semibold uppercase">
                      {activePage.is_published ? "Published" : "Draft"}
                    </Badge>
                  </div>
                  <CardDescription className="text-xs text-slate-500 pt-1 flex flex-wrap items-center gap-2">
                    <span>
                      Public Route: <code className="font-mono text-[11px] text-blue-600">/api/pages/{activePage.slug}?locale=en|am</code>
                    </span>
                    <span>•</span>
                    <span className="flex items-center gap-1">
                      <Clock className="h-3 w-3" />
                      {activePage.sections?.length || 0} {isFaq ? "FAQs" : "Sections"}
                    </span>
                  </CardDescription>
                </div>

                {/* Add FAQ Item or Section Dialog */}
                <Dialog open={isAddModalOpen} onOpenChange={setIsAddModalOpen}>
                  <DialogTrigger asChild>
                    <Button
                      size="sm"
                      className="bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold gap-1.5 shadow-sm"
                      onClick={() => {
                        setFormSortOrder((activePage.sections?.length || 0) + 1)
                        setIsAddModalOpen(true)
                      }}
                    >
                      <PlusCircle className="h-3.5 w-3.5" />
                      {isFaq ? "Add FAQ Question" : "Add Section"}
                    </Button>
                  </DialogTrigger>

                  <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                      <DialogTitle className="text-base font-bold">
                        {isFaq ? "Add New FAQ Question & Answer" : `Add Content Section to ${activePage.title}`}
                      </DialogTitle>
                      <DialogDescription className="text-xs text-slate-500">
                        Provide both English and Amharic versions. If Amharic is left blank, English will be served as fallback.
                      </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleCreateSection} className="space-y-4 py-2">
                      {/* English Question / Heading */}
                      <div className="space-y-1.5">
                        <div className="flex items-center justify-between">
                          <Label htmlFor="sec-title-en" className="text-xs font-semibold">
                            {isFaq ? "English Question *" : "Section Heading (English) *"}
                          </Label>
                          <span className="text-[10px] uppercase font-mono text-blue-600 font-bold">EN</span>
                        </div>
                        <Input
                          id="sec-title-en"
                          placeholder={isFaq ? "e.g. How do I reset my password?" : "e.g. 1. Acceptance of Terms"}
                          value={formEnTitle}
                          onChange={(e) => setFormEnTitle(e.target.value)}
                          required
                        />
                      </div>

                      {/* Amharic Question / Heading */}
                      <div className="space-y-1.5">
                        <div className="flex items-center justify-between">
                          <Label htmlFor="sec-title-am" className="text-xs font-semibold">
                            {isFaq ? "Amharic Question (የአማርኛ ጥያቄ)" : "Section Heading (Amharic)"}
                          </Label>
                          <span className="text-[10px] uppercase font-mono text-purple-600 font-bold">AM</span>
                        </div>
                        <Input
                          id="sec-title-am"
                          placeholder={isFaq ? "ለምሳሌ፦ የይለፍ ቃሌን እንዴት መቀየር እችላለሁ?" : "ለምሳሌ፦ 1. የውሎች ተቀባይነት"}
                          value={formAmTitle}
                          onChange={(e) => setFormAmTitle(e.target.value)}
                        />
                      </div>

                      {/* English Answer / Body */}
                      <div className="space-y-1.5">
                        <div className="flex items-center justify-between">
                          <Label htmlFor="sec-content-en" className="text-xs font-semibold">
                            {isFaq ? "English Answer *" : "Section Body (English) *"}
                          </Label>
                          <span className="text-[10px] uppercase font-mono text-blue-600 font-bold">EN</span>
                        </div>
                        <Textarea
                          id="sec-content-en"
                          rows={3}
                          placeholder={isFaq ? "Provide detailed, step-by-step guidance..." : "Write legal policy details..."}
                          value={formEnContent}
                          onChange={(e) => setFormEnContent(e.target.value)}
                          required
                        />
                      </div>

                      {/* Amharic Answer / Body */}
                      <div className="space-y-1.5">
                        <div className="flex items-center justify-between">
                          <Label htmlFor="sec-content-am" className="text-xs font-semibold">
                            {isFaq ? "Amharic Answer (የአማርኛ መልስ)" : "Section Body (Amharic)"}
                          </Label>
                          <span className="text-[10px] uppercase font-mono text-purple-600 font-bold">AM</span>
                        </div>
                        <Textarea
                          id="sec-content-am"
                          rows={3}
                          placeholder={isFaq ? "ዝርዝር የአማርኛ ማብራሪያ ያስገቡ..." : "የአማርኛ የፖሊሲ ይዘት ያስገቡ..."}
                          value={formAmContent}
                          onChange={(e) => setFormAmContent(e.target.value)}
                        />
                      </div>

                      <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-1">
                          <Label htmlFor="sec-order" className="text-xs font-semibold">Sort Order</Label>
                          <Input
                            id="sec-order"
                            type="number"
                            value={formSortOrder}
                            onChange={(e) => setFormSortOrder(parseInt(e.target.value) || 1)}
                          />
                        </div>
                        <div className="space-y-1">
                          <Label htmlFor="sec-pub" className="text-xs font-semibold">Publication State</Label>
                          <select
                            id="sec-pub"
                            value={formIsPublished ? "1" : "0"}
                            onChange={(e) => setFormIsPublished(e.target.value === "1")}
                            className="w-full h-9 rounded-md border border-slate-200 bg-white px-3 py-1 text-xs dark:border-slate-800 dark:bg-slate-950"
                          >
                            <option value="1">Published (Live to mobile)</option>
                            <option value="0">Draft (Hidden)</option>
                          </select>
                        </div>
                      </div>

                      <DialogFooter className="pt-4">
                        <Button
                          type="button"
                          variant="outline"
                          onClick={() => setIsAddModalOpen(false)}
                        >
                          Cancel
                        </Button>
                        <Button type="submit" disabled={saving} className="bg-blue-600 text-white hover:bg-blue-500">
                          {saving ? "Creating..." : isFaq ? "Save FAQ Question" : "Append Section"}
                        </Button>
                      </DialogFooter>
                    </form>
                  </DialogContent>
                </Dialog>
              </CardHeader>

              <CardContent className="space-y-6 pt-6">
                {activePage.sections?.length === 0 ? (
                  <div className="rounded-xl border border-dashed border-slate-200 p-8 text-center text-slate-400">
                    <HelpCircle className="mx-auto h-8 w-8 text-slate-300 mb-2" />
                    <p className="text-sm font-semibold text-slate-600 dark:text-slate-400">No items found</p>
                    <p className="text-xs text-slate-400">Click &quot;{isFaq ? "Add FAQ Question" : "Add Section"}&quot; above to create your first entry.</p>
                  </div>
                ) : (
                  activePage.sections?.map((section, idx) => {
                    const questionVal = section.question ?? section.question_en ?? section.heading ?? section.title ?? ""
                    const questionAmVal = section.question_am ?? section.heading_am ?? ""
                    const answerVal = section.answer ?? section.answer_en ?? section.body ?? section.content ?? ""
                    const answerAmVal = section.answer_am ?? section.body_am ?? ""

                    return (
                      <div
                        key={section.id}
                        className="rounded-xl border border-slate-200 bg-slate-50/40 p-4 transition-colors focus-within:border-blue-500 dark:border-slate-800 dark:bg-slate-900/30 space-y-4"
                      >
                        {/* Item Card Header */}
                        <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                          <div className="flex items-center gap-2">
                            <span className="flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                              {section.sort_order || idx + 1}
                            </span>
                            <span className="text-xs font-bold text-slate-700 dark:text-slate-300">
                              {isFaq ? `FAQ Question #${idx + 1}` : `Section #${idx + 1}`}
                            </span>
                            <Badge
                              variant={section.is_published ? "default" : "outline"}
                              className={`text-[9px] uppercase font-semibold ${
                                section.is_published
                                  ? "bg-emerald-600 text-white"
                                  : "text-slate-400 border-slate-300"
                              }`}
                            >
                              {section.is_published ? "Published" : "Draft"}
                            </Badge>
                          </div>

                          <div className="flex items-center gap-2">
                            <Button
                              size="sm"
                              variant="ghost"
                              className="h-7 text-xs text-slate-600 hover:text-slate-900 dark:text-slate-400"
                              onClick={() => handleToggleStatus(section.id)}
                              title={section.is_published ? "Unpublish item" : "Publish item"}
                            >
                              {section.is_published ? (
                                <>
                                  <EyeOff className="h-3.5 w-3.5 mr-1 text-slate-400" />
                                  Hide
                                </>
                              ) : (
                                <>
                                  <Eye className="h-3.5 w-3.5 mr-1 text-emerald-600" />
                                  Publish
                                </>
                              )}
                            </Button>

                            <Button
                              size="sm"
                              variant="ghost"
                              className="h-7 text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/30"
                              onClick={() => handleDeleteSection(section.id)}
                            >
                              <Trash2 className="h-3.5 w-3.5 mr-1" />
                              Remove
                            </Button>
                          </div>
                        </div>

                        {/* Bilingual Inputs Grid */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                          {/* English Column */}
                          <div className="space-y-3 bg-white dark:bg-slate-950 p-3 rounded-lg border border-slate-100 dark:border-slate-800">
                            <div className="flex items-center justify-between border-b pb-1.5 border-slate-100 dark:border-slate-800">
                              <span className="text-[11px] font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider flex items-center gap-1">
                                <Globe className="h-3 w-3" />
                                English (Primary / Canonical)
                              </span>
                              <span className="text-[10px] font-mono bg-blue-50 dark:bg-blue-950/50 text-blue-600 px-1.5 py-0.5 rounded font-bold">
                                EN
                              </span>
                            </div>

                            <div>
                              <Label className="text-[10px] font-semibold text-slate-400 uppercase">
                                {isFaq ? "Question (EN)" : "Heading (EN)"}
                              </Label>
                              <Input
                                className="mt-1 font-semibold text-xs"
                                value={questionVal}
                                onChange={(e) =>
                                  handleSectionChange(
                                    section.id,
                                    isFaq ? "question" : "heading",
                                    e.target.value
                                  )
                                }
                              />
                            </div>

                            <div>
                              <Label className="text-[10px] font-semibold text-slate-400 uppercase">
                                {isFaq ? "Answer (EN)" : "Body Content (EN)"}
                              </Label>
                              <Textarea
                                rows={3}
                                className="mt-1 text-xs leading-relaxed"
                                value={answerVal}
                                onChange={(e) =>
                                  handleSectionChange(
                                    section.id,
                                    isFaq ? "answer" : "body",
                                    e.target.value
                                  )
                                }
                              />
                            </div>
                          </div>

                          {/* Amharic Column */}
                          <div className="space-y-3 bg-purple-50/30 dark:bg-purple-950/10 p-3 rounded-lg border border-purple-100 dark:border-purple-950/50">
                            <div className="flex items-center justify-between border-b pb-1.5 border-purple-100 dark:border-purple-900/40">
                              <span className="text-[11px] font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider flex items-center gap-1">
                                <Globe className="h-3 w-3" />
                                Amharic (የአማርኛ ትርጉም)
                              </span>
                              <span className="text-[10px] font-mono bg-purple-100 dark:bg-purple-950/80 text-purple-700 px-1.5 py-0.5 rounded font-bold">
                                AM
                              </span>
                            </div>

                            <div>
                              <Label className="text-[10px] font-semibold text-purple-700 dark:text-purple-400 uppercase">
                                {isFaq ? "Question (AM - አማርኛ)" : "Heading (AM - አማርኛ)"}
                              </Label>
                              <Input
                                className="mt-1 text-xs"
                                placeholder="የአማርኛ ጥያቄ ያስገቡ..."
                                value={questionAmVal}
                                onChange={(e) =>
                                  handleSectionChange(
                                    section.id,
                                    isFaq ? "question_am" : "heading_am",
                                    e.target.value
                                  )
                                }
                              />
                            </div>

                            <div>
                              <Label className="text-[10px] font-semibold text-purple-700 dark:text-purple-400 uppercase">
                                {isFaq ? "Answer (AM - አማርኛ)" : "Body Content (AM - አማርኛ)"}
                              </Label>
                              <Textarea
                                rows={3}
                                className="mt-1 text-xs leading-relaxed"
                                placeholder="ዝርዝር የአማርኛ ማብራሪያ ያስገቡ..."
                                value={answerAmVal}
                                onChange={(e) =>
                                  handleSectionChange(
                                    section.id,
                                    isFaq ? "answer_am" : "body_am",
                                    e.target.value
                                  )
                                }
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    )
                  })
                )}
              </CardContent>
            </Card>
          )}
        </div>
      </div>
    </div>
  )
}
