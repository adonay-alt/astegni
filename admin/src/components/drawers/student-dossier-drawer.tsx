"use client"

import React, { useState } from "react"
import { useRouter } from "next/navigation"
import { StudentProfile, StudentStatus } from "@/types/student"
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription,
} from "@/components/ui/sheet"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Separator } from "@/components/ui/separator"
import {
  GraduationCap,
  Mail,
  Phone,
  School,
  Calendar,
  CheckCircle,
  HelpCircle,
  TrendingUp,
  KeyRound,
  Ban,
  Clock,
  ShieldCheck,
} from "lucide-react"

interface StudentDossierDrawerProps {
  student: StudentProfile | null
  open: boolean
  onOpenChange: (open: boolean) => void
  onStatusChange: (studentId: number, newStatus: StudentStatus) => void
}

export function StudentDossierDrawer({
  student,
  open,
  onOpenChange,
  onStatusChange,
}: StudentDossierDrawerProps) {
  const router = useRouter()
  const [otpSentNotice, setOtpSentNotice] = useState(false)

  if (!student) return null

  const completionPercentage = Math.round(
    (student.chapters_completed / student.total_chapters) * 100
  )

  const handleResetOtp = () => {
    setOtpSentNotice(true)
    setTimeout(() => setOtpSentNotice(false), 5000)
  }

  const toggleStatus = () => {
    const nextStatus = student.status === "active" ? "suspended" : "active"
    onStatusChange(student.id, nextStatus)
  }

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent side="right" className="w-full sm:max-w-lg overflow-y-auto p-6">
        <SheetHeader className="space-y-1 pb-4 text-left">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold uppercase tracking-wider text-slate-400">
              Student ID #{student.id}
            </span>
            <Badge variant={student.status === "active" ? "success" : "destructive"}>
              {student.status === "active" ? "Active Learner" : "Suspended"}
            </Badge>
          </div>
          <SheetTitle className="text-xl font-bold text-slate-900 dark:text-slate-100">
            {student.name}
          </SheetTitle>
          <SheetDescription className="text-xs text-slate-500">
            Enrolled {new Date(student.created_at).toLocaleDateString()}
          </SheetDescription>
        </SheetHeader>

        <div className="space-y-6 pt-2">
          {/* Student Profile Card */}
          <div className="flex items-start gap-4 rounded-xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
            <Avatar className="h-14 w-14 border border-slate-200 dark:border-slate-700">
              <AvatarImage src={student.avatar_url} alt={student.name} />
              <AvatarFallback className="bg-indigo-600 text-base font-bold text-white">
                {student.name
                  .split(" ")
                  .map((n) => n[0])
                  .join("")}
              </AvatarFallback>
            </Avatar>

            <div className="flex-1 space-y-1 text-xs">
              <div className="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                <Mail className="h-3.5 w-3.5 text-slate-400" />
                <span className="font-medium">{student.email}</span>
              </div>
              <div className="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                <Phone className="h-3.5 w-3.5 text-slate-400" />
                <span>{student.phone}</span>
              </div>
              {student.school && (
                <div className="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                  <School className="h-3.5 w-3.5 text-slate-400" />
                  <span>{student.school}</span>
                </div>
              )}
            </div>
          </div>

          {/* Academic Placement */}
          <div className="space-y-2">
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500">
              Academic Cohort
            </h4>
            <div className="flex items-center gap-2 text-xs">
              <Badge variant="outline" className="bg-blue-50 text-blue-700 border-blue-200">
                <GraduationCap className="mr-1 h-3.5 w-3.5" />
                {student.grade_name}
              </Badge>
              {student.stream && (
                <Badge variant="outline" className="capitalize bg-purple-50 text-purple-700 border-purple-200">
                  {student.stream} Stream
                </Badge>
              )}
              <span className="text-[11px] text-slate-400 flex items-center gap-1 ml-auto">
                <Clock className="h-3 w-3" />
                Last active {new Date(student.last_active_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
              </span>
            </div>
          </div>

          {/* Learning Progress Metrics */}
          <div className="space-y-3">
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500">
              LMS Learning Progress
            </h4>

            {/* Progress Bar */}
            <div className="space-y-1.5 rounded-xl border border-slate-100 bg-white p-3.5 dark:border-slate-800 dark:bg-slate-900/40">
              <div className="flex items-center justify-between text-xs">
                <span className="font-semibold text-slate-700 dark:text-slate-300">
                  Curriculum Completion
                </span>
                <span className="font-bold text-slate-900 dark:text-slate-100">
                  {student.chapters_completed} / {student.total_chapters} Chapters ({completionPercentage}%)
                </span>
              </div>
              <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                <div
                  className="h-full bg-gradient-to-r from-blue-600 to-indigo-600 transition-all"
                  style={{ width: `${completionPercentage}%` }}
                />
              </div>
            </div>

            {/* Quiz Performance Cards */}
            <div className="grid grid-cols-2 gap-3 text-xs">
              <div className="rounded-xl border border-slate-100 bg-white p-3 dark:border-slate-800 dark:bg-slate-900/40">
                <div className="flex items-center gap-1.5 text-slate-400">
                  <HelpCircle className="h-3.5 w-3.5 text-blue-500" />
                  <span>Quiz Attempts</span>
                </div>
                <div className="mt-1 text-lg font-extrabold text-slate-900 dark:text-slate-100">
                  {student.quiz_attempts_count} Quizzes
                </div>
              </div>

              <div className="rounded-xl border border-slate-100 bg-white p-3 dark:border-slate-800 dark:bg-slate-900/40">
                <div className="flex items-center gap-1.5 text-slate-400">
                  <TrendingUp className="h-3.5 w-3.5 text-emerald-500" />
                  <span>Average Score</span>
                </div>
                <div className="mt-1 text-lg font-extrabold text-emerald-600 dark:text-emerald-400">
                  {student.average_quiz_score}%
                </div>
              </div>
            </div>
          </div>

          <Separator />

          {/* Account Governance & Security */}
          <div className="space-y-3">
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500">
              Account Management
            </h4>

            {otpSentNotice && (
              <div className="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-2.5 text-xs text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
                <ShieldCheck className="h-4 w-4 shrink-0 text-emerald-600" />
                <span>Verification OTP dispatched to {student.phone}.</span>
              </div>
            )}

            <div className="flex gap-2">
              <Button
                size="sm"
                variant="outline"
                onClick={handleResetOtp}
                className="flex-1 text-xs"
              >
                <KeyRound className="mr-1.5 h-3.5 w-3.5 text-slate-500" />
                Reset Password / OTP
              </Button>

              <Button
                size="sm"
                variant={student.status === "active" ? "destructive" : "default"}
                onClick={toggleStatus}
                className="flex-1 text-xs"
              >
                <Ban className="mr-1.5 h-3.5 w-3.5" />
                {student.status === "active" ? "Suspend Student" : "Reactivate"}
              </Button>
            </div>
          </div>
        </div>
      </SheetContent>
    </Sheet>
  )
}
