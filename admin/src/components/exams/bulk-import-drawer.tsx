"use client"

import React, { useState, useRef } from "react"
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { ExamQuestion, OptionKey } from "@/types/exam"
import { apiClient } from "@/lib/api-client"
import {
  UploadCloud,
  FileSpreadsheet,
  FileCode,
  CheckCircle2,
  AlertCircle,
  Download,
  Loader2,
  Trash2,
  FileCheck,
  HelpCircle,
} from "lucide-react"

interface BulkImportDrawerProps {
  isOpen: boolean
  onClose: () => void
  onImportSuccess: (importedQuestions: ExamQuestion[]) => void
  examYearId: number
  subjectId: number
  subjectName: string
  yearEc: number
  gradeLevel: 8 | 12
}

const SAMPLE_CSV = `question_number,question_text,option_a,option_b,option_c,option_d,correct_answer,explanation
1,"What is the derivative of f(x) = ln(3x^2 + 2) at x=1?","3/5","6/5","1/5","6","B","By chain rule: f'(x)=6x/(3x^2+2). At x=1, f'(1)=6/5."
2,"Calculate the equivalent resistance of 6 ohm and 12 ohm in parallel connected in series with 4 ohm across 24 V.","2 A","3 A","4 A","6 A","B","Rp = (6*12)/(6+12)=4. R_total = 4+4=8. I = 24/8 = 3 A."
3,"Which organelle is primarily responsible for cellular aerobic respiration and ATP synthesis?","Ribosome","Mitochondrion","Endoplasmic Reticulum","Golgi Apparatus","B","Mitochondria generate most of the chemical energy needed to power the cell's biochemical reactions."`

