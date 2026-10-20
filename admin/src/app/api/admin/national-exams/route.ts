import { NextResponse } from "next/server"
import { execFile } from "child_process"
import { promisify } from "util"

const execFileAsync = promisify(execFile)

export async function GET(request: Request) {
  const { searchParams } = new URL(request.url)
  const gradeParam = searchParams.get("grade") || searchParams.get("grade_level") || "12"
  const gradeLevel = parseInt(gradeParam, 10) === 8 ? 8 : 12
  const gradeId = gradeLevel === 12 ? 5 : 1

  const mysqlBin = "c:\\xampp\\mysql\\bin\\mysql.exe"

  try {
    const query = `
      SELECT
        ne.id as exam_id,
        ne.title as exam_title,
        ney.id as year_id,
        ney.year,
        COALESCE(ney.year_ec, ney.year) as year_ec,
        COALESCE(ney.year_gc, ney.year + 8) as year_gc,
        nes.id as subject_id,
        nes.name as subject_name,
        nes.stream_id,
        COUNT(neq.id) as questions_count
      FROM national_exams ne
      JOIN national_exam_years ney ON ney.national_exam_id = ne.id
      JOIN national_exam_subjects nes ON nes.national_exam_year_id = ney.id
      LEFT JOIN national_exam_questions neq ON neq.national_exam_subject_id = nes.id
      WHERE ne.grade_id = ${gradeId}
      GROUP BY ne.id, ne.title, ney.id, ney.year, ney.year_ec, ney.year_gc, nes.id, nes.name, nes.stream_id
      ORDER BY ney.year DESC, nes.id ASC;
    `

    const { stdout } = await execFileAsync(mysqlBin, [
      "-u",
      "root",
      "-e",
      `USE asteghi_admin_dev; ${query}`,
    ])

    const lines = stdout.trim().split("\n")
    if (lines.length <= 1) {
      return NextResponse.json({
        status: "success",
        data: {
          exam: {
            id: gradeLevel === 12 ? 1 : 2,
            grade_level: gradeLevel,
            title:
              gradeLevel === 12
                ? "Ethiopian University Entrance Examination (EUEE)"
                : "Ministry Regional Primary Exam (Grade 8)",
          },
          years: [],
          stats: { total_years: 0, total_subjects: 0, total_questions: 0 },
        },
      })
    }

    let examId = gradeLevel === 12 ? 1 : 2
    let examTitle =
      gradeLevel === 12
        ? "Ethiopian University Entrance Examination (EUEE)"
        : "Ministry Regional Primary Exam (Grade 8)"

    const yearsMap = new Map<number, any>()
    let totalQuestionsSum = 0

    for (let i = 1; i < lines.length; i++) {
      const parts = lines[i].trim().split("\t")
      if (parts.length < 10) continue

      examId = parseInt(parts[0], 10) || examId
      examTitle = parts[1] || examTitle
      const yearId = parseInt(parts[2], 10)
      const yearEc = parseInt(parts[4], 10) || parseInt(parts[3], 10)
      const yearGc = parseInt(parts[5], 10) || yearEc + 8
      const subjectId = parseInt(parts[6], 10)
      const subjectName = parts[7]
      const rawStreamId = parts[8] !== "NULL" && parts[8] !== "" ? parseInt(parts[8], 10) : null
      const qCount = parseInt(parts[9], 10) || 0

      totalQuestionsSum += qCount

      let stream = "common"
      if (rawStreamId === 1) stream = "natural"
      else if (rawStreamId === 2) stream = "social"
      else {
        const lower = subjectName.toLowerCase()
        if (["physics", "chemistry", "biology"].includes(lower)) stream = "natural"
        else if (["economics", "geography", "history"].includes(lower)) stream = "social"
      }

      if (!yearsMap.has(yearId)) {
        yearsMap.set(yearId, {
          id: yearId,
          exam_id: examId,
          grade_level: gradeLevel,
          year_ec: yearEc,
          year_gc: yearGc,
          is_published: true,
          total_questions: 0,
          subjects: [],
        })
      }

      const yrObj = yearsMap.get(yearId)
      yrObj.total_questions += qCount
      yrObj.subjects.push({
        id: subjectId,
        name: subjectName,
        code: subjectName.substring(0, 4).toUpperCase(),
        stream,
        stream_id: rawStreamId,
        stream_name:
          stream === "natural"
            ? "Natural Science"
            : stream === "social"
            ? "Social Science"
            : "Common Core",
        questions_count: qCount,
      })
    }

    const years = Array.from(yearsMap.values())
    const distinctSubjectNames = new Set(
      years.flatMap((y) => y.subjects.map((s: any) => s.name))
    )

    return NextResponse.json({
      status: "success",
      data: {
        exam: {
          id: examId,
          grade_level: gradeLevel,
          title: examTitle,
        },
        years,
        stats: {
          total_years: years.length,
          total_subjects: distinctSubjectNames.size,
          total_questions: totalQuestionsSum,
        },
      },
    })
  } catch (err) {
    console.error("Failed to query live national exams:", err)
    return NextResponse.json(
      {
        status: "error",
        message: "Failed to retrieve live national exams from database.",
      },
      { status: 500 }
    )
  }
}
