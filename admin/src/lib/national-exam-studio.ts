import { apiClient } from "@/lib/api-client"
import {
  ExamProgram,
  NationalExamSubjectOverview,
  StreamSlug,
  SubjectAvailableYear,
} from "@/types/exam"

export interface NationalExam {
  id: number;
  grade_id: number;
  title: string;
}

export interface NationalExamSubject {
  id: number;
  national_exam_id: number;
  national_exam_year_id: number;
  grade_id: number;
  stream_id: number | null; // null = Common Core, 1 = Natural, 2 = Social
  name: string;
  description?: string;
  stream?: { id: number; name: string } | null;
}

export const filterSubjectsByStream = (
  subjects: NationalExamSubjectOverview[],
  userStreamId?: number | null
): NationalExamSubjectOverview[] => {
  return subjects.filter((subject) => {
    // Common Core subjects (stream_id === null or undefined) are visible to everyone
    if (subject.stream_id === null || subject.stream_id === undefined) {
      return true;
    }
    // Otherwise, subject must match the user's stream (1: Natural, 2: Social)
    return subject.stream_id === userStreamId;
  });
};

export function filledYear(value: unknown): number | null {
  const n = Number(value)
  return Number.isFinite(n) && n > 0 ? n : null
}

export function resolveCalendarYears(row: {
  year?: unknown
  year_ec?: unknown
  year_gc?: unknown
}): { year_ec: number; year_gc: number } {
  const yearEcCol = filledYear(row.year_ec)
  const yearGcCol = filledYear(row.year_gc)
  const stored = filledYear(row.year)

  if (yearEcCol && yearGcCol) return { year_ec: yearEcCol, year_gc: yearGcCol }
  if (yearEcCol) return { year_ec: yearEcCol, year_gc: yearEcCol + 8 }
  if (yearGcCol) return { year_ec: yearGcCol - 8, year_gc: yearGcCol }
  if (stored) {
    if (stored > 2020) return { year_ec: stored - 8, year_gc: stored }
    return { year_ec: stored, year_gc: stored + 8 }
  }
  return { year_ec: 2016, year_gc: 2024 }
}

export function inferStream(name: string, streamId?: number | null): StreamSlug {
  if (streamId === 1) return "natural"
  if (streamId === 2) return "social"
  const lower = name.toLowerCase()
  if (/physics|chemistry|biology|agriculture|information\s*tech|\bit\b/.test(lower)) {
    return "natural"
  }
  if (/economics|geography|history/.test(lower)) {
    return "social"
  }
  return "common"
}

export function streamLabel(slug: StreamSlug): string {
  if (slug === "natural") return "Natural Science"
  if (slug === "social") return "Social Science"
  return "Common Core"
}

export function shortExamTitle(title: string): string {
  const t = title.toLowerCase()
  if (t.includes("matric")) return "Matric"
  if (t.includes("esslce") && t.includes("euee")) return "EUEE/ESSLCE"
  if (t.includes("esslce")) return "ESSLCE"
  if (t.includes("euee") || t.includes("entrance")) return "EUEE"
  if (t.includes("primary") || t.includes("grade 8")) return "Grade 8"
  return title.slice(0, 18) || "Exam"
}

export function examMatchesGrade(exam: any, grade: 8 | 12): boolean {
  const blob = `${exam?.title ?? ""} ${exam?.name ?? ""} ${exam?.grade?.name ?? ""} ${exam?.grade_name ?? ""}`.toLowerCase()
  if (grade === 8) {
    return /\b8\b|primary|ministry/.test(blob) && !/\beuee\b|\besslce\b|\bmatric\b|\b12\b/.test(blob)
  }
  return /\b12\b|euee|esslce|matric|entrance/.test(blob)
}

export function paperKey(year: Pick<SubjectAvailableYear, "nes_id" | "subject_id">): number {
  return Number(year.nes_id || year.subject_id || 0)
}

function unwrapList(payload: any): any[] {
  if (Array.isArray(payload?.data?.data)) return payload.data.data
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload)) return payload
  return []
}

function collapseYears(years: SubjectAvailableYear[]): SubjectAvailableYear[] {
  const grouped = new Map<string, SubjectAvailableYear[]>()
  years.forEach((year) => {
    const key = `${year.exam_id ?? 0}:${year.year_ec}`
    const bucket = grouped.get(key) ?? []
    bucket.push(year)
    grouped.set(key, bucket)
  })
  return Array.from(grouped.values())
    .map((bucket) => [...bucket].sort((a, b) => b.questions_count - a.questions_count)[0])
    .sort((a, b) => b.year_ec - a.year_ec || b.questions_count - a.questions_count)
}