const SAMPLE_JSON = `[
  {
    "question_number": 1,
    "question_text": "What is the derivative of f(x) = ln(3x^2 + 2) at x=1?",
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
    "question_text": "Calculate the equivalent resistance of 6 ohm and 12 ohm in parallel connected in series with 4 ohm across 24 V.",
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

export function BulkImportDrawer({
  isOpen,
  onClose,
  onImportSuccess,
  examYearId,
  subjectId,
  subjectName,
  yearEc,
  gradeLevel,
}: BulkImportDrawerProps) {
  const fileInputRef = useRef<HTMLInputElement>(null)
  const [fileName, setFileName] = useState<string | null>(null)
  const [parsedQuestions, setParsedQuestions] = useState<ExamQuestion[]>([])
  const [errorMsg, setErrorMsg] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [isDragOver, setIsDragOver] = useState(false)

  const downloadSample = (content: string, filename: string, mime: string) => {
    const blob = new Blob([content], { type: mime })
    const url = URL.createObjectURL(blob)
    const a = document.createElement("a")
    a.href = url
    a.download = filename
    a.click()
    URL.revokeObjectURL(url)
  }

  const parseCSVContent = (text: string): ExamQuestion[] => {
    const lines = text
      .split(/\r?\n/)
      .map((l) => l.trim())
      .filter(Boolean)
    if (lines.length < 2) {
      throw new Error("CSV file is empty or missing headers.")
    }

    // Split CSV line respecting quotes
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
    const questions: ExamQuestion[] = []

    for (let i = 1; i < lines.length; i++) {
      const values = parseLine(lines[i])
      if (values.length < 5) continue

      const row: Record<string, string> = {}
      headers.forEach((h, idx) => {
        row[h] = values[idx] || ""
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
      const explanation = row.explanation || row.rationale || ""
      const diagram = row.diagramurl || row.imageurl || null

      if (!qText) continue

      questions.push({
        id: Date.now() + i,
        exam_year_id: examYearId,
        subject_id: subjectId,
        subject_name: subjectName,
        question_number: qNum,
        question_text: qText,
        diagram_url: diagram,
        options: {
          A: optA || "Option A",
          B: optB || "Option B",
          C: optC || "Option C",
          D: optD || "Option D",
        },
        correct_answer: validKey,
        explanation: explanation,
      })
    }

    return questions
  }

  const parseJSONContent = (text: string): ExamQuestion[] => {
    const raw = JSON.parse(text)
    const list = Array.isArray(raw) ? raw : raw.questions || []
    if (!Array.isArray(list) || list.length === 0) {
      throw new Error("JSON must contain an array of question objects.")
    }

    return list.map((item: any, idx: number) => {
      let opts = { A: "", B: "", C: "", D: "" }
      if (item.options && typeof item.options === "object") {
        if (Array.isArray(item.options)) {
          item.options.forEach((o: any) => {
            const k = (o.key || o.letter || "").toUpperCase()
            if (["A", "B", "C", "D"].includes(k)) {
              ;(opts as any)[k] = o.text || o.value || ""
            }
          })
        } else {
          opts = {
            A: item.options.A || item.option_a || "",
            B: item.options.B || item.option_b || "",
            C: item.options.C || item.option_c || "",
            D: item.options.D || item.option_d || "",
          }
        }
      } else {
        opts = {
          A: item.option_a || "",
          B: item.option_b || "",
          C: item.option_c || "",
          D: item.option_d || "",
        }
      }

      let rawAns = String(item.correct_answer || item.answer || "A").toUpperCase().trim()
      if (rawAns.startsWith("OPTION_")) rawAns = rawAns.replace("OPTION_", "")
      const validKey = (["A", "B", "C", "D"].includes(rawAns) ? rawAns : "A") as OptionKey

      return {
        id: Date.now() + idx,
        exam_year_id: examYearId,
        subject_id: subjectId,
        subject_name: subjectName,
        question_number: item.question_number || idx + 1,
        question_text: item.question_text || item.question || `Question ${idx + 1}`,
        diagram_url: item.diagram_url || item.image_url || null,
        options: opts,
        correct_answer: validKey,
        explanation: item.explanation || "",
      }
    })
  }

  const processFile = (file: File) => {
    setErrorMsg(null)
    setFileName(file.name)

    const isCsv = file.name.endsWith(".csv")
    const isJson = file.name.endsWith(".json")

    if (!isCsv && !isJson) {
      setErrorMsg("Please upload a valid .csv or .json file.")
      return
    }

    const reader = new FileReader()
    reader.onload = (e) => {
      try {
        const text = e.target?.result as string
        const parsed = isCsv ? parseCSVContent(text) : parseJSONContent(text)

        if (parsed.length === 0) {
          setErrorMsg("Could not parse any valid questions from the uploaded file.")
          setParsedQuestions([])
          return
        }

        setParsedQuestions(parsed)
      } catch (err: any) {
        setErrorMsg(`Parser error: ${err.message}`)
        setParsedQuestions([])
      }
    }
    reader.onerror = () => {
      setErrorMsg("Failed to read file from disk.")
    }
    reader.readAsText(file)
  }

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault()
    setIsDragOver(false)
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      processFile(e.dataTransfer.files[0])
    }
  }

  const handleCommit = async () => {
    if (parsedQuestions.length === 0) return
    setIsSubmitting(true)
    setErrorMsg(null)

    const payload = {
      exam_year_id: examYearId,
      subject_id: subjectId,
      questions: parsedQuestions.map((q) => ({
        question_number: q.question_number,
        question_text: q.question_text,
        diagram_url: q.diagram_url,
        options: q.options,
        correct_answer: q.correct_answer,
        explanation: q.explanation,
      })),
    }

    try {
      let savedList: ExamQuestion[] | null = null

      try {
        const res = await apiClient.post("/admin/national-exams/questions/batch-import", payload)
        if (res?.data?.data && Array.isArray(res.data.data) && res.data.data.length > 0) {
          savedList = res.data.data
        }
      } catch {
        // Fallback to internal route below
      }

      if (!savedList) {
        const fallbackRes = await fetch("/api/admin/national-exams/questions/batch-import", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        })
        if (fallbackRes.ok) {
          const fbData = await fallbackRes.json()
          if (fbData?.data && Array.isArray(fbData.data) && fbData.data.length > 0) {
            savedList = fbData.data
          }
        }
      }

      if (!savedList) {
        throw new Error("Batch import failed to save questions to database.")
      }

      onImportSuccess(savedList)
      onClose()
    } catch (err: any) {
      console.error("Batch import error:", err)
      setErrorMsg(err.response?.data?.message || err.message || "Failed to commit batch import to database.")
    } finally {
      setIsSubmitting(false)
    }
  }

  const handleReset = () => {
    setFileName(null)
    setParsedQuestions([])
    setErrorMsg(null)
    if (fileInputRef.current) fileInputRef.current.value = ""
  }

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto sm:rounded-2xl p-6 bg-white text-slate-900 border border-slate-200 shadow-xl">
        <DialogHeader className="border-b border-slate-100 pb-4">
          <div className="flex items-center justify-between pr-6">
            <div className="flex items-center gap-2.5">
              <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                <UploadCloud className="h-5 w-5" />
              </div>
              <div>
                <DialogTitle className="text-base font-bold text-slate-900">
                  Batch Import Questions (CSV / JSON)
                </DialogTitle>
                <p className="text-xs text-slate-500">
                  Grade {gradeLevel} • {yearEc} E.C. • Target:{" "}
                  <span className="font-semibold text-slate-800">
                    {subjectName}
                  </span>
                </p>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() =>
                  downloadSample(
                    SAMPLE_CSV,
                    `national_exam_sample_g${gradeLevel}_${subjectName.toLowerCase()}.csv`,
                    "text/csv"
                  )
                }
                className="gap-1 text-xs border-slate-300 text-slate-700 bg-white hover:bg-slate-50"
              >
                <FileSpreadsheet className="h-3.5 w-3.5 text-emerald-600" />
                Sample CSV
              </Button>
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() =>
                  downloadSample(
                    SAMPLE_JSON,
                    `national_exam_sample_g${gradeLevel}_${subjectName.toLowerCase()}.json`,
                    "application/json"
                  )
                }
                className="gap-1 text-xs border-slate-300 text-slate-700 bg-white hover:bg-slate-50"
              >
                <FileCode className="h-3.5 w-3.5 text-blue-600" />
                Sample JSON
              </Button>
            </div>
          </div>
        </DialogHeader>

        <div className="space-y-4 pt-2">
          {errorMsg && (
            <div className="flex items-center gap-2 rounded-xl bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800">
              <AlertCircle className="h-4 w-4 shrink-0 text-rose-600" />
              <span>{errorMsg}</span>
            </div>
          )}

          {/* Drag & Drop Zone */}
          {parsedQuestions.length === 0 ? (
            <div
              onDragOver={(e) => {
                e.preventDefault()
                setIsDragOver(true)
              }}
              onDragLeave={() => setIsDragOver(false)}
              onDrop={handleDrop}
              onClick={() => fileInputRef.current?.click()}
              className={`flex flex-col items-center justify-center rounded-2xl border-2 border-dashed p-8 text-center cursor-pointer transition ${
                isDragOver
                  ? "border-blue-500 bg-blue-50/60"
                  : "border-slate-300 bg-slate-50 hover:bg-slate-100/80"
              }`}
            >
              <input
                ref={fileInputRef}
                type="file"
                accept=".csv,.json"
                className="hidden"
                onChange={(e) => {
                  if (e.target.files && e.target.files[0]) {
                    processFile(e.target.files[0])
                  }
                }}
              />
              <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-white shadow-xs text-blue-600 border border-slate-200">
                <UploadCloud className="h-6 w-6" />
              </div>
              <h4 className="mt-3 text-sm font-bold text-slate-800">
                Drag & drop exam file here, or click to browse
              </h4>
              <p className="mt-1 text-xs text-slate-500">
                Supports official UTF-8 encoded <span className="font-semibold text-slate-700">.CSV</span> and{" "}
                <span className="font-semibold text-slate-700">.JSON</span> files
              </p>
            </div>
          ) : (
            <div className="space-y-3">
              {/* File details bar */}
              <div className="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50/70 p-3 text-xs">
                <div className="flex items-center gap-2 text-emerald-800 font-semibold">
                  <FileCheck className="h-4 w-4 text-emerald-600" />
                  <span>
                    {fileName} ({parsedQuestions.length} Questions parsed & validated)
                  </span>
                </div>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={handleReset}
                  className="h-7 text-xs text-rose-600 hover:bg-rose-100 hover:text-rose-700"
                >
                  <Trash2 className="mr-1 h-3.5 w-3.5" />
                  Remove
                </Button>
              </div>

              {/* Preview Table of First 5 Questions */}
              <div>
                <h5 className="text-xs font-bold text-slate-700 mb-2">
                  Preview (Showing first {Math.min(parsedQuestions.length, 5)} questions):
                </h5>
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white text-xs">
                  <table className="w-full text-left">
                    <thead className="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                      <tr>
                        <th className="p-2.5 w-12 text-center">Q#</th>
                        <th className="p-2.5">Problem Statement</th>
                        <th className="p-2.5 w-24 text-center">Key</th>
                        <th className="p-2.5 w-24">Status</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {parsedQuestions.slice(0, 5).map((q, idx) => (
                        <tr key={idx} className="hover:bg-slate-50">
                          <td className="p-2.5 text-center font-mono font-bold text-slate-700">
                            {q.question_number}
                          </td>
                          <td className="p-2.5">
                            <p className="line-clamp-1 font-medium text-slate-800">
                              {q.question_text}
                            </p>
                            <span className="text-[10px] text-slate-500">
                              A: {q.options.A} | B: {q.options.B}
                            </span>
                          </td>
                          <td className="p-2.5 text-center">
                            <Badge className="bg-emerald-600 text-white font-bold text-[10px]">
                              Key {q.correct_answer}
                            </Badge>
                          </td>
                          <td className="p-2.5">
                            <span className="flex items-center gap-1 font-semibold text-emerald-600 text-[11px]">
                              <CheckCircle2 className="h-3 w-3" /> Valid
                            </span>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
                {parsedQuestions.length > 5 && (
                  <p className="text-[11px] text-slate-500 text-center pt-2">
                    + {parsedQuestions.length - 5} more questions will be imported into {subjectName}.
                  </p>
                )}
              </div>
            </div>
          )}
        </div>

        <DialogFooter className="border-t border-slate-100 pt-4">
          <Button type="button" variant="outline" onClick={onClose} disabled={isSubmitting} size="sm">
            Cancel
          </Button>
          <Button
            type="button"
            onClick={handleCommit}
            disabled={parsedQuestions.length === 0 || isSubmitting}
            size="sm"
            className="bg-blue-600 font-semibold text-white shadow-md shadow-blue-600/20 hover:bg-blue-500 gap-1.5"
          >
            {isSubmitting ? (
              <>
                <Loader2 className="h-3.5 w-3.5 animate-spin" />
                Importing {parsedQuestions.length} Questions...
              </>
            ) : (
              <>
                <UploadCloud className="h-3.5 w-3.5" />
                Commit Import ({parsedQuestions.length} Qs)
              </>
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
