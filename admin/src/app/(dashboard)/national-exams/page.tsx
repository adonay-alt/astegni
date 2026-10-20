"use client"

import React, { useState, useEffect, useMemo, useCallback } from "react"
import {
  ExamQuestion,
  NationalExamSubjectOverview,
  SubjectAvailableYear,
  StreamSlug,
  ExamProgram,
} from "@/types/exam"
import { apiClient, cachedGet } from "@/lib/api-client"
import { getCached, setCached, clearCache } from "@/lib/cache"
import {
  fetchStudentCatalog,
  filterByProgram,
  mergePrograms,
  mergeSubjectOverviews,
  normalizeOverviewPayload,
  paperKey,
} from "@/lib/national-exam-studio"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
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
import { Label } from "@/components/ui/label"
import { ExamQuestionModal } from "@/components/exams/exam-question-modal"
import { BulkImportDrawer } from "@/components/exams/bulk-import-drawer"
import {
  Award,
  GraduationCap,
  Calendar,
  BookOpen,
  UploadCloud,
  Plus,
  Search,
  CheckCircle2,
  AlertCircle,
  Image as ImageIcon,
  Edit,
  Trash2,
  Sparkles,
  Lightbulb,
  X,
  FileQuestion,
  Loader2,
  RefreshCw,
  ZoomIn,
  ChevronRight,
  Layers,
  Atom,
  FlaskConical,
  Compass,
  Landmark,
  TrendingUp,
  Binary,
  Globe,
  Languages,
  Check,
} from "lucide-react"

interface NationalExamsDataCache {
  programs: ExamProgram[]
  subjects: NationalExamSubjectOverview[]
}