function toProgram(exam: any): ExamProgram {
  const title = String(exam?.title ?? exam?.name ?? "Exam")
  return { id: Number(exam.id), title, short: shortExamTitle(title) }
}

export function mergePrograms(primary: ExamProgram[], extra: ExamProgram[]): ExamProgram[] {
  const map = new Map<number, ExamProgram>()
  ;[...primary, ...extra].forEach((program) => {
    if (!map.has(program.id)) {
      map.set(program.id, {
        ...program,
        short: program.short || shortExamTitle(program.title),
      })
    }
  })
  return Array.from(map.values())
}

export function mergeSubjectOverviews(
  primary: NationalExamSubjectOverview[],
  extra: NationalExamSubjectOverview[]
): NationalExamSubjectOverview[] {
  const map = new Map<string, NationalExamSubjectOverview>()

  const ingest = (subject: NationalExamSubjectOverview) => {
    const key = subject.name.trim().toLowerCase()
    const existing = map.get(key)
    if (!existing) {
      map.set(key, {
        ...subject,
        available_years: collapseYears([...(subject.available_years || [])]),
      })
      return
    }
    const mergedYears = collapseYears([
      ...(existing.available_years || []),
      ...(subject.available_years || []),
    ])
    const byNes = new Map<number, SubjectAvailableYear>()
    mergedYears.forEach((year) => {
      const nes = paperKey(year)
      const prev = byNes.get(nes)
      if (!prev || year.questions_count > prev.questions_count) {
        byNes.set(nes, year)
      }
    })
    const availableYears = collapseYears(Array.from(byNes.values()))
    map.set(key, {
      ...existing,
      stream: existing.stream === "common" ? subject.stream : existing.stream,
      stream_id: existing.stream_id ?? subject.stream_id,
      stream_name: existing.stream_name ?? subject.stream_name,
      available_years: availableYears,
      total_years: availableYears.length,
      total_questions: availableYears.reduce((sum, year) => sum + (year.questions_count || 0), 0),
    })
  }

  primary.forEach(ingest)
  extra.forEach(ingest)

  return Array.from(map.values()).sort((a, b) => b.total_questions - a.total_questions)
}

export async function fetchStudentCatalog(grade: 8 | 12): Promise<{
  programs: ExamProgram[]
  subjects: NationalExamSubjectOverview[]
}> {
  let exams: any[] = []
  try {
    const examsRes = await apiClient.get("/national-exams")
    exams = unwrapList(examsRes?.data).filter((exam) => examMatchesGrade(exam, grade))
  } catch (err) {
    console.warn("fetchStudentCatalog: failed to fetch /national-exams, falling back gracefully", err)
    exams = [
      grade === 12
        ? { id: 1, grade_id: 3, title: "Ethiopian University Entrance Exam (EUEE - Grade 12)" }
        : { id: 2, grade_id: 5, title: "Ministry Regional Primary Exam (Grade 8)" },
    ]
  }

  // If no exams matched grade from API response, fallback to default official exam
  if (exams.length === 0) {
    exams = [
      grade === 12
        ? { id: 1, grade_id: 3, title: "Ethiopian University Entrance Exam (EUEE - Grade 12)" }
        : { id: 2, grade_id: 5, title: "Ministry Regional Primary Exam (Grade 8)" },
    ]
  }

  const programs = exams.map(toProgram)

  const yearBundles = await Promise.all(
    exams.map(async (exam) => {
      try {
        const yearsRes = await apiClient.get(`/national-exams/${exam.id}/years`)
        const years = unwrapList(yearsRes?.data)
        return { exam, years }
      } catch (err) {
        console.warn(`fetchStudentCatalog: failed to fetch years for exam ${exam.id}`, err)
        return { exam, years: [] }
      }
    })
  )

  const subjectRows = await Promise.all(
    yearBundles.flatMap(({ exam, years }) =>
      years.map(async (year: any) => {
        try {
          const subjectsRes = await apiClient.get(`/national-exam-years/${year.id}/subjects`)
          return unwrapList(subjectsRes?.data).map((subject: any) => ({ exam, year, subject }))
        } catch {
          return []
        }
      })
    )
  )

  const subjectMap = new Map<string, NationalExamSubjectOverview>()
  subjectRows.flat().forEach(({ exam, year, subject }) => {
    const name = String(subject.name ?? subject.title ?? "Subject")
    const key = name.trim().toLowerCase()
    const rawStreamId = subject.stream_id ?? subject.stream?.id ?? null
    // Stream Filter Rule:
    // Grade 8 is strictly Common Core (stream_id = null)
    // Grade 12: stream_id == 1 is Natural, stream_id == 2 is Social, stream_id == null is Common Core
    const streamId = grade === 8 ? null : (rawStreamId == null ? null : Number(rawStreamId))
    const stream: StreamSlug = grade === 8
      ? "common"
      : streamId === 1
      ? "natural"
      : streamId === 2
      ? "social"
      : inferStream(name, null)
    const examTitle = String(exam.title ?? exam.name ?? "")
    const calendar = resolveCalendarYears(year)
    const count = Number(subject.questions_count ?? subject.questions?.length ?? 0)
    const paper: SubjectAvailableYear = {
      id: Number(year.id),
      year_id: Number(year.id),
      nes_id: Number(subject.id),
      subject_id: Number(subject.id),
      exam_id: Number(exam.id),
      exam_title: examTitle,
      exam_short: shortExamTitle(examTitle),
      year_ec: calendar.year_ec,
      year_gc: calendar.year_gc,
      questions_count: count,
      is_published: year.is_published !== false,
    }

    if (!subjectMap.has(key)) {
      subjectMap.set(key, {
        id: Number(subject.id),
        name,
        code: name.substring(0, 4).toUpperCase(),
        stream,
        stream_id: streamId,
        stream_name: grade === 8 ? "Common Core" : streamLabel(stream),
        total_years: 0,
        total_questions: 0,
        available_years: [],
      })
    }
    subjectMap.get(key)!.available_years.push(paper)
  })

  const subjects = Array.from(subjectMap.values()).map((subject) => {
    const availableYears = collapseYears(subject.available_years)
    return {
      ...subject,
      available_years: availableYears,
      total_years: availableYears.length,
      total_questions: availableYears.reduce((sum, year) => sum + (year.questions_count || 0), 0),
    }
  })

  return { programs, subjects }
}

