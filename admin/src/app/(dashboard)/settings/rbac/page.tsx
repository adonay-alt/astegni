"use client"

import React, { useState, useEffect, useCallback } from "react"
import { apiClient } from "@/lib/api-client"
import { useAuth } from "@/context/auth-context"
import { StaffMember, RoleDefinition, RolePermission, StaffApiResponse } from "@/types/settings"
import { AdminRole } from "@/types/auth"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import {
  ShieldCheck,
  UserPlus,
  UserCheck,
  UserMinus,
  Mail,
  CheckCircle2,
  XCircle,
  Clock,
  Shield,
  Search,
  AlertCircle,
  Loader2,
  Users,
  BookOpen,
  RefreshCw,
  MoreVertical,
  UserX,
  KeyRound,
  GraduationCap,
  ShieldAlert,
  Sparkles,
  Server,
  Edit,
  Trash2,
} from "lucide-react"

// Canonical role definitions derived strictly from backend architecture
const BACKEND_DEFAULT_ROLES: RoleDefinition[] = [
  {
    role: "super_admin",
    name: "Super Admin",
    description: "Unrestricted access to all modules, audit trails, and RBAC management.",
    color: "rose",
    is_staff: true,
  },
  {
    role: "academic_editor",
    name: "Academic Editor",
    description: "Curriculum LMS (Grades, Subjects, Chapters, Notes, Quizzes, Media) and National Exams.",
    color: "blue",
    is_staff: true,
  },
  {
    role: "tutor_moderator",
    name: "Tutor Moderator",
    description: "Tutor verification queue, dossier review, and credential approval.",
    color: "amber",
    is_staff: true,
  },
  {
    role: "support_moderator",
    name: "Support Moderator",
    description: "Support inquiries, official reply dispatch, and platform contact info settings.",
    color: "emerald",
    is_staff: true,
  },
]

// Authoritative RBAC activity and permission definitions
export const RBAC_ACTIVITIES: RolePermission[] = [
  {
    module: "Support Inquiries & Official Reply Dispatch",
    description: "Triage incoming student inquiries, manage ticket status, and dispatch authenticated email replies",
    category: "Support",
    super_admin: true,
    academic_editor: false,
    tutor_moderator: false,
    support_moderator: true,
  },
  {
    module: "Platform Contact Info Settings",
    description: "Update official platform support email address, primary hotline, and secondary phone numbers",
    category: "Support",
    super_admin: true,
    academic_editor: false,
    tutor_moderator: false,
    support_moderator: true,
  },
  {
    module: "Tutor Application Verification & Dossiers",
    description: "Review teacher applications, verify degrees, and inspect national IDs",
    category: "Tutors",
    super_admin: true,
    academic_editor: false,
    tutor_moderator: true,
    support_moderator: false,
  },
  {
    module: "Platform Testimonials & Review Moderation",
    description: "Approve, flag, or reject public student testimonials for landing showcase",
    category: "Marketing",
    super_admin: true,
    academic_editor: false,
    tutor_moderator: false,
    support_moderator: false,
  },
  {
    module: "LMS Curriculum, Grades & Chapters Management",
    description: "Create and update grades, subjects, chapters, and curriculum syllabus hierarchy",
    category: "Curriculum",
    super_admin: true,
    academic_editor: true,
    tutor_moderator: false,
    support_moderator: false,
  },
  {
    module: "Chapter Media Assets (Textbooks, PDFs, Videos)",
    description: "Upload and attach textbook PDFs, resources, and video lectures to chapters",
    category: "Curriculum",
    super_admin: true,
    academic_editor: true,
    tutor_moderator: false,
    support_moderator: false,
  },
  {
    module: "AI Notes Synthesis & Quizzes Question Studio",
    description: "Trigger AI synthesis notes generation and craft chapter quizzes and practice problems",
    category: "Curriculum",
    super_admin: true,
    academic_editor: true,
    tutor_moderator: false,
    support_moderator: false,
  },
  {
    module: "National Exam Question Bank (Years & Subjects)",
    description: "Maintain past entrance exam repositories across years and subject disciplines",
    category: "Exams",
    super_admin: true,
    academic_editor: true,
    tutor_moderator: false,
    support_moderator: false,
  },
  {
    module: "Bulk Questions Batch Import (JSON/CSV)",
    description: "Bulk ingest hundreds of standardized questions with answer keys from external files",
    category: "Exams",
    super_admin: true,
    academic_editor: true,
    tutor_moderator: false,
    support_moderator: false,
  },
  {
    module: "CMS Static Pages, Terms & Policy Editor",
    description: "Author and modify Terms of Service, Privacy Policy, and FAQ content",
    category: "CMS & Content",
    super_admin: true,
    academic_editor: false,
    tutor_moderator: false,
    support_moderator: false,
  },
  {
    module: "Platform Latest Updates & Announcements Posting",
    description: "Publish, toggle, and manage live banner alerts and public changelogs",
    category: "CMS & Content",
    super_admin: true,
    academic_editor: false,
    tutor_moderator: false,
    support_moderator: false,
  },
  {
    module: "Immutable Operational Audit Trail",
    description: "Read-only access to chronological security logs tracking all administrative actions",
    category: "Security",
    super_admin: true,
    academic_editor: false,
    tutor_moderator: false,
    support_moderator: false,
  },
  {
    module: "Staff Directory & RBAC Access Management",
    description: "Invite new staff, change roles, elevate accounts, and revoke administrator access",
    category: "IAM",
    super_admin: true,
    academic_editor: false,
    tutor_moderator: false,
    support_moderator: false,
  },
]

interface LookupUserData {
  id: number
  name: string
  email: string
  role: string
  is_admin: boolean
  created_at: string
  student_profile?: Record<string, unknown> | null
  tutor_profile?: Record<string, unknown> | null
}