export default function NationalExamsPage() {
  const cachedData = getCached<NationalExamsDataCache>("national_exams_data")

  // Navigation & Hierarchy State
  const [selectedGrade, setSelectedGrade] = useState<8 | 12>(12)
  const [selectedExamId, setSelectedExamId] = useState<number | "all">("all")
  const [examPrograms, setExamPrograms] = useState<ExamProgram[]>(cachedData?.programs || [])
  const [streamFilter, setStreamFilter] = useState<"all" | "natural" | "social" | "common">("all")
  const [subjectsOverview, setSubjectsOverview] = useState<NationalExamSubjectOverview[]>(cachedData?.subjects || [])
  const [selectedSubjectName, setSelectedSubjectName] = useState<string>(() => {
    if (cachedData?.subjects && cachedData.subjects.length > 0) {
      const withQ = cachedData.subjects.find((s) => s.total_questions > 0)
      return (withQ ?? cachedData.subjects[0])?.name || ""
    }
    return ""
  })
  const [selectedNesId, setSelectedNesId] = useState<number>(0)
  const [hideEmptyPapers, setHideEmptyPapers] = useState<boolean>(false)

  // Questions State
  const [questions, setQuestions] = useState<ExamQuestion[]>([])
  const [isLoading, setIsLoading] = useState<boolean>(!cachedData)
  const [isQuestionsLoading, setIsQuestionsLoading] = useState<boolean>(false)
  const [searchQuery, setSearchQuery] = useState<string>("")

  // Modals & Drawers State
  const [isQuestionModalOpen, setIsQuestionModalOpen] = useState<boolean>(false)
  const [questionToEdit, setQuestionToEdit] = useState<ExamQuestion | null>(null)
  const [isImportDrawerOpen, setIsImportDrawerOpen] = useState<boolean>(false)
  const [isCreateYearModalOpen, setIsCreateYearModalOpen] = useState<boolean>(false)
  const [previewDiagram, setPreviewDiagram] = useState<string | null>(null)

  // New Year Form
  const [newYearEc, setNewYearEc] = useState<number>(2017)
  const [newYearGc, setNewYearGc] = useState<number>(2025)
  const [isCreatingYear, setIsCreatingYear] = useState<boolean>(false)
  const [yearModalError, setYearModalError] = useState<string | null>(null)

  // Add Subject Form
  const [isAddSubjectModalOpen, setIsAddSubjectModalOpen] = useState<boolean>(false)
  const [newSubjectName, setNewSubjectName] = useState<string>("")
  const [newSubjectCode, setNewSubjectCode] = useState<string>("")
  const [newSubjectStream, setNewSubjectStream] = useState<"natural" | "social" | "common">("common")
  const [newSubjectYearId, setNewSubjectYearId] = useState<number>(0)
  const [isSubmittingSubject, setIsSubmittingSubject] = useState<boolean>(false)
  const [subjectModalError, setSubjectModalError] = useState<string | null>(null)

  // Edit Subject Form
  const [isEditSubjectModalOpen, setIsEditSubjectModalOpen] = useState<boolean>(false)
  const [editSubjectId, setEditSubjectId] = useState<number>(0)
  const [editSubjectName, setEditSubjectName] = useState<string>("")
  const [editSubjectCode, setEditSubjectCode] = useState<string>("")
  const [editSubjectStream, setEditSubjectStream] = useState<"natural" | "social" | "common">("common")
  const [isUpdatingSubject, setIsUpdatingSubject] = useState<boolean>(false)
  const [editSubjectError, setEditSubjectError] = useState<string | null>(null)

  // Edit Exam Title Form
  const [isEditExamModalOpen, setIsEditExamModalOpen] = useState<boolean>(false)
  const [editExamId, setEditExamId] = useState<number>(1)
  const [editExamTitle, setEditExamTitle] = useState<string>("")
  const [isUpdatingExam, setIsUpdatingExam] = useState<boolean>(false)
  const [editExamError, setEditExamError] = useState<string | null>(null)

  // Edit Year Form
  const [isEditYearModalOpen, setIsEditYearModalOpen] = useState<boolean>(false)
  const [editYearId, setEditYearId] = useState<number>(0)
  const [editYearEc, setEditYearEc] = useState<number>(2017)
  const [editYearGc, setEditYearGc] = useState<number>(2025)
  const [isUpdatingYear, setIsUpdatingYear] = useState<boolean>(false)
  const [editYearError, setEditYearError] = useState<string | null>(null)

  // Toast notifications
  const [toastMsg, setToastMsg] = useState<{ type: "success" | "error"; text: string } | null>(null)

  const showToast = (type: "success" | "error", text: string) => {
    setToastMsg({ type, text })
    setTimeout(() => setToastMsg(null), 4000)
  }

  // 1. Fetch Subjects Overview (Subject-first hierarchy)
  const fetchOverview = useCallback(async (isSilent = false) => {
    if (!isSilent && !getCached("national_exams_data")) {
      setIsLoading(true)
    }
    try {
      let overviewSubjects: NationalExamSubjectOverview[] = []
      let programs: ExamProgram[] = []

      try {
        const data = await cachedGet("/admin/national-exams/overview", {
          grade: selectedGrade,
          exam_id: "all",
        })
        const parsed = normalizeOverviewPayload(data)
        overviewSubjects = parsed.subjects
        programs = parsed.programs
      } catch {
        // Production may still be on the single-exam index endpoint.
      }

      const overviewQuestionTotal = overviewSubjects.reduce(
        (sum, subject) => sum + (subject.total_questions || 0),
        0
      )
      const overviewLooksPartial =
        programs.length <= 1 ||
        (selectedGrade === 12 && overviewQuestionTotal > 0 && overviewQuestionTotal < 1500)

      if (overviewLooksPartial || overviewSubjects.length === 0) {
        try {
          const catalog = await fetchStudentCatalog(selectedGrade)
          programs = mergePrograms(programs, catalog.programs)
          overviewSubjects = mergeSubjectOverviews(overviewSubjects, catalog.subjects)
        } catch (catalogErr) {
          console.error("Failed to merge student national-exam catalog:", catalogErr)
        }
      }

      const scoped = filterByProgram(overviewSubjects, selectedExamId)
      setExamPrograms(programs)
      setSubjectsOverview(scoped)
      setCached("national_exams_data", { programs, subjects: scoped })
      setSelectedSubjectName((prev) => {
        if (prev && scoped.some((s) => s.name.toLowerCase() === prev.toLowerCase())) {
          return prev
        }
        const withQ = scoped.find((s) => s.total_questions > 0)
        return (withQ ?? scoped[0])?.name || ""
      })
    } catch (err: any) {
      console.error("Failed to load national exams overview:", err)
      showToast("error", err.response?.data?.message || "Failed to load national exams.")
      if (!getCached("national_exams_data")) {
        setSubjectsOverview([])
      }
    } finally {
      setIsLoading(false)
    }
  }, [selectedGrade, selectedExamId])

  useEffect(() => {
    const hasCache = !!getCached("national_exams_data")
    fetchOverview(hasCache)
  }, [fetchOverview])

  // Filtered subjects based on Stream Filter (Common Core subjects are visible to all)
  const filteredSubjects = useMemo(() => {
    if (selectedGrade === 8 || streamFilter === "all") return subjectsOverview

    return subjectsOverview.filter((sub) => {
      // Common Core subjects (stream_id === null or undefined) are visible to everyone
      const isCommon =
        sub.stream_id === null ||
        sub.stream_id === undefined ||
        (!sub.stream_id && sub.stream === "common")
      const isNatural = sub.stream_id === 1 || sub.stream === "natural"
      const isSocial = sub.stream_id === 2 || sub.stream === "social"

      if (streamFilter === "natural") return isNatural || isCommon
      if (streamFilter === "social") return isSocial || isCommon
      if (streamFilter === "common") return isCommon
      return true
    })
  }, [subjectsOverview, selectedGrade, streamFilter])

  // Active Subject Object
  const activeSubject = useMemo((): NationalExamSubjectOverview | null => {
    if (!filteredSubjects || filteredSubjects.length === 0) {
      return subjectsOverview[0] || null
    }
    const found = filteredSubjects.find(
      (s) => s.name.toLowerCase() === selectedSubjectName.toLowerCase()
    )
    return found || filteredSubjects[0] || null
  }, [filteredSubjects, subjectsOverview, selectedSubjectName])

  // Available Years for Active Subject
  const availableYears = useMemo((): SubjectAvailableYear[] => {
    if (!activeSubject?.available_years) return []
    const years = [...activeSubject.available_years].sort(
      (a, b) => b.year_ec - a.year_ec || b.questions_count - a.questions_count
    )
    if (!hideEmptyPapers) return years
    const withQuestions = years.filter((year) => year.questions_count > 0)
    return withQuestions.length > 0 ? withQuestions : years
  }, [activeSubject, hideEmptyPapers])

  // Active Year Object
  const activeYear = useMemo((): SubjectAvailableYear | null => {
    if (!availableYears || availableYears.length === 0) return null
    const found = availableYears.find((y) => paperKey(y) === selectedNesId)
    return found || availableYears[0] || null
  }, [availableYears, selectedNesId])

  // Sync selected paper when the subject or filter changes
  useEffect(() => {
    if (availableYears.length > 0) {
      if (!availableYears.some((y) => paperKey(y) === selectedNesId)) {
        const preferred = availableYears.find((y) => y.questions_count > 0) || availableYears[0]
        setSelectedNesId(paperKey(preferred))
      }
    } else {
      setSelectedNesId(0)
    }
  }, [availableYears, selectedNesId])

  // Fetch Questions for Active Year and Active Subject
  const fetchQuestions = useCallback(async () => {
    const targetNesId = activeYear?.nes_id || activeYear?.subject_id
    if (!activeYear || !targetNesId) {
      setQuestions([])
      return
    }

    setIsQuestionsLoading(true)
    try {
      const res = await apiClient.get(
        `/admin/national-exams/years/${activeYear.id}/subjects/${targetNesId}/questions`,
        { params: { nes_id: targetNesId } }
      )
      const rawQuestions = res.data?.data ?? (Array.isArray(res.data) ? res.data : [])
      if (Array.isArray(rawQuestions)) {
        setQuestions(rawQuestions)
        const liveCount = Number(res.data?.count ?? rawQuestions.length)
        setSubjectsOverview((prev) =>
          prev.map((sub) => {
            if (sub.name.toLowerCase() !== activeSubject?.name.toLowerCase()) return sub
            const updatedYears = sub.available_years.map((yr) =>
              paperKey(yr) === targetNesId ? { ...yr, questions_count: liveCount } : yr
            )
            return {
              ...sub,
              available_years: updatedYears,
              total_questions: updatedYears.reduce((sum, yr) => sum + (yr.questions_count || 0), 0),
              total_years: updatedYears.length,
            }
          })
        )
      } else {
        setQuestions([])
      }
    } catch (err: any) {
      console.error("Failed to load questions:", err)
      setQuestions([])
    } finally {
      setIsQuestionsLoading(false)
    }
  }, [activeYear?.id, activeYear?.nes_id, activeYear?.subject_id])

  useEffect(() => {
    fetchQuestions()
  }, [fetchQuestions])

  // Filtered questions based on search query
  const filteredQuestions = useMemo(() => {
    if (!searchQuery.trim()) return questions
    const q = searchQuery.toLowerCase().trim()
    return questions.filter((item) => {
      return (
        String(item.question_number).includes(q) ||
        item.question_text.toLowerCase().includes(q) ||
        item.explanation?.toLowerCase().includes(q) ||
        Object.values(item.options).some((opt) => opt?.toLowerCase().includes(q))
      )
    })
  }, [questions, searchQuery])

  // Summary statistics across all subjects
  const summaryStats = useMemo(() => {
    const totalSubjectsCount = subjectsOverview.length
    const totalExamYearsCount = activeSubject?.total_years || availableYears.length || 0
    const totalQuestionBank = subjectsOverview.reduce((acc, s) => acc + (s.total_questions || 0), 0)
    return { totalSubjectsCount, totalExamYearsCount, totalQuestionBank }
  }, [subjectsOverview, activeSubject, availableYears])

  // Handlers for Question CRUD
  const handleOpenAddQuestion = () => {
    setQuestionToEdit(null)
    setIsQuestionModalOpen(true)
  }

  const handleOpenEditQuestion = (q: ExamQuestion) => {
    setQuestionToEdit(q)
    setIsQuestionModalOpen(true)
  }

  const handleSaveQuestion = (saved: ExamQuestion) => {
    clearCache("national_exams")
    setQuestions((prev) => {
      const exists = prev.some((item) => item.id === saved.id)
      if (exists) {
        return prev.map((item) => (item.id === saved.id ? saved : item))
      }
      return [...prev, saved].sort((a, b) => a.question_number - b.question_number)
    })

    // Update subject question count locally
    setSubjectsOverview((prev) =>
      prev.map((sub) => {
        if (sub.name.toLowerCase() !== activeSubject?.name.toLowerCase()) return sub
        const updatedYears = sub.available_years.map((yr) => {
          if (paperKey(yr) !== paperKey(activeYear || { nes_id: 0, subject_id: 0 })) return yr
          return { ...yr, questions_count: yr.questions_count + (questionToEdit ? 0 : 1) }
        })
        return {
          ...sub,
          total_questions: sub.total_questions + (questionToEdit ? 0 : 1),
          available_years: updatedYears,
        }
      })
    )

    showToast("success", `Question #${saved.question_number} saved successfully!`)
    fetchQuestions()
  }

  const handleDeleteQuestion = async (id: number) => {
    if (!confirm("Are you sure you want to permanently delete this exam question?")) return

    try {
      clearCache("national_exams")
      await apiClient.delete(`/admin/national-exams/questions/${id}`)
      setQuestions((prev) => prev.filter((q) => q.id !== id))

      // Update question counts locally
      setSubjectsOverview((prev) =>
        prev.map((sub) => {
          if (sub.name.toLowerCase() !== activeSubject?.name.toLowerCase()) return sub
          const updatedYears = sub.available_years.map((yr) => {
            if (paperKey(yr) !== paperKey(activeYear || { nes_id: 0, subject_id: 0 })) return yr
            return { ...yr, questions_count: Math.max(0, yr.questions_count - 1) }
          })
          return {
            ...sub,
            total_questions: Math.max(0, sub.total_questions - 1),
            available_years: updatedYears,
          }
        })
      )

      showToast("success", "Question deleted from exam bank.")
    } catch (err: any) {
      showToast("error", err.response?.data?.message || "Failed to delete question.")
    }
  }

  // Handle batch import success
  const handleBatchImportSuccess = (importedList: ExamQuestion[]) => {
    clearCache("national_exams")
    setQuestions((prev) => {
      const merged = [...prev, ...importedList]
      return merged.sort((a, b) => a.question_number - b.question_number)
    })

    setSubjectsOverview((prev) =>
      prev.map((sub) => {
        if (sub.name.toLowerCase() !== activeSubject?.name.toLowerCase()) return sub
        const updatedYears = sub.available_years.map((yr) => {
          if (paperKey(yr) !== paperKey(activeYear || { nes_id: 0, subject_id: 0 })) return yr
          return { ...yr, questions_count: yr.questions_count + importedList.length }
        })
        return {
          ...sub,
          total_questions: sub.total_questions + importedList.length,
          available_years: updatedYears,
        }
      })
    )

    showToast(
      "success",
      `Successfully imported ${importedList.length} questions into ${activeSubject?.name} (${activeYear?.year_ec} E.C.)!`
    )
    fetchQuestions()
  }

  // Handle update official national exam title
  const handleUpdateExamTitle = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!editExamTitle.trim()) {
      setEditExamError("Exam title cannot be empty.")
      return
    }

    setIsUpdatingExam(true)
    setEditExamError(null)
    try {
      clearCache("national_exams")
      const res = await apiClient.put(`/admin/national-exams/${editExamId}`, {
        title: editExamTitle.trim(),
      })
      showToast("success", res.data?.message || "Official national exam title updated successfully!")
      setIsEditExamModalOpen(false)
      await fetchOverview()
    } catch (err: any) {
      console.error("Failed to update national exam title:", err)
      const msg = err.response?.data?.message || err.response?.data?.error || "Failed to update exam title."
      setEditExamError(msg)
      showToast("error", msg)
    } finally {
      setIsUpdatingExam(false)
    }
  }

  // Handle create exam year
  const handleCreateYear = async (e: React.FormEvent) => {
    e.preventDefault()
    setIsCreatingYear(true)
    setYearModalError(null)

    try {
      const sanitizedYearEc = parseInt(String(newYearEc).replace(/\D/g, ""), 10)
      const sanitizedYearGc = newYearGc
        ? parseInt(String(newYearGc).replace(/\D/g, ""), 10)
        : sanitizedYearEc + 8
      const sanitizedGrade = Number(String(selectedGrade).replace(/\D/g, "")) || 12

      if (!sanitizedYearEc || sanitizedYearEc < 1990 || sanitizedYearEc > 2100) {
        setYearModalError("Please enter a valid Ethiopian year between 1990 and 2100.")
        setIsCreatingYear(false)
        return
      }

      // Rule: Grade 12 belongs to Exam 1, Grade 8 belongs to Exam 2
      const targetExamId = sanitizedGrade === 8 ? 2 : (selectedExamId !== "all" ? selectedExamId : 1)

      const payload: Record<string, unknown> = {
        grade: sanitizedGrade,
        grade_level: sanitizedGrade,
        national_exam_id: targetExamId,
        year_ec: sanitizedYearEc,
        year_gc: sanitizedYearGc,
        is_published: true,
      }
      clearCache("national_exams")
      const res = await apiClient.post("/admin/national-exams/years", payload)

      showToast("success", res.data?.message || `Exam Year ${sanitizedYearEc} E.C. created successfully!`)
      setIsCreateYearModalOpen(false)
      await fetchOverview()
    } catch (err: any) {
      console.error("Failed to create exam year:", err)
      const msg = err.response?.data?.message || err.response?.data?.error || "Failed to create exam year."
      setYearModalError(msg)
      showToast("error", msg)
    } finally {
      setIsCreatingYear(false)
    }
  }

  // Handle update exam year
  const handleUpdateYear = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!editYearId) return

    setIsUpdatingYear(true)
    setEditYearError(null)
    try {
      const sanitizedYearEc = parseInt(String(editYearEc).replace(/\D/g, ""), 10)
      const sanitizedYearGc = editYearGc
        ? parseInt(String(editYearGc).replace(/\D/g, ""), 10)
        : sanitizedYearEc + 8

      if (!sanitizedYearEc || sanitizedYearEc < 1990 || sanitizedYearEc > 2100) {
        setEditYearError("Please enter a valid Ethiopian year between 1990 and 2100.")
        setIsUpdatingYear(false)
        return
      }

      clearCache("national_exams")
      const res = await apiClient.put(`/admin/national-exams/years/${editYearId}`, {
        year_ec: sanitizedYearEc,
        year_gc: sanitizedYearGc,
        is_published: true,
      })

      showToast("success", res.data?.message || `Exam year ${sanitizedYearEc} E.C. updated successfully!`)
      setIsEditYearModalOpen(false)
      await fetchOverview()
    } catch (err: any) {
      console.error("Failed to update exam year:", err)
      const msg = err.response?.data?.message || err.response?.data?.error || "Failed to update exam year."
      setEditYearError(msg)
      showToast("error", msg)
    } finally {
      setIsUpdatingYear(false)
    }
  }

  // Handle delete exam year
  const handleDeleteExamYear = async (yearId: number, yearEc: number) => {
    if (
      !confirm(
        `Are you sure you want to permanently delete the ${yearEc} E.C. exam cohort and all its questions from MySQL?`
      )
    ) {
      return
    }
    try {
      clearCache("national_exams")
      const res = await apiClient.delete(`/admin/national-exams/years/${yearId}`)
      showToast("success", res.data?.message || `Exam year ${yearEc} E.C. deleted successfully.`)
      await fetchOverview()
    } catch (err: any) {
      console.error("Failed to delete exam year:", err)
      showToast("error", err.response?.data?.message || "Failed to delete exam year.")
    }
  }

  // Handle add subject to year
  const handleAddSubject = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!newSubjectName.trim()) {
      setSubjectModalError("Subject name is required.")
      return
    }
    const targetYearId = newSubjectYearId || activeYear?.id || availableYears[0]?.id
    if (!targetYearId) {
      setSubjectModalError("Please select an exam year.")
      return
    }

    setIsSubmittingSubject(true)
    setSubjectModalError(null)
    try {
      const isGrade8 = selectedGrade === 8
      const payload = {
        name: newSubjectName.trim(),
        code: newSubjectCode.trim() || newSubjectName.trim().substring(0, 4).toUpperCase(),
        stream: isGrade8 ? "common" : newSubjectStream,
        stream_id: isGrade8 ? null : (newSubjectStream === "natural" ? 1 : newSubjectStream === "social" ? 2 : null),
      }
      clearCache("national_exams")
      const res = await apiClient.post(`/admin/national-exams/years/${targetYearId}/subjects`, payload)
      showToast("success", res.data?.message || `Subject "${newSubjectName}" added successfully!`)
      setIsAddSubjectModalOpen(false)
      setSelectedSubjectName(newSubjectName.trim())
      await fetchOverview()
    } catch (err: any) {
      console.error("Failed to add subject:", err)
      const msg = err.response?.data?.message || err.response?.data?.error || "Failed to add subject."
      setSubjectModalError(msg)
      showToast("error", msg)
    } finally {
      setIsSubmittingSubject(false)
    }
  }

  // Handle update subject
  const handleUpdateSubject = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!editSubjectName.trim()) {
      setEditSubjectError("Subject name is required.")
      return
    }
    if (!editSubjectId) {
      setEditSubjectError("Subject identifier not found.")
      return
    }

    setIsUpdatingSubject(true)
    setEditSubjectError(null)
    try {
      const isGrade8 = selectedGrade === 8
      const payload = {
        name: editSubjectName.trim(),
        code: editSubjectCode.trim() || editSubjectName.trim().substring(0, 4).toUpperCase(),
        stream: isGrade8 ? "common" : editSubjectStream,
        stream_id: isGrade8 ? null : (editSubjectStream === "natural" ? 1 : editSubjectStream === "social" ? 2 : null),
      }
      clearCache("national_exams")
      const res = await apiClient.put(`/admin/national-exams/subjects/${editSubjectId}`, payload)
      showToast("success", res.data?.message || `Subject updated successfully!`)
      setSelectedSubjectName(editSubjectName.trim())
      setIsEditSubjectModalOpen(false)
      await fetchOverview()
    } catch (err: any) {
      console.error("Failed to update subject:", err)
      const msg = err.response?.data?.message || err.response?.data?.error || "Failed to update subject."
      setEditSubjectError(msg)
      showToast("error", msg)
    } finally {
      setIsUpdatingSubject(false)
    }
  }

  // Handle delete subject
  const handleDeleteSubject = async (subjectId: number, subjectName: string) => {
    if (
      !confirm(
        `Are you sure you want to permanently delete "${subjectName}" and all associated question papers from this exam year?`
      )
    ) {
      return
    }
    try {
      clearCache("national_exams")
      const res = await apiClient.delete(`/admin/national-exams/subjects/${subjectId}`)
      showToast("success", res.data?.message || `Subject "${subjectName}" deleted successfully.`)
      await fetchOverview()
    } catch (err: any) {
      console.error("Failed to delete subject:", err)
      showToast("error", err.response?.data?.message || "Failed to delete subject.")
    }
  }

  // Subject Icon Resolver
  const getSubjectIcon = (name: string, stream?: string) => {
    const l = name.toLowerCase()
    if (l.includes("sat")) return <Award className="w-5 h-5 text-fuchsia-500" />
    if (l.includes("math")) return <Binary className="w-5 h-5 text-blue-500" />
    if (l.includes("phys")) return <Atom className="w-5 h-5 text-indigo-500" />
    if (l.includes("chem")) return <FlaskConical className="w-5 h-5 text-purple-500" />
    if (l.includes("bio")) return <Sparkles className="w-5 h-5 text-emerald-500" />
    if (l.includes("econ")) return <TrendingUp className="w-5 h-5 text-amber-500" />
    if (l.includes("geog")) return <Compass className="w-5 h-5 text-teal-500" />
    if (l.includes("hist")) return <Landmark className="w-5 h-5 text-orange-500" />
    if (l.includes("eng") || l.includes("amh")) return <Languages className="w-5 h-5 text-rose-500" />
    if (l.includes("civ") || l.includes("cit")) return <Globe className="w-5 h-5 text-cyan-500" />
    return <BookOpen className="w-5 h-5 text-sky-500" />
  }

  return (
    <div className="space-y-8 pb-24">
      {/* Toast Alert */}
      {toastMsg && (
        <div
          className={`fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-xl p-4 shadow-xl text-xs font-semibold transition-all ${
            toastMsg.type === "success"
              ? "bg-emerald-600 text-white shadow-emerald-600/30"
              : "bg-rose-600 text-white shadow-rose-600/30"
          }`}
        >
          {toastMsg.type === "success" ? (
            <CheckCircle2 className="w-4 h-4 text-emerald-100" />
          ) : (
            <AlertCircle className="w-4 h-4 text-rose-100" />
          )}
          <span>{toastMsg.text}</span>
        </div>
      )}

      {/* ========================================================================= */}
      {/* STEP 1: HERO & GRADE SWITCHER                                             */}
      {/* ========================================================================= */}
      <div className="relative overflow-hidden rounded-3xl border border-border/70 bg-gradient-to-br from-card via-card/80 to-muted/40 p-6 lg:p-8 shadow-sm">
        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
          <div className="space-y-2">
            <div className="flex flex-wrap items-center gap-3">
              <h1 className="text-2xl lg:text-3xl font-bold tracking-tight text-foreground">
                {selectedGrade === 12
                  ? (examPrograms.find((p) => p.id === 1)?.title || "Grade 12 National Exams (EUEE)")
                  : (examPrograms.find((p) => p.id === 2)?.title || "Grade 8 Ministry Regional Primary Exam")}
              </h1>
              <Button
                variant="outline"
                size="sm"
                onClick={() => {
                  const targetExamId = selectedGrade === 8 ? 2 : 1
                  const currentProg = examPrograms.find((p) => p.id === targetExamId)
                  setEditExamId(targetExamId)
                  setEditExamTitle(
                    currentProg?.title ||
                      (selectedGrade === 8
                        ? "Ministry Regional Primary Exam (Grade 8)"
                        : "Ethiopian University Entrance Exam (EUEE - Grade 12)")
                  )
                  setEditExamError(null)
                  setIsEditExamModalOpen(true)
                }}
                className="h-7 px-2.5 rounded-lg border-border text-xs gap-1.5 text-muted-foreground hover:text-foreground shrink-0"
                title="Edit Official National Exam Title"
              >
                <Edit className="w-3 h-3" />
                <span>Edit Title</span>
              </Button>
            </div>
            <p className="text-xs lg:text-sm text-muted-foreground max-w-2xl">
              {selectedGrade === 12
                ? "Browse Matric, EUEE, and ESSLCE papers together. Years keep their real Ethiopian/Gregorian values, and each subject paper stays tied to its program."
                : "Ministry Regional Primary Exam papers by subject and year. Grade 8 is still being filled — empty papers stay visible so you can import into them."}
            </p>
          </div>

          {/* Segmented Hero Grade Switcher */}
          <div className="flex items-center gap-3 bg-muted/60 p-1.5 rounded-2xl border border-border">
            <button
              onClick={() => {
                setSelectedGrade(12)
                setStreamFilter("all")
                setSelectedExamId("all")
                setSelectedSubjectName("")
              }}
              className={`flex items-center gap-2.5 px-5 py-3 rounded-xl text-xs font-semibold transition-all ${
                selectedGrade === 12
                  ? "bg-blue-600 text-white shadow-lg shadow-blue-600/25 scale-[1.02]"
                  : "text-muted-foreground hover:text-foreground hover:bg-muted/40"
              }`}
            >
              <GraduationCap className="w-4 h-4" />
              <div className="text-left">
                <div className="font-bold">Grade 12</div>
                <div className="text-[10px] opacity-80">Matric / EUEE / ESSLCE</div>
              </div>
            </button>

            <button
              onClick={() => {
                setSelectedGrade(8)
                setStreamFilter("all")
                setSelectedExamId("all")
                setSelectedSubjectName("")
              }}
              className={`flex items-center gap-2.5 px-5 py-3 rounded-xl text-xs font-semibold transition-all ${
                selectedGrade === 8
                  ? "bg-indigo-600 text-white shadow-lg shadow-indigo-600/25 scale-[1.02]"
                  : "text-muted-foreground hover:text-foreground hover:bg-muted/40"
              }`}
            >
              <Award className="w-4 h-4" />
              <div className="text-left">
                <div className="font-bold">Grade 8</div>
                <div className="text-[10px] opacity-80">Ministry Primary</div>
              </div>
            </button>
          </div>
        </div>

        <div className="mt-6 pt-6 border-t border-border/50 space-y-3">
          <div className="flex flex-wrap items-center justify-between gap-4">
            {selectedGrade === 12 ? (
              <div className="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
                <span className="text-xs font-medium text-muted-foreground mr-2">Stream Filter:</span>
                {(
                  [
                    { id: "all", label: "All Subjects" },
                    { id: "natural", label: "Natural Science" },
                    { id: "social", label: "Social Science" },
                    { id: "common", label: "Common Core" },
                  ] as const
                ).map((tab) => (
                  <button
                    key={tab.id}
                    onClick={() => setStreamFilter(tab.id)}
                    className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all ${
                      streamFilter === tab.id
                        ? "bg-foreground text-background font-semibold shadow-sm"
                        : "bg-muted/40 text-muted-foreground hover:bg-muted hover:text-foreground"
                    }`}
                  >
                    <span>{tab.label}</span>
                  </button>
                ))}
              </div>
            ) : (
              <div className="text-xs text-muted-foreground">Primary exam subjects are not split by stream.</div>
            )}

            <div className="flex items-center gap-4 text-xs text-muted-foreground">
              <div className="flex items-center gap-1.5">
                <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span><strong>{summaryStats.totalSubjectsCount}</strong> Subjects</span>
              </div>
              <div className="flex items-center gap-1.5">
                <Calendar className="w-3.5 h-3.5 text-blue-500" />
                <span><strong>{summaryStats.totalExamYearsCount}</strong> Papers</span>
              </div>
              <div className="flex items-center gap-1.5">
                <FileQuestion className="w-3.5 h-3.5 text-purple-500" />
                <span><strong>{summaryStats.totalQuestionBank}</strong> Questions</span>
              </div>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => fetchOverview()}
                className="h-8 px-2 text-xs"
              >
                <RefreshCw className={`w-3.5 h-3.5 ${isLoading ? "animate-spin" : ""}`} />
              </Button>
            </div>
          </div>

          {examPrograms.length > 0 && (
            <div className="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
              <span className="text-xs font-medium text-muted-foreground mr-1.5 flex items-center gap-1">
                <Layers className="w-3.5 h-3.5 opacity-70" />
                Program:
              </span>
              <button
                onClick={() => { setSelectedExamId("all"); setSelectedSubjectName("") }}
                className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-all ${
                  selectedExamId === "all"
                    ? "bg-blue-600 text-white shadow-sm"
                    : "bg-muted/40 text-muted-foreground hover:bg-muted hover:text-foreground"
                }`}
              >
                All Programs
              </button>
              {examPrograms.map((prog) => (
                <button
                  key={prog.id}
                  onClick={() => { setSelectedExamId(prog.id); setSelectedSubjectName("") }}
                  className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-all max-w-[220px] truncate ${
                    selectedExamId === prog.id
                      ? "bg-blue-600 text-white shadow-sm"
                      : "bg-muted/40 text-muted-foreground hover:bg-muted hover:text-foreground"
                  }`}
                  title={prog.title}
                >
                  {prog.short || prog.title}
                </button>
              ))}
            </div>
          )}
        </div>
      </div>

      {isLoading && (
        <div className="p-12 text-center rounded-2xl border border-border bg-card">
          <Loader2 className="w-8 h-8 text-blue-600 animate-spin mx-auto mb-2" />
          <p className="text-sm font-semibold text-foreground">Loading national exam catalog...</p>
          <p className="text-xs text-muted-foreground mt-0.5">
            Combining exam programs, years, and subject papers from the live API.
          </p>
        </div>
      )}

      {/* ========================================================================= */}
      {/* STEP 2: SUBJECT CARDS CATALOG (FIRST LEVEL)                               */}
      {/* ========================================================================= */}
      {!isLoading && filteredSubjects.length > 0 && (
        <div className="space-y-3">
          <div className="flex items-center justify-between gap-3">
            <h2 className="text-sm font-bold text-foreground">Subjects</h2>
            <div className="flex items-center gap-3">
              <Button
                variant="outline"
                size="sm"
                onClick={() => {
                  setSubjectModalError(null)
                  setNewSubjectName("")
                  setNewSubjectCode("")
                  setNewSubjectStream("common")
                  setNewSubjectYearId(activeYear?.id || availableYears[0]?.id || 0)
                  setIsAddSubjectModalOpen(true)
                }}
                className="h-8 rounded-xl border-dashed border-border gap-1.5 text-xs text-muted-foreground hover:text-foreground hover:border-foreground"
              >
                <Plus className="w-3.5 h-3.5" />
                Add Exam Subject
              </Button>
              <label className="flex items-center gap-2 text-xs text-muted-foreground cursor-pointer">
                <input
                  type="checkbox"
                  checked={hideEmptyPapers}
                  onChange={(e) => setHideEmptyPapers(e.target.checked)}
                />
                Hide empty papers
              </label>
            </div>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
            {filteredSubjects.map((subject) => {
              const isActive = activeSubject?.name.toLowerCase() === subject.name.toLowerCase()
              return (
                <button
                  key={subject.name}
                  onClick={() => setSelectedSubjectName(subject.name)}
                  className={`text-left rounded-2xl border p-4 transition-all ${
                    isActive
                      ? "border-blue-500 bg-blue-500/5 shadow-sm"
                      : "border-border bg-card hover:border-blue-500/40"
                  }`}
                >
                  <div className="flex items-start justify-between gap-2">
                    <div className="flex items-center gap-2">
                      {getSubjectIcon(subject.name, subject.stream)}
                      <div>
                        <div className="text-sm font-bold text-foreground leading-tight">{subject.name}</div>
                        <div className="mt-1 flex items-center">
                          {subject.stream_id === 1 || subject.stream === "natural" ? (
                            <Badge
                              variant="outline"
                              className="text-[9px] px-1.5 py-0 border-indigo-500/30 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-medium"
                            >
                              Natural Science
                            </Badge>
                          ) : subject.stream_id === 2 || subject.stream === "social" ? (
                            <Badge
                              variant="outline"
                              className="text-[9px] px-1.5 py-0 border-amber-500/30 bg-amber-500/10 text-amber-600 dark:text-amber-400 font-medium"
                            >
                              Social Science
                            </Badge>
                          ) : (
                            <Badge
                              variant="outline"
                              className="text-[9px] px-1.5 py-0 border-blue-500/30 bg-blue-500/10 text-blue-600 dark:text-blue-400 font-medium"
                            >
                              Common Core
                            </Badge>
                          )}
                        </div>
                      </div>
                    </div>
                  </div>
                  <div className="mt-3 flex items-center justify-between text-[11px] text-muted-foreground">
                    <span>{subject.total_years} papers</span>
                    <span className={subject.total_questions > 0 ? "text-emerald-600 font-semibold" : ""}>
                      {subject.total_questions} Qs
                    </span>
                  </div>
                </button>
              )
            })}
          </div>
        </div>
      )}

      {!isLoading && !activeSubject && (
        <div className="p-12 text-center rounded-2xl border border-dashed border-border bg-card">
          <FileQuestion className="w-8 h-8 text-muted-foreground mx-auto mb-2" />
          <h3 className="text-base font-bold">No national exam subjects found</h3>
          <p className="text-xs text-muted-foreground mt-1">Try another grade or program filter.</p>
        </div>
      )}

      {activeSubject && (
        <div className="rounded-2xl border border-border bg-card p-5 shadow-sm space-y-4">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <div className="flex items-center gap-2 text-xs text-muted-foreground">
                <span className="font-semibold text-foreground">
                  Grade {selectedGrade}
                </span>
                <ChevronRight className="w-3.5 h-3.5" />
                <span className="font-semibold text-blue-600 dark:text-blue-400">
                  {activeSubject.name}
                </span>
                <ChevronRight className="w-3.5 h-3.5" />
                <span>Available Exam Years</span>
              </div>
              <h3 className="text-base font-bold text-foreground mt-0.5">
                Select Exam Paper Year for {activeSubject.name}
              </h3>
            </div>

            <div className="flex items-center gap-2 self-start sm:self-auto">
              {activeYear && (
                <>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => {
                      setEditYearId(activeYear.id)
                      setEditYearEc(activeYear.year_ec)
                      setEditYearGc(activeYear.year_gc)
                      setEditYearError(null)
                      setIsEditYearModalOpen(true)
                    }}
                    className="h-9 rounded-xl border-border text-foreground hover:bg-muted gap-1.5 text-xs font-medium"
                  >
                    <Edit className="w-3.5 h-3.5 text-muted-foreground" />
                    <span>Edit {activeYear.year_ec} E.C.</span>
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handleDeleteExamYear(activeYear.id, activeYear.year_ec)}
                    className="h-9 rounded-xl border-red-500/30 text-red-500 hover:bg-red-500/10 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300 gap-1.5 text-xs font-medium"
                  >
                    <Trash2 className="w-3.5 h-3.5" />
                    <span>Delete {activeYear.year_ec} E.C.</span>
                  </Button>
                </>
              )}
              <Button
                variant="outline"
                size="sm"
                onClick={() => setIsCreateYearModalOpen(true)}
                className="h-9 rounded-xl border-dashed border-border gap-1.5 text-xs text-muted-foreground hover:text-foreground hover:border-foreground"
              >
                <Plus className="w-3.5 h-3.5" />
                Add Exam Year
              </Button>
            </div>
          </div>

          {/* Horizontal Pill Selector */}
          <div className="flex items-center gap-2.5 overflow-x-auto pb-1 scrollbar-none">
            {availableYears.length === 0 ? (
              <div className="text-xs text-muted-foreground py-2">
                No exam years configured yet for this subject.
              </div>
            ) : (
              availableYears.map((yr) => {
                const isYrSelected = activeYear?.id === yr.id

                return (
                  <button
                    key={yr.id}
                    onClick={() => setSelectedNesId(paperKey(yr))}
                    className={`group relative flex items-center gap-3 px-4 py-2.5 rounded-xl border text-xs font-semibold transition-all duration-200 ${
                      isYrSelected
                        ? "bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-600/25 scale-[1.02]"
                        : "bg-muted/40 border-border/70 text-foreground hover:bg-muted/80 hover:border-border"
                    }`}
                  >
                    <Calendar
                      className={`w-4 h-4 ${
                        isYrSelected ? "text-white" : "text-blue-500"
                      }`}
                    />
                    <div className="text-left">
                      <div className="font-bold">
                        {yr.year_ec} E.C.
                        <span className="text-[10px] font-normal opacity-75 ml-1">
                          ({yr.year_gc} G.C.)
                        </span>
                      </div>
                    </div>

                    <Badge
                      variant="outline"
                      className={`text-[10px] px-2 py-0.5 border ${
                        isYrSelected
                          ? "bg-white/20 text-white border-transparent"
                          : yr.questions_count > 0
                          ? "bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20"
                          : "bg-muted text-muted-foreground border-border"
                      }`}
                    >
                      {yr.questions_count} Qs
                    </Badge>
                  </button>
                )
              })
            )}
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* STEP 4: QUESTION STUDIO CANVAS (THIRD LEVEL)                              */}
      {/* ========================================================================= */}
      {activeSubject && activeYear ? (
        <div className="space-y-4">
          {/* Header & Action Toolbar */}
          <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-card p-5 rounded-2xl border border-border shadow-sm">
            <div>
              <div className="flex items-center gap-2">
                <h2 className="text-lg font-bold text-foreground">
                  {activeSubject.name} • {activeYear.year_ec} E.C. Question Bank
                </h2>
                <Badge
                  variant="outline"
                  className="bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20 text-[11px]"
                >
                  Grade {selectedGrade}
                </Badge>
              </div>
              <p className="text-xs text-muted-foreground mt-0.5">
                {questions.length} multiple-choice questions indexed for this examination paper.
              </p>
            </div>

            {/* Actions & Search */}
            <div className="flex flex-wrap items-center gap-2.5">
              <div className="relative w-full sm:w-60">
                <Search className="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <Input
                  type="text"
                  placeholder="Filter by keyword / #..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="h-9 pl-8 text-xs rounded-xl"
                />
                {searchQuery && (
                  <button
                    onClick={() => setSearchQuery("")}
                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                  >
                    <X className="w-3.5 h-3.5" />
                  </button>
                )}
              </div>

              <Button
                variant="outline"
                size="sm"
                onClick={() => {
                  setEditSubjectError(null)
                  setEditSubjectId(activeYear.nes_id || activeYear.subject_id || 0)
                  setEditSubjectName(activeSubject.name)
                  setEditSubjectCode(activeSubject.code || activeSubject.name.substring(0, 4).toUpperCase())
                  setEditSubjectStream((activeSubject.stream as any) || "common")
                  setIsEditSubjectModalOpen(true)
                }}
                className="h-9 gap-1.5 rounded-xl border-border text-xs font-semibold"
              >
                <Edit className="w-3.5 h-3.5 text-muted-foreground" />
                Edit Subject
              </Button>

              <Button
                variant="outline"
                size="sm"
                onClick={() =>
                  handleDeleteSubject(
                    activeYear.nes_id || activeYear.subject_id || 0,
                    activeSubject.name
                  )
                }
                className="h-9 gap-1.5 rounded-xl border-red-500/30 text-red-500 hover:bg-red-500/10 hover:text-red-600 dark:text-red-400 text-xs font-semibold"
              >
                <Trash2 className="w-3.5 h-3.5" />
                Delete Subject
              </Button>

              <Button
                variant="outline"
                size="sm"
                onClick={() => setIsImportDrawerOpen(true)}
                className="h-9 gap-1.5 rounded-xl border-border text-xs font-semibold"
              >
                <UploadCloud className="w-4 h-4 text-sky-500" />
                Batch Import (CSV/JSON)
              </Button>

              <Button
                size="sm"
                onClick={handleOpenAddQuestion}
                className="h-9 gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-md shadow-blue-600/20"
              >
                <Plus className="w-4 h-4" />
                Add Question
              </Button>
            </div>
          </div>

          {/* Question List */}
          {isQuestionsLoading ? (
            <div className="p-12 text-center rounded-2xl border border-border bg-card">
              <Loader2 className="w-8 h-8 text-blue-600 animate-spin mx-auto mb-2" />
              <p className="text-sm font-semibold text-foreground">
                Loading question bank...
              </p>
              <p className="text-xs text-muted-foreground mt-0.5">
                Retrieving questions for {activeSubject.name} ({activeYear.year_ec} E.C.)
              </p>
            </div>
          ) : filteredQuestions.length === 0 ? (
            <div className="p-12 text-center rounded-2xl border border-dashed border-border bg-card space-y-3">
              <div className="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-600 mx-auto flex items-center justify-center">
                <FileQuestion className="w-6 h-6" />
              </div>
              <h3 className="text-base font-bold text-foreground">
                {searchQuery
                  ? "No questions match your filter"
                  : `No questions in ${activeSubject.name} (${activeYear.year_ec} E.C.) yet`}
              </h3>
              <p className="text-xs text-muted-foreground max-w-md mx-auto">
                {searchQuery
                  ? `Clear your search term "${searchQuery}" to see all available questions.`
                  : "Start curating this national exam paper by batch importing a CSV or adding individual questions manually."}
              </p>
              <div className="flex items-center justify-center gap-2 pt-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setIsImportDrawerOpen(true)}
                  className="rounded-xl gap-1.5 text-xs"
                >
                  <UploadCloud className="w-3.5 h-3.5 text-sky-500" />
                  Import CSV/JSON
                </Button>
                <Button
                  size="sm"
                  onClick={handleOpenAddQuestion}
                  className="rounded-xl bg-blue-600 text-white gap-1.5 text-xs"
                >
                  <Plus className="w-3.5 h-3.5" />
                  Add First Question
                </Button>
              </div>
            </div>
          ) : (
            <div className="space-y-4">
              {filteredQuestions.map((q) => (
                <Card
                  key={q.id}
                  className="overflow-hidden border-border/80 shadow-sm transition-all hover:border-blue-500/30"
                >
                  <CardContent className="p-5 lg:p-6 space-y-4">
                    {/* Question Header & Controls */}
                    <div className="flex items-start justify-between gap-4">
                      <div className="flex items-center gap-2.5">
                        <Badge
                          variant="secondary"
                          className="text-xs px-2.5 py-1 font-mono font-bold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20"
                        >
                          Q# {String(q.question_number).padStart(2, "0")}
                        </Badge>
                        <span className="text-xs text-muted-foreground">
                          {activeSubject.name} • {activeYear.year_ec} E.C.
                        </span>
                      </div>

                      <div className="flex items-center gap-1.5">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleOpenEditQuestion(q)}
                          className="h-8 w-8 p-0 text-muted-foreground hover:text-foreground hover:bg-muted"
                          title="Edit Question"
                        >
                          <Edit className="w-3.5 h-3.5" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleDeleteQuestion(q.id)}
                          className="h-8 w-8 p-0 text-muted-foreground hover:text-rose-600 hover:bg-rose-500/10"
                          title="Delete Question"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </Button>
                      </div>
                    </div>

                    {/* Question Statement */}
                    <div className="text-sm font-medium text-foreground leading-relaxed whitespace-pre-wrap">
                      {q.question_text}
                    </div>

                    {/* Diagram / Lightbox Thumbnail */}
                    {q.diagram_url && (
                      <div
                        onClick={() => setPreviewDiagram(q.diagram_url!)}
                        className="group/img relative inline-block cursor-pointer rounded-xl border border-border/80 bg-slate-950/20 p-1.5 max-w-sm overflow-hidden"
                      >
                        <img
                          src={q.diagram_url}
                          alt={`Diagram for Question ${q.question_number}`}
                          className="max-h-44 w-auto object-contain rounded-lg transition-transform group-hover/img:scale-[1.02]"
                        />
                        <div className="absolute inset-0 bg-black/40 opacity-0 group-hover/img:opacity-100 flex items-center justify-center transition-opacity text-white text-xs font-semibold gap-1.5 backdrop-blur-[2px] rounded-lg">
                          <ZoomIn className="w-4 h-4" />
                          Click to expand figure
                        </div>
                      </div>
                    )}

                    {/* Choices Grid with Emerald Green Correct Answer Key */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-2.5 pt-1">
                      {(["A", "B", "C", "D"] as const).map((key) => {
                        const isCorrect = q.correct_answer === key
                        const optText = q.options[key] || ""

                        return (
                          <div
                            key={key}
                            className={`flex items-start gap-3 p-3 rounded-xl border text-xs transition-all ${
                              isCorrect
                                ? "bg-emerald-500/[0.08] dark:bg-emerald-950/30 border-emerald-500 text-foreground ring-1 ring-emerald-500/30 font-medium"
                                : "bg-muted/30 border-border text-foreground hover:bg-muted/50"
                            }`}
                          >
                            <span
                              className={`w-6 h-6 rounded-lg flex items-center justify-center font-bold text-[11px] shrink-0 ${
                                isCorrect
                                  ? "bg-emerald-600 text-white shadow-sm shadow-emerald-600/30"
                                  : "bg-muted text-muted-foreground"
                              }`}
                            >
                              {key}
                            </span>
                            <span className="flex-1 leading-relaxed mt-0.5 whitespace-pre-wrap">
                              {optText || <span className="text-muted-foreground/60 italic">(Empty choice)</span>}
                            </span>
                            {isCorrect && (
                              <Badge
                                variant="outline"
                                className="bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/30 text-[10px] shrink-0 font-bold gap-1"
                              >
                                <Check className="w-3 h-3" /> Correct Key
                              </Badge>
                            )}
                          </div>
                        )
                      })}
                    </div>

                    {/* Formula Derivation / Explanation Box */}
                    {q.explanation && (
                      <div className="rounded-xl border border-amber-500/20 bg-amber-500/[0.06] p-4 text-xs space-y-1.5">
                        <div className="flex items-center gap-1.5 font-bold text-amber-700 dark:text-amber-400">
                          <Lightbulb className="w-4 h-4 text-amber-500" />
                          <span>Formula Derivation & Explanation</span>
                        </div>
                        <p className="text-muted-foreground leading-relaxed whitespace-pre-wrap font-mono text-[11px]">
                          {q.explanation}
                        </p>
                      </div>
                    )}
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </div>
      ) : null}

      {/* ========================================================================= */}
      {/* MODALS & DRAWERS                                                          */}
      {/* ========================================================================= */}

      {/* 1. Add / Edit Single Question Modal */}
      {isQuestionModalOpen && activeYear && activeSubject && (
        <ExamQuestionModal
          isOpen={isQuestionModalOpen}
          onClose={() => setIsQuestionModalOpen(false)}
          onSave={handleSaveQuestion}
          questionToEdit={questionToEdit}
          examYearId={activeYear.id}
          subjectId={activeYear.nes_id || activeYear.subject_id}
          subjectName={activeSubject.name}
          nextQuestionNumber={questions.length + 1}
        />
      )}

      {/* 2. Bulk Import Drawer */}
      {isImportDrawerOpen && activeYear && activeSubject && (
        <BulkImportDrawer
          isOpen={isImportDrawerOpen}
          onClose={() => setIsImportDrawerOpen(false)}
          onImportSuccess={handleBatchImportSuccess}
          examYearId={activeYear.id}
          subjectId={activeYear.nes_id || activeYear.subject_id}
          subjectName={activeSubject.name}
          yearEc={activeYear.year_ec}
          gradeLevel={selectedGrade}
        />
      )}

      {/* 3. Create Exam Year Dialog */}
      <Dialog
        open={isCreateYearModalOpen}
        onOpenChange={(open) => {
          setIsCreateYearModalOpen(open)
          if (!open) setYearModalError(null)
        }}
      >
        <DialogContent className="max-w-md rounded-2xl">
          <DialogHeader>
            <DialogTitle className="text-base font-bold flex items-center gap-2">
              <Calendar className="w-4 h-4 text-blue-500" />
              Add Exam Cohort Year (Grade {selectedGrade})
            </DialogTitle>
            <DialogDescription className="text-xs text-muted-foreground">
              Configure a new national exam year. All subjects for Grade {selectedGrade} will automatically be initialized.
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleCreateYear} className="space-y-4 py-2">
            {yearModalError && (
              <div className="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs flex items-center gap-2">
                <AlertCircle className="w-4 h-4 shrink-0" />
                <span>{yearModalError}</span>
              </div>
            )}

            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="year_ec" className="text-xs font-medium">
                  Ethiopian Year (E.C.)
                </Label>
                <Input
                  id="year_ec"
                  type="number"
                  value={newYearEc}
                  onChange={(e) => {
                    const ec = parseInt(e.target.value, 10) || 2017
                    setNewYearEc(ec)
                    setNewYearGc(ec + 8)
                  }}
                  className="rounded-xl text-xs"
                  required
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="year_gc" className="text-xs font-medium">
                  Gregorian Year (G.C.)
                </Label>
                <Input
                  id="year_gc"
                  type="number"
                  value={newYearGc}
                  onChange={(e) => setNewYearGc(parseInt(e.target.value, 10) || 2025)}
                  className="rounded-xl text-xs"
                  required
                />
              </div>
            </div>

            <DialogFooter className="pt-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setIsCreateYearModalOpen(false)}
                className="rounded-xl text-xs"
              >
                Cancel
              </Button>
              <Button
                type="submit"
                size="sm"
                disabled={isCreatingYear}
                className="rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs gap-1.5"
              >
                {isCreatingYear ? (
                  <Loader2 className="w-3.5 h-3.5 animate-spin" />
                ) : (
                  <Plus className="w-3.5 h-3.5" />
                )}
                Create Cohort
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* 4. Add Exam Subject Dialog */}
      <Dialog
        open={isAddSubjectModalOpen}
        onOpenChange={(open) => {
          setIsAddSubjectModalOpen(open)
          if (!open) setSubjectModalError(null)
        }}
      >
        <DialogContent className="max-w-md rounded-2xl">
          <DialogHeader>
            <DialogTitle className="text-base font-bold flex items-center gap-2">
              <BookOpen className="w-4 h-4 text-blue-500" />
              Add Exam Subject to Year
            </DialogTitle>
            <DialogDescription className="text-xs text-muted-foreground">
              Add a new examination subject and paper to a specific cohort year.
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleAddSubject} className="space-y-4 py-2">
            {subjectModalError && (
              <div className="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs flex items-center gap-2">
                <AlertCircle className="w-4 h-4 shrink-0" />
                <span>{subjectModalError}</span>
              </div>
            )}

            <div className="space-y-1.5">
              <Label htmlFor="sub_year" className="text-xs font-medium">
                Target Exam Cohort Year
              </Label>
              <select
                id="sub_year"
                value={newSubjectYearId || activeYear?.id || availableYears[0]?.id || ""}
                onChange={(e) => setNewSubjectYearId(Number(e.target.value))}
                className="w-full h-9 rounded-xl border border-input bg-background px-3 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
              >
                {availableYears.map((yr) => (
                  <option key={yr.id} value={yr.id}>
                    {yr.year_ec} E.C. ({yr.year_gc} G.C.)
                  </option>
                ))}
              </select>
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="sub_name" className="text-xs font-medium">
                  Subject Name
                </Label>
                <Input
                  id="sub_name"
                  placeholder="e.g. Biology"
                  value={newSubjectName}
                  onChange={(e) => {
                    setNewSubjectName(e.target.value)
                    if (!newSubjectCode) {
                      setNewSubjectCode(e.target.value.substring(0, 4).toUpperCase())
                    }
                  }}
                  className="rounded-xl text-xs"
                  required
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="sub_code" className="text-xs font-medium">
                  Subject Code
                </Label>
                <Input
                  id="sub_code"
                  placeholder="e.g. BIOL"
                  value={newSubjectCode}
                  onChange={(e) => setNewSubjectCode(e.target.value.toUpperCase())}
                  className="rounded-xl text-xs"
                />
              </div>
            </div>

            {selectedGrade === 12 && (
              <div className="space-y-1.5">
                <Label className="text-xs font-medium">Stream / Core Classification</Label>
                <div className="grid grid-cols-3 gap-2">
                  {[
                    { id: "natural", label: "Natural Science" },
                    { id: "social", label: "Social Science" },
                    { id: "common", label: "Common Core" },
                  ].map((opt) => (
                    <button
                      key={opt.id}
                      type="button"
                      onClick={() => setNewSubjectStream(opt.id as any)}
                      className={`px-3 py-2 rounded-xl text-xs font-medium border transition-all text-center ${
                        newSubjectStream === opt.id
                          ? "border-blue-600 bg-blue-600/10 text-blue-600 font-semibold dark:border-blue-500"
                          : "border-border text-muted-foreground hover:bg-muted"
                      }`}
                    >
                      {opt.label}
                    </button>
                  ))}
                </div>
              </div>
            )}

            <DialogFooter className="pt-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setIsAddSubjectModalOpen(false)}
                className="rounded-xl text-xs"
              >
                Cancel
              </Button>
              <Button
                type="submit"
                size="sm"
                disabled={isSubmittingSubject}
                className="rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs gap-1.5"
              >
                {isSubmittingSubject ? (
                  <Loader2 className="w-3.5 h-3.5 animate-spin" />
                ) : (
                  <Plus className="w-3.5 h-3.5" />
                )}
                Add Subject
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* 5. Edit Exam Subject Dialog */}
      <Dialog
        open={isEditSubjectModalOpen}
        onOpenChange={(open) => {
          setIsEditSubjectModalOpen(open)
          if (!open) setEditSubjectError(null)
        }}
      >
        <DialogContent className="max-w-md rounded-2xl">
          <DialogHeader>
            <DialogTitle className="text-base font-bold flex items-center gap-2">
              <Edit className="w-4 h-4 text-blue-500" />
              Edit Exam Subject
            </DialogTitle>
            <DialogDescription className="text-xs text-muted-foreground">
              Modify the subject title, code, or academic stream for this exam paper.
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleUpdateSubject} className="space-y-4 py-2">
            {editSubjectError && (
              <div className="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs flex items-center gap-2">
                <AlertCircle className="w-4 h-4 shrink-0" />
                <span>{editSubjectError}</span>
              </div>
            )}

            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="edit_sub_name" className="text-xs font-medium">
                  Subject Name
                </Label>
                <Input
                  id="edit_sub_name"
                  value={editSubjectName}
                  onChange={(e) => setEditSubjectName(e.target.value)}
                  className="rounded-xl text-xs"
                  required
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="edit_sub_code" className="text-xs font-medium">
                  Subject Code
                </Label>
                <Input
                  id="edit_sub_code"
                  value={editSubjectCode}
                  onChange={(e) => setEditSubjectCode(e.target.value.toUpperCase())}
                  className="rounded-xl text-xs"
                />
              </div>
            </div>

            {selectedGrade === 12 && (
              <div className="space-y-1.5">
                <Label className="text-xs font-medium">Stream / Core Classification</Label>
                <div className="grid grid-cols-3 gap-2">
                  {[
                    { id: "natural", label: "Natural Science" },
                    { id: "social", label: "Social Science" },
                    { id: "common", label: "Common Core" },
                  ].map((opt) => (
                    <button
                      key={opt.id}
                      type="button"
                      onClick={() => setEditSubjectStream(opt.id as any)}
                      className={`px-3 py-2 rounded-xl text-xs font-medium border transition-all text-center ${
                        editSubjectStream === opt.id
                          ? "border-blue-600 bg-blue-600/10 text-blue-600 font-semibold dark:border-blue-500"
                          : "border-border text-muted-foreground hover:bg-muted"
                      }`}
                    >
                      {opt.label}
                    </button>
                  ))}
                </div>
              </div>
            )}

            <DialogFooter className="pt-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setIsEditSubjectModalOpen(false)}
                className="rounded-xl text-xs"
              >
                Cancel
              </Button>
              <Button
                type="submit"
                size="sm"
                disabled={isUpdatingSubject}
                className="rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs gap-1.5"
              >
                {isUpdatingSubject ? (
                  <Loader2 className="w-3.5 h-3.5 animate-spin" />
                ) : (
                  <Check className="w-3.5 h-3.5" />
                )}
                Save Changes
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* 4. Diagram Lightbox Preview Dialog */}
      {previewDiagram && (
        <Dialog open={!!previewDiagram} onOpenChange={() => setPreviewDiagram(null)}>
          <DialogContent className="max-w-3xl p-4 rounded-2xl">
            <DialogHeader>
              <DialogTitle className="text-sm font-semibold flex items-center gap-2">
                <ImageIcon className="w-4 h-4 text-sky-500" />
                Diagram & Circuit Lightbox Preview
              </DialogTitle>
            </DialogHeader>
            <div className="flex items-center justify-center p-4 bg-slate-950/40 rounded-xl overflow-hidden">
              <img
                src={previewDiagram}
                alt="Question Diagram"
                className="max-h-[70vh] object-contain rounded-lg shadow-xl"
              />
            </div>
            <DialogFooter>
              <Button
                size="sm"
                variant="outline"
                onClick={() => setPreviewDiagram(null)}
                className="rounded-xl text-xs"
              >
                Close Preview
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      )}

      {/* 6. Edit Official National Exam Title Dialog */}
      <Dialog
        open={isEditExamModalOpen}
        onOpenChange={(open) => {
          setIsEditExamModalOpen(open)
          if (!open) setEditExamError(null)
        }}
      >
        <DialogContent className="max-w-md rounded-2xl">
          <DialogHeader>
            <DialogTitle className="text-base font-bold flex items-center gap-2">
              <Edit className="w-4 h-4 text-blue-500" />
              Edit Official National Exam Title
            </DialogTitle>
            <DialogDescription className="text-xs text-muted-foreground">
              Update the official title for Examination #{editExamId} (Grade {selectedGrade}).
              Note: Database record creation and deletion are strictly locked to safeguard Ethiopian National Exam standards.
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleUpdateExamTitle} className="space-y-4 py-2">
            {editExamError && (
              <div className="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs flex items-center gap-2">
                <AlertCircle className="w-4 h-4 shrink-0" />
                <span>{editExamError}</span>
              </div>
            )}

            <div className="space-y-1.5">
              <Label htmlFor="exam_title" className="text-xs font-medium">
                Official Exam Title
              </Label>
              <Input
                id="exam_title"
                value={editExamTitle}
                onChange={(e) => setEditExamTitle(e.target.value)}
                className="rounded-xl text-xs"
                placeholder="e.g. Ethiopian University Entrance Exam (EUEE - Grade 12)"
                required
              />
            </div>

            <div className="p-3 rounded-xl bg-muted/50 border border-border/80 text-[11px] text-muted-foreground space-y-1">
              <div className="font-semibold text-foreground flex items-center gap-1.5">
                <Award className="w-3.5 h-3.5 text-amber-500" />
                <span>Database Protection Invariant</span>
              </div>
              <p>
                This updates row ID #{editExamId} bound to Grade ID {selectedGrade === 8 ? 5 : 3}. All historical papers and student records remain intact.
              </p>
            </div>

            <DialogFooter className="pt-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setIsEditExamModalOpen(false)}
                className="rounded-xl text-xs"
              >
                Cancel
              </Button>
              <Button
                type="submit"
                size="sm"
                disabled={isUpdatingExam}
                className="rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs gap-1.5"
              >
                {isUpdatingExam ? (
                  <Loader2 className="w-3.5 h-3.5 animate-spin" />
                ) : (
                  <Check className="w-3.5 h-3.5" />
                )}
                Save Exam Title
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* 7. Edit Exam Cohort Year Dialog */}
      <Dialog
        open={isEditYearModalOpen}
        onOpenChange={(open) => {
          setIsEditYearModalOpen(open)
          if (!open) setEditYearError(null)
        }}
      >
        <DialogContent className="max-w-md rounded-2xl">
          <DialogHeader>
            <DialogTitle className="text-base font-bold flex items-center gap-2">
              <Calendar className="w-4 h-4 text-blue-500" />
              Edit Exam Cohort Year
            </DialogTitle>
            <DialogDescription className="text-xs text-muted-foreground">
              Update the calendar years for this examination cohort (Grade {selectedGrade}).
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleUpdateYear} className="space-y-4 py-2">
            {editYearError && (
              <div className="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs flex items-center gap-2">
                <AlertCircle className="w-4 h-4 shrink-0" />
                <span>{editYearError}</span>
              </div>
            )}

            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="edit_year_ec" className="text-xs font-medium">
                  Ethiopian Year (E.C.)
                </Label>
                <Input
                  id="edit_year_ec"
                  type="number"
                  value={editYearEc}
                  onChange={(e) => {
                    const ec = parseInt(e.target.value, 10) || 2017
                    setEditYearEc(ec)
                    setEditYearGc(ec + 8)
                  }}
                  className="rounded-xl text-xs"
                  required
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="edit_year_gc" className="text-xs font-medium">
                  Gregorian Year (G.C.)
                </Label>
                <Input
                  id="edit_year_gc"
                  type="number"
                  value={editYearGc}
                  onChange={(e) => setEditYearGc(parseInt(e.target.value, 10) || 2025)}
                  className="rounded-xl text-xs"
                  required
                />
              </div>
            </div>

            <DialogFooter className="pt-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setIsEditYearModalOpen(false)}
                className="rounded-xl text-xs"
              >
                Cancel
              </Button>
              <Button
                type="submit"
                size="sm"
                disabled={isUpdatingYear}
                className="rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs gap-1.5"
              >
                {isUpdatingYear ? (
                  <Loader2 className="w-3.5 h-3.5 animate-spin" />
                ) : (
                  <Check className="w-3.5 h-3.5" />
                )}
                Save Changes
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  )
}
