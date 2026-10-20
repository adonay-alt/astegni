"use client"

import React, { useState } from "react"
import { INITIAL_STUDENTS } from "@/lib/mock-data"
import { StudentProfile, StudentStatus } from "@/types/student"
import { StudentDossierDrawer } from "@/components/drawers/student-dossier-drawer"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { Card, CardContent } from "@/components/ui/card"
import {
  Search,
  Users,
  GraduationCap,
  Download,
  Eye,
  CheckCircle,
  Clock,
} from "lucide-react"

export default function UsersPage() {
  const [students, setStudents] = useState<StudentProfile[]>(INITIAL_STUDENTS)
  const [selectedStudent, setSelectedStudent] = useState<StudentProfile | null>(null)
  const [drawerOpen, setDrawerOpen] = useState(false)

  // Filters
  const [searchQuery, setSearchQuery] = useState("")
  const [gradeFilter, setGradeFilter] = useState("all")
  const [streamFilter, setStreamFilter] = useState("all")
  const [statusFilter, setStatusFilter] = useState("all")

  const filteredStudents = students.filter((student) => {
    if (gradeFilter !== "all" && student.grade_name !== gradeFilter) return false
    if (streamFilter !== "all" && student.stream !== streamFilter) return false
    if (statusFilter !== "all" && student.status !== statusFilter) return false

    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase()
      const matches =
        student.name.toLowerCase().includes(q) ||
        student.email.toLowerCase().includes(q) ||
        student.phone.includes(q) ||
        (student.school && student.school.toLowerCase().includes(q))
      if (!matches) return false
    }

    return true
  })

  const handleStatusChange = (studentId: number, newStatus: StudentStatus) => {
    setStudents((prev) =>
      prev.map((s) => (s.id === studentId ? { ...s, status: newStatus } : s))
    )
    if (selectedStudent && selectedStudent.id === studentId) {
      setSelectedStudent((prev) => (prev ? { ...prev, status: newStatus } : null))
    }
  }

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100">
            Student & Learner Directory
          </h1>
          <p className="text-xs text-slate-500">
            Inspect curriculum progression, examine quiz scores, and support student sessions.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <Button
            size="sm"
            variant="outline"
            className="border-slate-200 text-xs shadow-sm dark:border-slate-800"
            onClick={() => window.alert("Exporting Student Roster...")}
          >
            <Download className="mr-1.5 h-3.5 w-3.5" />
            Export Roster
          </Button>
        </div>
      </div>

      {/* Filter Bar */}
      <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <CardContent className="p-4">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            {/* Search Input */}
            <div className="relative flex-1 max-w-sm">
              <Search className="absolute left-3 top-2.5 h-3.5 w-3.5 text-slate-400" />
              <Input
                placeholder="Search student, school, phone..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-8 text-xs border-slate-200 dark:border-slate-800"
              />
            </div>

            {/* Dropdown Filters */}
            <div className="flex flex-wrap items-center gap-2">
              <select
                value={gradeFilter}
                onChange={(e) => setGradeFilter(e.target.value)}
                className="h-9 rounded-md border border-slate-200 bg-white px-2.5 text-xs text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300"
              >
                <option value="all">All Grades</option>
                <option value="Grade 12">Grade 12</option>
                <option value="Grade 11">Grade 11</option>
                <option value="Grade 10">Grade 10</option>
                <option value="Grade 9">Grade 9</option>
              </select>

              <select
                value={streamFilter}
                onChange={(e) => setStreamFilter(e.target.value)}
                className="h-9 rounded-md border border-slate-200 bg-white px-2.5 text-xs text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300"
              >
                <option value="all">All Streams</option>
                <option value="natural">Natural Science</option>
                <option value="social">Social Science</option>
              </select>

              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="h-9 rounded-md border border-slate-200 bg-white px-2.5 text-xs text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300"
              >
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Main Student Data Table */}
      <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs text-slate-700 dark:text-slate-300">
            <thead className="border-b border-slate-200 bg-slate-50/70 font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-900/40">
              <tr>
                <th className="p-3.5">Student</th>
                <th className="p-3.5">Phone</th>
                <th className="p-3.5">Grade & Stream</th>
                <th className="p-3.5">School</th>
                <th className="p-3.5">Chapters Done</th>
                <th className="p-3.5">Quiz Avg</th>
                <th className="p-3.5">Status</th>
                <th className="p-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {filteredStudents.length === 0 ? (
                <tr>
                  <td colSpan={8} className="p-8 text-center text-slate-400">
                    No students match the current filters.
                  </td>
                </tr>
              ) : (
                filteredStudents.map((student) => {
                  const progressPct = Math.round(
                    (student.chapters_completed / student.total_chapters) * 100
                  )

                  return (
                    <tr
                      key={student.id}
                      className="transition hover:bg-slate-50/70 dark:hover:bg-slate-900/40"
                    >
                      <td className="p-3.5">
                        <div className="font-bold text-slate-900 dark:text-slate-100">
                          {student.name}
                        </div>
                        <div className="text-[11px] text-slate-500">{student.email}</div>
                      </td>
                      <td className="p-3.5">{student.phone}</td>
                      <td className="p-3.5">
                        <div className="flex items-center gap-1.5">
                          <Badge variant="outline" className="text-[10px]">
                            {student.grade_name}
                          </Badge>
                          {student.stream && (
                            <span className="capitalize text-[10px] text-purple-600 dark:text-purple-400 font-medium">
                              ({student.stream})
                            </span>
                          )}
                        </div>
                      </td>
                      <td className="p-3.5 text-slate-600 dark:text-slate-400">
                        {student.school || "—"}
                      </td>
                      <td className="p-3.5">
                        <div className="flex items-center gap-2">
                          <div className="h-1.5 w-16 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div
                              className="h-full bg-blue-600"
                              style={{ width: `${progressPct}%` }}
                            />
                          </div>
                          <span className="font-semibold text-[11px]">
                            {student.chapters_completed}/{student.total_chapters}
                          </span>
                        </div>
                      </td>
                      <td className="p-3.5">
                        <span className="font-bold text-emerald-600 dark:text-emerald-400">
                          {student.average_quiz_score}%
                        </span>
                        <span className="text-[10px] text-slate-400 ml-1">
                          ({student.quiz_attempts_count} q.)
                        </span>
                      </td>
                      <td className="p-3.5">
                        <Badge
                          variant={student.status === "active" ? "success" : "destructive"}
                          className="text-[10px]"
                        >
                          {student.status}
                        </Badge>
                      </td>
                      <td className="p-3.5 text-right">
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => {
                            setSelectedStudent(student)
                            setDrawerOpen(true)
                          }}
                          className="h-8 border-slate-200 text-xs hover:bg-blue-50 hover:text-blue-700 dark:border-slate-800 dark:hover:bg-blue-950/40"
                        >
                          <Eye className="mr-1 h-3 w-3" />
                          Inspect
                        </Button>
                      </td>
                    </tr>
                  )
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Student Dossier Drawer */}
      <StudentDossierDrawer
        student={selectedStudent}
        open={drawerOpen}
        onOpenChange={setDrawerOpen}
        onStatusChange={handleStatusChange}
      />
    </div>
  )
}
