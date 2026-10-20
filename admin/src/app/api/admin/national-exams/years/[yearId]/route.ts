import { NextResponse } from "next/server"
import { execFile } from "child_process"
import { promisify } from "util"

const execFileAsync = promisify(execFile)

export async function DELETE(
  request: Request,
  { params }: { params: Promise<{ yearId: string }> }
) {
  try {
    const { yearId } = await params
    const id = parseInt(yearId, 10)
    if (!id) {
      return NextResponse.json({ status: "error", message: "Invalid exam year ID." }, { status: 400 })
    }

    const mysqlBin = "c:\\xampp\\mysql\\bin\\mysql.exe"
    const deleteSql = `
      DELETE FROM national_exam_questions WHERE national_exam_subject_id IN (SELECT id FROM national_exam_subjects WHERE national_exam_year_id = ${id});
      DELETE FROM national_exam_questions WHERE national_exam_year_id = ${id};
      DELETE FROM national_exam_subjects WHERE national_exam_year_id = ${id};
      DELETE FROM national_exam_years WHERE id = ${id};
    `

    await execFileAsync(mysqlBin, [
      "-u",
      "root",
      "-e",
      `USE asteghi_admin_dev; ${deleteSql}`,
    ])

    return NextResponse.json({
      status: "success",
      message: `Exam year and all associated question papers deleted successfully.`,
    })
  } catch (err: any) {
    console.error("Failed to delete exam year:", err)
    return NextResponse.json(
      { status: "error", message: "Failed to delete exam year: " + err.message },
      { status: 500 }
    )
  }
}
