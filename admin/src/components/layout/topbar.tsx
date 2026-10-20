"use client"

import React, { useState } from "react"
import { useRouter } from "next/navigation"
import { useAuth } from "@/context/auth-context"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Badge } from "@/components/ui/badge"
import { cn } from "@/lib/utils"
import { Sidebar } from "./sidebar"
import {
  Menu,
  LogOut,
  User,
  Shield,
  ExternalLink,
  Sparkles,
  ChevronDown,
  Check,
  RotateCcw,
} from "lucide-react"

import { normalizeRole } from "@/lib/rbac"

export function Topbar() {
  const router = useRouter()
  const { user, logout, simulatedRole, setSimulatedRole, effectiveRole } = useAuth()
  const [mobileOpen, setMobileOpen] = useState(false)
  const isSuperAdmin = normalizeRole(user?.role) === "super_admin"

  const userInitials = user?.name
    ? user.name
        .split(" ")
        .map((n) => n[0])
        .slice(0, 2)
        .join("")
        .toUpperCase()
    : "AD"

  return (
    <header className="sticky top-0 z-30 flex h-16 w-full items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6">
      <div className="flex items-center gap-3">
        {/* Mobile Navigation Drawer Trigger */}
        <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
          <SheetTrigger asChild>
            <Button
              variant="ghost"
              size="icon"
              className="lg:hidden text-slate-700 hover:bg-slate-100 cursor-pointer"
            >
              <Menu className="h-5 w-5" />
              <span className="sr-only">Toggle Sidebar</span>
            </Button>
          </SheetTrigger>
          <SheetContent side="left" className="p-0 w-72">
            <Sidebar onNavigate={() => setMobileOpen(false)} />
          </SheetContent>
        </Sheet>
      </div>

      <div className="flex items-center gap-3">
        {/* Super Admin RBAC Role Simulator */}
        {isSuperAdmin && (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button
                variant="outline"
                size="sm"
                className={cn(
                  "h-8 gap-1.5 text-xs font-semibold rounded-lg shadow-xs cursor-pointer border transition-colors",
                  simulatedRole
                    ? "bg-amber-50 text-amber-800 border-amber-300 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800"
                    : "bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800"
                )}
              >
                <Sparkles className="h-3.5 w-3.5 text-amber-600 dark:text-amber-400" />
                <span className="hidden sm:inline text-slate-500 font-normal">Preview:</span>
                <span className="font-extrabold capitalize text-blue-600 dark:text-blue-400">
                  {effectiveRole.replace("_", " ")}
                </span>
                <ChevronDown className="h-3 w-3 opacity-60" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
              align="end"
              className="w-64 border-slate-200 bg-white shadow-xl text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
            >
              <DropdownMenuLabel className="text-[11px] font-bold text-slate-500 uppercase tracking-wider px-2 py-1.5">
                🎭 RBAC Role Previewer
              </DropdownMenuLabel>
              <DropdownMenuSeparator />

              <DropdownMenuItem
                onClick={() => setSimulatedRole(null)}
                className="flex items-center justify-between cursor-pointer text-xs font-medium py-2 hover:bg-slate-50"
              >
                <div className="flex items-center gap-2">
                  <span className="text-base">🛡️</span>
                  <div>
                    <p className="font-bold text-slate-900">Super Admin</p>
                    <p className="text-[10px] text-slate-500">Unrestricted system access</p>
                  </div>
                </div>
                {!simulatedRole && <Check className="h-4 w-4 text-emerald-600" />}
              </DropdownMenuItem>

              <DropdownMenuItem
                onClick={() => setSimulatedRole("academic_editor")}
                className="flex items-center justify-between cursor-pointer text-xs font-medium py-2 hover:bg-slate-50"
              >
                <div className="flex items-center gap-2">
                  <span className="text-base">🎓</span>
                  <div>
                    <p className="font-bold text-slate-900">Academic Editor</p>
                    <p className="text-[10px] text-slate-500">Curriculum & National Exams only</p>
                  </div>
                </div>
                {simulatedRole === "academic_editor" && (
                  <Check className="h-4 w-4 text-emerald-600" />
                )}
              </DropdownMenuItem>

              <DropdownMenuItem
                onClick={() => setSimulatedRole("tutor_moderator")}
                className="flex items-center justify-between cursor-pointer text-xs font-medium py-2 hover:bg-slate-50"
              >
                <div className="flex items-center gap-2">
                  <span className="text-base">👨‍🏫</span>
                  <div>
                    <p className="font-bold text-slate-900">Tutor Moderator</p>
                    <p className="text-[10px] text-slate-500">Tutor Marketplace & reviews only</p>
                  </div>
                </div>
                {simulatedRole === "tutor_moderator" && (
                  <Check className="h-4 w-4 text-emerald-600" />
                )}
              </DropdownMenuItem>

              <DropdownMenuItem
                onClick={() => setSimulatedRole("support_moderator")}
                className="flex items-center justify-between cursor-pointer text-xs font-medium py-2 hover:bg-slate-50"
              >
                <div className="flex items-center gap-2">
                  <span className="text-base">🎧</span>
                  <div>
                    <p className="font-bold text-slate-900">Support Moderator</p>
                    <p className="text-[10px] text-slate-500">Support Inbox & Contact Settings Only</p>
                  </div>
                </div>
                {simulatedRole === "support_moderator" && (
                  <Check className="h-4 w-4 text-emerald-600" />
                )}
              </DropdownMenuItem>

              {simulatedRole && (
                <>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    onClick={() => setSimulatedRole(null)}
                    className="cursor-pointer text-xs font-semibold text-amber-700 dark:text-amber-400 focus:bg-amber-50"
                  >
                    <RotateCcw className="mr-2 h-3.5 w-3.5" />
                    <span>Exit Role Preview</span>
                  </DropdownMenuItem>
                </>
              )}
            </DropdownMenuContent>
          </DropdownMenu>
        )}

        {/* User Profile Dropdown */}
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button
              variant="ghost"
              className="relative flex h-9 items-center gap-2 rounded-full p-1 hover:bg-slate-100 cursor-pointer"
            >
              <Avatar className="h-8 w-8 border border-slate-200 shadow-xs">
                <AvatarImage src={user?.avatar_url || undefined} alt={user?.name || "Admin"} />
                <AvatarFallback className="bg-blue-600 text-[11px] font-bold text-white">
                  {userInitials}
                </AvatarFallback>
              </Avatar>
              <div className="hidden flex-col text-left lg:flex">
                <span className="text-xs font-bold text-slate-900">
                  {user?.name || "System Admin"}
                </span>
                <span className="text-[10px] font-semibold text-slate-500 capitalize">
                  {normalizeRole(effectiveRole).replace("_", " ")}
                </span>
              </div>
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-56 border-slate-200 bg-white text-slate-900 shadow-lg">
            <DropdownMenuLabel className="font-normal">
              <div className="flex flex-col space-y-1">
                <p className="text-xs font-bold leading-none text-slate-900">
                  {user?.name || "Admin User"}
                </p>
                <p className="text-[11px] leading-none text-slate-500 font-medium">
                  {user?.email || "admin@astegni.com"}
                </p>
              </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator className="bg-slate-100" />
            <DropdownMenuItem
              className="cursor-pointer text-xs font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-900"
              onClick={() => router.push("/settings/profile?tab=profile")}
            >
              <User className="mr-2 h-3.5 w-3.5 text-slate-500" />
              <span>Admin Profile</span>
            </DropdownMenuItem>
            <DropdownMenuItem
              className="cursor-pointer text-xs font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-900"
              onClick={() => router.push("/settings/profile?tab=security")}
            >
              <Shield className="mr-2 h-3.5 w-3.5 text-slate-500" />
              <span>Security</span>
            </DropdownMenuItem>
            <DropdownMenuItem
              className="cursor-pointer text-xs font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-900"
              onClick={() => window.open("https://astegni.com", "_blank")}
            >
              <ExternalLink className="mr-2 h-3.5 w-3.5 text-slate-500" />
              <span>Public Learning Portal</span>
            </DropdownMenuItem>
            <DropdownMenuSeparator className="bg-slate-100" />
            <DropdownMenuItem
              onClick={logout}
              className="cursor-pointer text-xs font-semibold text-rose-600 focus:bg-rose-50 focus:text-rose-700 hover:bg-rose-50 hover:text-rose-700"
            >
              <LogOut className="mr-2 h-3.5 w-3.5" />
              <span>Sign Out</span>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </header>
  )
}
