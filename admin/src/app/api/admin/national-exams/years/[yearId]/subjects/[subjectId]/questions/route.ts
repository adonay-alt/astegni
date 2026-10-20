import { NextResponse } from "next/server"
import { execFile } from "child_process"
import { promisify } from "util"

const execFileAsync = promisify(execFile)

export async function GET(
  request: Request,
  { params }: { params: Promise<{ yearId: string; subjectId: string }> }
) {
  const { yearId, subjectId } = await params
  const yId = parseInt(yearId, 10)
  const sId = parseInt(subjectId, 10)

  const mysqlBin = "c:\\xampp\\mysql\\bin\\mysql.exe"

  try {
    const query = `
      SELECT
        neq.id,
        neq.national_exam_year_id,
        neq.national_exam_subject_id,
        nes.name as subject_name,
        neq.question_number,
        neq.question,
        COALESCE(neq.image_url, '') as image_url,
        COALESCE(neq.option_a, '') as option_a,
        COALESCE(neq.option_b, '') as option_b,
        COALESCE(neq.option_c, '') as option_c,
        COALESCE(neq.option_d, '') as option_d,
        neq.correct_answer,
        COALESCE(neq.explanation, '') as explanation,
        COALESCE(neq.created_at, NOW()) as created_at
      FROM national_exam_questions neq
      JOIN national_exam_subjects nes ON nes.id = neq.national_exam_subject_id
      WHERE neq.national_exam_year_id = ${yId}
        AND neq.national_exam_subject_id = ${sId}
      ORDER BY neq.question_number ASC;
    `

    const { stdout } = await execFileAsync(mysqlBin, [
      "-u",
      "root",
      "-e",
      `USE asteghi_admin_dev; ${query}`,
    ])

    const lines = stdout.trim().split("\n")
    const questions = []

    for (let i = 1; i < lines.length; i++) {
      const parts = lines[i].trim().split("\t")
      if (parts.length < 13) continue

      const id = parseInt(parts[0], 10)
      const examYearId = parseInt(parts[1], 10)
      const subId = parseInt(parts[2], 10)
      const subjectName = parts[3]
      const questionNumber = parseInt(parts[4], 10) || i
      const questionText = parts[5]
      const diagramUrl = parts[6] || null
      const optionA = parts[7]
      const optionB = parts[8]
      const optionC = parts[9]
      const optionD = parts[10]
      const rawAns = parts[11].replace(/^option_/i, "").toUpperCase()
      const explanation = parts[12]
      const createdAt = parts[13]

      questions.push({
        id,
        exam_year_id: examYearId,
        subject_id: subId,
        subject_name: subjectName,
        question_number: questionNumber,
        question_text: questionText,
        diagram_url: diagramUrl,
        options: {
          A: optionA,
          B: optionB,
          C: optionC,
          D: optionD,
        },
        correct_answer: (["A", "B", "C", "D"].includes(rawAns) ? rawAns : "A") as any,
        explanation,
        created_at: createdAt,
      })
    }

    return NextResponse.json({
      status: "success",
      data: questions,
      count: questions.length,
    })
  } catch (err) {
    console.error("Failed to query live exam questions:", err)
    return NextResponse.json({
      status: "success",
      data: [],
      count: 0,
    })
  }
}
