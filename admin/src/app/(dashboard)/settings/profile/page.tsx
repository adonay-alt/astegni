"use client"

import React, { useState, useEffect, useRef, Suspense } from "react"
import { useRouter, useSearchParams } from "next/navigation"
import Link from "next/link"
import { useAuth } from "@/context/auth-context"
import { apiClient } from "@/lib/api-client"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Separator } from "@/components/ui/separator"
import {
  User,
  Shield,
  ShieldCheck,
  KeyRound,
  Mail,
  Phone,
  Camera,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Eye,
  EyeOff,
  Lock,
  History,
  ArrowRight,
  LogOut,
  Server,
  UserCheck,
  Check,
} from "lucide-react"

interface ProfileData {
  id: number
  first_name: string
  last_name: string
  email: string
  role?: string
  is_admin?: boolean
  phone?: string | null
  gender?: string | null
  photo_url?: string | null
  grade?: string | null
}

function AdminProfileContent() {
  const router = useRouter()
  const searchParams = useSearchParams()
  const { user, logout, updateCurrentUser, refreshCurrentUser } = useAuth()

  const tabParam = searchParams.get("tab")
  const [activeTab, setActiveTab] = useState<"profile" | "security">(
    tabParam === "security" ? "security" : "profile"
  )

  useEffect(() => {
    if (tabParam === "security") {
      setActiveTab("security")
    } else if (tabParam === "profile") {
      setActiveTab("profile")
    }
  }, [tabParam])

  const handleTabChange = (tab: "profile" | "security") => {
    setActiveTab(tab)
    router.replace(`/settings/profile?tab=${tab}`, { scroll: false })
  }

  // Profile form state
  const [isLoadingProfile, setIsLoadingProfile] = useState(true)
  const [isSavingProfile, setIsSavingProfile] = useState(false)
  const [profileSuccessMsg, setProfileSuccessMsg] = useState<string | null>(null)
  const [profileErrorMsg, setProfileErrorMsg] = useState<string | null>(null)

  const [firstName, setFirstName] = useState("")
  const [lastName, setLastName] = useState("")
  const [email, setEmail] = useState("")
  const [phone, setPhone] = useState("")
  const [gender, setGender] = useState<string>("")
  const [avatarUrl, setAvatarUrl] = useState<string | null>(null)
  const [isUploadingPhoto, setIsUploadingPhoto] = useState(false)
  const fileInputRef = useRef<HTMLInputElement | null>(null)

  // Password form state
  const [currentPassword, setCurrentPassword] = useState("")
  const [newPassword, setNewPassword] = useState("")
  const [confirmPassword, setConfirmPassword] = useState("")
  const [showCurrentPw, setShowCurrentPw] = useState(false)
  const [showNewPw, setShowNewPw] = useState(false)
  const [showConfirmPw, setShowConfirmPw] = useState(false)
  const [isSavingPassword, setIsSavingPassword] = useState(false)
  const [passwordSuccessMsg, setPasswordSuccessMsg] = useState<string | null>(null)
  const [passwordErrorMsg, setPasswordErrorMsg] = useState<string | null>(null)

  // Fetch full profile from backend
  useEffect(() => {
    let isMounted = true

    const fetchProfile = async () => {
      setIsLoadingProfile(true)
      try {
        const response = await apiClient.get("/profile")
        const resData = response.data as { user?: ProfileData; data?: { user?: ProfileData } }
        const pUser = resData.user || resData.data?.user

        if (pUser && isMounted) {
          setFirstName(pUser.first_name || "")
          setLastName(pUser.last_name || "")
          setEmail(pUser.email || "")
          setPhone(pUser.phone || "")
          setGender(pUser.gender || "")
          setAvatarUrl(pUser.photo_url || null)
        }
      } catch (err) {
        console.error("Failed to load profile details", err)
        // Fallback to auth context user if /profile fails
        if (isMounted && user) {
          const names = (user.name || "").split(" ")
          setFirstName(user.first_name || names[0] || "")
          setLastName(user.last_name || names.slice(1).join(" ") || "")
          setEmail(user.email || "")
          setAvatarUrl(user.avatar_url || null)
        }
      } finally {
        if (isMounted) {
          setIsLoadingProfile(false)
        }
      }
    }

    fetchProfile()

    return () => {
      isMounted = false
    }
  }, [user])

  // Handle Profile Update
  const handleSaveProfile = async (e: React.FormEvent) => {
    e.preventDefault()
    setProfileSuccessMsg(null)
    setProfileErrorMsg(null)
    setIsSavingProfile(true)

    try {
      const payload: Record<string, string | null> = {
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        email: email.trim(),
      }

      if (phone.trim()) {
        payload.phone = phone.trim()
      }
      if (gender) {
        payload.gender = gender
      }

      const response = await apiClient.put("/profile", payload)
      const resData = response.data as { message?: string; user?: ProfileData }

      setProfileSuccessMsg(resData.message || "Admin profile updated successfully.")

      // Update auth context state and localStorage immediately
      const updatedFullName = `${firstName.trim()} ${lastName.trim()}`.trim()
      updateCurrentUser({
        name: updatedFullName || user?.name || "Administrator",
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        email: email.trim(),
      })

      // Optionally refresh user from server
      refreshCurrentUser().catch(() => {})
    } catch (err: unknown) {
      console.error("Failed to update profile", err)
      let msg = "Failed to update profile. Please try again."
      if (typeof err === "object" && err !== null && "response" in err) {
        const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
        if (axiosErr.response?.data?.errors) {
          const firstErr = Object.values(axiosErr.response.data.errors)[0]
          if (Array.isArray(firstErr) && firstErr[0]) {
            msg = firstErr[0]
          }
        } else if (axiosErr.response?.data?.message) {
          msg = axiosErr.response.data.message
        }
      }
      setProfileErrorMsg(msg)
    } finally {
      setIsSavingProfile(false)
    }
  }

  // Handle Profile Photo Upload
  const handlePhotoSelect = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (!file) return

    setProfileSuccessMsg(null)
    setProfileErrorMsg(null)
    setIsUploadingPhoto(true)

    try {
      const formData = new FormData()
      formData.append("photo", file)

      const response = await apiClient.post("/profile/photo", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      })

      const resData = response.data as { status?: string; message?: string; data?: { photo_url?: string } }
      const newPhotoUrl = resData.data?.photo_url

      if (newPhotoUrl) {
        setAvatarUrl(newPhotoUrl)
        updateCurrentUser({ avatar_url: newPhotoUrl })
        setProfileSuccessMsg("Profile photo uploaded successfully.")
      } else {
        // Fallback: re-fetch profile to get latest photo
        await refreshCurrentUser()
        setProfileSuccessMsg("Profile photo updated successfully.")
      }
    } catch (err: unknown) {
      console.error("Photo upload failed", err)
      let msg = "Failed to upload photo. File must be an image (JPEG, PNG, WEBP) under 5MB."
      if (typeof err === "object" && err !== null && "response" in err) {
        const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
        if (axiosErr.response?.data?.message) {
          msg = axiosErr.response.data.message
        }
      }
      setProfileErrorMsg(msg)
    } finally {
      setIsUploadingPhoto(false)
      if (fileInputRef.current) {
        fileInputRef.current.value = ""
      }
    }
  }

  // Handle Password Change
  const handleSavePassword = async (e: React.FormEvent) => {
    e.preventDefault()
    setPasswordSuccessMsg(null)
    setPasswordErrorMsg(null)

    if (newPassword.length < 8) {
      setPasswordErrorMsg("New password must be at least 8 characters long.")
      return
    }

    if (newPassword !== confirmPassword) {
      setPasswordErrorMsg("New password and confirmation password do not match.")
      return
    }

    setIsSavingPassword(true)

    try {
      const response = await apiClient.post("/change-password", {
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: confirmPassword,
      })

      const resData = response.data as { message?: string }
      setPasswordSuccessMsg(resData.message || "Password changed successfully.")
      setCurrentPassword("")
      setNewPassword("")
      setConfirmPassword("")
    } catch (err: unknown) {
      console.error("Password change failed", err)
      let msg = "Failed to change password. Please verify your current password."
      if (typeof err === "object" && err !== null && "response" in err) {
        const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
        if (axiosErr.response?.data?.errors) {
          const firstErr = Object.values(axiosErr.response.data.errors)[0]
          if (Array.isArray(firstErr) && firstErr[0]) {
            msg = firstErr[0]
          }
        } else if (axiosErr.response?.data?.message) {
          msg = axiosErr.response.data.message
        }
      }
      setPasswordErrorMsg(msg)
    } finally {
      setIsSavingPassword(false)
    }
  }

  const userInitials = (firstName && lastName)
    ? `${firstName[0]}${lastName[0]}`.toUpperCase()
    : user?.name
    ? user.name
        .split(" ")
        .map((n) => n[0])
        .slice(0, 2)
        .join("")
        .toUpperCase()
    : "AD"

  const roleLabel = (user?.role || "super_admin").replace(/_/g, " ").toUpperCase()

  return (
    <div className="space-y-6 pb-12">
      {/* Top Header & Breadcrumb */}
      <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div className="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>System & Governance</span>
            <span>/</span>
            <span className="text-slate-900 dark:text-slate-200">Admin Account</span>
          </div>
          <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-slate-50">
            Admin Profile & Security
          </h1>
          <p className="text-sm text-slate-500 dark:text-slate-400">
            Manage your administrator profile details, access credentials, and account password.
          </p>
        </div>

        {/* Tab Toggle Buttons */}
        <div className="flex items-center rounded-lg border border-slate-200 bg-slate-100 p-1 dark:border-slate-800 dark:bg-slate-900">
          <button
            type="button"
            onClick={() => handleTabChange("profile")}
            className={`flex items-center gap-2 rounded-md px-3.5 py-1.5 text-xs font-semibold transition-all ${
              activeTab === "profile"
                ? "bg-white text-slate-900 shadow-sm dark:bg-slate-800 dark:text-slate-100"
                : "text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
            }`}
          >
            <User className="h-3.5 w-3.5" />
            Admin Profile
          </button>
          <button
            type="button"
            onClick={() => handleTabChange("security")}
            className={`flex items-center gap-2 rounded-md px-3.5 py-1.5 text-xs font-semibold transition-all ${
              activeTab === "security"
                ? "bg-white text-slate-900 shadow-sm dark:bg-slate-800 dark:text-slate-100"
                : "text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
            }`}
          >
            <Shield className="h-3.5 w-3.5" />
            Security
          </button>
        </div>
      </div>

      {/* TAB 1: ADMIN PROFILE */}
      {activeTab === "profile" && (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
          {/* Left Hero Card: Identity & Avatar */}
          <div className="space-y-6 lg:col-span-1">
            <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
              <CardHeader className="text-center pb-2">
                <div className="relative mx-auto mb-3">
                  <Avatar className="h-28 w-28 border-4 border-white shadow-lg ring-2 ring-slate-200 dark:border-slate-900 dark:ring-slate-700">
                    <AvatarImage src={avatarUrl || user?.avatar_url || undefined} alt="Admin Avatar" />
                    <AvatarFallback className="bg-gradient-to-br from-blue-600 to-indigo-700 text-2xl font-bold text-white">
                      {userInitials}
                    </AvatarFallback>
                  </Avatar>

                  {/* Hidden file input */}
                  <input
                    type="file"
                    ref={fileInputRef}
                    onChange={handlePhotoSelect}
                    accept="image/jpeg,image/png,image/jpg,image/webp"
                    className="hidden"
                  />

                  {/* Change Avatar Button Trigger */}
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={isUploadingPhoto}
                    onClick={() => fileInputRef.current?.click()}
                    className="absolute -bottom-2 -right-2 h-9 w-9 rounded-full border-slate-200 bg-white p-0 shadow-md hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700"
                    title="Change Profile Photo"
                  >
                    {isUploadingPhoto ? (
                      <Loader2 className="h-4 w-4 animate-spin text-blue-600" />
                    ) : (
                      <Camera className="h-4 w-4 text-slate-700 dark:text-slate-200" />
                    )}
                  </Button>
                </div>

                <CardTitle className="text-lg font-bold text-slate-900 dark:text-slate-100">
                  {firstName || lastName ? `${firstName} ${lastName}`.trim() : user?.name || "Admin User"}
                </CardTitle>
                <CardDescription className="text-xs text-slate-500 dark:text-slate-400">
                  {email || user?.email || "admin@asteghi.com"}
                </CardDescription>

                <div className="mt-3 flex flex-wrap items-center justify-center gap-2">
                  <Badge variant="default" className="bg-blue-600 text-[10px] font-bold text-white hover:bg-blue-700">
                    {roleLabel}
                  </Badge>
                </div>
              </CardHeader>

              <CardContent className="space-y-4 pt-4 text-xs">
                <Separator />
                <div className="space-y-3">
                  <div className="flex items-center justify-between text-slate-600 dark:text-slate-400">
                    <span className="flex items-center gap-1.5">
                      <ShieldCheck className="h-3.5 w-3.5 text-blue-600" />
                      Privilege Level
                    </span>
                    <span className="font-semibold text-slate-900 dark:text-slate-200">
                      Root Governance
                    </span>
                  </div>

                  <div className="flex items-center justify-between text-slate-600 dark:text-slate-400">
                    <span className="flex items-center gap-1.5">
                      <Server className="h-3.5 w-3.5 text-slate-500" />
                      Auth Provider
                    </span>
                    <span className="font-medium text-slate-900 dark:text-slate-200">
                      Laravel Sanctum
                    </span>
                  </div>
                </div>

                <div className="rounded-lg border border-slate-100 bg-slate-50 p-3 text-[11px] text-slate-600 dark:border-slate-800/60 dark:bg-slate-950/50 dark:text-slate-400">
                  <p className="font-medium text-slate-900 dark:text-slate-200">Profile Photo Guidelines:</p>
                  <p className="mt-1 text-slate-500">
                    PNG, JPG or WEBP up to 5MB. Clear face photography recommended for institutional audit logs.
                  </p>
                </div>
              </CardContent>
            </Card>

            {/* Quick Security Status Box */}
            <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
              <CardHeader className="pb-3">
                <CardTitle className="text-sm font-semibold flex items-center gap-2">
                  <KeyRound className="h-4 w-4 text-indigo-600" />
                  Credentials & Access
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3 text-xs text-slate-600 dark:text-slate-400">
                <p>
                  Need to update your login password or manage access credentials?
                </p>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => handleTabChange("security")}
                  className="w-full justify-between text-xs"
                >
                  <span className="flex items-center gap-1.5">
                    <Shield className="h-3.5 w-3.5 text-blue-600" />
                    Open Security
                  </span>
                  <ArrowRight className="h-3.5 w-3.5" />
                </Button>
              </CardContent>
            </Card>
          </div>

          {/* Right Form Card: Personal Information */}
          <div className="lg:col-span-2">
            <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
              <CardHeader>
                <CardTitle className="text-base font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                  <UserCheck className="h-4 w-4 text-blue-600" />
                  Administrator Details
                </CardTitle>
                <CardDescription className="text-xs text-slate-500 dark:text-slate-400">
                  Update your contact identity and administrative contact preferences.
                </CardDescription>
              </CardHeader>

              <form onSubmit={handleSaveProfile}>
                <CardContent className="space-y-5">
                  {/* Notifications */}
                  {profileSuccessMsg && (
                    <div className="flex items-center gap-2 rounded-md border border-emerald-500/30 bg-emerald-50 p-3 text-xs text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                      <CheckCircle2 className="h-4 w-4 shrink-0 text-emerald-600" />
                      <span>{profileSuccessMsg}</span>
                    </div>
                  )}

                  {profileErrorMsg && (
                    <div className="flex items-center gap-2 rounded-md border border-rose-500/30 bg-rose-50 p-3 text-xs text-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                      <AlertCircle className="h-4 w-4 shrink-0 text-rose-600" />
                      <span>{profileErrorMsg}</span>
                    </div>
                  )}

                  {isLoadingProfile ? (
                    <div className="flex items-center justify-center py-12 text-slate-500">
                      <Loader2 className="mr-2 h-5 w-5 animate-spin text-blue-600" />
                      <span className="text-xs">Loading profile details...</span>
                    </div>
                  ) : (
                    <>
                      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                          <Label htmlFor="first_name" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                            First Name <span className="text-rose-500">*</span>
                          </Label>
                          <Input
                            id="first_name"
                            value={firstName}
                            onChange={(e) => setFirstName(e.target.value)}
                            placeholder="e.g. John"
                            required
                            className="h-9 text-xs"
                          />
                        </div>

                        <div className="space-y-1.5">
                          <Label htmlFor="last_name" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Last Name <span className="text-rose-500">*</span>
                          </Label>
                          <Input
                            id="last_name"
                            value={lastName}
                            onChange={(e) => setLastName(e.target.value)}
                            placeholder="e.g. Doe"
                            required
                            className="h-9 text-xs"
                          />
                        </div>
                      </div>

                      <div className="space-y-1.5">
                        <Label htmlFor="email" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                          Administrative Email Address <span className="text-rose-500">*</span>
                        </Label>
                        <div className="relative">
                          <Input
                            id="email"
                            type="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder="admin@example.com"
                            required
                            className="h-9 pl-8 text-xs"
                          />
                          <Mail className="absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" />
                        </div>
                        <p className="text-[11px] text-slate-500">
                          Used for system notifications, security alerts, and administrative communications.
                        </p>
                      </div>

                      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                          <Label htmlFor="phone" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Phone Number
                          </Label>
                          <div className="relative">
                            <Input
                              id="phone"
                              type="tel"
                              value={phone}
                              onChange={(e) => setPhone(e.target.value)}
                              placeholder="+251 9..."
                              className="h-9 pl-8 text-xs"
                            />
                            <Phone className="absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" />
                          </div>
                        </div>

                        <div className="space-y-1.5">
                          <Label htmlFor="gender" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Gender
                          </Label>
                          <select
                            id="gender"
                            value={gender}
                            onChange={(e) => setGender(e.target.value)}
                            className="h-9 w-full rounded-md border border-slate-200 bg-white px-3 py-1 text-xs text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                          >
                            <option value="">Not specified</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                          </select>
                        </div>
                      </div>

                      <Separator className="my-2" />

                      <div className="space-y-1.5">
                        <Label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                          Assigned Administrative Role
                        </Label>
                        <div className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/60">
                          <ShieldCheck className="h-5 w-5 text-blue-600" />
                          <div className="flex-1 text-xs">
                            <p className="font-semibold text-slate-900 dark:text-slate-100">
                              {roleLabel}
                            </p>
                            <p className="text-[11px] text-slate-500">
                              Role assignments and permissions are strictly managed in the RBAC Governance module.
                            </p>
                          </div>
                          <Link
                            href="/settings/rbac"
                            className="text-[11px] font-semibold text-blue-600 hover:underline dark:text-blue-400"
                          >
                            View RBAC
                          </Link>
                        </div>
                      </div>
                    </>
                  )}
                </CardContent>

                <CardFooter className="flex justify-end gap-3 border-t border-slate-100 bg-slate-50/50 px-6 py-3.5 dark:border-slate-800 dark:bg-slate-950/30">
                  <Button
                    type="submit"
                    disabled={isSavingProfile || isLoadingProfile}
                    className="h-9 bg-blue-600 px-4 text-xs font-semibold text-white hover:bg-blue-700"
                  >
                    {isSavingProfile && <Loader2 className="mr-2 h-3.5 w-3.5 animate-spin" />}
                    Save Profile Changes
                  </Button>
                </CardFooter>
              </form>
            </Card>
          </div>
        </div>
      )}

      {/* TAB 2: SECURITY */}
      {activeTab === "security" && (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
          {/* Left Column: Password Management */}
          <div className="space-y-6 lg:col-span-2">
            <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
              <CardHeader>
                <CardTitle className="text-base font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                  <Lock className="h-4 w-4 text-blue-600" />
                  Change Account Password
                </CardTitle>
                <CardDescription className="text-xs text-slate-500 dark:text-slate-400">
                  Ensure your account is using a long, high-entropy password to protect administrative systems.
                </CardDescription>
              </CardHeader>

              <form onSubmit={handleSavePassword}>
                <CardContent className="space-y-4">
                  {passwordSuccessMsg && (
                    <div className="flex items-center gap-2 rounded-md border border-emerald-500/30 bg-emerald-50 p-3 text-xs text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                      <CheckCircle2 className="h-4 w-4 shrink-0 text-emerald-600" />
                      <span>{passwordSuccessMsg}</span>
                    </div>
                  )}

                  {passwordErrorMsg && (
                    <div className="flex items-center gap-2 rounded-md border border-rose-500/30 bg-rose-50 p-3 text-xs text-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                      <AlertCircle className="h-4 w-4 shrink-0 text-rose-600" />
                      <span>{passwordErrorMsg}</span>
                    </div>
                  )}

                  <div className="space-y-1.5">
                    <Label htmlFor="current_password" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                      Current Password <span className="text-rose-500">*</span>
                    </Label>
                    <div className="relative">
                      <Input
                        id="current_password"
                        type={showCurrentPw ? "text" : "password"}
                        value={currentPassword}
                        onChange={(e) => setCurrentPassword(e.target.value)}
                        placeholder="Enter current password"
                        required
                        className="h-9 pr-9 text-xs"
                      />
                      <button
                        type="button"
                        onClick={() => setShowCurrentPw(!showCurrentPw)}
                        className="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        title={showCurrentPw ? "Hide password" : "Show password"}
                      >
                        {showCurrentPw ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </button>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div className="space-y-1.5">
                      <Label htmlFor="new_password" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                        New Password <span className="text-rose-500">*</span>
                      </Label>
                      <div className="relative">
                        <Input
                          id="new_password"
                          type={showNewPw ? "text" : "password"}
                          value={newPassword}
                          onChange={(e) => setNewPassword(e.target.value)}
                          placeholder="At least 8 characters"
                          required
                          className="h-9 pr-9 text-xs"
                        />
                        <button
                          type="button"
                          onClick={() => setShowNewPw(!showNewPw)}
                          className="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                          title={showNewPw ? "Hide password" : "Show password"}
                        >
                          {showNewPw ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                        </button>
                      </div>
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="confirm_password" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                        Confirm New Password <span className="text-rose-500">*</span>
                      </Label>
                      <div className="relative">
                        <Input
                          id="confirm_password"
                          type={showConfirmPw ? "text" : "password"}
                          value={confirmPassword}
                          onChange={(e) => setConfirmPassword(e.target.value)}
                          placeholder="Re-type new password"
                          required
                          className="h-9 pr-9 text-xs"
                        />
                        <button
                          type="button"
                          onClick={() => setShowConfirmPw(!showConfirmPw)}
                          className="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                          title={showConfirmPw ? "Hide password" : "Show password"}
                        >
                          {showConfirmPw ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                        </button>
                      </div>
                    </div>
                  </div>

                  {/* Password Requirements Checklist */}
                  <div className="rounded-lg border border-slate-100 bg-slate-50 p-3.5 text-[11px] text-slate-600 dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-400">
                    <p className="font-semibold text-slate-800 dark:text-slate-200 mb-1.5">Password Policy Requirements:</p>
                    <ul className="space-y-1">
                      <li className="flex items-center gap-1.5">
                        <Check className={`h-3.5 w-3.5 ${newPassword.length >= 8 ? "text-emerald-500" : "text-slate-400"}`} />
                        <span className={newPassword.length >= 8 ? "text-emerald-700 dark:text-emerald-400 font-medium" : ""}>
                          Minimum 8 characters in length
                        </span>
                      </li>
                      <li className="flex items-center gap-1.5">
                        <Check className={`h-3.5 w-3.5 ${newPassword && newPassword === confirmPassword ? "text-emerald-500" : "text-slate-400"}`} />
                        <span className={newPassword && newPassword === confirmPassword ? "text-emerald-700 dark:text-emerald-400 font-medium" : ""}>
                          New password and confirmation match
                        </span>
                      </li>
                    </ul>
                  </div>
                </CardContent>

                <CardFooter className="flex justify-end gap-3 border-t border-slate-100 bg-slate-50/50 px-6 py-3.5 dark:border-slate-800 dark:bg-slate-950/30">
                  <Button
                    type="submit"
                    disabled={isSavingPassword || !currentPassword || !newPassword || !confirmPassword}
                    className="h-9 bg-blue-600 px-4 text-xs font-semibold text-white hover:bg-blue-700"
                  >
                    {isSavingPassword && <Loader2 className="mr-2 h-3.5 w-3.5 animate-spin" />}
                    Update Password
                  </Button>
                </CardFooter>
              </form>
            </Card>
          </div>

          {/* Right Column: Sessions & Audit Trail Link */}
          <div className="space-y-6 lg:col-span-1">
            {/* Active Session Details */}
            <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
              <CardHeader className="pb-3">
                <CardTitle className="text-sm font-semibold flex items-center gap-2">
                  <Shield className="h-4 w-4 text-blue-600" />
                  Active Admin Session
                </CardTitle>
                <CardDescription className="text-xs text-slate-500 dark:text-slate-400">
                  Current authenticated browser environment.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-3 text-xs">
                <div className="flex items-center justify-between">
                  <span className="text-slate-500">Status</span>
                  <Badge variant="outline" className="border-emerald-500/30 bg-emerald-50 text-[10px] font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                    Active Now
                  </Badge>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-slate-500">Role Authority</span>
                  <span className="font-semibold text-slate-900 dark:text-slate-200">{roleLabel}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-slate-500">Signed In As</span>
                  <span className="font-medium text-slate-900 dark:text-slate-200 truncate max-w-[150px]">{user?.email}</span>
                </div>

                <Separator className="my-2" />

                <Button
                  type="button"
                  variant="destructive"
                  size="sm"
                  onClick={logout}
                  className="w-full text-xs font-semibold"
                >
                  <LogOut className="mr-2 h-3.5 w-3.5" />
                  Sign Out of Dashboard
                </Button>
              </CardContent>
            </Card>

            {/* Audit Trail Quick Link */}
            <Card className="border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
              <CardHeader className="pb-3">
                <CardTitle className="text-sm font-semibold flex items-center gap-2">
                  <History className="h-4 w-4 text-purple-600" />
                  Security Audit Trail
                </CardTitle>
                <CardDescription className="text-xs text-slate-500 dark:text-slate-400">
                  Every administrative action is immutably logged for institutional compliance.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-3 text-xs">
                <p className="text-slate-600 dark:text-slate-400">
                  Inspect logins, role modifications, curriculum updates, and moderation records in real time.
                </p>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  asChild
                  className="w-full justify-between text-xs"
                >
                  <Link href="/settings/audit-logs">
                    <span className="flex items-center gap-1.5">
                      <History className="h-3.5 w-3.5 text-purple-600" />
                      View Immutable Audit Logs
                    </span>
                    <ArrowRight className="h-3.5 w-3.5" />
                  </Link>
                </Button>
              </CardContent>
            </Card>
          </div>
        </div>
      )}
    </div>
  )
}

export default function AdminProfilePage() {
  return (
    <Suspense
      fallback={
        <div className="flex h-64 items-center justify-center">
          <Loader2 className="h-6 w-6 animate-spin text-blue-600" />
        </div>
      }
    >
      <AdminProfileContent />
    </Suspense>
  )
}
