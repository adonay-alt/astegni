import { NextResponse } from "next/server"
import { execFile } from "child_process"
import { promisify } from "util"

const execFileAsync = promisify(execFile)

export async function POST(request: Request) {
  try {
    const body = await request.json()
    const { grade_level, year_ec, year_gc } = body
    const gradeLevel = parseInt(grade_level, 10) === 8 ? 8 : 12
    const gradeId = gradeLevel === 12 ? 5 : 1
    const examId = gradeLevel === 12 ? 1 : 2
    const yearEc = parseInt(year_ec, 10)
    const yearGc = parseInt(year_gc, 10) || yearEc + 8

    const mysqlBin = "c:\\xampp\\mysql\\bin\\mysql.exe"

    // Insert year record
    const insertYearSql = `
      INSERT INTO national_exam_years (national_exam_id, grade_id, year, year_ec, year_gc, created_at, updated_at)
      VALUES (${examId}, ${gradeId}, ${yearEc}, ${yearEc}, ${yearGc}, NOW(), NOW());
      SELECT LAST_INSERT_ID() as new_year_id;
    `

    const { stdout } = await execFileAsync(mysqlBin, [
      "-u",
      "root",
      "-e",
      `USE asteghi_admin_dev; ${insertYearSql}`,
    ])

    const lastLine = stdout.trim().split("\n").pop()
    const newYearId = parseInt(lastLine || "0", 10) || Date.now()

    // Default subjects for new year
    const subjects =
      gradeLevel === 12
        ? [
            { name: "Mathematics", stream: "common", stream_id: null },
            { name: "Physics", stream: "natural", stream_id: 1 },
            { name: "Chemistry", stream: "natural", stream_id: 1 },
            { name: "Biology", stream: "natural", stream_id: 1 },
            { name: "English", stream: "common", stream_id: null },
            { name: "Civics", stream: "common", stream_id: null },
            { name: "Economics", stream: "social", stream_id: 2 },
            { name: "Geography", stream: "social", stream_id: 2 },
            { name: "History", stream: "social", stream_id: 2 },
          ]
        : [
            { name: "Mathematics", stream: "common", stream_id: null },
            { name: "General Science", stream: "common", stream_id: null },
            { name: "English", stream: "common", stream_id: null },
            { name: "Social Studies", stream: "common", stream_id: null },
            { name: "Citizenship", stream: "common", stream_id: null },
            { name: "Amharic", stream: "common", stream_id: null },
          ]

    const insertSubjectsSql = subjects
      .map(
        (s) =>
          `INSERT INTO national_exam_subjects (national_exam_year_id, national_exam_id, grade_id, stream_id, name, description, created_at, updated_at)
           VALUES (${newYearId}, ${examId}, ${gradeId}, ${s.stream_id ? s.stream_id : "NULL"}, '${s.name}', '${s.name} Examination Paper', NOW(), NOW());`
      )
      .join("\n")

    await execFileAsync(mysqlBin, [
      "-u",
      "root",
      "-e",
      `USE asteghi_admin_dev; ${insertSubjectsSql}`,
    ])

    return NextResponse.json({
      status: "success",
      message: "National exam year created successfully.",
      data: {
        id: newYearId,
        exam_id: examId,
        grade_level: gradeLevel,
        year_ec: yearEc,
        year_gc: yearGc,
        is_published: true,
        total_questions: 0,
        subjects: subjects.map((s, idx) => ({
          id: newYearId * 100 + idx,
          name: s.name,
          code: s.name.substring(0, 4).toUpperCase(),
          stream: s.stream,
          stream_id: s.stream_id,
          stream_name:
            s.stream === "natural"
              ? "Natural Science"
              : s.stream === "social"
              ? "Social Science"
              : "Common Core",
          questions_count: 0,
        })),
      },
    })
  } catch (err) {
    console.error("Failed to create national exam year:", err)
    return NextResponse.json(
      { status: "error", message: "Failed to create exam year." },
      { status: 500 }
    )
  }
}
