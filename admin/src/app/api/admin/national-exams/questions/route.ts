import { NextResponse } from "next/server"
import { execFile } from "child_process"
import { promisify } from "util"

const execFileAsync = promisify(execFile)

export async function POST(request: Request) {
  try {
    const body = await request.json()
    const {
      national_exam_year_id,
      national_exam_subject_id,
      question_number,
      question,
      option_a,
      option_b,
      option_c,
      option_d,
      correct_answer,
      explanation,
    } = body

    const mysqlBin = "c:\\xampp\\mysql\\bin\\mysql.exe"

    // Escape single quotes for SQL
    const esc = (str: any) =>
      typeof str === "string" ? str.replace(/'/g, "\\'") : ""

    const rawAns = (correct_answer || "A").toLowerCase()
    const normalizedAns = rawAns.startsWith("option_") ? rawAns : `option_${rawAns}`

    const insertSql = `
      INSERT INTO national_exam_questions (
        grade_id,
        national_exam_id,
        national_exam_year_id,
        national_exam_subject_id,
        question_number,
        question,
        option_a,
        option_b,
        option_c,
        option_d,
        correct_answer,
        explanation,
        created_at,
        updated_at
      ) VALUES (
        (SELECT grade_id FROM national_exam_years WHERE id = ${parseInt(national_exam_year_id, 10)} LIMIT 1),
        (SELECT national_exam_id FROM national_exam_years WHERE id = ${parseInt(national_exam_year_id, 10)} LIMIT 1),
        ${parseInt(national_exam_year_id, 10)},
        ${parseInt(national_exam_subject_id, 10)},
        ${parseInt(question_number, 10) || 1},
        '${esc(question)}',
        '${esc(option_a)}',
        '${esc(option_b)}',
        '${esc(option_c)}',
        '${esc(option_d)}',
        '${esc(normalizedAns)}',
        '${esc(explanation)}',
        NOW(),
        NOW()
      );
      SELECT LAST_INSERT_ID() as new_id;
    `

    const { stdout } = await execFileAsync(mysqlBin, [
      "-u",
      "root",
      "-e",
      `USE asteghi_admin_dev; ${insertSql}`,
    ])

    const lastLine = stdout.trim().split("\n").pop()
    const newId = parseInt(lastLine || "0", 10) || Date.now()

    return NextResponse.json({
      status: "success",
      message: "National exam question created successfully.",
      data: {
        id: newId,
        exam_year_id: parseInt(national_exam_year_id, 10),
        subject_id: parseInt(national_exam_subject_id, 10),
        question_number: parseInt(question_number, 10) || 1,
        question_text: question,
        options: {
          A: option_a || "",
          B: option_b || "",
          C: option_c || "",
          D: option_d || "",
        },
        correct_answer: (correct_answer || "A").replace(/^option_/i, "").toUpperCase(),
        explanation: explanation || "",
      },
    })
  } catch (err) {
    console.error("Failed to insert question into live database:", err)
    return NextResponse.json(
      { status: "error", message: "Failed to create question." },
      { status: 500 }
    )
  }
}
