import { NextResponse } from "next/server"
import { execFile } from "child_process"
import { promisify } from "util"

const execFileAsync = promisify(execFile)

export async function POST(request: Request) {
  try {
    const body = await request.json()
    const rawYearId = body.national_exam_year_id || body.exam_year_id
    const rawSubId = body.national_exam_subject_id || body.subject_id
    const questions = body.questions

    if (!Array.isArray(questions) || questions.length === 0) {
      return NextResponse.json(
        { status: "error", message: "No questions provided for import." },
        { status: 422 }
      )
    }

    const yId = parseInt(rawYearId, 10)
    const sId = parseInt(rawSubId, 10)
    const mysqlBin = "c:\\xampp\\mysql\\bin\\mysql.exe"
    const esc = (str: any) =>
      typeof str === "string" ? str.replace(/'/g, "\\'") : ""

    const insertStatements = questions.map((q: any, idx: number) => {
      const qNum = parseInt(q.question_number, 10) || idx + 1
      const qText = q.question_text || q.question || ""
      const imgUrl = q.diagram_url || q.image_url || null
      const optA = q.options?.A || q.option_a || ""
      const optB = q.options?.B || q.option_b || ""
      const optC = q.options?.C || q.option_c || ""
      const optD = q.options?.D || q.option_d || ""
      const rawAns = (q.correct_answer || "A").toLowerCase()
      const normalizedAns = rawAns.startsWith("option_") ? rawAns : `option_${rawAns}`
      const expl = q.explanation || ""

      return `INSERT INTO national_exam_questions (
        grade_id, national_exam_id, national_exam_year_id, national_exam_subject_id,
        question_number, question, image_url, option_a, option_b, option_c, option_d,
        correct_answer, explanation, created_at, updated_at
      ) VALUES (
        (SELECT grade_id FROM national_exam_years WHERE id = ${yId} LIMIT 1),
        (SELECT national_exam_id FROM national_exam_years WHERE id = ${yId} LIMIT 1),
        ${yId}, ${sId}, ${qNum}, '${esc(qText)}', ${imgUrl ? `'${esc(imgUrl)}'` : "NULL"}, '${esc(optA)}', '${esc(optB)}', '${esc(optC)}', '${esc(optD)}',
        '${esc(normalizedAns)}', '${esc(expl)}', NOW(), NOW()
      );`
    })

    const bulkSql = insertStatements.join("\n")

    await execFileAsync(mysqlBin, [
      "-u",
      "root",
      "-e",
      `USE asteghi_admin_dev; ${bulkSql}`,
    ])

    // Query back the newly saved questions
    const querySql = `SELECT id, question_number, question, image_url, option_a, option_b, option_c, option_d, correct_answer, explanation, created_at FROM national_exam_questions WHERE national_exam_year_id = ${yId} AND national_exam_subject_id = ${sId} ORDER BY question_number ASC;`
    const { stdout } = await execFileAsync(mysqlBin, [
      "-u",
      "root",
      "-e",
      `USE asteghi_admin_dev; ${querySql}`,
      "--batch",
      "--raw",
    ])

    const rows = stdout.trim().split("\n").slice(1)
    const savedList = rows.map((r) => {
      const [id, qNum, qText, imgUrl, optA, optB, optC, optD, cAns, expl, createdAt] = r.split("\t")
      const key = (cAns || "option_a").replace("option_", "").toUpperCase()
      return {
        id: parseInt(id, 10),
        exam_year_id: yId,
        subject_id: sId,
        question_number: parseInt(qNum, 10),
        question_text: qText,
        diagram_url: imgUrl && imgUrl !== "NULL" ? imgUrl : null,
        options: {
          A: optA,
          B: optB,
          C: optC,
          D: optD,
        },
        correct_answer: key,
        explanation: expl,
        created_at: createdAt,
      }
    })

    return NextResponse.json({
      status: "success",
      message: `Successfully imported ${questions.length} questions into database.`,
      imported_count: questions.length,
      data: savedList,
    })
  } catch (err) {
    console.error("Failed to batch import questions into database:", err)
    return NextResponse.json(
      { status: "error", message: "Failed to batch import questions." },
      { status: 500 }
    )
  }
}
