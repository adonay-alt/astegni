import { NextResponse } from "next/server"
import { execFile } from "child_process"
import { promisify } from "util"

const execFileAsync = promisify(execFile)

export async function POST(
  request: Request,
  { params }: { params: Promise<{ yearId: string }> }
) {
  try {
    const { yearId } = await params
    const yId = parseInt(yearId, 10)
    if (!yId) {
      return NextResponse.json({ status: "error", message: "Invalid year ID." }, { status: 400 })
    }

    const body = await request.json()
    const { name, code, stream } = body
    if (!name || typeof name !== "string") {
      return NextResponse.json({ status: "error", message: "Subject name is required." }, { status: 422 })
    }

    let streamId: number | null = null
    const s = String(stream || "").toLowerCase()
    if (s === "natural") streamId = 1
    else if (s === "social") streamId = 2

    const mysqlBin = "c:\\xampp\\mysql\\bin\\mysql.exe"
    const escapedName = name.replace(/'/g, "\\'")

    const insertSql = `
      SELECT national_exam_id, grade_id INTO @exam_id, @grade_id FROM national_exam_years WHERE id = ${yId};
      INSERT INTO national_exam_subjects (national_exam_year_id, national_exam_id, grade_id, stream_id, name, description, created_at, updated_at)
      VALUES (${yId}, IFNULL(@exam_id, 1), IFNULL(@grade_id, 5), ${streamId !== null ? streamId : "NULL"}, '${escapedName}', '${escapedName} Examination Paper', NOW(), NOW());
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
      message: "Exam subject added successfully.",
      data: {
        id: newId,
        name,
        code: code || name.substring(0, 4).toUpperCase(),
        stream: s === "natural" || s === "social" ? s : "common",
        stream_id: streamId,
        stream_name: s === "natural" ? "Natural Science" : s === "social" ? "Social Science" : "Common Core",
        questions_count: 0,
      },
    }, { status: 201 })
  } catch (err: any) {
    console.error("Failed to add subject:", err)
    return NextResponse.json(
      { status: "error", message: "Failed to add subject: " + err.message },
      { status: 500 }
    )
  }
}
