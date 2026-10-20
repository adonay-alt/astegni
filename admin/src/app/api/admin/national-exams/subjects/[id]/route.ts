import { NextResponse } from "next/server"
import { execFile } from "child_process"
import { promisify } from "util"

const execFileAsync = promisify(execFile)

export async function PUT(
  request: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    const { id } = await params
    const subjectId = parseInt(id, 10)
    if (!subjectId) {
      return NextResponse.json({ status: "error", message: "Invalid subject ID." }, { status: 400 })
    }

    const body = await request.json()
    const { name, stream, stream_id } = body

    let targetStreamId = stream_id
    if (targetStreamId === undefined) {
      if (stream === "natural") targetStreamId = 1
      else if (stream === "social") targetStreamId = 2
      else targetStreamId = null
    }

    const mysqlBin = "c:\\xampp\\mysql\\bin\\mysql.exe"
    let updateClauses = [`stream_id = ${targetStreamId ? targetStreamId : "NULL"}`, `updated_at = NOW()`]
    if (name && typeof name === "string") {
      updateClauses.push(`name = '${name.replace(/'/g, "\\'")}'`)
    }

    const updateSql = `
      UPDATE national_exam_subjects
      SET ${updateClauses.join(", ")}
      WHERE id = ${subjectId};
    `

    await execFileAsync(mysqlBin, [
      "-u",
      "root",
      "-e",
      `USE asteghi_admin_dev; ${updateSql}`,
    ])

    return NextResponse.json({
      status: "success",
      message: "Exam subject updated successfully.",
      data: {
        id: subjectId,
        name: name || undefined,
        stream: targetStreamId === 1 ? "natural" : targetStreamId === 2 ? "social" : "common",
        stream_id: targetStreamId,
        stream_name:
          targetStreamId === 1
            ? "Natural Science"
            : targetStreamId === 2
            ? "Social Science"
            : "Common Core",
      },
    })
  } catch (err: any) {
    console.error("Failed to update subject:", err)
    return NextResponse.json(
      { status: "error", message: "Failed to update subject: " + err.message },
      { status: 500 }
    )
  }
}

export async function DELETE(
  request: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    const { id } = await params
    const subjectId = parseInt(id, 10)
    if (!subjectId) {
      return NextResponse.json({ status: "error", message: "Invalid subject ID." }, { status: 400 })
    }

    const mysqlBin = "c:\\xampp\\mysql\\bin\\mysql.exe"
    const deleteSql = `
      DELETE FROM national_exam_questions WHERE national_exam_subject_id = ${subjectId};
      DELETE FROM national_exam_subjects WHERE id = ${subjectId};
    `

    await execFileAsync(mysqlBin, [
      "-u",
      "root",
      "-e",
      `USE asteghi_admin_dev; ${deleteSql}`,
    ])

    return NextResponse.json({
      status: "success",
      message: "Exam subject and its question papers deleted successfully.",
    })
  } catch (err: any) {
    console.error("Failed to delete subject:", err)
    return NextResponse.json(
      { status: "error", message: "Failed to delete subject: " + err.message },
      { status: 500 }
    )
  }
}
