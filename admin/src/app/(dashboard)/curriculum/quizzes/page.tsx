"use client"

import React, { useState, useEffect, useMemo } from "react"
import { apiClient, cachedGet } from "@/lib/api-client"
import { OptionKey, QuizQuestion, ChapterQuiz } from "@/types/quiz"
import { QuestionEditorModal } from "@/components/quizzes/question-editor-modal"
import { BatchQuestionModal } from "@/components/quizzes/batch-question-modal"
import { NewQuizModal } from "@/components/quizzes/new-quiz-modal"
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
  HelpCircle,
  PlusCircle,
  Search,
  CheckCircle2,
  Sparkles,
  Layers,
  BookOpen,
  GraduationCap,
  Edit2,
  Trash2,
  RefreshCw,
  AlertTriangle,
  Lightbulb,
  Loader2,
  FileQuestion,
  BookMarked,
  Check,
  Brain,
  ListOrdered,
  CheckSquare,
  Square,
  MinusSquare,
  FolderPlus,
  X,
  CopyPlus,
  CheckCheck,
} from "lucide-react"

interface GradeItem {
  id: number
  name: string
}

interface SubjectItem {
  id: number
  grade_id: number
  grade_name?: string
  title: string
}

interface ChapterItem {
  id: number
  subject_id: number
  grade_id?: number
  chapter_number: number
  title: string
  ai_generated_notes?: string | null
}

function normalizeBackendQuestion(raw: any, index: number): QuizQuestion {
  let correctKey: OptionKey = "A"
  const ans = String(raw.correct_answer || "option_a").toLowerCase()
  if (ans.includes("b") || ans === "b") correctKey = "B"
  else if (ans.includes("c") || ans === "c") correctKey = "C"
  else if (ans.includes("d") || ans === "d") correctKey = "D"

  return {
    id: raw.id || Date.now() + index,
    quiz_id: raw.quiz_id || 0,
    question_number: index + 1,
    question_text: raw.question || raw.question_text || `Question ${index + 1}`,
    options: {
      A: raw.option_a || raw.options?.A || "Option A",
      B: raw.option_b || raw.options?.B || "Option B",
      C: raw.option_c || raw.options?.C || "Option C",
      D: raw.option_d || raw.options?.D || "Option D",
    },
    correct_answer: correctKey,
    explanation:
      raw.explanation ||
      "Derivation step: Standard syllabus multiple choice assessment rationale.",
    created_at: raw.created_at,
    updated_at: raw.updated_at,
  }
}

const SEED_MOCK_QUESTIONS: QuizQuestion[] = [
  {
    id: 1,
    quiz_id: 1,
    question_number: 1,
    question_text:
      "A 1200 kg vehicle accelerates uniformly from rest to 24 m/s in 8.0 seconds along a horizontal surface. What is the net horizontal force acting on the car?",
    options: {
      A: "2,400 N",
      B: "3,600 N",
      C: "4,800 N",
      D: "9,600 N",
    },
    correct_answer: "B",
    explanation:
      "Step 1: Calculate acceleration: a = (v - u) / t = (24 - 0) / 8.0 = 3.0 m/s².\nStep 2: Apply Newton's Second Law: F = m · a = 1200 kg · 3.0 m/s² = 3,600 N.",
  },
  {
    id: 2,
    quiz_id: 1,
    question_number: 2,
    question_text:
      "Which of the following physical quantities is a scalar quantity?",
    options: {
      A: "Linear Momentum",
      B: "Electric Field Strength",
      C: "Gravitational Potential Energy",
      D: "Centripetal Acceleration",
    },
    correct_answer: "C",
    explanation:
      "Energy is a scalar quantity possessing magnitude only without directional orientation. Momentum, electric field strength, and centripetal acceleration are all vector quantities.",
  },
  {
    id: 3,
    quiz_id: 1,
    question_number: 3,
    question_text:
      "According to Kepler's Third Law, the ratio of the square of the orbital period (T²) to the cube of the semi-major axis (r³) for planets orbiting the Sun is:",
    options: {
      A: "Directly proportional to the planetary mass",
      B: "A universal constant for all solar satellites: 4π² / (G·M_sun)",
      C: "Inversely proportional to the orbital speed",
      D: "Dependent on planetary eccentricity",
    },
    correct_answer: "B",
    explanation:
      "Derivation: Equating gravitational force to centripetal force: G·M·m / r² = m · (4π²r / T²). Rearranging gives T² / r³ = 4π² / (G·M_sun), which is constant for all bodies orbiting the central mass.",
  },
  {
    id: 4,
    quiz_id: 1,
    question_number: 4,
    question_text:
      "What provides the centripetal force necessary for a car to negotiate an unbanked curved road of radius r at velocity v?",
    options: {
      A: "Gravitational weight component",
      B: "Static friction between tires and road surface",
      C: "Kinetic air resistance",
      D: "Centrifugal reaction force",
    },
    correct_answer: "B",
    explanation:
      "On an unbanked flat road, the lateral static friction force acts inward toward the center of curvature to provide the required centripetal acceleration: f_s = mv²/r.",
  },
]

