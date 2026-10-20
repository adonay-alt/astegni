"use client"

import React, { useState, useEffect, useMemo } from "react"
import { apiClient } from "@/lib/api-client"
import { ExamYear, ExamQuestion, OptionKey } from "@/types/exam"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import {
  UploadCloud,
  FileSpreadsheet,
  FileCode,
  CheckCircle,
  AlertTriangle,
  CheckCircle2,
  Loader2,
} from "lucide-react"

const SAMPLE_CSV = `question_number,question_text,option_a,option_b,option_c,option_d,correct_answer,explanation
1,"What is the derivative of ln(3x^2 + 2) at x=1?","3/5","6/5","1/5","6","B","By chain rule: f'(x)=6x/(3x^2+2). At x=1, f'(1)=6/5."
2,"Calculate equivalent resistance for 6 and 12 ohm in parallel connected to 4 ohm series.","2 A","3 A","4 A","6 A","B","Rp = (6*12)/(6+12)=4. R_total = 4+4=8. I = 24/8 = 3 A."`

const SAMPLE_JSON = `[
  {
    "question_number": 1,
    "question_text": "What is the derivative of ln(3x^2 + 2) at x=1?",
    "options": {
      "A": "3/5",
      "B": "6/5",
      "C": "1/5",
      "D": "6"
    },
    "correct_answer": "B",
    "explanation": "By chain rule: f'(x)=6x/(3x^2+2). At x=1, f'(1)=6/5."
  },
  {
    "question_number": 2,
    "question_text": "Calculate equivalent resistance for 6 and 12 ohm in parallel connected to 4 ohm series.",
    "options": {
      "A": "2 A",
      "B": "3 A",
      "C": "4 A",
      "D": "6 A"
    },
    "correct_answer": "B",
    "explanation": "Rp = (6*12)/(6+12)=4. R_total = 4+4=8. I = 24/8 = 3 A."
  }
]`

