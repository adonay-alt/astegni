"use client"

import React, { useState, useEffect } from "react"
import Link from "next/link"
import { apiClient } from "@/lib/api-client"
import { ContactSetting } from "@/types/contact-setting"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import {
  PhoneCall,
  Mail,
  Phone,
  Save,
  CheckCircle2,
  AlertCircle,
  Loader2,
  X,
  ExternalLink,
  Sparkles,
  Inbox,
  Smartphone,
  ShieldCheck,
  RefreshCw,
} from "lucide-react"

export default function ContactSettingsPage() {
  const [formData, setFormData] = useState<ContactSetting>({
    email: "support@asteghi.com",
    phone: "+251 911 000 000",
    secondary_phone: "",
  })

  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)
  const [toast, setToast] = useState<{ type: "success" | "error"; text: string } | null>(null)

  const showToast = (type: "success" | "error", text: string) => {
    setToast({ type, text })
    setTimeout(() => {
      setToast((prev) => (prev?.text === text ? null : prev))
    }, 4500)
  }

  // Fetch current settings on load
  const fetchSettings = async () => {
    setIsLoading(true)
    try {
      const res = await apiClient.get("/admin/settings/contact")
      if (res.data?.status === "success" && res.data.data) {
        const d = res.data.data
        setFormData({
          email: d.email || "support@asteghi.com",
          phone: d.phone || "+251 911 000 000",
          secondary_phone: d.secondary_phone || "",
        })
      }
    } catch (err: any) {
      console.error("Failed to fetch contact settings:", err)
      showToast("error", err.response?.data?.message || "Failed to load contact settings.")
    } finally {
      setIsLoading(false)
    }
  }

  useEffect(() => {
    fetchSettings()
  }, [])

  // Handle Save
  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!formData.email.trim() || !formData.phone.trim()) {
      showToast("error", "Email and Primary Phone are required fields.")
      return
    }

    setIsSaving(true)
    try {
      const res = await apiClient.put("/admin/settings/contact", {
        email: formData.email.trim(),
        phone: formData.phone.trim(),
        secondary_phone: formData.secondary_phone?.trim() || null,
      })

      if (res.data?.status === "success") {
        showToast("success", res.data?.message || "Platform contact settings updated successfully!")
        if (res.data.data) {
          setFormData((prev) => ({ ...prev, ...res.data.data }))
        }
      } else {
        showToast("error", res.data?.message || "Failed to update settings.")
      }
    } catch (err: any) {
      console.error("Failed to update contact settings:", err)
      const errorMsg =
        err.response?.data?.message ||
        (err.response?.data?.errors
          ? Object.values(err.response.data.errors).flat().join(" ")
          : "An unexpected error occurred while saving.")
      showToast("error", errorMsg)
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <div className="space-y-6 max-w-5xl mx-auto">
      {/* Toast Notification Banner */}
      {toast && (
        <div
          className={`fixed top-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-lg shadow-xl border backdrop-blur-md transition-all animate-in fade-in slide-in-from-top-4 ${
            toast.type === "success"
              ? "bg-emerald-950/90 border-emerald-500/40 text-emerald-200"
              : "bg-rose-950/90 border-rose-500/40 text-rose-200"
          }`}
        >
          {toast.type === "success" ? (
            <CheckCircle2 className="w-5 h-5 text-emerald-400 shrink-0" />
          ) : (
            <AlertCircle className="w-5 h-5 text-rose-400 shrink-0" />
          )}
          <span className="text-sm font-medium">{toast.text}</span>
          <button
            onClick={() => setToast(null)}
            className="ml-2 text-slate-400 hover:text-white transition-colors"
          >
            <X className="w-4 h-4" />
          </button>
        </div>
      )}

      {/* Header & Quick Navigation */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2.5">
            <div className="p-2 rounded-lg bg-indigo-600/10 text-indigo-500 border border-indigo-500/20">
              <PhoneCall className="w-6 h-6" />
            </div>
            <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
              Platform Contact Settings
            </h1>
            <Badge
              variant="outline"
              className="bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20 gap-1.5 px-2.5 py-0.5 text-xs font-semibold"
            >
              Public Broadcast
            </Badge>
          </div>
          <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Manage the official support email and phone numbers displayed to students on the public app.
          </p>
        </div>

        <div className="flex items-center gap-2.5">
          <Link href="/support/contacts">
            <Button
              variant="outline"
              size="sm"
              className="border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 gap-1.5"
            >
              <Inbox className="w-4 h-4" />
              Support Inbox
            </Button>
          </Link>
          <Button
            variant="outline"
            size="sm"
            onClick={fetchSettings}
            disabled={isLoading}
            className="border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
          >
            <RefreshCw className={`w-4 h-4 mr-1.5 ${isLoading ? "animate-spin" : ""}`} />
            Refresh
          </Button>
        </div>
      </div>

      {isLoading ? (
        <Card className="border-slate-200 dark:border-slate-800 p-12 text-center">
          <Loader2 className="w-8 h-8 animate-spin text-indigo-500 mx-auto mb-3" />
          <p className="text-sm text-slate-500 dark:text-slate-400">Loading platform contact settings...</p>
        </Card>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
          {/* LEFT: Settings Editor Form (7 cols) */}
          <div className="lg:col-span-7">
            <Card className="border-slate-200 dark:border-slate-800 shadow-sm">
              <CardHeader className="p-5 pb-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-900/40">
                <CardTitle className="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                  <ShieldCheck className="w-4 h-4 text-indigo-500" />
                  Official Contact Channels
                </CardTitle>
                <CardDescription className="text-xs text-slate-500 dark:text-slate-400">
                  Changes made here update MySQL, invalidate public caches immediately, and sync to student apps.
                </CardDescription>
              </CardHeader>

              <CardContent className="p-6">
                <form onSubmit={handleSave} className="space-y-4">
                  {/* Primary Email */}
                  <div className="space-y-1.5">
                    <label className="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                      <Mail className="w-3.5 h-3.5 text-blue-500" />
                      Primary Support Email <span className="text-rose-500">*</span>
                    </label>
                    <Input
                      type="email"
                      required
                      placeholder="support@asteghi.com"
                      value={formData.email}
                      onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                      className="text-xs bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 font-mono"
                    />
                    <p className="text-[11px] text-slate-400">
                      Displayed on the website footer, student app, and used for official student inquiries.
                    </p>
                  </div>

                  {/* Primary Phone */}
                  <div className="space-y-1.5">
                    <label className="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                      <Phone className="w-3.5 h-3.5 text-emerald-500" />
                      Primary Support Phone Number <span className="text-rose-500">*</span>
                    </label>
                    <Input
                      type="text"
                      required
                      placeholder="+251 911 000 000"
                      value={formData.phone}
                      onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                      className="text-xs bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 font-mono"
                    />
                    <p className="text-[11px] text-slate-400">
                      Direct hotline for student support calls and verification.
                    </p>
                  </div>

                  {/* Secondary Phone */}
                  <div className="space-y-1.5">
                    <label className="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                      <PhoneCall className="w-3.5 h-3.5 text-cyan-500" />
                      Secondary Phone (Optional)
                    </label>
                    <Input
                      type="text"
                      placeholder="+251 922 000 000"
                      value={formData.secondary_phone || ""}
                      onChange={(e) => setFormData({ ...formData, secondary_phone: e.target.value })}
                      className="text-xs bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 font-mono"
                    />
                    <p className="text-[11px] text-slate-400">
                      Alternative phone number for student inquiries.
                    </p>
                  </div>

                  {/* Submit Button */}
                  <div className="pt-3">
                    <Button
                      type="submit"
                      disabled={isSaving}
                      className="h-10 px-6 text-xs font-semibold bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-md shadow-blue-500/20 gap-2 transition-all w-full sm:w-auto"
                    >
                      {isSaving ? (
                        <>
                          <Loader2 className="w-4 h-4 animate-spin" />
                          Saving Contact Settings...
                        </>
                      ) : (
                        <>
                          <Save className="w-4 h-4" />
                          💾 Save Contact Settings
                        </>
                      )}
                    </Button>
                  </div>
                </form>
              </CardContent>
            </Card>
          </div>

          {/* RIGHT: Live Student Visual Preview Card (5 cols) */}
          <div className="lg:col-span-5 space-y-4">
            <Card className="border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
              <CardHeader className="p-4 pb-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Smartphone className="w-4 h-4 text-indigo-500" />
                  <span className="text-xs font-bold text-slate-800 dark:text-slate-200">
                    Student Mobile & Web Live Preview
                  </span>
                </div>
                <Badge variant="outline" className="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30 text-[10px] py-0 px-1.5 font-medium">
                  Live View
                </Badge>
              </CardHeader>

              <CardContent className="p-5 space-y-4 bg-slate-50/30 dark:bg-slate-950/20">
                <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-md p-5 space-y-4">
                  {/* Card Header inside preview */}
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-sm">
                      <Sparkles className="w-5 h-5" />
                    </div>
                    <div>
                      <h4 className="text-sm font-bold text-slate-900 dark:text-white">
                        Need Help & Assistance?
                      </h4>
                      <p className="text-[11px] text-slate-500 dark:text-slate-400">
                        Official Asteghi Student Care
                      </p>
                    </div>
                  </div>

                  {/* Contact Channels List */}
                  <div className="space-y-2">
                    {/* Primary Phone button */}
                    <a
                      href={`tel:${formData.phone}`}
                      className="flex items-center justify-between p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:border-emerald-400 bg-slate-50/50 dark:bg-slate-800/40 transition-colors group"
                    >
                      <div className="flex items-center gap-2.5">
                        <div className="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                          <Phone className="w-4 h-4" />
                        </div>
                        <div>
                          <p className="text-[11px] font-medium text-slate-400">Call Support Hotline</p>
                          <p className="text-xs font-bold font-mono text-slate-800 dark:text-slate-200">
                            {formData.phone}
                          </p>
                        </div>
                      </div>
                      <ExternalLink className="w-3.5 h-3.5 text-slate-400 group-hover:text-emerald-500 transition-colors" />
                    </a>

                    {/* Secondary Phone if set */}
                    {formData.secondary_phone && (
                      <a
                        href={`tel:${formData.secondary_phone}`}
                        className="flex items-center justify-between p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:border-cyan-400 bg-slate-50/50 dark:bg-slate-800/40 transition-colors group"
                      >
                        <div className="flex items-center gap-2.5">
                          <div className="w-8 h-8 rounded-lg bg-cyan-100 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 flex items-center justify-center">
                            <PhoneCall className="w-4 h-4" />
                          </div>
                          <div>
                            <p className="text-[11px] font-medium text-slate-400">Secondary Line</p>
                            <p className="text-xs font-bold font-mono text-slate-800 dark:text-slate-200">
                              {formData.secondary_phone}
                            </p>
                          </div>
                        </div>
                        <ExternalLink className="w-3.5 h-3.5 text-slate-400 group-hover:text-cyan-500 transition-colors" />
                      </a>
                    )}

                    {/* Email button */}
                    <a
                      href={`mailto:${formData.email}`}
                      className="flex items-center justify-between p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:border-blue-400 bg-slate-50/50 dark:bg-slate-800/40 transition-colors group"
                    >
                      <div className="flex items-center gap-2.5">
                        <div className="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                          <Mail className="w-4 h-4" />
                        </div>
                        <div>
                          <p className="text-[11px] font-medium text-slate-400">Official Support Email</p>
                          <p className="text-xs font-bold font-mono text-slate-800 dark:text-slate-200">
                            {formData.email}
                          </p>
                        </div>
                      </div>
                      <ExternalLink className="w-3.5 h-3.5 text-slate-400 group-hover:text-blue-500 transition-colors" />
                    </a>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      )}
    </div>
  )
}