export default function RbacSettingsPage() {
  const { user: currentAdmin } = useAuth()

  // Real backend state - initialized with canonical RBAC definitions
  const [staffList, setStaffList] = useState<StaffMember[]>([])
  const [backendRoles, setBackendRoles] = useState<RoleDefinition[]>(BACKEND_DEFAULT_ROLES)
  const [permissionsMatrix, setPermissionsMatrix] = useState<RolePermission[]>(RBAC_ACTIVITIES)
  const [kpis, setKpis] = useState({
    total_staff: 0,
    super_admins: 0,
    academic_editors: 0,
    tutor_moderators: 0,
    support_moderators: 0,
  })

  // Loading & Error States
  const [isLoading, setIsLoading] = useState(true)
  const [isRefreshing, setIsRefreshing] = useState(false)
  const [fetchError, setFetchError] = useState<string | null>(null)

  // Filters
  const [searchQuery, setSearchQuery] = useState("")
  const [roleFilter, setRoleFilter] = useState<string>("all")
  const [matrixSearch, setMatrixSearch] = useState("")
  const [matrixCategoryFilter, setMatrixCategoryFilter] = useState<string>("all")

  // Feedback Toast
  const [toast, setToast] = useState<{ type: "success" | "error"; message: string } | null>(null)
  const showToast = (type: "success" | "error", message: string) => {
    setToast({ type, message })
    setTimeout(() => {
      setToast((prev) => (prev?.message === message ? null : prev))
    }, 4500)
  }

  // Modal 1: Invite / Create Staff State
  const [isInviteOpen, setIsInviteOpen] = useState(false)
  const [inviteName, setInviteName] = useState("")
  const [inviteEmail, setInviteEmail] = useState("")
  const [invitePassword, setInvitePassword] = useState("")
  const [inviteRole, setInviteRole] = useState<AdminRole>("academic_editor")
  const [inviteStatus, setInviteStatus] = useState<"active" | "inactive">("active")
  const [isInviting, setIsInviting] = useState(false)
  const [formError, setFormError] = useState<string | null>(null)

  // Modal 5: Edit Staff Member State
  const [selectedStaffForEdit, setSelectedStaffForEdit] = useState<StaffMember | null>(null)
  const [editFirstName, setEditFirstName] = useState("")
  const [editLastName, setEditLastName] = useState("")
  const [editEmail, setEditEmail] = useState("")
  const [editRole, setEditRole] = useState<AdminRole>("academic_editor")
  const [editStatus, setEditStatus] = useState<"active" | "inactive">("active")
  const [editPassword, setEditPassword] = useState("")
  const [isUpdatingStaff, setIsUpdatingStaff] = useState(false)
  const [editFormError, setEditFormError] = useState<string | null>(null)

  // Modal 6: Delete Staff Member State
  const [selectedStaffForDelete, setSelectedStaffForDelete] = useState<StaffMember | null>(null)
  const [isDeletingStaff, setIsDeletingStaff] = useState(false)

  // Account Status Quick Toggle State
  const [togglingStatusId, setTogglingStatusId] = useState<number | null>(null)

  // Modal 2: Promote Existing User State
  const [isPromoteOpen, setIsPromoteOpen] = useState(false)
  const [lookupEmail, setLookupEmail] = useState("")
  const [isLookingUp, setIsLookingUp] = useState(false)
  const [lookupResult, setLookupResult] = useState<LookupUserData | null>(null)
  const [lookupError, setLookupError] = useState<string | null>(null)
  const [promoteRole, setPromoteRole] = useState<AdminRole>("academic_editor")
  const [isPromoting, setIsPromoting] = useState(false)

  // Modal 3: Change Staff Role State
  const [selectedStaffForRole, setSelectedStaffForRole] = useState<StaffMember | null>(null)
  const [targetRole, setTargetRole] = useState<AdminRole>("academic_editor")
  const [isUpdatingRole, setIsUpdatingRole] = useState(false)

  // Modal 4: Revoke Staff Access State
  const [selectedStaffForRevoke, setSelectedStaffForRevoke] = useState<StaffMember | null>(null)
  const [isRevoking, setIsRevoking] = useState(false)

  // Fetch actual data from backend API
  const fetchStaffData = useCallback(async (isRefresh = false) => {
    if (isRefresh) {
      setIsRefreshing(true)
    } else {
      setIsLoading(true)
    }
    setFetchError(null)

    try {
      const res = await apiClient.get<StaffApiResponse>("/admin/staff")
      if (res.data?.status === "success") {
        const rawStaff = Array.isArray(res.data.data) ? res.data.data : []
        setStaffList(rawStaff)

        if (Array.isArray(res.data.roles) && res.data.roles.length > 0) {
          setBackendRoles(res.data.roles)
        }

        if (Array.isArray(res.data.permissions_matrix)) {
          setPermissionsMatrix(res.data.permissions_matrix)
        }

        if (res.data.kpis) {
          setKpis({
            total_staff: res.data.kpis.total_staff,
            super_admins: res.data.kpis.super_admins,
            academic_editors: res.data.kpis.academic_editors,
            tutor_moderators: res.data.kpis.tutor_moderators,
            support_moderators:
              res.data.kpis.support_moderators ??
              rawStaff.filter((s) => s.role === "support_moderator" || s.role === "support_agent").length,
          })
        } else {
          setKpis({
            total_staff: rawStaff.length,
            super_admins: rawStaff.filter((s) => s.role === "super_admin").length,
            academic_editors: rawStaff.filter((s) => s.role === "academic_editor").length,
            tutor_moderators: rawStaff.filter((s) => s.role === "tutor_moderator").length,
            support_moderators: rawStaff.filter(
              (s) => s.role === "support_moderator" || s.role === "support_agent"
            ).length,
          })
        }
      } else {
        setFetchError(res.data?.message || "Failed to load staff list from backend.")
      }
    } catch (err: any) {
      const errorMsg =
        err.response?.data?.message ||
        err.message ||
        "Could not connect to backend staff API. Please verify server connectivity."
      setFetchError(errorMsg)
      setStaffList([])
    } finally {
      setIsLoading(false)
      setIsRefreshing(false)
    }
  }, [])

  useEffect(() => {
    fetchStaffData()
  }, [fetchStaffData])

  // Handle Lookup User
  const handleLookupUser = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!lookupEmail.trim()) return

    setIsLookingUp(true)
    setLookupError(null)
    setLookupResult(null)

    try {
      const res = await apiClient.get(`/admin/staff/lookup-user?email=${encodeURIComponent(lookupEmail.trim())}`)
      if (res.data?.status === "success" && res.data?.data) {
        setLookupResult(res.data.data)
      } else {
        setLookupError(res.data?.message || "User not found in system.")
      }
    } catch (err: any) {
      setLookupError(err.response?.data?.message || "No registered user found with that email address.")
    } finally {
      setIsLookingUp(false)
    }
  }

  // Handle Promote User
  const handlePromoteUser = async () => {
    if (!lookupResult) return

    setIsPromoting(true)
    try {
      const res = await apiClient.post("/admin/staff/promote", {
        user_id: lookupResult.id,
        role: promoteRole,
      })

      if (res.data?.status === "success") {
        showToast("success", `Successfully elevated ${lookupResult.name} to ${formatRoleName(promoteRole)}!`)
        setIsPromoteOpen(false)
        setLookupResult(null)
        setLookupEmail("")
        await fetchStaffData(true)
      } else {
        showToast("error", res.data?.message || "Failed to promote user.")
      }
    } catch (err: any) {
      showToast("error", err.response?.data?.message || "Failed to promote user to staff role.")
    } finally {
      setIsPromoting(false)
    }
  }

  // Handle Invite / Create Staff
  const handleCreateStaff = async (e: React.FormEvent) => {
    e.preventDefault()
    setFormError(null)

    if (!inviteName.trim() || !inviteEmail.trim() || !invitePassword.trim()) {
      setFormError("Please fill in all required fields including a password.")
      return
    }

    setIsInviting(true)
    try {
      const payload = {
        name: inviteName.trim(),
        email: inviteEmail.trim().toLowerCase(),
        password: invitePassword.trim(),
        role: inviteRole,
        is_active: inviteStatus === "active",
      }

      const res = await apiClient.post("/admin/staff", payload)

      if (res.data?.status === "success") {
        const newStaff = res.data.data
        if (newStaff) {
          setStaffList((prev) => [newStaff, ...prev])
        }
        showToast("success", `Staff member ${inviteName} created successfully with role ${formatRoleName(inviteRole)}!`)
        setIsInviteOpen(false)
        setInviteName("")
        setInviteEmail("")
        setInvitePassword("")
        setInviteRole("academic_editor")
        setInviteStatus("active")
        setFormError(null)
        await fetchStaffData(true)
      } else {
        setFormError(res.data?.message || "Failed to create staff member.")
      }
    } catch (err: any) {
      console.error("Failed to create staff member:", err)
      const errorMsg =
        err.response?.data?.errors?.email?.[0] ||
        err.response?.data?.errors?.password?.[0] ||
        err.response?.data?.message ||
        err.message ||
        "Failed to create administrative staff member."
      setFormError(errorMsg)
    } finally {
      setIsInviting(false)
    }
  }

  // Open Edit Modal with prefilled values
  const openEditModal = (staff: StaffMember) => {
    setSelectedStaffForEdit(staff)
    let fName = staff.first_name || ""
    let lName = staff.last_name || ""
    if (!fName && !lName && staff.name) {
      const parts = staff.name.trim().split(" ")
      fName = parts[0] || ""
      lName = parts.slice(1).join(" ")
    }
    setEditFirstName(fName)
    setEditLastName(lName)
    setEditEmail(staff.email)
    setEditRole(staff.role as AdminRole)
    const isCurrentlyActive = staff.is_active !== false && staff.status !== "inactive"
    setEditStatus(isCurrentlyActive ? "active" : "inactive")
    setEditPassword("")
    setEditFormError(null)
  }

  // Handle Update Staff Profile & Status
  const handleUpdateStaff = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!selectedStaffForEdit) return
    setEditFormError(null)

    const isSelf = currentAdmin?.id === selectedStaffForEdit.id
    const isOnlySuperAdmin =
      selectedStaffForEdit.role === "super_admin" &&
      staffList.filter((s) => s.role === "super_admin").length <= 1

    if (editStatus === "inactive" && isSelf) {
      setEditFormError("You cannot deactivate your own administrative account.")
      return
    }

    if (editStatus === "inactive" && isOnlySuperAdmin) {
      setEditFormError("Cannot deactivate the only remaining Super Admin on the platform.")
      return
    }

    if (isOnlySuperAdmin && editRole !== "super_admin") {
      setEditFormError("Cannot demote the only remaining Super Admin on the platform.")
      return
    }

    setIsUpdatingStaff(true)
    try {
      const fullName = `${editFirstName.trim()} ${editLastName.trim()}`.trim() || editFirstName.trim()
      const payload: Record<string, any> = {
        first_name: editFirstName.trim(),
        last_name: editLastName.trim(),
        name: fullName,
        email: editEmail.trim().toLowerCase(),
        role: editRole,
        is_active: editStatus === "active",
      }

      if (editPassword.trim()) {
        payload.password = editPassword.trim()
      }

      const res = await apiClient.put(`/admin/staff/${selectedStaffForEdit.id}`, payload)

      if (res.data?.status === "success") {
        showToast("success", res.data?.message || `Staff account for ${fullName} updated successfully.`)
        setSelectedStaffForEdit(null)
        await fetchStaffData(true)
      } else {
        setEditFormError(res.data?.message || "Failed to update staff account.")
      }
    } catch (err: any) {
      console.error("Failed to update staff:", err)
      const errorMsg =
        err.response?.data?.errors?.email?.[0] ||
        err.response?.data?.errors?.password?.[0] ||
        err.response?.data?.message ||
        err.message ||
        "Failed to update administrative staff account."
      setEditFormError(errorMsg)
    } finally {
      setIsUpdatingStaff(false)
    }
  }

  // Handle Quick Toggle Status (Active <-> Inactive)
  const handleToggleStatus = async (staff: StaffMember) => {
    const isSelf = currentAdmin?.id === staff.id
    const isCurrentActive = staff.is_active !== false && staff.status !== "inactive"
    const isOnlySuperAdmin =
      staff.role === "super_admin" &&
      staffList.filter((s) => s.role === "super_admin").length <= 1

    if (isSelf && isCurrentActive) {
      showToast("error", "You cannot deactivate your own account.")
      return
    }

    if (isOnlySuperAdmin && isCurrentActive) {
      showToast("error", "Cannot deactivate the only remaining Super Admin on the platform.")
      return
    }

    setTogglingStatusId(staff.id)
    try {
      const newStatusBool = !isCurrentActive
      const res = await apiClient.patch(`/admin/staff/${staff.id}/status`, {
        is_active: newStatusBool,
        status: newStatusBool ? "active" : "inactive",
      })

      if (res.data?.status === "success") {
        showToast(
          "success",
          res.data?.message || `Account for ${staff.name} set to ${newStatusBool ? "ACTIVE" : "INACTIVE"}.`
        )
        // Optimistically update local state
        setStaffList((prev) =>
          prev.map((s) =>
            s.id === staff.id
              ? { ...s, is_active: newStatusBool, status: newStatusBool ? "active" : "inactive" }
              : s
          )
        )
        await fetchStaffData(true)
      } else {
        showToast("error", res.data?.message || "Failed to update account status.")
      }
    } catch (err: any) {
      showToast("error", err.response?.data?.message || "Failed to change account status.")
    } finally {
      setTogglingStatusId(null)
    }
  }

  // Handle Delete Staff Account
  const handleDeleteStaff = async () => {
    if (!selectedStaffForDelete) return

    const isSelf = currentAdmin?.id === selectedStaffForDelete.id
    const isOnlySuperAdmin =
      selectedStaffForDelete.role === "super_admin" &&
      staffList.filter((s) => s.role === "super_admin").length <= 1

    if (isSelf) {
      showToast("error", "You cannot delete your own account.")
      return
    }

    if (isOnlySuperAdmin) {
      showToast("error", "Cannot delete the only remaining Super Admin on the platform.")
      return
    }

    setIsDeletingStaff(true)
    try {
      const res = await apiClient.delete(`/admin/staff/${selectedStaffForDelete.id}`)

      if (res.data?.status === "success") {
        showToast(
          "success",
          res.data?.message || `Staff account for ${selectedStaffForDelete.name} permanently deleted.`
        )
        setSelectedStaffForDelete(null)
        await fetchStaffData(true)
      } else {
        showToast("error", res.data?.message || "Failed to delete staff member.")
      }
    } catch (err: any) {
      showToast("error", err.response?.data?.message || "Failed to delete staff member.")
    } finally {
      setIsDeletingStaff(false)
    }
  }

  // Handle Update Role
  const handleUpdateRole = async () => {
    if (!selectedStaffForRole) return

    setIsUpdatingRole(true)
    try {
      const res = await apiClient.put(`/admin/staff/${selectedStaffForRole.id}/role`, {
        role: targetRole,
      })

      if (res.data?.status === "success") {
        showToast("success", `Updated role for ${selectedStaffForRole.name} to ${formatRoleName(targetRole)}.`)
        setSelectedStaffForRole(null)
        await fetchStaffData(true)
      } else {
        showToast("error", res.data?.message || "Failed to update role.")
      }
    } catch (err: any) {
      showToast("error", err.response?.data?.message || "Failed to update staff member's role.")
    } finally {
      setIsUpdatingRole(false)
    }
  }

  // Handle Revoke Access
  const handleRevokeAccess = async () => {
    if (!selectedStaffForRevoke) return

    setIsRevoking(true)
    try {
      const res = await apiClient.post(`/admin/staff/${selectedStaffForRevoke.id}/revoke`)

      if (res.data?.status === "success") {
        showToast(
          "success",
          `Privileges revoked for ${selectedStaffForRevoke.name}. Demoted to student and active tokens invalidated.`
        )
        setSelectedStaffForRevoke(null)
        await fetchStaffData(true)
      } else {
        showToast("error", res.data?.message || "Failed to revoke privileges.")
      }
    } catch (err: any) {
      showToast("error", err.response?.data?.message || "Failed to revoke administrative privileges.")
    } finally {
      setIsRevoking(false)
    }
  }

  // Helpers
  const formatRoleName = (role: string) => {
    switch (role) {
      case "super_admin":
        return "Super Admin"
      case "academic_editor":
        return "Academic Editor"
      case "tutor_moderator":
        return "Tutor Moderator"
      case "support_moderator":
      case "support_agent":
        return "Support Moderator"
      default:
        return role.replace(/_/g, " ").replace(/\b\w/g, (l) => l.toUpperCase())
    }
  }

  const getRoleBadge = (role: string) => {
    switch (role) {
      case "super_admin":
        return (
          <Badge className="inline-flex items-center gap-1 bg-rose-500/15 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900 font-semibold text-xs">
            <ShieldAlert className="h-3 w-3" />
            Super Admin
          </Badge>
        )
      case "academic_editor":
        return (
          <Badge className="inline-flex items-center gap-1 bg-blue-500/15 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-900 font-semibold text-xs">
            <BookOpen className="h-3 w-3" />
            Academic Editor
          </Badge>
        )
      case "tutor_moderator":
        return (
          <Badge className="inline-flex items-center gap-1 bg-amber-500/15 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900 font-semibold text-xs">
            <UserCheck className="h-3 w-3" />
            Tutor Moderator
          </Badge>
        )
      case "support_moderator":
      case "support_agent":
        return (
          <Badge className="inline-flex items-center gap-1 border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-semibold text-xs">
            🎧 Support Moderator
          </Badge>
        )
      default:
        return (
          <Badge variant="secondary" className="font-semibold text-xs">
            {formatRoleName(role)}
          </Badge>
        )
    }
  }

  // Filter staff directory
  const filteredStaff = staffList.filter((staff) => {
    const matchesSearch =
      staff.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      staff.email.toLowerCase().includes(searchQuery.toLowerCase()) ||
      staff.role.toLowerCase().includes(searchQuery.toLowerCase())
    const matchesRole = roleFilter === "all" || staff.role === roleFilter
    return matchesSearch && matchesRole
  })

  // Filter permission matrix
  const categories = Array.from(new Set(permissionsMatrix.map((p) => p.category || "General")))
  const filteredMatrix = permissionsMatrix.filter((item) => {
    const matchesQuery = item.module.toLowerCase().includes(matrixSearch.toLowerCase())
    const matchesCategory =
      matrixCategoryFilter === "all" || (item.category || "General") === matrixCategoryFilter
    return matchesQuery && matchesCategory
  })

  return (
    <div className="space-y-8 pb-12">
      {/* Feedback Toast Notification */}
      {toast && (
        <div
          className={`fixed top-5 right-5 z-50 flex items-center gap-3 rounded-lg border px-4 py-3 shadow-lg transition-all animate-in slide-in-from-top-2 max-w-md ${
            toast.type === "success"
              ? "border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/90 dark:text-emerald-200"
              : "border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900 dark:bg-rose-950/90 dark:text-rose-200"
          }`}
        >
          {toast.type === "success" ? (
            <CheckCircle2 className="h-5 w-5 text-emerald-600 flex-shrink-0" />
          ) : (
            <AlertCircle className="h-5 w-5 text-rose-600 flex-shrink-0" />
          )}
          <div className="text-xs font-medium leading-relaxed">{toast.message}</div>
          <button
            type="button"
            onClick={() => setToast(null)}
            className="ml-auto text-xs font-bold opacity-60 hover:opacity-100"
          >
            ✕
          </button>
        </div>
      )}

      {/* Header Banner */}
      <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 sm:text-3xl">
            Staff Directory & Role-Based Permissions
          </h1>
          <p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
            Operational personnel and authorization matrix backed directly by Laravel Sanctum gates and RoleMiddleware.
          </p>
        </div>

        {/* Action Buttons */}
        <div className="flex flex-wrap items-center gap-2.5">
          <Button
            variant="outline"
            size="sm"
            onClick={() => fetchStaffData(true)}
            disabled={isRefreshing}
            className="border-slate-200 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900"
          >
            <RefreshCw className={`mr-2 h-3.5 w-3.5 ${isRefreshing ? "animate-spin text-blue-600" : ""}`} />
            Refresh
          </Button>

          {/* Promote Existing User Modal Trigger */}
          <Button
            variant="outline"
            className="border-blue-200 bg-blue-50/50 font-semibold text-blue-700 shadow-sm hover:bg-blue-100/70 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300 dark:hover:bg-blue-900/50"
            onClick={() => {
              setLookupEmail("")
              setLookupResult(null)
              setLookupError(null)
              setPromoteRole("academic_editor")
              setIsPromoteOpen(true)
            }}
          >
            <UserCheck className="mr-2 h-4 w-4" />
            Promote Existing User
          </Button>

          {/* Invite / Create Staff Modal Trigger */}
          <Button
            className="bg-blue-600 font-semibold text-white shadow-md shadow-blue-600/20 hover:bg-blue-500"
            onClick={() => {
              setInviteName("")
              setInviteEmail("")
              setInvitePassword("")
              setInviteRole("academic_editor")
              setFormError(null)
              setIsInviteOpen(true)
            }}
          >
            <UserPlus className="mr-2 h-4 w-4" />
            Invite / Create Staff
          </Button>
        </div>
      </div>

      {/* Error Alert Box (if backend API failed) */}
      {fetchError && (
        <div className="flex items-center justify-between gap-3 rounded-lg border border-rose-200 bg-rose-50 p-4 text-xs font-medium text-rose-900 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-200">
          <div className="flex items-center gap-2">
            <AlertCircle className="h-5 w-5 text-rose-600 flex-shrink-0" />
            <div>
              <span className="font-bold">Backend Connection Issue:</span> {fetchError}
            </div>
          </div>
          <Button
            size="sm"
            variant="outline"
            onClick={() => fetchStaffData(false)}
            className="border-rose-300 text-rose-800 hover:bg-rose-100 dark:border-rose-800 dark:text-rose-200"
          >
            Retry Connection
          </Button>
        </div>
      )}

      {/* KPI Counters Banner (5 cards: Total Staff, Super Admins, Academic Editors, Tutor Moderators, Support Moderators) */}
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-5">
        <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <span className="text-xs font-medium text-slate-500 dark:text-slate-400">Total Staff</span>
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                <Users className="h-4 w-4" />
              </div>
            </div>
            <div className="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-50">
              {isLoading ? "..." : kpis.total_staff}
            </div>
            <p className="mt-0.5 text-[11px] text-slate-400">Live database count</p>
          </CardContent>
        </Card>

        <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <span className="text-xs font-medium text-slate-500 dark:text-slate-400">Super Admins</span>
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                <ShieldAlert className="h-4 w-4" />
              </div>
            </div>
            <div className="mt-2 text-2xl font-bold text-rose-600 dark:text-rose-400">
              {isLoading ? "..." : kpis.super_admins}
            </div>
            <p className="mt-0.5 text-[11px] text-slate-400">Full system authority</p>
          </CardContent>
        </Card>

        <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <span className="text-xs font-medium text-slate-500 dark:text-slate-400">Academic Editors</span>
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                <BookOpen className="h-4 w-4" />
              </div>
            </div>
            <div className="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">
              {isLoading ? "..." : kpis.academic_editors}
            </div>
            <p className="mt-0.5 text-[11px] text-slate-400">Curriculum, Exams & Updates</p>
          </CardContent>
        </Card>

        <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <span className="text-xs font-medium text-slate-500 dark:text-slate-400">Tutor Moderators</span>
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                <UserCheck className="h-4 w-4" />
              </div>
            </div>
            <div className="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">
              {isLoading ? "..." : kpis.tutor_moderators}
            </div>
            <p className="mt-0.5 text-[11px] text-slate-400">Verification & Reviews</p>
          </CardContent>
        </Card>

        <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <span className="text-xs font-medium text-slate-500 dark:text-slate-400">Support Mods</span>
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                <Mail className="h-4 w-4" />
              </div>
            </div>
            <div className="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
              {isLoading ? "..." : kpis.support_moderators}
            </div>
            <p className="mt-0.5 text-[11px] text-slate-400">Inbox & Contact Info</p>
          </CardContent>
        </Card>
      </div>

      {/* Backend Role Architecture Blueprint */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
        {backendRoles.map((r) => (
          <div
            key={r.role}
            className="rounded-lg border border-slate-200 bg-white p-3.5 shadow-sm dark:border-slate-800 dark:bg-slate-950 flex flex-col justify-between"
          >
            <div>
              <div className="flex items-center justify-between">
                {getRoleBadge(r.role)}
                <span className="text-[10px] font-mono text-slate-400">
                  {r.count !== undefined ? `${r.count} assigned` : ""}
                </span>
              </div>
              <p className="mt-2 text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                {r.description}
              </p>
            </div>
          </div>
        ))}
      </div>

      {/* Staff Directory Table */}
      <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <CardHeader className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <CardTitle className="text-base font-bold text-slate-950 dark:text-slate-50 flex items-center gap-2">
              <Users className="h-4 w-4 text-blue-600" />
              Active Administrative Personnel
            </CardTitle>
            <CardDescription className="text-xs text-slate-500">
              Live personnel authorized in the backend database to access internal operations
            </CardDescription>
          </div>

          <div className="flex flex-col sm:flex-row items-center gap-2.5 w-full sm:w-auto">
            {/* Role Filter */}
            <select
              value={roleFilter}
              onChange={(e) => setRoleFilter(e.target.value)}
              className="w-full sm:w-44 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
            >
              <option value="all">All Roles ({staffList.length})</option>
              {backendRoles.map((r) => (
                <option key={r.role} value={r.role}>
                  {r.name}
                </option>
              ))}
            </select>

            {/* Search Input */}
            <div className="relative w-full sm:w-64">
              <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" />
              <Input
                placeholder="Search staff by name or email..."
                className="pl-8 text-xs h-8"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>
          </div>
        </CardHeader>

        <CardContent>
          {isLoading ? (
            <div className="flex flex-col items-center justify-center py-16 text-slate-400">
              <Loader2 className="h-8 w-8 animate-spin text-blue-600 mb-2" />
              <p className="text-xs font-medium">Synchronizing live staff directory with backend...</p>
            </div>
          ) : filteredStaff.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-16 text-center">
              <div className="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-3">
                <Users className="h-6 w-6" />
              </div>
              <h3 className="text-sm font-semibold text-slate-800 dark:text-slate-200">
                {staffList.length === 0 ? "No staff members registered in database" : "No matching staff members"}
              </h3>
              <p className="text-xs text-slate-500 max-w-sm mt-1">
                {staffList.length === 0
                  ? "Use the 'Invite / Create Staff' or 'Promote Existing User' buttons above to onboard administrative personnel."
                  : "No personnel match your search filters. Try clearing the search query or role filter."}
              </p>
            </div>
          ) : (
            <div className="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800">
              <table className="w-full text-left text-xs">
                <thead className="border-b border-slate-200 bg-slate-50 font-semibold uppercase text-slate-500 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-400">
                  <tr>
                    <th className="px-4 py-3">Staff Member</th>
                    <th className="px-4 py-3">Assigned Role</th>
                    <th className="px-4 py-3">Linked Accounts</th>
                    <th className="px-4 py-3">Account Status</th>
                    <th className="px-4 py-3">Last Active</th>
                    <th className="px-4 py-3 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 dark:divide-slate-800/80">
                  {filteredStaff.map((staff) => {
                    const isSelf = currentAdmin?.id === staff.id
                    const isOnlySuperAdmin =
                      staff.role === "super_admin" &&
                      staffList.filter((s) => s.role === "super_admin").length <= 1

                    return (
                      <tr
                        key={staff.id}
                        className="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-900/30"
                      >
                        {/* Member Identity */}
                        <td className="px-4 py-3.5">
                          <div className="flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                              {staff.name ? staff.name.slice(0, 2).toUpperCase() : "AD"}
                            </div>
                            <div>
                              <div className="flex items-center gap-1.5">
                                <span className="font-bold text-slate-900 dark:text-slate-100">
                                  {staff.name}
                                </span>
                                {isSelf && (
                                  <span className="rounded bg-blue-500/10 px-1.5 py-0.2 text-[10px] font-semibold text-blue-600">
                                    You
                                  </span>
                                )}
                              </div>
                              <div className="flex items-center gap-1 text-[11px] text-slate-500 dark:text-slate-400">
                                <Mail className="h-3 w-3" />
                                {staff.email}
                              </div>
                            </div>
                          </div>
                        </td>

                        {/* Assigned Role */}
                        <td className="px-4 py-3.5">{getRoleBadge(staff.role)}</td>

                        {/* Linked Accounts */}
                        <td className="px-4 py-3.5">
                          <div className="flex flex-wrap items-center gap-1">
                            {staff.tutor_profile ? (
                              <Badge
                                variant="outline"
                                className="border-purple-200 bg-purple-50 text-purple-700 text-[10px] dark:border-purple-900 dark:bg-purple-950/40 dark:text-purple-300"
                              >
                                <GraduationCap className="mr-1 h-3 w-3" />
                                Tutor Profile
                              </Badge>
                            ) : staff.student_profile ? (
                              <Badge
                                variant="outline"
                                className="border-sky-200 bg-sky-50 text-sky-700 text-[10px] dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-300"
                              >
                                Student
                              </Badge>
                            ) : (
                              <span className="text-[11px] text-slate-400 italic">Staff Only</span>
                            )}
                          </div>
                        </td>

                        {/* Status */}
                        <td className="px-4 py-3.5">
                          {(() => {
                            const isActive = staff.is_active !== false && staff.status !== "inactive"
                            const canToggle = !isSelf && (!isOnlySuperAdmin || !isActive)
                            const isCurrentToggling = togglingStatusId === staff.id

                            return (
                              <button
                                type="button"
                                disabled={!canToggle || isCurrentToggling}
                                onClick={() => handleToggleStatus(staff)}
                                title={
                                  isSelf
                                    ? "You cannot alter your own account status"
                                    : isOnlySuperAdmin && isActive
                                    ? "Cannot deactivate the only platform Super Admin"
                                    : `Click to set status to ${isActive ? "Inactive" : "Active"}`
                                }
                                className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase transition-all shadow-xs ${
                                  isActive
                                    ? "bg-emerald-500/15 text-emerald-700 border border-emerald-300/60 hover:bg-emerald-500/25 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800"
                                    : "bg-rose-500/15 text-rose-700 border border-rose-300/60 hover:bg-rose-500/25 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800"
                                } ${!canToggle ? "cursor-not-allowed opacity-80" : "cursor-pointer"}`}
                              >
                                {isCurrentToggling ? (
                                  <Loader2 className="h-3 w-3 animate-spin" />
                                ) : (
                                  <span
                                    className={`h-1.5 w-1.5 rounded-full ${
                                      isActive ? "bg-emerald-500 animate-pulse" : "bg-rose-500"
                                    }`}
                                  />
                                )}
                                {isActive ? "Active" : "Inactive"}
                              </button>
                            )
                          })()}
                        </td>

                        {/* Last Active */}
                        <td className="px-4 py-3.5 text-slate-500 dark:text-slate-400">
                          <span className="flex items-center gap-1">
                            <Clock className="h-3 w-3" />
                            {staff.last_active_at
                              ? new Date(staff.last_active_at).toLocaleDateString("en-US", {
                                  month: "short",
                                  day: "numeric",
                                  hour: "2-digit",
                                  minute: "2-digit",
                                })
                              : staff.created_at
                              ? new Date(staff.created_at).toLocaleDateString("en-US", {
                                  month: "short",
                                  day: "numeric",
                                })
                              : "Recently"}
                          </span>
                        </td>

                        {/* Actions */}
                        <td className="px-4 py-3.5 text-right">
                          <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                              <Button
                                size="sm"
                                variant="ghost"
                                className="h-7 w-7 p-0 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
                              >
                                <MoreVertical className="h-4 w-4" />
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-52">
                              <DropdownMenuItem
                                onClick={() => openEditModal(staff)}
                                className="cursor-pointer text-xs"
                              >
                                <Edit className="mr-2 h-3.5 w-3.5 text-slate-600 dark:text-slate-300" />
                                Edit Account
                              </DropdownMenuItem>

                              <DropdownMenuItem
                                onClick={() => {
                                  setSelectedStaffForRole(staff)
                                  setTargetRole(staff.role as AdminRole)
                                }}
                                className="cursor-pointer text-xs"
                              >
                                <KeyRound className="mr-2 h-3.5 w-3.5 text-blue-600" />
                                Change Role
                              </DropdownMenuItem>

                              {/* Toggle Status in menu */}
                              <DropdownMenuItem
                                disabled={isSelf || (isOnlySuperAdmin && staff.is_active !== false && staff.status !== "inactive")}
                                onClick={() => handleToggleStatus(staff)}
                                className={`cursor-pointer text-xs ${
                                  isSelf || (isOnlySuperAdmin && staff.is_active !== false && staff.status !== "inactive")
                                    ? "opacity-50 cursor-not-allowed"
                                    : staff.is_active !== false && staff.status !== "inactive"
                                    ? "text-amber-600 focus:text-amber-600 focus:bg-amber-50 dark:focus:bg-amber-950/40"
                                    : "text-emerald-600 focus:text-emerald-600 focus:bg-emerald-50 dark:focus:bg-emerald-950/40"
                                }`}
                              >
                                {staff.is_active !== false && staff.status !== "inactive" ? (
                                  <>
                                    <UserMinus className="mr-2 h-3.5 w-3.5" />
                                    Deactivate Account
                                  </>
                                ) : (
                                  <>
                                    <UserCheck className="mr-2 h-3.5 w-3.5" />
                                    Activate Account
                                  </>
                                )}
                              </DropdownMenuItem>

                              <DropdownMenuSeparator />

                              <DropdownMenuItem
                                disabled={isSelf || isOnlySuperAdmin}
                                onClick={() => setSelectedStaffForRevoke(staff)}
                                className={`cursor-pointer text-xs ${
                                  isSelf || isOnlySuperAdmin
                                    ? "opacity-50 cursor-not-allowed"
                                    : "text-amber-700 focus:text-amber-700 focus:bg-amber-50 dark:focus:bg-amber-950/40"
                                }`}
                              >
                                <UserX className="mr-2 h-3.5 w-3.5" />
                                Revoke Admin Privileges
                              </DropdownMenuItem>

                              <DropdownMenuItem
                                disabled={isSelf || isOnlySuperAdmin}
                                onClick={() => setSelectedStaffForDelete(staff)}
                                className={`cursor-pointer text-xs ${
                                  isSelf || isOnlySuperAdmin
                                    ? "opacity-50 cursor-not-allowed"
                                    : "text-rose-600 focus:text-rose-600 focus:bg-rose-50 dark:focus:bg-rose-950/40 font-medium"
                                }`}
                              >
                                <Trash2 className="mr-2 h-3.5 w-3.5" />
                                Delete Account
                              </DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </td>
                      </tr>
                    )
                  })}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Role-Based Access Control (RBAC) Permission Matrix */}
      <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <CardHeader>
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <CardTitle className="flex items-center gap-2 text-base font-bold text-slate-950 dark:text-slate-50">
                <Shield className="h-4 w-4 text-blue-600" />
                Backend RBAC Permission & Activity Matrix
              </CardTitle>
              <CardDescription className="text-xs text-slate-500">
                Authoritative permission matrix actively enforced across backend controllers and API middleware
              </CardDescription>
            </div>

            <div className="flex items-center gap-2">
              {categories.length > 1 && (
                <select
                  value={matrixCategoryFilter}
                  onChange={(e) => setMatrixCategoryFilter(e.target.value)}
                  className="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 h-8"
                >
                  <option value="all">All Categories</option>
                  {categories.map((c) => (
                    <option key={c} value={c}>
                      {c}
                    </option>
                  ))}
                </select>
              )}

              <div className="relative w-full sm:w-52">
                <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" />
                <Input
                  placeholder="Filter permissions..."
                  className="pl-8 text-xs h-8"
                  value={matrixSearch}
                  onChange={(e) => setMatrixSearch(e.target.value)}
                />
              </div>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800">
            <table className="w-full text-left text-xs">
              <thead className="border-b border-slate-200 bg-slate-50 font-semibold uppercase text-slate-600 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-400">
                <tr>
                  <th className="px-4 py-3 w-5/12">Operational Module</th>
                  <th className="px-4 py-3 text-center">
                    <span className="text-rose-600 font-bold">Super Admin</span>
                  </th>
                  <th className="px-4 py-3 text-center">
                    <span className="text-blue-600 font-bold">Academic Editor</span>
                  </th>
                  <th className="px-4 py-3 text-center">
                    <span className="text-amber-600 font-bold">Tutor Moderator</span>
                  </th>
                  <th className="px-4 py-3 text-center">
                    <span className="text-emerald-600 font-bold">Support Moderator</span>
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 dark:divide-slate-800/80">
                {filteredMatrix.map((item, idx) => (
                  <tr
                    key={idx}
                    className="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-900/30"
                  >
                    <td className="px-4 py-3">
                      <div className="font-semibold text-slate-800 dark:text-slate-200">
                        {item.module}
                      </div>
                      {item.description && (
                        <p className="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400 leading-normal">
                          {item.description}
                        </p>
                      )}
                      {item.category && (
                        <span className="inline-block mt-0.5 rounded bg-slate-100 px-1.5 py-0.2 text-[10px] font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                          {item.category}
                        </span>
                      )}
                    </td>

                    {/* Super Admin */}
                    <td className="px-4 py-3 text-center">
                      {item.super_admin ? (
                        <div className="flex justify-center text-emerald-600 dark:text-emerald-400">
                          <CheckCircle2 className="h-4 w-4" />
                        </div>
                      ) : (
                        <div className="flex justify-center text-slate-300 dark:text-slate-700">
                          <XCircle className="h-4 w-4" />
                        </div>
                      )}
                    </td>

                    {/* Academic Editor */}
                    <td className="px-4 py-3 text-center">
                      {item.academic_editor ? (
                        <div className="flex justify-center text-blue-600 dark:text-blue-400">
                          <CheckCircle2 className="h-4 w-4" />
                        </div>
                      ) : (
                        <div className="flex justify-center text-slate-300 dark:text-slate-700">
                          <XCircle className="h-4 w-4" />
                        </div>
                      )}
                    </td>

                    {/* Tutor Moderator */}
                    <td className="px-4 py-3 text-center">
                      {item.tutor_moderator ? (
                        <div className="flex justify-center text-amber-600 dark:text-amber-400">
                          <CheckCircle2 className="h-4 w-4" />
                        </div>
                      ) : (
                        <div className="flex justify-center text-slate-300 dark:text-slate-700">
                          <XCircle className="h-4 w-4" />
                        </div>
                      )}
                    </td>

                    {/* Support Moderator */}
                    <td className="px-4 py-3 text-center">
                      {item.support_moderator ? (
                        <div className="flex justify-center text-emerald-600 dark:text-emerald-400">
                          <CheckCircle2 className="h-4 w-4" />
                        </div>
                      ) : (
                        <div className="flex justify-center text-slate-300 dark:text-slate-700">
                          <XCircle className="h-4 w-4" />
                        </div>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      {/* ========================================================================= */}
      {/* MODAL 1: Invite / Create Staff Member */}
      {/* ========================================================================= */}
      <Dialog open={isInviteOpen} onOpenChange={setIsInviteOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2 text-base font-bold">
              <Shield className="h-5 w-5 text-blue-600" />
              Invite / Create Staff Member
            </DialogTitle>
            <DialogDescription className="text-xs text-slate-500">
              Create a direct administrative account with designated operational role credentials.
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleCreateStaff} className="space-y-4 py-2">
            {formError && (
              <div className="rounded-lg border border-red-500/20 bg-red-500/10 p-3 text-xs font-medium text-red-500 flex items-center gap-2">
                <AlertCircle className="h-4 w-4 text-red-500 shrink-0" />
                <span>{formError}</span>
              </div>
            )}

            <div className="space-y-1.5">
              <Label htmlFor="staff-name" className="text-xs font-semibold">
                Full Name <span className="text-rose-500">*</span>
              </Label>
              <Input
                id="staff-name"
                placeholder="e.g. Dawit Tadesse"
                value={inviteName}
                onChange={(e) => setInviteName(e.target.value)}
                required
                className="text-xs"
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="staff-email" className="text-xs font-semibold">
                Work Email Address <span className="text-rose-500">*</span>
              </Label>
              <Input
                id="staff-email"
                type="email"
                placeholder="dawit@asteghi.com"
                value={inviteEmail}
                onChange={(e) => setInviteEmail(e.target.value)}
                required
                className="text-xs"
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="staff-password" className="text-xs font-semibold">
                Initial Password <span className="text-rose-500">*</span>
              </Label>
              <Input
                id="staff-password"
                type="password"
                placeholder="Minimum 8 characters"
                value={invitePassword}
                onChange={(e) => setInvitePassword(e.target.value)}
                required
                minLength={8}
                className="text-xs"
              />
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="staff-role" className="text-xs font-semibold">
                  Operational Role <span className="text-rose-500">*</span>
                </Label>
                <select
                  id="staff-role"
                  className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                  value={inviteRole}
                  onChange={(e) => setInviteRole(e.target.value as AdminRole)}
                >
                  <option value="academic_editor">Academic Editor</option>
                  <option value="tutor_moderator">Tutor Moderator</option>
                  <option value="support_moderator">Support Moderator - Inquiries & Contact Settings</option>
                  <option value="super_admin">Super Admin</option>
                </select>
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="staff-status" className="text-xs font-semibold">
                  Account Status <span className="text-rose-500">*</span>
                </Label>
                <select
                  id="staff-status"
                  className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                  value={inviteStatus}
                  onChange={(e) => setInviteStatus(e.target.value as "active" | "inactive")}
                >
                  <option value="active">Active (Default)</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>

            <DialogFooter className="pt-3">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setIsInviteOpen(false)}
                disabled={isInviting}
              >
                Cancel
              </Button>
              <Button
                type="submit"
                size="sm"
                className="bg-blue-600 text-white hover:bg-blue-500 font-semibold"
                disabled={isInviting}
              >
                {isInviting ? (
                  <>
                    <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                    Creating Staff...
                  </>
                ) : (
                  <>
                    <UserPlus className="mr-1.5 h-3.5 w-3.5" />
                    Create Staff Member
                  </>
                )}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* ========================================================================= */}
      {/* MODAL 2: Promote Existing User (Student / Tutor) */}
      {/* ========================================================================= */}
      <Dialog open={isPromoteOpen} onOpenChange={setIsPromoteOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2 text-base font-bold">
              <UserCheck className="h-5 w-5 text-blue-600" />
              Promote Existing User to Staff
            </DialogTitle>
            <DialogDescription className="text-xs text-slate-500">
              Search for any existing registered student or tutor account by email and elevate their privileges to an administrative role.
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4 py-2">
            {/* Search Lookup Form */}
            <form onSubmit={handleLookupUser} className="flex gap-2">
              <div className="relative flex-1">
                <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" />
                <Input
                  type="email"
                  placeholder="Enter student or tutor email..."
                  value={lookupEmail}
                  onChange={(e) => setLookupEmail(e.target.value)}
                  className="pl-8 text-xs h-9"
                  required
                />
              </div>
              <Button
                type="submit"
                size="sm"
                variant="secondary"
                disabled={isLookingUp}
                className="font-semibold text-xs h-9"
              >
                {isLookingUp ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : "Lookup User"}
              </Button>
            </form>

            {/* Lookup Error Banner */}
            {lookupError && (
              <div className="flex items-center gap-2 rounded-md bg-rose-50 p-3 text-xs font-medium text-rose-800 dark:bg-rose-950/40 dark:text-rose-200">
                <AlertCircle className="h-4 w-4 text-rose-600 flex-shrink-0" />
                {lookupError}
              </div>
            )}

            {/* User Details Card */}
            {lookupResult && (
              <div className="rounded-lg border border-slate-200 bg-slate-50/70 p-4 space-y-3 dark:border-slate-800 dark:bg-slate-900/50">
                <div className="flex items-start justify-between">
                  <div>
                    <h4 className="text-sm font-bold text-slate-900 dark:text-slate-100">{lookupResult.name}</h4>
                    <p className="text-xs text-slate-500">{lookupResult.email}</p>
                  </div>
                  <Badge variant={lookupResult.is_admin ? "default" : "secondary"} className="text-[10px]">
                    Current Role: {formatRoleName(lookupResult.role)}
                  </Badge>
                </div>

                <div className="flex flex-wrap gap-2 text-xs">
                  {lookupResult.tutor_profile && (
                    <span className="inline-flex items-center gap-1 rounded bg-purple-100 px-2 py-0.5 text-[11px] font-medium text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">
                      <GraduationCap className="h-3 w-3" />
                      Active Tutor Profile (Preserved)
                    </span>
                  )}
                  {lookupResult.student_profile && (
                    <span className="inline-flex items-center gap-1 rounded bg-sky-100 px-2 py-0.5 text-[11px] font-medium text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">
                      Active Student Profile (Preserved)
                    </span>
                  )}
                  <span className="text-slate-400 text-[11px] flex items-center">
                    Registered: {new Date(lookupResult.created_at).toLocaleDateString()}
                  </span>
                </div>

                <div className="border-t border-slate-200 pt-3 dark:border-slate-800 space-y-2">
                  <Label htmlFor="promote-role-select" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                    Assign Target Administrative Role
                  </Label>
                  <select
                    id="promote-role-select"
                    className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                    value={promoteRole}
                    onChange={(e) => setPromoteRole(e.target.value as AdminRole)}
                  >
                    {backendRoles.map((r) => (
                      <option key={r.role} value={r.role}>
                        {r.name} - {r.description.split(",")[0]}
                      </option>
                    ))}
                  </select>
                </div>
              </div>
            )}
          </div>

          <DialogFooter className="pt-2">
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => setIsPromoteOpen(false)}
              disabled={isPromoting}
            >
              Cancel
            </Button>
            <Button
              type="button"
              size="sm"
              className="bg-blue-600 text-white hover:bg-blue-500 font-semibold"
              disabled={!lookupResult || isPromoting}
              onClick={handlePromoteUser}
            >
              {isPromoting ? (
                <>
                  <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                  Promoting...
                </>
              ) : (
                <>
                  <Sparkles className="mr-1.5 h-3.5 w-3.5" />
                  Confirm Role Elevation
                </>
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ========================================================================= */}
      {/* MODAL 3: Change Staff Role */}
      {/* ========================================================================= */}
      <Dialog
        open={Boolean(selectedStaffForRole)}
        onOpenChange={(open) => !open && setSelectedStaffForRole(null)}
      >
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2 text-base font-bold">
              <KeyRound className="h-5 w-5 text-blue-600" />
              Change Staff Role
            </DialogTitle>
            <DialogDescription className="text-xs text-slate-500">
              Update operational permissions for {selectedStaffForRole?.name}.
            </DialogDescription>
          </DialogHeader>

          {selectedStaffForRole && (
            <div className="space-y-4 py-2">
              <div className="rounded-md bg-slate-50 p-3 text-xs dark:bg-slate-900">
                <div className="font-semibold text-slate-800 dark:text-slate-200">
                  {selectedStaffForRole.name} ({selectedStaffForRole.email})
                </div>
                <div className="mt-1 text-slate-500 flex items-center gap-1.5">
                  Current Role: {getRoleBadge(selectedStaffForRole.role)}
                </div>
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="change-role-select" className="text-xs font-semibold">
                  New Administrative Role
                </Label>
                <select
                  id="change-role-select"
                  className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                  value={targetRole}
                  onChange={(e) => setTargetRole(e.target.value as AdminRole)}
                >
                  {backendRoles.map((r) => (
                    <option key={r.role} value={r.role}>
                      {r.name} - {r.description.split(",")[0]}
                    </option>
                  ))}
                </select>
              </div>
            </div>
          )}

          <DialogFooter className="pt-2">
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => setSelectedStaffForRole(null)}
              disabled={isUpdatingRole}
            >
              Cancel
            </Button>
            <Button
              type="button"
              size="sm"
              className="bg-blue-600 text-white hover:bg-blue-500 font-semibold"
              disabled={isUpdatingRole}
              onClick={handleUpdateRole}
            >
              {isUpdatingRole ? (
                <>
                  <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                  Updating...
                </>
              ) : (
                "Save Role Assignment"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ========================================================================= */}
      {/* MODAL 4: Revoke Staff Access Confirmation */}
      {/* ========================================================================= */}
      <Dialog
        open={Boolean(selectedStaffForRevoke)}
        onOpenChange={(open) => !open && setSelectedStaffForRevoke(null)}
      >
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2 text-base font-bold text-rose-600">
              <UserX className="h-5 w-5 text-rose-600" />
              Revoke Staff Privileges
            </DialogTitle>
            <DialogDescription className="text-xs text-slate-500">
              Are you sure you want to demote this administrator?
            </DialogDescription>
          </DialogHeader>

          {selectedStaffForRevoke && (
            <div className="space-y-3 py-2">
              <div className="rounded-lg border border-rose-200 bg-rose-50/50 p-3 text-xs text-rose-950 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-200">
                <p className="font-semibold">Demotion Effect Summary:</p>
                <ul className="mt-1.5 list-disc pl-4 space-y-1 text-slate-600 dark:text-slate-400">
                  <li>
                    <strong>{selectedStaffForRevoke.name}</strong> ({selectedStaffForRevoke.email}) will have their admin flag revoked (<code className="text-slate-800 dark:text-slate-200">is_admin = 0</code>).
                  </li>
                  <li>Their role will revert to standard <code className="text-slate-800 dark:text-slate-200">student</code>.</li>
                  <li>
                    All active administrative authentication tokens will be immediately invalidated.
                  </li>
                  <li>
                    Their student/tutor history and personal data will <strong>not</strong> be deleted.
                  </li>
                </ul>
              </div>
            </div>
          )}

          <DialogFooter className="pt-2">
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => setSelectedStaffForRevoke(null)}
              disabled={isRevoking}
            >
              Cancel
            </Button>
            <Button
              type="button"
              size="sm"
              className="bg-rose-600 text-white hover:bg-rose-500 font-semibold"
              disabled={isRevoking}
              onClick={handleRevokeAccess}
            >
              {isRevoking ? (
                <>
                  <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                  Revoking...
                </>
              ) : (
                "Revoke Privileges"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ========================================================================= */}
      {/* MODAL 5: Edit Staff Account */}
      {/* ========================================================================= */}
      <Dialog
        open={Boolean(selectedStaffForEdit)}
        onOpenChange={(open) => !open && setSelectedStaffForEdit(null)}
      >
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2 text-base font-bold">
              <Edit className="h-5 w-5 text-blue-600" />
              Edit Staff Account
            </DialogTitle>
            <DialogDescription className="text-xs text-slate-500">
              Update administrative profile details, system role, active status, and password credentials.
            </DialogDescription>
          </DialogHeader>

          {selectedStaffForEdit && (
            <form onSubmit={handleUpdateStaff} className="space-y-4 py-2">
              {editFormError && (
                <div className="rounded-lg border border-red-500/20 bg-red-500/10 p-3 text-xs font-medium text-red-500 flex items-center gap-2">
                  <AlertCircle className="h-4 w-4 text-red-500 shrink-0" />
                  <span>{editFormError}</span>
                </div>
              )}

              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1.5">
                  <Label htmlFor="edit-first-name" className="text-xs font-semibold">
                    First Name <span className="text-rose-500">*</span>
                  </Label>
                  <Input
                    id="edit-first-name"
                    placeholder="e.g. Dawit"
                    value={editFirstName}
                    onChange={(e) => setEditFirstName(e.target.value)}
                    required
                    className="text-xs"
                  />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="edit-last-name" className="text-xs font-semibold">
                    Last Name <span className="text-rose-500">*</span>
                  </Label>
                  <Input
                    id="edit-last-name"
                    placeholder="e.g. Tadesse"
                    value={editLastName}
                    onChange={(e) => setEditLastName(e.target.value)}
                    required
                    className="text-xs"
                  />
                </div>
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="edit-staff-email" className="text-xs font-semibold">
                  Work Email Address <span className="text-rose-500">*</span>
                </Label>
                <Input
                  id="edit-staff-email"
                  type="email"
                  placeholder="dawit@asteghi.com"
                  value={editEmail}
                  onChange={(e) => setEditEmail(e.target.value)}
                  required
                  className="text-xs"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1.5">
                  <Label htmlFor="edit-staff-role" className="text-xs font-semibold">
                    Assigned Operational Role
                  </Label>
                  <select
                    id="edit-staff-role"
                    className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                    value={editRole}
                    onChange={(e) => setEditRole(e.target.value as AdminRole)}
                  >
                    {backendRoles.map((r) => (
                      <option key={r.role} value={r.role}>
                        {r.name}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="edit-staff-status" className="text-xs font-semibold">
                    Account Status
                  </Label>
                  <select
                    id="edit-staff-status"
                    className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                    value={editStatus}
                    onChange={(e) => setEditStatus(e.target.value as "active" | "inactive")}
                  >
                    <option value="active">🟢 Active (Authorized)</option>
                    <option value="inactive">🔴 Inactive (Deactivated / Blocked)</option>
                  </select>
                </div>
              </div>

              {editStatus === "inactive" && (
                <div className="rounded-md border border-amber-200 bg-amber-50/70 p-2.5 text-[11px] text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300">
                  ⚠️ Deactivating this account will immediately revoke all active authentication sessions and prevent login to any operational portals.
                </div>
              )}

              <div className="space-y-1.5 pt-1">
                <Label htmlFor="edit-staff-password" className="text-xs font-semibold flex items-center justify-between">
                  <span>Change Password</span>
                  <span className="text-[10px] font-normal text-slate-400">Optional: leave empty to retain current password</span>
                </Label>
                <Input
                  id="edit-staff-password"
                  type="password"
                  placeholder="New password (min. 8 characters)"
                  value={editPassword}
                  onChange={(e) => setEditPassword(e.target.value)}
                  minLength={8}
                  className="text-xs"
                />
              </div>

              <DialogFooter className="pt-3">
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => setSelectedStaffForEdit(null)}
                  disabled={isUpdatingStaff}
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  size="sm"
                  className="bg-blue-600 text-white hover:bg-blue-500 font-semibold"
                  disabled={isUpdatingStaff}
                >
                  {isUpdatingStaff ? (
                    <>
                      <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                      Saving Changes...
                    </>
                  ) : (
                    "Save Changes"
                  )}
                </Button>
              </DialogFooter>
            </form>
          )}
        </DialogContent>
      </Dialog>

      {/* ========================================================================= */}
      {/* MODAL 6: Delete Staff Account Confirmation */}
      {/* ========================================================================= */}
      <Dialog
        open={Boolean(selectedStaffForDelete)}
        onOpenChange={(open) => !open && setSelectedStaffForDelete(null)}
      >
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2 text-base font-bold text-rose-600">
              <Trash2 className="h-5 w-5 text-rose-600" />
              Delete Staff Account
            </DialogTitle>
            <DialogDescription className="text-xs text-slate-500">
              Permanently delete this administrative staff member account.
            </DialogDescription>
          </DialogHeader>

          {selectedStaffForDelete && (
            <div className="space-y-3 py-2">
              <div className="rounded-lg border border-rose-200 bg-rose-50/50 p-3 text-xs text-rose-950 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-200">
                <p className="font-semibold">Permanent Deletion Notice:</p>
                <ul className="mt-1.5 list-disc pl-4 space-y-1 text-slate-600 dark:text-slate-400">
                  <li>
                    User <strong>{selectedStaffForDelete.name}</strong> ({selectedStaffForDelete.email}) will be completely deleted from the database.
                  </li>
                  <li>
                    All active authentication tokens will be immediately invalidated.
                  </li>
                  <li>
                    This action is irreversible and will be logged to the Immutable Audit Ledger.
                  </li>
                </ul>
              </div>
            </div>
          )}

          <DialogFooter className="pt-2">
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => setSelectedStaffForDelete(null)}
              disabled={isDeletingStaff}
            >
              Cancel
            </Button>
            <Button
              type="button"
              size="sm"
              className="bg-rose-600 text-white hover:bg-rose-500 font-semibold"
              disabled={isDeletingStaff}
              onClick={handleDeleteStaff}
            >
              {isDeletingStaff ? (
                <>
                  <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                  Deleting Account...
                </>
              ) : (
                "Permanently Delete"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