export default function ChapterQuizStudioPage() {
  // Cascading Hierarchy Filters
  const [selectedGradeId, setSelectedGradeId] = useState<string>("all")
  const [selectedSubjectId, setSelectedSubjectId] = useState<string>("all")
  const [selectedChapterId, setSelectedChapterId] = useState<number | null>(null)
  const [searchQuery, setSearchQuery] = useState("")

  // Master Data Lists
  const [gradesList, setGradesList] = useState<GradeItem[]>([])
  const [subjectsList, setSubjectsList] = useState<SubjectItem[]>([])
  const [chaptersList, setChaptersList] = useState<ChapterItem[]>([])
  const [isLoadingMetadata, setIsLoadingMetadata] = useState(true)

  // Current Chapter Quizzes (Multi-Quiz support)
  const [chapterQuizzes, setChapterQuizzes] = useState<ChapterQuiz[]>([])
  const [selectedQuizId, setSelectedQuizId] = useState<number | null>(null)
  const [currentQuiz, setCurrentQuiz] = useState<ChapterQuiz | null>(null)
  const [isLoadingQuiz, setIsLoadingQuiz] = useState(false)

  // Question Selection State ("select ... a few or all questions")
  const [selectedQuestionIds, setSelectedQuestionIds] = useState<Set<number>>(new Set())

  // Single Question Editor Modal
  const [isEditorOpen, setIsEditorOpen] = useState(false)
  const [questionToEdit, setQuestionToEdit] = useState<QuizQuestion | null>(null)

  // Batch Add Questions Modal
  const [isBatchEditorOpen, setIsBatchEditorOpen] = useState(false)

  // New Quiz Modal
  const [isNewQuizModalOpen, setIsNewQuizModalOpen] = useState(false)

  // Bulk Delete Modal ("Delete a few")
  const [bulkDeleteModalOpen, setBulkDeleteModalOpen] = useState(false)
  const [isBulkDeleting, setIsBulkDeleting] = useState(false)

  // Delete All Questions Modal ("Delete all")
  const [deleteAllModalOpen, setDeleteAllModalOpen] = useState(false)
  const [isDeletingAll, setIsDeletingAll] = useState(false)

  // Single Question Delete State
  const [deleteModalOpen, setDeleteModalOpen] = useState(false)
  const [questionToDelete, setQuestionToDelete] = useState<QuizQuestion | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)

  // Delete Entire Quiz Modal State
  const [deleteQuizModalOpen, setDeleteQuizModalOpen] = useState(false)
  const [isDeletingQuiz, setIsDeletingQuiz] = useState(false)

  // AI Generation & Append/Overwrite Modal State
  const [aiModalOpen, setAiModalOpen] = useState(false)
  const [aiMode, setAiMode] = useState<"append" | "overwrite">("append")
  const [isAiGenerating, setIsAiGenerating] = useState(false)
  const [aiStepIndex, setAiStepIndex] = useState(0)

  // Feedback Toasts
  const [successToast, setSuccessToast] = useState<string | null>(null)
  const [errorToast, setErrorToast] = useState<string | null>(null)

  const AI_PIPELINE_STEPS = [
    "Analyzing chapter syllabus & core concepts...",
    "Synthesizing multi-choice questions with balanced distractors...",
    "Formulating step-by-step answer derivations...",
    "Finalizing answer keys and question sets...",
  ]

  // 1. Initial Load: Fetch Grades, Subjects, Chapters
  useEffect(() => {
    let isMounted = true
    setIsLoadingMetadata(true)

    Promise.all([
      cachedGet("/grades").catch(() => []),
      cachedGet("/admin/subjects").catch(() => cachedGet("/subjects").catch(() => [])),
      cachedGet("/admin/chapters").catch(() => []),
    ])
      .then(([gradesData, subjectsData, chaptersData]) => {
        if (!isMounted) return

        // Process Grades
        const rawGrades = Array.isArray(gradesData)
          ? gradesData
          : gradesData?.data || []
        const parsedGrades: GradeItem[] = rawGrades.map((g: any) => ({
          id: g.id,
          name: g.name || `Grade ${g.id}`,
        }))
        parsedGrades.sort((a, b) => {
          const numA = parseInt(a.name.replace(/\D/g, "") || "0", 10)
          const numB = parseInt(b.name.replace(/\D/g, "") || "0", 10)
          return numA - numB
        })
        setGradesList(parsedGrades)

        // Process Subjects
        const rawSubjects = Array.isArray(subjectsData)
          ? subjectsData
          : subjectsData?.data || []
        const parsedSubjects: SubjectItem[] = rawSubjects.map((s: any) => ({
          id: s.id,
          grade_id: s.grade_id,
          grade_name: s.grade?.name,
          title: s.title || s.name || `Subject ${s.id}`,
        }))
        setSubjectsList(parsedSubjects)

        // Process Chapters
        const rawChapters = Array.isArray(chaptersData)
          ? chaptersData
          : chaptersData?.data || []
        const parsedChapters: ChapterItem[] = rawChapters.map((c: any) => ({
          id: c.id,
          subject_id: c.subject_id,
          grade_id: c.grade_id,
          chapter_number: c.chapter_number || 1,
          title: c.title || `Chapter ${c.id}`,
          ai_generated_notes: c.ai_generated_notes,
        }))
        setChaptersList(parsedChapters)

        // Auto-select first chapter if available
        if (parsedChapters.length > 0) {
          const firstCh = parsedChapters[0]
          setSelectedChapterId(firstCh.id)
          if (firstCh.grade_id) setSelectedGradeId(String(firstCh.grade_id))
          if (firstCh.subject_id) setSelectedSubjectId(String(firstCh.subject_id))
        }
      })
      .catch((err) => {
        console.error("Failed to load curriculum hierarchy:", err)
      })
      .finally(() => {
        if (isMounted) setIsLoadingMetadata(false)
      })

    return () => {
      isMounted = false
    }
  }, [])

  // 2. Filtered Subjects based on Grade
  const filteredSubjects = useMemo(() => {
    if (selectedGradeId === "all") return subjectsList
    const gId = parseInt(selectedGradeId, 10)
    return subjectsList.filter((s) => s.grade_id === gId)
  }, [subjectsList, selectedGradeId])

  // 3. Filtered Chapters based on Subject & Grade
  const filteredChapters = useMemo(() => {
    return chaptersList.filter((ch) => {
      if (selectedGradeId !== "all") {
        const gId = parseInt(selectedGradeId, 10)
        if (ch.grade_id && ch.grade_id !== gId) {
          const sub = subjectsList.find((s) => s.id === ch.subject_id)
          if (sub && sub.grade_id !== gId) return false
        }
      }
      if (selectedSubjectId !== "all") {
        const sId = parseInt(selectedSubjectId, 10)
        if (ch.subject_id !== sId) return false
      }
      return true
    })
  }, [chaptersList, selectedGradeId, selectedSubjectId, subjectsList])

  // Active Chapter
  const activeChapter = useMemo(() => {
    return chaptersList.find((c) => c.id === selectedChapterId) || null
  }, [chaptersList, selectedChapterId])

  const activeGradeName = useMemo(() => {
    if (!activeChapter) return ""
    const g = gradesList.find((gr) => gr.id === activeChapter.grade_id)
    if (g) return g.name
    const s = subjectsList.find((sub) => sub.id === activeChapter.subject_id)
    return s?.grade_name || ""
  }, [activeChapter, gradesList, subjectsList])

  const activeSubjectName = useMemo(() => {
    if (!activeChapter) return ""
    const s = subjectsList.find((sub) => sub.id === activeChapter.subject_id)
    return s?.title || ""
  }, [activeChapter, subjectsList])

  // 4. Fetch Live Quiz from MySQL for Selected Chapter
  const fetchLiveQuiz = async (chapterId: number) => {
    setIsLoadingQuiz(true)
    setSelectedQuestionIds(new Set())

    try {
      const res = await apiClient.get(`/admin/chapters/${chapterId}/quizzes`)
      const resData = res.data

      if (resData && (Array.isArray(resData.questions) || resData.questions !== undefined)) {
        const quizData = resData.data
        const rawQuestions = resData.questions || []
        const parsedQuestions: QuizQuestion[] = rawQuestions.map((q: any, idx: number) =>
          normalizeBackendQuestion(q, idx)
        )

        if (quizData) {
          const qz: ChapterQuiz = {
            id: quizData.id,
            chapter_id: chapterId,
            chapter_title: activeChapter?.title,
            subject_name: activeSubjectName,
            grade_name: activeGradeName,
            title: quizData.title || `${activeChapter?.title} Quiz Drill`,
            description: quizData.description || "",
            is_premium: Boolean(quizData.is_premium),
            is_ai_generated: Boolean(
              quizData.title?.toLowerCase().includes("ai") || quizData.is_premium
            ),
            questions_count: parsedQuestions.length,
            questions: parsedQuestions,
            last_generated_at: quizData.updated_at || quizData.created_at,
          }

          setChapterQuizzes([qz])
          setSelectedQuizId(qz.id)
          setCurrentQuiz(qz)
        } else {
          setChapterQuizzes([])
          setCurrentQuiz(null)
          setSelectedQuizId(null)
        }
      } else if (Array.isArray(resData?.data) && resData.data.length > 0) {
        const parsedQuizzes: ChapterQuiz[] = resData.data.map((quizData: any) => {
          const rawQuestions = quizData.questions || []
          const parsedQuestions: QuizQuestion[] = rawQuestions.map((q: any, idx: number) =>
            normalizeBackendQuestion(q, idx)
          )
          return {
            id: quizData.id,
            chapter_id: chapterId,
            chapter_title: activeChapter?.title,
            subject_name: activeSubjectName,
            grade_name: activeGradeName,
            title: quizData.title || `${activeChapter?.title} Quiz Drill`,
            description: quizData.description || "",
            is_premium: Boolean(quizData.is_premium),
            is_ai_generated: Boolean(
              quizData.title?.toLowerCase().includes("ai") || quizData.is_premium
            ),
            questions_count: parsedQuestions.length,
            questions: parsedQuestions,
            last_generated_at: quizData.updated_at || quizData.created_at,
          }
        })

        setChapterQuizzes(parsedQuizzes)
        const firstQuiz = parsedQuizzes[0]
        setSelectedQuizId(firstQuiz.id)
        setCurrentQuiz(firstQuiz)
      } else {
        setChapterQuizzes([])
        setCurrentQuiz(null)
        setSelectedQuizId(null)
      }
    } catch (err) {
      console.error("Failed to fetch live quiz from MySQL:", err)
      setChapterQuizzes([])
      setCurrentQuiz(null)
      setSelectedQuizId(null)
    } finally {
      setIsLoadingQuiz(false)
    }
  }

  useEffect(() => {
    if (selectedChapterId) {
      fetchLiveQuiz(selectedChapterId)
    } else {
      setChapterQuizzes([])
      setCurrentQuiz(null)
      setSelectedQuizId(null)
      setSelectedQuestionIds(new Set())
    }
  }, [selectedChapterId, activeChapter, activeGradeName, activeSubjectName])

  // Handle switching quiz within chapter
  const handleSelectQuiz = (quiz: ChapterQuiz) => {
    setSelectedQuizId(quiz.id)
    setCurrentQuiz(quiz)
    setSelectedQuestionIds(new Set())
  }

  // Filter questions by search query
  const filteredQuestions = useMemo(() => {
    if (!currentQuiz) return []
    if (!searchQuery.trim()) return currentQuiz.questions

    const q = searchQuery.toLowerCase().trim()
    return currentQuiz.questions.filter((item) => {
      const matchText = item.question_text.toLowerCase().includes(q)
      const matchExplanation = item.explanation.toLowerCase().includes(q)
      const matchOpts = Object.values(item.options).some((opt) => opt.toLowerCase().includes(q))
      return matchText || matchExplanation || matchOpts
    })
  }, [currentQuiz, searchQuery])

  // ==========================================
  // SELECTION SYSTEM ("select ... questions")
  // ==========================================
  const handleToggleQuestionSelect = (id: number) => {
    setSelectedQuestionIds((prev) => {
      const next = new Set(prev)
      if (next.has(id)) {
        next.delete(id)
      } else {
        next.add(id)
      }
      return next
    })
  }

  const handleSelectAll = () => {
    if (!currentQuiz) return
    const allFilteredIds = filteredQuestions.map((q) => q.id)
    setSelectedQuestionIds(new Set(allFilteredIds))
  }

  const handleDeselectAll = () => {
    setSelectedQuestionIds(new Set())
  }

  const isAllFilteredSelected = useMemo(() => {
    if (filteredQuestions.length === 0) return false
    return filteredQuestions.every((q) => selectedQuestionIds.has(q.id))
  }, [filteredQuestions, selectedQuestionIds])

  const isSomeFilteredSelected = useMemo(() => {
    if (filteredQuestions.length === 0) return false
    return (
      filteredQuestions.some((q) => selectedQuestionIds.has(q.id)) &&
      !isAllFilteredSelected
    )
  }, [filteredQuestions, selectedQuestionIds, isAllFilteredSelected])

  // ==========================================
  // DELETE SYSTEM ("delete a few or all")
  // ==========================================

  // 1. Bulk Delete Selected Questions ("delete a few")
  const handleConfirmBulkDelete = async () => {
    if (!currentQuiz || selectedQuestionIds.size === 0) return
    setIsBulkDeleting(true)

    const idsToDelete = Array.from(selectedQuestionIds)
    const validBackendIds = idsToDelete.filter((id) => id > 0 && id < 1000000000000)

    try {
      if (validBackendIds.length > 0) {
        await apiClient.post("/admin/quiz-questions/bulk-delete", { ids: validBackendIds })
      }

      if (selectedChapterId) {
        await fetchLiveQuiz(selectedChapterId)
      }

      setSelectedQuestionIds(new Set())
      setBulkDeleteModalOpen(false)
      setSuccessToast(`Successfully deleted ${idsToDelete.length} question(s) from database.`)
    } catch (err: any) {
      console.error("Bulk delete error:", err)
      setErrorToast("Failed to bulk delete questions: " + (err.response?.data?.message || err.message))
    } finally {
      setIsBulkDeleting(false)
    }
  }

  // 2. Delete All Questions ("delete all questions of quiz")
  const handleConfirmDeleteAll = async () => {
    if (!currentQuiz) return
    setIsDeletingAll(true)

    try {
      if (currentQuiz.id > 0) {
        await apiClient.delete(`/admin/quizzes/${currentQuiz.id}/questions`)
      }

      if (selectedChapterId) {
        await fetchLiveQuiz(selectedChapterId)
      }

      setSelectedQuestionIds(new Set())
      setDeleteAllModalOpen(false)
      setSuccessToast(`All questions have been deleted from "${currentQuiz.title}".`)
    } catch (err: any) {
      console.error("Delete all error:", err)
      setErrorToast("Failed to delete all questions: " + (err.response?.data?.message || err.message))
    } finally {
      setIsDeletingAll(false)
    }
  }

  // 3. Delete Single Question
  const handleDeleteSingleQuestion = async () => {
    if (!questionToDelete || !currentQuiz) return
    setIsDeleting(true)

    try {
      await apiClient.delete(`/admin/quiz-questions/${questionToDelete.id}`)

      if (selectedChapterId) {
        await fetchLiveQuiz(selectedChapterId)
      }

      setSelectedQuestionIds((prev) => {
        const next = new Set(prev)
        next.delete(questionToDelete.id)
        return next
      })

      setDeleteModalOpen(false)
      setQuestionToDelete(null)
      setSuccessToast("Question deleted successfully from database.")
    } catch (err: any) {
      console.error("Delete question error:", err)
      setErrorToast("Failed to delete question: " + (err.response?.data?.message || err.message))
    } finally {
      setIsDeleting(false)
    }
  }

  // 4. Delete Entire Quiz
  const handleConfirmDeleteQuiz = async () => {
    if (!currentQuiz) return
    setIsDeletingQuiz(true)

    try {
      if (currentQuiz.id > 0 && currentQuiz.id < 1000000000000) {
        await apiClient.delete(`/admin/quizzes/${currentQuiz.id}`)
      }

      if (selectedChapterId) {
        await fetchLiveQuiz(selectedChapterId)
      }

      setSelectedQuestionIds(new Set())
      setDeleteQuizModalOpen(false)
      setSuccessToast(`Quiz "${currentQuiz.title}" was deleted.`)
    } catch (err: any) {
      console.error("Delete quiz error:", err)
      setErrorToast("Failed to delete quiz.")
    } finally {
      setIsDeletingQuiz(false)
    }
  }

  // ==========================================
  // AI GENERATION (Append or Overwrite)
  // ==========================================
  const executeAiGeneration = async (mode: "append" | "overwrite") => {
    if (!activeChapter) return
    setIsAiGenerating(true)
    setAiMode(mode)
    setAiStepIndex(0)

    const interval = setInterval(() => {
      setAiStepIndex((prev) => (prev < AI_PIPELINE_STEPS.length - 1 ? prev + 1 : prev))
    }, 1000)

    try {
      if (mode === "append") {
        await apiClient.post(`/admin/chapters/${activeChapter.id}/generate-quiz`, {
          overwrite: false,
          count: 5,
        })
        clearInterval(interval)
        await fetchLiveQuiz(activeChapter.id)
        setSuccessToast(`Successfully synthesized and appended 5 AI questions to MySQL!`)
      } else {
        await apiClient.post(`/admin/chapters/${activeChapter.id}/generate-quiz`, {
          overwrite: true,
          count: 10,
        })
        clearInterval(interval)
        await fetchLiveQuiz(activeChapter.id)
        setSuccessToast("All questions re-synthesized (10 fresh questions saved to MySQL)!")
        setAiModalOpen(false)
      }
    } catch (err: any) {
      clearInterval(interval)
      console.error("AI Quiz Generation Error:", err)
      setErrorToast("Failed to generate AI questions: " + (err.response?.data?.message || err.message))
    } finally {
      setIsAiGenerating(false)
    }
  }

  // Cascading Filter Handlers
  const handleGradeChange = (gradeId: string) => {
    setSelectedGradeId(gradeId)
    if (gradeId !== "all") {
      const gNum = parseInt(gradeId, 10)
      const validSubjects = subjectsList.filter((s) => s.grade_id === gNum)
      if (validSubjects.length > 0) {
        setSelectedSubjectId(String(validSubjects[0].id))
        const firstCh = chaptersList.find((c) => c.subject_id === validSubjects[0].id)
        if (firstCh) setSelectedChapterId(firstCh.id)
      } else {
        setSelectedSubjectId("all")
      }
    }
  }

  const handleSubjectChange = (subjectId: string) => {
    setSelectedSubjectId(subjectId)
    if (subjectId !== "all") {
      const sNum = parseInt(subjectId, 10)
      const firstCh = chaptersList.find((c) => c.subject_id === sNum)
      if (firstCh) setSelectedChapterId(firstCh.id)
    }
  }

  return (
    <div className="space-y-6 pb-28">
      {/* Page Header */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
              Chapter Quiz Studio
            </h1>
          </div>
          <p className="mt-1 text-sm text-slate-500">
            Select, author, and bulk-manage multi-choice questions and quizzes for chapter curriculums.
          </p>
        </div>

        {/* Global Action: New Quiz & Add Questions */}
        {activeChapter && (
          <div className="flex flex-wrap items-center gap-2">
            <Button
              variant="outline"
              onClick={() => setIsNewQuizModalOpen(true)}
              className="gap-2 border-slate-200 text-xs font-semibold shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
            >
              <FolderPlus className="h-4 w-4 text-purple-600" />
              New Chapter Quiz
            </Button>

            {currentQuiz && (
              <>
                <Button
                  variant="outline"
                  onClick={() => setIsBatchEditorOpen(true)}
                  className="gap-2 border-blue-200 text-xs font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-900 dark:text-blue-300 dark:hover:bg-blue-950/40"
                >
                  <CopyPlus className="h-4 w-4 text-blue-600" />
                  Batch Add Questions
                </Button>

                <Button
                  onClick={() => {
                    setQuestionToEdit(null)
                    setIsEditorOpen(true)
                  }}
                  className="gap-2 text-xs font-semibold shadow-xs"
                >
                  <PlusCircle className="h-4 w-4" />
                  Add Question
                </Button>
              </>
            )}
          </div>
        )}
      </div>

      {/* Toast Alerts */}
      {successToast && (
        <div className="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50/80 p-3.5 text-xs text-emerald-800 shadow-xs dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300">
          <div className="flex items-center gap-2">
            <CheckCircle2 className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
            <span>{successToast}</span>
          </div>
          <button onClick={() => setSuccessToast(null)} className="text-emerald-700 hover:text-emerald-900">
            ×
          </button>
        </div>
      )}

      {errorToast && (
        <div className="flex items-center justify-between rounded-xl border border-rose-200 bg-rose-50/80 p-3.5 text-xs text-rose-800 shadow-xs dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-300">
          <div className="flex items-center gap-2">
            <AlertTriangle className="h-4 w-4 text-rose-600 dark:text-rose-400" />
            <span>{errorToast}</span>
          </div>
          <button onClick={() => setErrorToast(null)} className="text-rose-700 hover:text-rose-900">
            ×
          </button>
        </div>
      )}

      {/* 1. Cascading Filter Bar (Grade -> Subject -> Chapter) */}
      <Card className="border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <CardContent className="p-4 sm:p-5">
          <div className="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-end">
            {/* Step 1: Grade Selector */}
            <div className="space-y-1.5 md:col-span-3">
              <Label className="flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                <Layers className="h-3.5 w-3.5 text-blue-500" />
                1. Grade Level
              </Label>
              <select
                value={selectedGradeId}
                onChange={(e) => handleGradeChange(e.target.value)}
                disabled={isLoadingMetadata}
                className="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-800 shadow-xs focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
              >
                <option value="all">All Secondary Grades</option>
                {gradesList.map((g) => (
                  <option key={g.id} value={String(g.id)}>
                    {g.name}
                  </option>
                ))}
              </select>
            </div>

            {/* Step 2: Subject Selector */}
            <div className="space-y-1.5 md:col-span-4">
              <Label className="flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                <BookOpen className="h-3.5 w-3.5 text-purple-500" />
                2. Subject Curriculum
              </Label>
              <select
                value={selectedSubjectId}
                onChange={(e) => handleSubjectChange(e.target.value)}
                disabled={isLoadingMetadata}
                className="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-800 shadow-xs focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
              >
                <option value="all">All Subjects ({filteredSubjects.length})</option>
                {filteredSubjects.map((s) => (
                  <option key={s.id} value={String(s.id)}>
                    {s.title} {s.grade_name ? `(${s.grade_name})` : ""}
                  </option>
                ))}
              </select>
            </div>

            {/* Step 3: Chapter Selector */}
            <div className="space-y-1.5 md:col-span-5">
              <Label className="flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                <GraduationCap className="h-3.5 w-3.5 text-emerald-500" />
                3. Chapter Target ({filteredChapters.length})
              </Label>
              <select
                value={selectedChapterId || ""}
                onChange={(e) => setSelectedChapterId(Number(e.target.value))}
                disabled={isLoadingMetadata || filteredChapters.length === 0}
                className="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-800 shadow-xs focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
              >
                {filteredChapters.length === 0 && <option value="">No chapters available</option>}
                {filteredChapters.map((c) => (
                  <option key={c.id} value={c.id}>
                    Ch {c.chapter_number}: {c.title}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* 2. Multi-Quiz Selector Bar */}
      {activeChapter && (
        <div className="space-y-3">
              <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-2.5 dark:border-slate-800">
                <div className="flex flex-wrap items-center gap-2">
                  <span className="text-xs font-bold uppercase tracking-wider text-slate-500">
                    Quizzes for Chapter {activeChapter.chapter_number} ({chapterQuizzes.length}):
                  </span>
                  {chapterQuizzes.map((qz) => {
                    const isActive = qz.id === selectedQuizId
                    return (
                      <button
                        key={qz.id}
                        onClick={() => handleSelectQuiz(qz)}
                        className={`group flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-medium transition ${
                          isActive
                            ? "bg-slate-900 text-white shadow-xs dark:bg-slate-100 dark:text-slate-900"
                            : "bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        }`}
                      >
                        <span>{qz.title}</span>
                        <span
                          className={`rounded-full px-1.5 py-0.2 text-[10px] font-bold ${
                            isActive
                              ? "bg-slate-700 text-white dark:bg-slate-200 dark:text-slate-900"
                              : "bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300"
                          }`}
                        >
                          {qz.questions_count}
                        </span>
                        {qz.is_premium && (
                          <span className="text-[10px] text-amber-400">★</span>
                        )}
                      </button>
                    )
                  })}

                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => setIsNewQuizModalOpen(true)}
                    className="h-8 gap-1.5 text-xs text-purple-600 hover:bg-purple-50 hover:text-purple-700 dark:text-purple-400 dark:hover:bg-purple-950/40"
                  >
                    <PlusCircle className="h-3.5 w-3.5" />
                    Add Quiz
                  </Button>
                </div>
              </div>
            </div>
          )}

          {/* 3. Quiz Details & Primary Actions Card */}
          {activeChapter && currentQuiz && (
            <Card className="overflow-hidden border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
              <CardContent className="p-5 sm:p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                  {/* Left Title & Breadcrumbs */}
                  <div>
                    <div className="flex items-center gap-2 text-xs font-medium text-slate-500">
                      <span>{activeGradeName || "Secondary"}</span>
                      <span>›</span>
                      <span>{activeSubjectName || "Curriculum"}</span>
                      <span>›</span>
                      <span className="font-semibold text-slate-800 dark:text-slate-200">
                        Chapter {activeChapter.chapter_number}
                      </span>
                    </div>

                    <div className="mt-1 flex items-center gap-2.5">
                      <h2 className="text-xl font-bold text-slate-900 dark:text-slate-100">
                        {currentQuiz.title}
                      </h2>
                      {currentQuiz.is_premium && (
                        <Badge className="bg-amber-500 text-white hover:bg-amber-600 text-[10px]">
                          ★ Premium Assessment
                        </Badge>
                      )}
                    </div>

                    {currentQuiz.description && (
                      <p className="mt-1 text-xs text-slate-500">{currentQuiz.description}</p>
                    )}

                    <div className="mt-2.5 flex flex-wrap items-center gap-2">
                      <Badge variant="secondary" className="gap-1 text-xs">
                        <ListOrdered className="h-3 w-3 text-blue-500" />
                        {currentQuiz.questions_count} Questions
                      </Badge>

                      {currentQuiz.is_ai_generated ? (
                        <Badge
                          variant="outline"
                          className="gap-1 border-purple-200 bg-purple-50 text-xs text-purple-700 dark:border-purple-900 dark:bg-purple-950/40 dark:text-purple-300"
                        >
                          <Sparkles className="h-3 w-3 text-purple-600 dark:text-purple-400" />
                          AI Synthesized
                        </Badge>
                      ) : (
                        <Badge
                          variant="outline"
                          className="gap-1 border-slate-200 bg-slate-50 text-xs text-slate-700 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-300"
                        >
                          Teacher Authored
                        </Badge>
                      )}
                    </div>
                  </div>

                  {/* Action Buttons Toolbar */}
                  <div className="flex flex-wrap items-center gap-2">
                    <Button
                      variant="outline"
                      onClick={() => executeAiGeneration("append")}
                      disabled={isAiGenerating}
                      className="gap-1.5 text-xs border-purple-200 text-purple-700 hover:bg-purple-50 dark:border-purple-800 dark:text-purple-300 dark:hover:bg-purple-950/40"
                    >
                      {isAiGenerating && aiMode === "append" ? (
                        <>
                          <Loader2 className="h-3.5 w-3.5 animate-spin" />
                          Appending...
                        </>
                      ) : (
                        <>
                          <Sparkles className="h-3.5 w-3.5 text-purple-600" />
                          + Append 5 AI Qs
                        </>
                      )}
                    </Button>

                    <Button
                      variant="outline"
                      onClick={() => {
                        setAiMode("overwrite")
                        setAiModalOpen(true)
                      }}
                      disabled={isAiGenerating}
                      className="gap-1.5 text-xs border-amber-200 text-amber-700 hover:bg-amber-50 dark:border-amber-800 dark:text-amber-300 dark:hover:bg-amber-950/40"
                    >
                      <RefreshCw className="h-3.5 w-3.5" />
                      Re-synthesize All
                    </Button>

                    {/* Delete Quiz */}
                    {chapterQuizzes.length > 1 && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => setDeleteQuizModalOpen(true)}
                        className="h-9 text-xs text-rose-600 hover:bg-rose-50 hover:text-rose-700 dark:text-rose-400 dark:hover:bg-rose-950/40"
                        title="Delete this entire quiz"
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    )}
                  </div>
                </div>
              </CardContent>
            </Card>
          )}

          {/* 4. Question Bank Explorer */}
          {isLoadingQuiz ? (
            <div className="flex flex-col items-center justify-center py-20 text-slate-400">
              <Loader2 className="h-8 w-8 animate-spin text-purple-600" />
              <p className="mt-3 text-xs font-medium">Loading chapter questions...</p>
            </div>
          ) : filteredQuestions.length === 0 && searchQuery.trim() ? (
            <Card className="p-8 text-center text-slate-400">
              <Search className="mx-auto h-8 w-8 text-slate-300" />
              <p className="mt-2 text-xs">No questions matched &quot;{searchQuery}&quot;</p>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => setSearchQuery("")}
                className="mt-2 text-xs"
              >
                Clear search
              </Button>
            </Card>
          ) : currentQuiz && currentQuiz.questions.length > 0 ? (
            <div className="space-y-4">
              {/* Question Bank Selection & Filter Toolbar */}
              <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between rounded-xl border border-slate-200 bg-white p-3.5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                {/* Selection Checkbox & Counts */}
                <div className="flex items-center gap-3">
                  <button
                    type="button"
                    onClick={isAllFilteredSelected ? handleDeselectAll : handleSelectAll}
                    className="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                  >
                    {isAllFilteredSelected ? (
                      <CheckSquare className="h-4 w-4 text-purple-600" />
                    ) : isSomeFilteredSelected ? (
                      <MinusSquare className="h-4 w-4 text-purple-600" />
                    ) : (
                      <Square className="h-4 w-4 text-slate-400" />
                    )}
                    <span>
                      {isAllFilteredSelected
                        ? "Deselect All"
                        : `Select All (${filteredQuestions.length})`}
                    </span>
                  </button>

                  {selectedQuestionIds.size > 0 && (
                    <Badge
                      variant="secondary"
                      className="gap-1 border-purple-200 bg-purple-50 text-xs font-semibold text-purple-700 dark:border-purple-900 dark:bg-purple-950/40 dark:text-purple-300"
                    >
                      <Check className="h-3 w-3" />
                      {selectedQuestionIds.size} Selected
                    </Badge>
                  )}
                </div>

                {/* Search Input */}
                <div className="relative w-full sm:w-72">
                  <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" />
                  <Input
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Search questions or derivations..."
                    className="h-8 pl-8 text-xs"
                  />
                </div>
              </div>

              {/* Questions Cards List */}
              <div className="space-y-4">
                {filteredQuestions.map((q) => {
                  const isSelected = selectedQuestionIds.has(q.id)
                  return (
                    <Card
                      key={q.id}
                      className={`overflow-hidden transition ${
                        isSelected
                          ? "border-purple-500 bg-purple-50/15 shadow-sm ring-1 ring-purple-400/40 dark:border-purple-600 dark:bg-purple-950/20"
                          : "border-slate-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900"
                      }`}
                    >
                      <CardContent className="p-5 sm:p-6">
                        {/* Card Header: Checkbox + Q# Badge & Actions */}
                        <div className="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800/80">
                          <div className="flex items-center gap-3">
                            {/* Question Select Checkbox */}
                            <button
                              type="button"
                              onClick={() => handleToggleQuestionSelect(q.id)}
                              className="flex h-5 w-5 items-center justify-center rounded border border-slate-300 text-purple-600 transition hover:border-purple-500 dark:border-slate-600"
                              title={isSelected ? "Deselect question" : "Select question"}
                            >
                              {isSelected ? (
                                <CheckSquare className="h-4 w-4 text-purple-600" />
                              ) : (
                                <Square className="h-4 w-4 text-slate-300 dark:text-slate-600" />
                              )}
                            </button>

                            <span className="flex h-7 items-center justify-center rounded-lg bg-slate-900 px-2.5 text-xs font-bold text-white dark:bg-slate-100 dark:text-slate-900">
                              Q# {String(q.question_number).padStart(2, "0")}
                            </span>

                            <span className="text-xs text-slate-400">
                              Multiple Choice (Single Correct)
                            </span>
                          </div>

                          <div className="flex items-center gap-1">
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => {
                                setQuestionToEdit(q)
                                setIsEditorOpen(true)
                              }}
                              className="h-8 gap-1 text-xs text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
                            >
                              <Edit2 className="h-3.5 w-3.5" />
                              Edit
                            </Button>

                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => {
                                setQuestionToDelete(q)
                                setDeleteModalOpen(true)
                              }}
                              className="h-8 text-xs text-rose-600 hover:bg-rose-50 hover:text-rose-700 dark:text-rose-400 dark:hover:bg-rose-950/40"
                            >
                              <Trash2 className="h-3.5 w-3.5" />
                            </Button>
                          </div>
                        </div>

                        {/* Problem Statement */}
                        <div className="my-4">
                          <p className="text-sm font-semibold leading-relaxed text-slate-900 dark:text-slate-100">
                            {q.question_text}
                          </p>
                        </div>

                        {/* 4 Choices Grid (A, B, C, D) with Emerald Green on correct answer */}
                        <div className="my-4 grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                          {(["A", "B", "C", "D"] as OptionKey[]).map((key) => {
                            const isCorrect = q.correct_answer === key
                            return (
                              <div
                                key={key}
                                className={`relative flex items-start gap-3 rounded-xl border p-3.5 text-xs transition ${
                                  isCorrect
                                    ? "border-emerald-500 bg-emerald-50/70 shadow-xs dark:border-emerald-600 dark:bg-emerald-950/30"
                                    : "border-slate-200 bg-slate-50/50 text-slate-700 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-300"
                                }`}
                              >
                                <span
                                  className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-xs font-bold ${
                                    isCorrect
                                      ? "bg-emerald-600 text-white"
                                      : "bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                  }`}
                                >
                                  {key}
                                </span>

                                <div className="min-w-0 flex-1">
                                  <p
                                    className={
                                      isCorrect
                                        ? "font-semibold text-emerald-950 dark:text-emerald-200"
                                        : ""
                                    }
                                  >
                                    {q.options[key]}
                                  </p>
                                </div>

                                {isCorrect && (
                                  <span className="flex shrink-0 items-center gap-1 rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white">
                                    <Check className="h-3 w-3" />
                                    Correct Answer
                                  </span>
                                )}
                              </div>
                            )
                          })}
                        </div>

                        {/* Explanation Card */}
                        {q.explanation && (
                          <div className="rounded-xl border border-slate-100 bg-slate-50/70 p-4 text-xs text-slate-700 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300">
                            <div className="flex items-center gap-1.5 font-bold text-slate-900 dark:text-slate-100 mb-1">
                              <Lightbulb className="h-4 w-4 text-amber-500" />
                              <span>Answer Derivation &amp; Syllabus Context:</span>
                            </div>
                            <p className="leading-relaxed pl-5">{q.explanation}</p>
                          </div>
                        )}
                      </CardContent>
                    </Card>
                  )
                })}
              </div>
            </div>
          ) : activeChapter ? (
            /* Empty State: No Quiz or 0 Questions */
            <Card className="border-dashed border-slate-200 bg-white p-12 text-center dark:border-slate-800 dark:bg-slate-900">
              <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                <FileQuestion className="h-8 w-8" />
              </div>
              <h3 className="mt-4 text-base font-bold text-slate-900 dark:text-slate-100">
                {currentQuiz
                  ? `"${currentQuiz.title}" has no questions yet.`
                  : "No quiz exists for this chapter yet."}
              </h3>
              <p className="mx-auto mt-1.5 max-w-md text-xs text-slate-500">
                Synthesize multi-choice questions with full derivations using AI synthesis, or construct questions individually or in batch.
              </p>

              <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
                <Button
                  onClick={() => {
                    setAiMode("overwrite")
                    executeAiGeneration("overwrite")
                  }}
                  disabled={isAiGenerating}
                  className="gap-2 bg-gradient-to-r from-purple-600 to-blue-600 shadow-sm hover:from-purple-700 hover:to-blue-700 text-xs font-semibold"
                >
                  {isAiGenerating ? (
                    <>
                      <Loader2 className="h-4 w-4 animate-spin" />
                      Synthesizing Questions...
                    </>
                  ) : (
                    <>
                      <Sparkles className="h-4 w-4" />
                      ⚡ Generate Quiz with AI
                    </>
                  )}
                </Button>

                <Button
                  variant="outline"
                  onClick={() => setIsBatchEditorOpen(true)}
                  className="gap-2 text-xs font-semibold"
                >
                  <CopyPlus className="h-4 w-4 text-blue-500" />
                  Batch Add Questions
                </Button>

                <Button
                  variant="outline"
                  onClick={() => {
                    setQuestionToEdit(null)
                    setIsEditorOpen(true)
                  }}
                  className="gap-2 text-xs font-semibold"
                >
                  <PlusCircle className="h-4 w-4" />
                  Add Single Question
                </Button>
              </div>
            </Card>
          ) : (
            /* Empty State: No Chapter Selected */
            <Card className="border-dashed border-slate-200 p-12 text-center text-slate-400 dark:border-slate-800">
              <GraduationCap className="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" />
              <h3 className="mt-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                Select a Chapter to Inspect Quiz
              </h3>
              <p className="mt-1 text-xs text-slate-400">
                Choose a Grade and Subject above to review or author chapter questions.
              </p>
            </Card>
          )}

      {/* ==========================================
          FLOATING BULK ACTIONS BAR ("Delete a few")
         ========================================== */}
      {selectedQuestionIds.size > 0 && (
        <div className="fixed bottom-6 left-1/2 z-50 flex -translate-x-1/2 items-center gap-3 rounded-2xl border border-slate-700/60 bg-slate-900/95 px-5 py-3 text-white shadow-2xl backdrop-blur-md dark:border-slate-600 dark:bg-slate-950/95">
          <div className="flex items-center gap-2 text-xs font-semibold">
            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-purple-500 text-xs font-bold text-white">
              {selectedQuestionIds.size}
            </span>
            <span>
              {selectedQuestionIds.size} of {currentQuiz?.questions.length || 0} questions selected
            </span>
          </div>

          <div className="h-4 w-px bg-slate-700" />

          <Button
            type="button"
            variant="ghost"
            size="sm"
            onClick={handleDeselectAll}
            className="h-8 text-xs text-slate-300 hover:bg-slate-800 hover:text-white"
          >
            Clear Selection
          </Button>

          <Button
            type="button"
            size="sm"
            onClick={() => setBulkDeleteModalOpen(true)}
            className="h-8 gap-1.5 bg-rose-600 text-xs font-semibold text-white hover:bg-rose-700"
          >
            <Trash2 className="h-3.5 w-3.5" />
            Delete Selected ({selectedQuestionIds.size})
          </Button>
        </div>
      )}

      {/* ==========================================
          MODALS
         ========================================== */}

      {/* 1. Single Question Editor Modal */}
      {activeChapter && (
        <QuestionEditorModal
          isOpen={isEditorOpen}
          onClose={() => {
            setIsEditorOpen(false)
            setQuestionToEdit(null)
          }}
          chapterId={activeChapter.id}
          chapterTitle={activeChapter.title}
          quizId={currentQuiz?.id}
          questionToEdit={questionToEdit}
          nextQuestionNumber={(currentQuiz?.questions.length || 0) + 1}
          onSaved={async (savedQuestion) => {
            if (activeChapter?.id) {
              await fetchLiveQuiz(activeChapter.id)
            }
            setSuccessToast("Question saved successfully.")
          }}
        />
      )}

      {/* 2. Batch Question Editor Modal */}
      {activeChapter && (
        <BatchQuestionModal
          isOpen={isBatchEditorOpen}
          onClose={() => setIsBatchEditorOpen(false)}
          quizId={currentQuiz?.id}
          chapterId={activeChapter.id}
          chapterTitle={activeChapter.title}
          startingQuestionNumber={(currentQuiz?.questions.length || 0) + 1}
          onSaved={async (newQuestions) => {
            if (activeChapter?.id) {
              await fetchLiveQuiz(activeChapter.id)
            }
            setSuccessToast(`Successfully added ${newQuestions.length} questions.`)
          }}
        />
      )}

      {/* 3. New Quiz Modal */}
      {activeChapter && (
        <NewQuizModal
          isOpen={isNewQuizModalOpen}
          onClose={() => setIsNewQuizModalOpen(false)}
          chapterId={activeChapter.id}
          chapterTitle={activeChapter.title}
          gradeId={activeChapter.grade_id}
          subjectId={activeChapter.subject_id}
          gradeName={activeGradeName}
          subjectName={activeSubjectName}
          onCreated={async (newQuiz) => {
            if (activeChapter?.id) {
              await fetchLiveQuiz(activeChapter.id)
            }
            setSuccessToast(`Created new quiz "${newQuiz.title}".`)
          }}
        />
      )}

      {/* 4. Bulk Delete Confirmation Modal ("Delete a few") */}
      <Dialog open={bulkDeleteModalOpen} onOpenChange={setBulkDeleteModalOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <div className="flex items-center gap-2 text-rose-600 dark:text-rose-400">
              <Trash2 className="h-5 w-5" />
              <DialogTitle>Delete {selectedQuestionIds.size} Selected Questions?</DialogTitle>
            </div>
            <DialogDescription className="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
              You are about to permanently delete{" "}
              <strong className="text-slate-900 dark:text-slate-200">
                {selectedQuestionIds.size} questions
              </strong>{" "}
              from <strong className="text-slate-900 dark:text-slate-200">{currentQuiz?.title}</strong>. This action cannot be undone.
            </DialogDescription>
          </DialogHeader>

          <DialogFooter className="mt-4 gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => setBulkDeleteModalOpen(false)}
              disabled={isBulkDeleting}
            >
              Cancel
            </Button>
            <Button
              type="button"
              variant="destructive"
              onClick={handleConfirmBulkDelete}
              disabled={isBulkDeleting}
              className="gap-2"
            >
              {isBulkDeleting ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Deleting...
                </>
              ) : (
                `Yes, Delete ${selectedQuestionIds.size} Questions`
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* 5. Delete All Questions Confirmation Modal ("Delete all") */}
      <Dialog open={deleteAllModalOpen} onOpenChange={setDeleteAllModalOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <div className="flex items-center gap-2 text-rose-600 dark:text-rose-400">
              <AlertTriangle className="h-5 w-5" />
              <DialogTitle>Delete All Questions in Quiz?</DialogTitle>
            </div>
            <DialogDescription className="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
              Are you sure you want to permanently remove all{" "}
              <strong className="text-slate-900 dark:text-slate-200">
                {currentQuiz?.questions.length || 0} questions
              </strong>{" "}
              from <strong className="text-slate-900 dark:text-slate-200">{currentQuiz?.title}</strong>? The quiz container itself will be preserved, but all questions will be removed.
            </DialogDescription>
          </DialogHeader>

          <DialogFooter className="mt-4 gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => setDeleteAllModalOpen(false)}
              disabled={isDeletingAll}
            >
              Cancel
            </Button>
            <Button
              type="button"
              variant="destructive"
              onClick={handleConfirmDeleteAll}
              disabled={isDeletingAll}
              className="gap-2"
            >
              {isDeletingAll ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Deleting All Questions...
                </>
              ) : (
                "Yes, Delete All Questions"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* 6. Single Question Delete Confirmation Modal */}
      <Dialog open={deleteModalOpen} onOpenChange={setDeleteModalOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <div className="flex items-center gap-2 text-rose-600 dark:text-rose-400">
              <Trash2 className="h-5 w-5" />
              <DialogTitle>Delete Question</DialogTitle>
            </div>
            <DialogDescription className="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
              Are you sure you want to permanently delete Question #{questionToDelete?.question_number}? This action cannot be undone.
            </DialogDescription>
          </DialogHeader>

          <DialogFooter className="mt-4 gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => setDeleteModalOpen(false)}
              disabled={isDeleting}
            >
              Cancel
            </Button>
            <Button
              type="button"
              variant="destructive"
              onClick={handleDeleteSingleQuestion}
              disabled={isDeleting}
              className="gap-2"
            >
              {isDeleting ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Deleting...
                </>
              ) : (
                "Delete Question"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* 7. Delete Entire Quiz Confirmation Modal */}
      <Dialog open={deleteQuizModalOpen} onOpenChange={setDeleteQuizModalOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <div className="flex items-center gap-2 text-rose-600 dark:text-rose-400">
              <Trash2 className="h-5 w-5" />
              <DialogTitle>Delete Entire Quiz?</DialogTitle>
            </div>
            <DialogDescription className="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
              Are you sure you want to permanently delete the quiz{" "}
              <strong className="text-slate-900 dark:text-slate-200">
                "{currentQuiz?.title}"
              </strong>{" "}
              and all of its questions?
            </DialogDescription>
          </DialogHeader>

          <DialogFooter className="mt-4 gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => setDeleteQuizModalOpen(false)}
              disabled={isDeletingQuiz}
            >
              Cancel
            </Button>
            <Button
              type="button"
              variant="destructive"
              onClick={handleConfirmDeleteQuiz}
              disabled={isDeletingQuiz}
              className="gap-2"
            >
              {isDeletingQuiz ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Deleting Quiz...
                </>
              ) : (
                "Yes, Delete Entire Quiz"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* 8. AI Generation Modal (Append or Overwrite) */}
      <Dialog open={aiModalOpen} onOpenChange={setAiModalOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <div className="flex items-center gap-2 text-purple-600 dark:text-purple-400">
              <Sparkles className="h-5 w-5" />
              <DialogTitle>
                {aiMode === "append" ? "Append 5 AI Questions" : "Regenerate Quiz Questions"}
              </DialogTitle>
            </div>
            <DialogDescription className="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
              {aiMode === "append"
                ? `The AI synthesis engine will generate 5 new assessment questions from the chapter syllabus and append them to "${currentQuiz?.title}". Existing questions will not be modified.`
                : `This will replace all existing questions in "${currentQuiz?.title}" with freshly synthesized questions, balanced distractors, and derivations. Continue?`}
            </DialogDescription>
          </DialogHeader>

          {isAiGenerating ? (
            <div className="space-y-3 py-4">
              <div className="flex items-center gap-3">
                <Loader2 className="h-5 w-5 animate-spin text-purple-600" />
                <p className="text-xs font-semibold text-slate-800 dark:text-slate-200">
                  {AI_PIPELINE_STEPS[aiStepIndex]}
                </p>
              </div>
              <div className="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                <div
                  className="h-full bg-gradient-to-r from-purple-600 to-blue-600 transition-all duration-500"
                  style={{
                    width: `${((aiStepIndex + 1) / AI_PIPELINE_STEPS.length) * 100}%`,
                  }}
                />
              </div>
            </div>
          ) : (
            <DialogFooter className="mt-4 gap-2">
              <Button
                type="button"
                variant="outline"
                onClick={() => setAiModalOpen(false)}
                disabled={isAiGenerating}
              >
                Cancel
              </Button>
              <Button
                type="button"
                onClick={() => executeAiGeneration(aiMode)}
                disabled={isAiGenerating}
                className={`gap-2 ${
                  aiMode === "append"
                    ? "bg-purple-600 hover:bg-purple-700 text-white"
                    : "bg-amber-600 hover:bg-amber-700 text-white"
                }`}
              >
                {aiMode === "append" ? "Generate & Append 5 Questions" : "Yes, Overwrite All"}
              </Button>
            </DialogFooter>
          )}
        </DialogContent>
      </Dialog>
    </div>
  )
}
