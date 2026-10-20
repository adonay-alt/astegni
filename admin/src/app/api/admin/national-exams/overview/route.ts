import { NextResponse } from "next/server"
import { execFile } from "child_process"
import { promisify } from "util"

const execFileAsync = promisify(execFile)

export async function GET(request: Request) {
  const { searchParams } = new URL(request.url)
  const gradeParam = searchParams.get("grade") || searchParams.get("grade_level") || "12"
  const gradeLevel = parseInt(gradeParam, 10) === 8 ? 8 : 12
  // Forward exam_id so per-program or all-programs aggregation works correctly
  const examId = searchParams.get("exam_id") || "all"

  const phpBin = "c:\\xampp\\php\\php.exe"
  const laravelDir = "c:\\xampp\\htdocs\\apiv8\\apiv2.asteghi.com"

  try {
    const script = `
      require 'vendor/autoload.php';
      $app = require_once 'bootstrap/app.php';
      $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
      $req = Illuminate\\Http\\Request::create('/api/admin/national-exams/overview', 'GET', ['grade' => ${gradeLevel}, 'exam_id' => '${examId}']);
      $res = (new App\\Http\\Controllers\\Api\\AdminNationalExamController())->overview($req);
      echo $res->getContent();
    `

    const { stdout } = await execFileAsync(phpBin, ["-r", script], {
      cwd: laravelDir,
    })

    const parsed = JSON.parse(stdout)
    return NextResponse.json(parsed)
  } catch (err: any) {
    console.error("Failed in overview API handler:", err)
    return NextResponse.json(
      { status: "error", message: err.message || "Failed to fetch overview", data: [] },
      { status: 500 }
    )
  }
}