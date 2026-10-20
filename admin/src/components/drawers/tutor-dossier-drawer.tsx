"use client"

import React, { useState } from "react"
import { TutorProfile, TutorStatus } from "@/types/tutor"
import { apiClient } from "@/lib/api-client"
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription,
} from "@/components/ui/sheet"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Separator } from "@/components/ui/separator"
import {
  CheckCircle,
  XCircle,
  AlertTriangle,
  FileText,
  Phone,
  Mail,
  MapPin,
  BookOpen,
  GraduationCap,
  Trash2,
  ExternalLink,
  ShieldAlert,
  Camera,
  ZoomIn,
  Maximize2,
} from "lucide-react"

interface TutorDossierDrawerProps {
  tutor: TutorProfile | null
  open: boolean
  onOpenChange: (open: boolean) => void
  onStatusChange: (tutorId: number, status: TutorStatus, reason?: string) => void
  onDelete: (tutorId: number) => void
}

export const DEDICATED_REJECTION_REASON = "Inappropriate picture in tutor profile"
export const REJECTION_FEEDBACK_MESSAGE =
  "Your application was rejected because your uploaded profile picture does not meet platform guidelines (must be a clear, professional headshot). Please update your photo and re-apply."

export function TutorDossierDrawer({
  tutor,
  open,
  onOpenChange,
  onStatusChange,
  onDelete,
}: TutorDossierDrawerProps) {
  const [isPhotoZoomOpen, setIsPhotoZoomOpen] = useState(false)
  const [isRejectDialogOpen, setIsRejectDialogOpen] = useState(false)
  const [isProcessing, setIsProcessing] = useState(false)

  if (!tutor) return null

  const handleApprove = async () => {
    setIsProcessing(true)
    try {
      // PATCH /admin/tutors/{id}/status
      await apiClient.patch(`/admin/tutors/${tutor.id}/status`, {
        status: "approved",
      })
    } catch (err) {
      console.error("Failed to approve tutor:", err)
    } finally {
      setIsProcessing(false)
      onStatusChange(tutor.id, "approved")
      onOpenChange(false)
    }
  }

  const handleConfirmRejection = async () => {
    setIsProcessing(true)
    try {
      // PATCH /admin/tutors/{id}/status
      await apiClient.patch(`/admin/tutors/${tutor.id}/status`, {
        status: "rejected",
        reason: DEDICATED_REJECTION_REASON,
        feedback: REJECTION_FEEDBACK_MESSAGE,
      })
    } catch (err) {
      console.error("Failed to reject tutor:", err)
    } finally {
      setIsProcessing(false)
      onStatusChange(tutor.id, "rejected", DEDICATED_REJECTION_REASON)
      setIsRejectDialogOpen(false)
      onOpenChange(false)
    }
  }

  const getStatusBadge = (status: TutorStatus) => {
    switch (status) {
      case "approved":
        return <Badge variant="success">Approved</Badge>
      case "pending":
        return <Badge variant="warning">Pending Review</Badge>
      case "rejected":
        return <Badge variant="destructive">Rejected</Badge>
      case "suspended":
        return <Badge variant="destructive">Suspended</Badge>
      default:
        return <Badge variant="outline">{status}</Badge>
    }
  }

  return (
    <>
      <Sheet open={open} onOpenChange={onOpenChange}>
        <SheetContent side="right" className="w-full sm:max-w-xl overflow-y-auto p-6">
          <SheetHeader className="space-y-1 pb-4 text-left border-b border-slate-100 dark:border-slate-800">
            <div className="flex items-center justify-between">
              <span className="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                Tutor Dossier #{tutor.id}
              </span>
              {getStatusBadge(tutor.status)}
            </div>
            <SheetTitle className="text-xl font-bold text-slate-900 dark:text-slate-100">
              {tutor.name}
            </SheetTitle>
            <SheetDescription className="text-xs text-slate-500">
              Registered on{" "}
              {new Date(tutor.created_at).toLocaleDateString("en-US", {
                month: "short",
                day: "numeric",
                year: "numeric",
              })}
            </SheetDescription>
          </SheetHeader>

          <div className="space-y-6 pt-4">
            {/* 1. HIGH-VISIBILITY PHOTO INSPECTION CARD */}
            <div className="rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-blue-50/30 p-4 shadow-sm dark:border-slate-800 dark:from-slate-900/60 dark:via-slate-900/30 dark:to-blue-950/20">
              <div className="flex items-center justify-between pb-3">
                <div className="flex items-center gap-2">
                  <Camera className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                  <span className="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Profile Picture Inspection
                  </span>
                </div>
                {tutor.avatar_url ? (
                  <Badge variant="outline" className="border-emerald-300 bg-emerald-50 text-[10px] font-semibold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                    Photo Attached
                  </Badge>
                ) : (
                  <Badge variant="destructive" className="text-[10px] font-bold gap-1">
                    ⚠️ No photo uploaded
                  </Badge>
                )}
              </div>

              <div className="flex flex-col sm:flex-row items-center gap-5">
                {/* Photo Frame with Zoom Trigger */}
                <div
                  className="relative group cursor-pointer shrink-0"
                  onClick={() => tutor.avatar_url && setIsPhotoZoomOpen(true)}
                  title={tutor.avatar_url ? "Click to enlarge portrait" : "No photo attached"}
                >
                  {tutor.avatar_url ? (
                    <div className="relative h-36 w-36 overflow-hidden rounded-2xl border-2 border-slate-200 bg-slate-100 shadow-md transition group-hover:border-blue-500 group-hover:scale-[1.02] dark:border-slate-700">
                      <img
                        src={tutor.avatar_url}
                        alt={tutor.name}
                        className="h-full w-full object-cover"
                      />
                      <div className="absolute inset-0 flex items-center justify-center bg-black/45 opacity-0 backdrop-blur-[1px] transition group-hover:opacity-100">
                        <span className="flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1 text-xs font-bold text-slate-900 shadow">
                          <ZoomIn className="h-3.5 w-3.5" /> Enlarge
                        </span>
                      </div>
                    </div>
                  ) : (
                    <div className="flex h-36 w-36 flex-col items-center justify-center rounded-2xl border-2 border-dashed border-amber-300 bg-amber-50/50 p-3 text-center text-amber-800 dark:border-amber-800/60 dark:bg-amber-950/20 dark:text-amber-300">
                      <AlertTriangle className="h-7 w-7 text-amber-500 mb-1" />
                      <span className="text-xs font-bold leading-tight">No Photo Attached</span>
                      <span className="text-[10px] text-amber-600 dark:text-amber-400 mt-1">
                        Required for verification
                      </span>
                    </div>
                  )}
                </div>

                {/* Photo Details & Lightbox Button */}
                <div className="flex-1 space-y-2.5 text-xs text-center sm:text-left">
                  <div>
                    <h4 className="font-bold text-slate-900 text-sm dark:text-slate-100">
                      {tutor.name}
                    </h4>
                    <p className="text-[11px] text-slate-500">{tutor.email}</p>
                  </div>

                  <p className="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                    Review headshot clarity, background neutrality, and compliance with platform standards before approving.
                  </p>

                  {tutor.avatar_url ? (
                    <Button
                      size="sm"
                      variant="outline"
                      className="h-8 text-xs font-semibold gap-1.5 border-slate-300 bg-white hover:bg-slate-100 text-blue-600 dark:border-slate-700 dark:bg-slate-900 dark:text-blue-400 dark:hover:bg-slate-800"
                      onClick={() => setIsPhotoZoomOpen(true)}
                    >
                      <Maximize2 className="h-3.5 w-3.5" />
                      Inspect High-Res Lightbox
                    </Button>
                  ) : (
                    <div className="rounded-lg bg-amber-100/60 p-2 text-[11px] font-medium text-amber-900 dark:bg-amber-950/40 dark:text-amber-300">
                      Candidate has not uploaded a portrait image.
                    </div>
                  )}
                </div>
              </div>
            </div>

            {/* Profile Info Card */}
            <div className="flex items-start gap-4 rounded-xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
              <div className="flex-1 space-y-1.5 text-xs">
                <div className="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                  <Mail className="h-3.5 w-3.5 text-slate-400" />
                  <span className="font-medium">{tutor.email}</span>
                </div>
                <div className="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                  <Phone className="h-3.5 w-3.5 text-slate-400" />
                  <span className="font-medium">{tutor.phone}</span>
                </div>
                <div className="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                  <MapPin className="h-3.5 w-3.5 text-slate-400" />
                  <span>
                    {tutor.city} • <span className="capitalize">{tutor.gender}</span>
                  </span>
                </div>
              </div>
            </div>

            {/* Academic Coverage */}
            <div className="space-y-3">
              <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                Teaching Credentials & Coverage
              </h4>

              <div className="space-y-2 text-xs">
                <div>
                  <span className="font-semibold text-slate-700 dark:text-slate-300">
                    Target Grades:
                  </span>
                  <div className="mt-1 flex flex-wrap gap-1.5">
                    {tutor.grades.map((grade) => (
                      <Badge key={grade} variant="secondary" className="text-xs">
                        <GraduationCap className="mr-1 h-3 w-3" />
                        {grade}
                      </Badge>
                    ))}
                  </div>
                </div>

                <div className="pt-1">
                  <span className="font-semibold text-slate-700 dark:text-slate-300">
                    Assigned Subjects:
                  </span>
                  <div className="mt-1 flex flex-wrap gap-1.5">
                    {tutor.subjects.map((sub) => (
                      <Badge
                        key={sub}
                        variant="outline"
                        className="border-blue-200 bg-blue-50/50 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-300"
                      >
                        <BookOpen className="mr-1 h-3 w-3" />
                        {sub}
                      </Badge>
                    ))}
                  </div>
                </div>
              </div>
            </div>

            {/* Bio statement */}
            {tutor.bio && (
              <div className="space-y-1.5 text-xs">
                <h4 className="font-bold uppercase tracking-wider text-slate-500">
                  Professional Bio
                </h4>
                <p className="rounded-lg border border-slate-100 bg-white p-3 text-slate-600 leading-relaxed dark:border-slate-800 dark:bg-slate-900/40 dark:text-slate-400">
                  {tutor.bio}
                </p>
              </div>
            )}

            {/* Verification Documents */}
            <div className="space-y-2">
              <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                Submitted Credentials ({tutor.documents.length})
              </h4>
              {tutor.documents.length === 0 ? (
                <p className="text-xs italic text-slate-400">
                  No electronic documents uploaded.
                </p>
              ) : (
                <div className="space-y-1.5">
                  {tutor.documents.map((doc, i) => (
                    <div
                      key={i}
                      className="flex items-center justify-between rounded-lg border border-slate-200 p-2.5 text-xs transition hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900"
                    >
                      <div className="flex items-center gap-2">
                        <FileText className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        <div>
                          <p className="font-semibold text-slate-900 dark:text-slate-100">
                            {doc.name}
                          </p>
                          <p className="text-[10px] text-slate-500">{doc.type}</p>
                        </div>
                      </div>
                      <Button
                        size="sm"
                        variant="ghost"
                        className="h-7 text-xs text-blue-600"
                        onClick={() =>
                          window.alert(`Opening document preview for ${doc.name}`)
                        }
                      >
                        Inspect <ExternalLink className="ml-1 h-3 w-3" />
                      </Button>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Prior Rejection Alert */}
            {tutor.status === "rejected" && tutor.rejection_reason && (
              <div className="rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
                <span className="font-bold flex items-center gap-1.5 mb-1 text-red-900 dark:text-red-200">
                  <AlertTriangle className="h-3.5 w-3.5" /> Rejection Record:
                </span>
                <p className="font-semibold">{tutor.rejection_reason}</p>
              </div>
            )}

            <Separator />

            {/* 2. STREAMLINED MODERATOR DECISIONS */}
            <div className="space-y-3">
              <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                Moderator Decisions
              </h4>

              <div className="grid grid-cols-2 gap-2.5">
                <Button
                  size="sm"
                  onClick={handleApprove}
                  disabled={isProcessing}
                  className="bg-emerald-600 font-semibold text-white shadow-md shadow-emerald-600/20 hover:bg-emerald-500"
                >
                  <CheckCircle className="mr-1.5 h-4 w-4" />
                  Approve Tutor
                </Button>

                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => setIsRejectDialogOpen(true)}
                  disabled={isProcessing}
                  className="border-red-200 text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950/50"
                >
                  <XCircle className="mr-1.5 h-4 w-4" />
                  Reject Application
                </Button>
              </div>
            </div>

            <Separator />

            {/* Administrative Overrides */}
            <div className="space-y-2 pt-1">
              <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1">
                <ShieldAlert className="h-3.5 w-3.5 text-red-500" />
                Administrative Overrides
              </h4>

              <div className="flex flex-col gap-2">
                <Button
                  size="sm"
                  variant="ghost"
                  onClick={() => {
                    if (confirm(`Permanently delete tutor profile for ${tutor.name}?`)) {
                      onDelete(tutor.id)
                      onOpenChange(false)
                    }
                  }}
                  className="justify-start text-xs text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/50"
                >
                  <Trash2 className="mr-2 h-3.5 w-3.5" />
                  Permanently Delete Record
                </Button>
              </div>
            </div>
          </div>
        </SheetContent>
      </Sheet>

      {/* 3. LIGHTBOX / PHOTO ZOOM MODAL */}
      {tutor.avatar_url && (
        <Dialog open={isPhotoZoomOpen} onOpenChange={setIsPhotoZoomOpen}>
          <DialogContent className="sm:max-w-2xl p-0 overflow-hidden bg-slate-950 border-slate-800 text-white">
            <div className="p-4 border-b border-slate-800 flex items-center justify-between">
              <div>
                <DialogTitle className="text-base font-bold text-white flex items-center gap-2">
                  <Camera className="h-4 w-4 text-blue-400" />
                  Profile Photo Inspector — {tutor.name}
                </DialogTitle>
                <DialogDescription className="text-xs text-slate-400">
                  Inspect high-resolution photo clarity, framing, and compliance with platform guidelines.
                </DialogDescription>
              </div>
            </div>

            <div className="flex items-center justify-center p-6 bg-black/90">
              <img
                src={tutor.avatar_url}
                alt={tutor.name}
                className="max-h-[65vh] w-auto max-w-full rounded-xl object-contain shadow-2xl border border-slate-800"
              />
            </div>

            <div className="p-3.5 bg-slate-900 border-t border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs text-slate-400">
              <span>Guideline: Must be a clear, authentic headshot with good lighting.</span>
              <div className="flex items-center gap-2">
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => {
                    setIsPhotoZoomOpen(false)
                    setIsRejectDialogOpen(true)
                  }}
                  className="h-7 text-xs border-red-500/40 text-red-400 hover:bg-red-950/50"
                >
                  <XCircle className="h-3.5 w-3.5 mr-1" />
                  Reject (Inappropriate Photo)
                </Button>
                <Button
                  size="sm"
                  variant="ghost"
                  onClick={() => setIsPhotoZoomOpen(false)}
                  className="h-7 text-xs text-slate-300 hover:text-white"
                >
                  Close Lightbox
                </Button>
              </div>
            </div>
          </DialogContent>
        </Dialog>
      )}

      {/* 4. DEDICATED REJECTION CONFIRMATION MODAL */}
      <Dialog open={isRejectDialogOpen} onOpenChange={setIsRejectDialogOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2 text-base font-bold text-rose-600">
              <XCircle className="h-5 w-5" />
              Reject Tutor Application
            </DialogTitle>
            <DialogDescription className="text-xs text-slate-500">
              Confirm rejection for applicant <strong>{tutor.name}</strong>.
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-3.5 py-2 text-xs">
            <div>
              <label className="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                Stated Rejection Reason (Locked Preset)
              </label>
              <div className="mt-1 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 p-2.5 font-bold text-red-900 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200">
                <AlertTriangle className="h-4 w-4 text-red-600 shrink-0" />
                <span>{DEDICATED_REJECTION_REASON}</span>
              </div>
            </div>

            <div>
              <label className="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                Feedback Message Sent to Tutor
              </label>
              <div className="mt-1 rounded-lg border border-slate-200 bg-slate-50 p-3 leading-relaxed text-slate-700 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-300 italic">
                &quot;{REJECTION_FEEDBACK_MESSAGE}&quot;
              </div>
            </div>

            <div className="rounded-lg bg-slate-100 p-2.5 text-[11px] text-slate-500 dark:bg-slate-900 dark:text-slate-400">
              The tutor will receive an automated notification prompting them to re-upload a compliant headshot.
            </div>
          </div>

          <DialogFooter className="gap-2 pt-2 sm:space-x-0">
            <Button
              type="button"
              variant="outline"
              onClick={() => setIsRejectDialogOpen(false)}
              disabled={isProcessing}
            >
              Cancel
            </Button>
            <Button
              type="button"
              variant="destructive"
              className="font-semibold shadow-md shadow-red-600/20"
              onClick={handleConfirmRejection}
              disabled={isProcessing}
            >
              <XCircle className="mr-1.5 h-4 w-4" />
              Confirm Rejection (Inappropriate Picture)
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  )
}
