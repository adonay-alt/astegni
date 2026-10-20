import { NextResponse } from "next/server"
import { execFile } from "child_process"
import { promisify } from "util"

const execFileAsync = promisify(execFile)

export async function PUT(
  request: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const { id } = await params
  const subjectId = parseInt(id, 10)

  try {
    const body = await request.json()
    const { stream, stream_id } = body

    let targetStreamId = stream_id
    if (targetStreamId === undefined) {
      if (stream === "natural") targetStreamId = 1
      else if (stream === "social") targetStreamId = 2
      else targetStreamId = null
    }

    const mysqlBin = "c:\\xampp\\mysql\\bin\\mysql.exe"
    const updateSql = `
      UPDATE national_exam_subjects
      SET stream_id = ${targetStreamId ? targetStreamId : "NULL"}, updated_at = NOW()
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
      message: "Subject stream updated successfully.",
      data: {
        id: subjectId,
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
  } catch (err) {
    console.error("Failed to update subject stream:", err)
    return NextResponse.json(
      { status: "error", message: "Failed to update stream." },
      { status: 500 }
    )
  }
}
