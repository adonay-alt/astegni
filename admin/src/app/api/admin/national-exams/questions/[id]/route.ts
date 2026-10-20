import { NextResponse } from "next/server"
import { execFile } from "child_process"
import { promisify } from "util"

const execFileAsync = promisify(execFile)

export async function PUT(
  request: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const { id } = await params
  const qId = parseInt(id, 10)

  try {
    const body = await request.json()
    const {
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
    const esc = (str: any) =>
      typeof str === "string" ? str.replace(/'/g, "\\'") : ""

    const rawAns = (correct_answer || "A").toLowerCase()
    const normalizedAns = rawAns.startsWith("option_") ? rawAns : `option_${rawAns}`

    const updateSql = `
      UPDATE national_exam_questions SET
        question_number = ${parseInt(question_number, 10) || 1},
        question = '${esc(question)}',
        option_a = '${esc(option_a)}',
        option_b = '${esc(option_b)}',
        option_c = '${esc(option_c)}',
        option_d = '${esc(option_d)}',
        correct_answer = '${esc(normalizedAns)}',
        explanation = '${esc(explanation)}',
        updated_at = NOW()
      WHERE id = ${qId};
    `

    await execFileAsync(mysqlBin, [
      "-u",
      "root",
      "-e",
      `USE asteghi_admin_dev; ${updateSql}`,
    ])

    return NextResponse.json({
      status: "success",
      message: "Question updated successfully.",
      data: {
        id: qId,
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
    console.error("Failed to update question in live database:", err)
    return NextResponse.json(
      { status: "error", message: "Failed to update question." },
      { status: 500 }
    )
  }
}

export async function DELETE(
  request: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const { id } = await params
  const qId = parseInt(id, 10)

  const mysqlBin = "c:\\xampp\\mysql\\bin\\mysql.exe"

  try {
    const deleteSql = `DELETE FROM national_exam_questions WHERE id = ${qId};`
    await execFileAsync(mysqlBin, [
      "-u",
      "root",
      "-e",
      `USE asteghi_admin_dev; ${deleteSql}`,
    ])

    return NextResponse.json({
      status: "success",
      message: "Question deleted successfully.",
    })
  } catch (err) {
    console.error("Failed to delete question from live database:", err)
    return NextResponse.json(
      { status: "error", message: "Failed to delete question." },
      { status: 500 }
    )
  }
}