export function filterByProgram(
  subjects: NationalExamSubjectOverview[],
  examId: number | "all"
): NationalExamSubjectOverview[] {
  if (examId === "all") return subjects
  return subjects
    .map((subject) => {
      const availableYears = subject.available_years.filter((year) => year.exam_id === examId)
      return {
        ...subject,
        available_years: availableYears,
        total_years: availableYears.length,
        total_questions: availableYears.reduce((sum, year) => sum + (year.questions_count || 0), 0),
      }
    })
    .filter((subject) => subject.available_years.length > 0)
}

export async function hydratePaperCounts(
  subjects: NationalExamSubjectOverview[]
): Promise<NationalExamSubjectOverview[]> {
  const papers = subjects.flatMap((subject) => subject.available_years)
  const missing = papers.filter((year) => (year.questions_count || 0) === 0)
  if (missing.length === 0) return subjects

  const counted = await Promise.all(
    missing.map(async (year) => {
      const nesId = paperKey(year)
      try {
        const res = await apiClient.get(
          `/admin/national-exams/years/${year.id}/subjects/${nesId}/questions`,
          { params: { nes_id: nesId, count_only: 1 } }
        )
        const count = Number(res.data?.count ?? res.data?.data?.length ?? 0)
        return { nesId, count }
      } catch {
        return { nesId, count: 0 }
      }
    })
  )

  const countByNes = new Map(counted.map((row) => [row.nesId, row.count]))
  return subjects.map((subject) => {
    const availableYears = subject.available_years.map((year) => {
      const nesId = paperKey(year)
      const nextCount = countByNes.get(nesId)
      if (nextCount == null || nextCount <= (year.questions_count || 0)) return year
      return { ...year, questions_count: nextCount }
    })
    return {
      ...subject,
      available_years: availableYears,
      total_years: availableYears.length,
      total_questions: availableYears.reduce((sum, year) => sum + (year.questions_count || 0), 0),
    }
  })
}

export function normalizeOverviewPayload(payload: any): {
  subjects: NationalExamSubjectOverview[]
  programs: ExamProgram[]
} {
  const subjects: NationalExamSubjectOverview[] = Array.isArray(payload?.data) ? payload.data : []
  const programs: ExamProgram[] = Array.isArray(payload?.exams)
    ? payload.exams.map((exam: any) => ({
        id: Number(exam.id),
        title: String(exam.title ?? exam.name ?? "Exam"),
        short: exam.short || shortExamTitle(String(exam.title ?? exam.name ?? "")),
      }))
    : []
  return { subjects, programs }
}
