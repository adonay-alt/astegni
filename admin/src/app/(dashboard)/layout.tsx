"use client"

import React, { useEffect } from "react"
import { usePathname, useRouter } from "next/navigation"
import { useAuth } from "@/context/auth-context"
import { isRouteAllowed, getDefaultRouteForRole, hasModuleAccess } from "@/lib/rbac"
import { Sidebar } from "@/components/layout/sidebar"
import { Topbar } from "@/components/layout/topbar"
import { Loader2 } from "lucide-react"

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode
}) {
  const { user, isAuthenticated, isLoading, effectiveRole } = useAuth()
  const pathname = usePathname()
  const router = useRouter()

  const role = effectiveRole || user?.role || "academic_editor"
  const isAllowed = isRouteAllowed(role, pathname)

  useEffect(() => {
    if (!isLoading) {
      if (!isAuthenticated || !user) {
        router.replace("/login")
        return
      }

      // 1. If user lands on / or /dashboard without dashboard access, redirect immediately to their authorized home
      if ((pathname === "/" || pathname === "/dashboard" || pathname === "") && !hasModuleAccess(role, "dashboard")) {
        router.replace(getDefaultRouteForRole(role))
        return
      }

      // 2. If accessing any unauthorized route, redirect immediately to their default home
      if (!isRouteAllowed(role, pathname)) {
        router.replace(getDefaultRouteForRole(role))
      }
    }
  }, [isLoading, isAuthenticated, user, pathname, role, router])

  if (isLoading) {
    return (
      <div className="flex min-h-screen flex-col items-center justify-center bg-slate-50">
        <div className="flex flex-col items-center gap-3">
          <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
          <p className="text-sm font-semibold text-slate-600">Initializing Astegni Admin Engine...</p>
        </div>
      </div>
    )
  }

  if (!isAuthenticated || !user) {
    return null
  }

  // If not allowed, show redirecting state to prevent forbidden child queries
  if (!isAllowed) {
    return (
      <div className="flex min-h-screen flex-col items-center justify-center bg-slate-50">
        <div className="flex flex-col items-center gap-3">
          <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
          <p className="text-sm font-semibold text-slate-600">Redirecting to authorized workspace...</p>
        </div>
      </div>
    )
  }

  return (
    <div className="flex min-h-screen flex-col bg-slate-50">
      <div className="flex flex-1 overflow-hidden">
        {/* Desktop Sidebar (Fixed Left) */}
        <Sidebar className="hidden lg:flex" />

        {/* Content Viewport */}
        <div className="flex flex-1 flex-col overflow-y-auto">
          <Topbar />
          <main className="flex-1 p-4 sm:p-6 lg:p-8">
            <div className="mx-auto max-w-7xl">
              {children}
            </div>
          </main>
        </div>
      </div>
    </div>
  )
}