export default function BulkImportPage() {
  // Category state: 1 = Grade 12 (EUEE), 2 = Grade 8 (Ministry)
  const [selectedExamId, setSelectedExamId] = useState<1 | 2>(1)
  const [examYears, setExamYears] = useState<ExamYear[]>([])
  const [selectedYearId, setSelectedYearId] = useState<number>(0)
  const [selectedSubjectId, setSelectedSubjectId] = useState<number>(0)
  const [isLoadingYears, setIsLoadingYears] = useState(true)

  const [parsedRecords, setParsedRecords] = useState<ExamQuestion[]>([])
  const [fileName, setFileName] = useState<string | null>(null)
  const [validationError, setValidationError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [successCount, setSuccessCount] = useState<number | null>(null)

  const selectedGrade = selectedExamId === 2 ? 8 : 12

  // Fetch real exam cohorts from backend when selectedExamId changes
  useEffect(() => {
    let isMounted = true
    setIsLoadingYears(true)

    const fetchCohorts = async () => {
      let loadedYears: ExamYear[] | null = null

      try {
        const res = await apiClient.get(`/admin/national-exams?grade=${selectedGrade}&exam_id=${selectedExamId}`)
        if (res.data?.status === "success" && Array.isArray(res.data?.data?.years)) {
          loadedYears = res.data.data.years
        }
      } catch {
        // Fallback to overview or local route if direct call fails
      }

      if (!loadedYears) {
        try {
          const ovRes = await apiClient.get(`/admin/national-exams/overview`, {
            params: { grade: selectedGrade, exam_id: selectedExamId },
          })
          if (ovRes.data?.data && Array.isArray(ovRes.data.data)) {
            // Build years structure from subjects overview
            const yearMap = new Map<number, any>()
            ovRes.data.data.forEach((sub: any) => {
              if (Array.isArray(sub.available_years)) {
                sub.available_years.forEach((yr: any) => {
                  if (!yearMap.has(yr.year_id || yr.id)) {
                    yearMap.set(yr.year_id || yr.id, {
                      id: yr.year_id || yr.id,
                      exam_id: selectedExamId,
                      grade_level: selectedGrade,
                      year: yr.year_ec || yr.year,
                      year_ec: yr.year_ec || yr.year,
                      year_gc: yr.year_gc || (yr.year_ec ? yr.year_ec + 8 : 2025),
                      is_published: true,
                      total_questions: 0,
                      subjects: [],
                    })
                  }
                  const yObj = yearMap.get(yr.year_id || yr.id)
                  yObj.total_questions += yr.questions_count || 0
                  yObj.subjects.push({
                    id: yr.nes_id || sub.id,
                    name: sub.name,
                    code: sub.code || sub.name.substring(0, 4).toUpperCase(),
                    stream: sub.stream || "common",
                    stream_id: sub.stream_id || null,
                    questions_count: yr.questions_count || 0,
                  })
                })
              }
            })
            loadedYears = Array.from(yearMap.values())
          }
        } catch {
          // Fallback to internal next API
        }
      }

      if (!loadedYears) {
        try {
          const localRes = await fetch(`/api/admin/national-exams?grade=${selectedGrade}&exam_id=${selectedExamId}`)
          if (localRes.ok) {
            const localData = await localRes.json()
            if (localData?.status === "success" && Array.isArray(localData?.data?.years)) {
              loadedYears = localData.data.years
            }
          }
        } catch (err) {
          console.error("Failed to fetch cohorts:", err)
        }
      }

      if (isMounted) {
        if (loadedYears && loadedYears.length > 0) {
          setExamYears(loadedYears)
          setSelectedYearId(loadedYears[0].id)
          const firstSubjects = loadedYears[0].subjects ?? []
          const withQ = firstSubjects.find((s: any) => (s.questions_count ?? 0) > 0)
          setSelectedSubjectId((withQ ?? firstSubjects[0])?.id ?? 0)
        } else {
          setExamYears([])
          setSelectedYearId(0)
          setSelectedSubjectId(0)
        }
        setIsLoadingYears(false)
      }
    }

    fetchCohorts()
    return () => {
      isMounted = false
    }
  }, [selectedExamId, selectedGrade])

  const activeYear = useMemo(() => {
    return examYears.find((y) => y.id === selectedYearId) || examYears[0] || null
  }, [examYears, selectedYearId])

  const availableSubjects = useMemo(() => {
    return activeYear?.subjects || []
  }, [activeYear])

  const activeSubject = useMemo(() => {
    return availableSubjects.find((s) => s.id === selectedSubjectId) || availableSubjects[0] || null
  }, [availableSubjects, selectedSubjectId])

  const downloadFile = (content: string, name: string, type: string) => {
    const blob = new Blob([content], { type })
    const url = URL.createObjectURL(blob)
    const a = document.createElement("a")
    a.href = url
    a.download = name
    a.click()
    URL.revokeObjectURL(url)
  }

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (!file) return

    setValidationError(null)
    setSuccessCount(null)
    setFileName(file.name)

    const reader = new FileReader()
    reader.onload = (event) => {
      try {
        const text = event.target?.result as string

        if (file.name.endsWith(".json")) {
          const raw = JSON.parse(text)
          if (!Array.isArray(raw)) {
            throw new Error("JSON payload must be an array of question objects.")
          }

          const validated: ExamQuestion[] = raw.map((item, index) => {
            if (!item.question_text && !item.question) {
              throw new Error(`Record #${index + 1} is missing question text.`)
            }

            let optA = ""
            let optB = ""
            let optC = ""
            let optD = ""

            if (Array.isArray(item.options)) {
              optA = item.options.find((o: any) => o?.key === "A")?.text || item.options[0]?.text || ""
              optB = item.options.find((o: any) => o?.key === "B")?.text || item.options[1]?.text || ""
              optC = item.options.find((o: any) => o?.key === "C")?.text || item.options[2]?.text || ""
              optD = item.options.find((o: any) => o?.key === "D")?.text || item.options[3]?.text || ""
            } else if (item.options && typeof item.options === "object") {
              optA = item.options.A || ""
              optB = item.options.B || ""
              optC = item.options.C || ""
              optD = item.options.D || ""
            } else {
              optA = item.option_a || ""
              optB = item.option_b || ""
              optC = item.option_c || ""
              optD = item.option_d || ""
            }

            let rawAns = String(item.correct_answer || item.answer || "A").toUpperCase().trim()
            if (rawAns.startsWith("OPTION_")) rawAns = rawAns.replace("OPTION_", "")
            const validKey = (["A", "B", "C", "D"].includes(rawAns) ? rawAns : "A") as OptionKey

            return {
              id: Date.now() + index,
              exam_year_id: selectedYearId,
              subject_id: selectedSubjectId,
              subject_name: activeSubject?.name || "Subject",
              stream: activeSubject?.stream || "common",
              question_number: item.question_number || index + 1,
              question_text: item.question_text || item.question || "",
              diagram_url: item.diagram_url || item.image_url || null,
              options: { A: optA, B: optB, C: optC, D: optD },
              correct_answer: validKey,
              explanation: item.explanation || "",
            }
          })
          setParsedRecords(validated)
        } else {
          // Parse CSV
          const lines = text.split(/\r?\n/).map((l) => l.trim()).filter(Boolean)
          if (lines.length <= 1) {
            throw new Error("CSV file does not contain question rows.")
          }

          const parseLine = (line: string): string[] => {
            const result: string[] = []
            let cur = ""
            let inQuotes = false
            for (let i = 0; i < line.length; i++) {
              const ch = line[i]
              if (ch === '"') {
                inQuotes = !inQuotes
              } else if (ch === "," && !inQuotes) {
                result.push(cur.trim().replace(/^"|"$/g, ""))
                cur = ""
              } else {
                cur += ch
              }
            }
            result.push(cur.trim().replace(/^"|"$/g, ""))
            return result
          }

          const headers = parseLine(lines[0]).map((h) => h.toLowerCase().replace(/[\s_]+/g, ""))
          const parsedCsv: ExamQuestion[] = []

          for (let i = 1; i < lines.length; i++) {
            const cols = parseLine(lines[i])
            if (cols.length < 5) continue

            const row: Record<string, string> = {}
            headers.forEach((h, idx) => {
              row[h] = cols[idx] || ""
            })

            const qNum = parseInt(row.questionnumber || String(i), 10) || i
            const qText = row.questiontext || row.question || ""
            const optA = row.optiona || row.a || ""
            const optB = row.optionb || row.b || ""
            const optC = row.optionc || row.c || ""
            const optD = row.optiond || row.d || ""

            let rawAns = (row.correctanswer || row.answer || "A").toUpperCase().trim()
            if (rawAns.startsWith("OPTION_")) rawAns = rawAns.replace("OPTION_", "")
            const validKey = (["A", "B", "C", "D"].includes(rawAns) ? rawAns : "A") as OptionKey
            const explanation = row.explanation || ""
            const diagram = row.diagramurl || row.imageurl || null

            if (!qText) continue

            parsedCsv.push({
              id: Date.now() + i,
              exam_year_id: selectedYearId,
              subject_id: selectedSubjectId,
              subject_name: activeSubject?.name || "Subject",
              stream: activeSubject?.stream || "common",
              question_number: qNum,
              question_text: qText,
              diagram_url: diagram,
              options: { A: optA, B: optB, C: optC, D: optD },
              correct_answer: validKey,
              explanation,
            })
          }
          setParsedRecords(parsedCsv)
        }
      } catch (err: unknown) {
        const msg = err instanceof Error ? err.message : "Failed to parse file."
        setValidationError(msg)
        setParsedRecords([])
      }
    }
    reader.readAsText(file)
  }

  const handleCommitImport = async () => {
    if (parsedRecords.length === 0 || !selectedYearId || !selectedSubjectId) return
    setIsSubmitting(true)
    setValidationError(null)

    const payload = {
      national_exam_id: selectedExamId,
      exam_year_id: selectedYearId,
      national_exam_year_id: selectedYearId,
      subject_id: selectedSubjectId,
      national_exam_subject_id: selectedSubjectId,
      questions: parsedRecords.map((q) => ({
        question_number: q.question_number,
        question_text: q.question_text,
        diagram_url: q.diagram_url,
        options: q.options,
        correct_answer: q.correct_answer,
        explanation: q.explanation,
      })),
    }

    try {
      let success = false
      let commitMessage = ""

      try {
        const res = await apiClient.post("/admin/national-exams/questions/batch-import", payload)
        if (res?.data?.status === "success") {
          success = true
          commitMessage = res.data.message || ""
        }
      } catch {
        // Fallback to internal route below
      }

      if (!success) {
        const localRes = await fetch("/api/admin/national-exams/questions/batch-import", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        })
        if (localRes.ok) {
          const localData = await localRes.json()
          if (localData?.status === "success") {
            success = true
            commitMessage = localData.message || ""
          }
        }
      }

      if (success) {
        setSuccessCount(parsedRecords.length)
        setParsedRecords([])
        setFileName(null)
      } else {
        throw new Error("Batch import failed to save to database.")
      }
    } catch (err: any) {
      console.error("Import commit error:", err)
      setValidationError(err.message || "Failed to commit questions to database.")
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="space-y-6">
      {/* Header - Cleaned up with no top-right link button as requested */}
      <div>
        <h1 className="text-2xl font-extrabold tracking-tight text-slate-900">
          Bulk Question Importer
        </h1>
        <p className="text-xs text-slate-500 mt-1">
          Batch ingest official entrance examination questions, options, and derivations directly into the live database.
        </p>
      </div>

      {/* Configuration & Target Parameters */}
      <Card className="border-slate-200 bg-white shadow-xs">
        <CardHeader className="pb-4">
          <CardTitle className="text-sm font-bold text-slate-900">
            Step 1: Set Target Assessment Placement
          </CardTitle>
          <CardDescription className="text-xs text-slate-500">
            Select the examination category, academic cohort year, and subject where questions will be saved.
          </CardDescription>
        </CardHeader>

        <CardContent>
          <div className="grid gap-4 sm:grid-cols-3">
            {/* Examination Category: Strictly Grade 12 (ID 1) and Grade 8 (ID 2) */}
            <div className="space-y-1.5">
              <label className="text-xs font-semibold text-slate-700">
                Examination Category
              </label>
              <select
                value={selectedExamId}
                onChange={(e) => {
                  const id = parseInt(e.target.value, 10) as 1 | 2
                  setSelectedExamId(id)
                }}
                className="w-full h-9 rounded-md border border-slate-300 bg-white px-2.5 text-xs text-slate-800 shadow-xs focus:border-blue-500 focus:outline-hidden"
              >
                <option value={1}>Ethiopian University Entrance Exam (EUEE - Grade 12)</option>
                <option value={2}>Ministry Regional Primary Exam (Grade 8)</option>
              </select>
            </div>

            {/* Target Year (E.C.): Cascading dropdown based on national_exam_id */}
            <div className="space-y-1.5">
              <label className="text-xs font-semibold text-slate-700">
                Target Year (E.C.)
              </label>
              <select
                value={selectedYearId}
                onChange={(e) => {
                  const id = parseInt(e.target.value, 10)
                  setSelectedYearId(id)
                  const yr = examYears.find((y) => y.id === id)
                  if (yr && yr.subjects && yr.subjects.length > 0) {
                    setSelectedSubjectId(yr.subjects[0].id)
                  }
                }}
                disabled={isLoadingYears || examYears.length === 0}
                className="w-full h-9 rounded-md border border-slate-300 bg-white px-2.5 text-xs text-slate-800 shadow-xs focus:border-blue-500 focus:outline-hidden disabled:bg-slate-100"
              >
                {examYears.length === 0 ? (
                  <option value={0}>No years found for this exam</option>
                ) : (
                  examYears.map((yr) => {
                    const ec = yr.year_ec || yr.year || 2016
                    const gc = yr.year_gc || (ec + 8)
                    const count = yr.total_questions || 0
                    return (
                      <option key={yr.id} value={yr.id}>
                        {ec} E.C. ({gc} G.C.) • {count} Questions
                      </option>
                    )
                  })
                )}
              </select>
            </div>

            {/* Subject: Cascading dropdown based on national_exam_year_id */}
            <div className="space-y-1.5">
              <label className="text-xs font-semibold text-slate-700">
                Subject
              </label>
              <select
                value={selectedSubjectId}
                onChange={(e) => setSelectedSubjectId(parseInt(e.target.value, 10))}
                disabled={isLoadingYears || availableSubjects.length === 0}
                className="w-full h-9 rounded-md border border-slate-300 bg-white px-2.5 text-xs text-slate-800 shadow-xs focus:border-blue-500 focus:outline-hidden disabled:bg-slate-100"
              >
                {availableSubjects.length === 0 ? (
                  <option value={0}>No subjects found for this year</option>
                ) : (
                  availableSubjects.map((sub) => (
                    <option key={sub.id} value={sub.id}>
                      {sub.name} ({sub.questions_count || 0} Qs)
                    </option>
                  ))
                )}
              </select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Dropzone & Templates */}
      <div className="grid gap-6 lg:grid-cols-3">
        {/* Upload Zone */}
        <div className="lg:col-span-2">
          <Card className="border-slate-200 bg-white shadow-xs">
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-bold text-slate-900">
                Step 2: Upload Data File (.CSV or .JSON)
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50/70 p-8 text-center hover:bg-slate-100/80 transition">
                <UploadCloud className="mx-auto h-10 w-10 text-blue-600 mb-3" />
                <p className="text-sm font-bold text-slate-900">
                  {fileName ? fileName : "Drag & drop question file here"}
                </p>
                <p className="text-xs text-slate-500 mt-1 mb-4">
                  Accepts formatted .csv or .json files with options and derivations
                </p>

                <label className="inline-flex cursor-pointer items-center rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-md shadow-blue-600/20 hover:bg-blue-500">
                  <span>Browse Local File...</span>
                  <input
                    type="file"
                    accept=".csv,.json"
                    onChange={handleFileUpload}
                    className="sr-only"
                  />
                </label>
              </div>

              {validationError && (
                <div className="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-800">
                  <AlertTriangle className="h-4 w-4 shrink-0 text-red-600" />
                  <span>Validation error: {validationError}</span>
                </div>
              )}

              {successCount !== null && (
                <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-900">
                  <CheckCircle2 className="h-4 w-4 shrink-0 text-emerald-600" />
                  <span>
                    Successfully committed {successCount} questions into {selectedExamId === 2 ? "Grade 8" : "Grade 12"} {activeYear?.year_ec || activeYear?.year} E.C. {activeSubject?.name} bank!
                  </span>
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Templates Card */}
        <div>
          <Card className="border-slate-200 bg-white shadow-xs">
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-bold text-slate-900">
                Standard Import Templates
              </CardTitle>
              <CardDescription className="text-xs text-slate-500">
                Download headers and sample schema files
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              <Button
                variant="outline"
                size="sm"
                onClick={() =>
                  downloadFile(
                    SAMPLE_CSV,
                    "asteghi_exam_questions_template.csv",
                    "text/csv"
                  )
                }
                className="w-full justify-start text-xs border-slate-300 text-slate-700 bg-white hover:bg-slate-50"
              >
                <FileSpreadsheet className="mr-2 h-4 w-4 text-emerald-600" />
                Download CSV Template (.csv)
              </Button>

              <Button
                variant="outline"
                size="sm"
                onClick={() =>
                  downloadFile(
                    SAMPLE_JSON,
                    "asteghi_exam_questions_template.json",
                    "application/json"
                  )
                }
                className="w-full justify-start text-xs border-slate-300 text-slate-700 bg-white hover:bg-slate-50"
              >
                <FileCode className="mr-2 h-4 w-4 text-blue-600" />
                Download JSON Template (.json)
              </Button>

              <div className="rounded-xl bg-slate-50 p-3 text-[11px] text-slate-600 border border-slate-200 space-y-1">
                <span className="font-bold text-slate-900">
                  Required Format Fields:
                </span>
                <ul className="list-disc list-inside space-y-0.5 pt-0.5 text-slate-600">
                  <li><code>question_number</code> (1, 2, 3...)</li>
                  <li><code>question_text</code> (Problem statement)</li>
                  <li><code>option_a</code> through <code>option_d</code></li>
                  <li><code>correct_answer</code> (&quot;A&quot;, &quot;B&quot;, &quot;C&quot;, &quot;D&quot;)</li>
                  <li><code>explanation</code> (Derivation formulas)</li>
                </ul>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Batch Preview Table (When records parsed) */}
      {parsedRecords.length > 0 && (
        <Card className="border-slate-200 bg-white shadow-xs">
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle className="text-base font-bold text-slate-900">
                  Step 3: Preview Parsed Batch ({parsedRecords.length} Questions)
                </CardTitle>
                <CardDescription className="text-xs text-slate-500">
                  Target: {selectedExamId === 2 ? "Grade 8" : "Grade 12"} • {activeYear?.year_ec || activeYear?.year} E.C. • {activeSubject?.name}
                </CardDescription>
              </div>
              <Button
                onClick={handleCommitImport}
                disabled={isSubmitting}
                className="bg-emerald-600 font-semibold text-white hover:bg-emerald-700 shadow-md shadow-emerald-600/20"
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Committing to Database...
                  </>
                ) : (
                  <>
                    <CheckCircle className="mr-2 h-4 w-4" />
                    Commit {parsedRecords.length} Questions
                  </>
                )}
              </Button>
            </div>
          </CardHeader>

          <CardContent>
            <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
              <table className="w-full text-left text-xs text-slate-800">
                <thead className="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500">
                  <tr>
                    <th className="p-3 w-12 text-center">Q#</th>
                    <th className="p-3">Question Text</th>
                    <th className="p-3">Options Key</th>
                    <th className="p-3">Correct</th>
                    <th className="p-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {parsedRecords.slice(0, 5).map((rec, i) => (
                    <tr key={i} className="hover:bg-slate-50">
                      <td className="p-3 text-center font-bold font-mono text-slate-700">{rec.question_number}</td>
                      <td className="p-3 max-w-md font-medium text-slate-900 line-clamp-1">
                        {rec.question_text}
                      </td>
                      <td className="p-3">
                        <span className="text-[11px] text-slate-500">
                          {Array.isArray(rec.options)
                            ? (rec.options as any[]).map((o) => o.key).join(", ")
                            : Object.keys(rec.options || {}).join(", ")}
                        </span>
                      </td>
                      <td className="p-3">
                        <Badge className="bg-emerald-600 text-white text-[10px] font-bold">
                          Option {rec.correct_answer}
                        </Badge>
                      </td>
                      <td className="p-3">
                        <span className="text-emerald-700 font-semibold text-[11px] flex items-center gap-1">
                          <CheckCircle2 className="h-3 w-3" /> Validated
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {parsedRecords.length > 5 && (
              <p className="text-[11px] text-slate-500 text-center pt-2">
                ...and {parsedRecords.length - 5} more valid questions ready for import.
              </p>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  )
}
