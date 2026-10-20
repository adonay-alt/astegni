"use client"

import React from "react"
import Link from "next/link"
import { usePathname } from "next/navigation"
import { cn } from "@/lib/utils"
import { Badge } from "@/components/ui/badge"
import {
  LayoutDashboard,
  GraduationCap,
  Layers,
  BookOpen,
  HelpCircle,
  Award,
  FileQuestion,
  UploadCloud,
  UserCheck,
  Users,
  Star,
  UserCog,
  ShieldCheck,
  BellRing,
  MessageSquareQuote,
  FileText,
  Sparkles,
  History,
  ChevronRight,
  Coins,
  Mail,
  PhoneCall,
  Inbox,
  LogOut,
} from "lucide-react"
import { hasModuleAccess, getDefaultRouteForRole, NavModule } from "@/lib/rbac"
import { useAuth } from "@/context/auth-context"

interface NavItem {
  title: string
  href: string
  icon: React.ComponentType<{ className?: string }>
  badge?: {
    text: string
    variant?: "default" | "secondary" | "destructive" | "outline" | "warning" | "success"
  }
}

interface NavGroup {
  label: string
  module: NavModule
  items: NavItem[]
}

export const navGroups: NavGroup[] = [
  {
    label: "Analytics & Overview",
    module: "dashboard",
    items: [
      {
        title: "Dashboard",
        href: "/",
        icon: LayoutDashboard,
      },
    ],
  },
  {
    label: "Academic Curriculum (LMS)",
    module: "curriculum",
    items: [
      {
        title: "Grades & Streams",
        href: "/curriculum/grades",
        icon: Layers,
      },
      {
        title: "Subjects Catalog",
        href: "/curriculum/subjects",
        icon: BookOpen,
      },
      {
        title: "Chapters & Content Hub",
        href: "/curriculum/chapters",
        icon: GraduationCap,
      },
      {
        title: "Quizzes & Questions",
        href: "/curriculum/quizzes",
        icon: HelpCircle,
      },
      {
        title: "Chapter Notes",
        href: "/curriculum/notes",
        icon: FileText,
      },
      {
        title: "AI Prompts & Settings",
        href: "/curriculum/ai-prompts",
        icon: Sparkles,
      },
      {
        title: "Resources & Pricing",
        href: "/curriculum/resources",
        icon: Coins,
      },
    ],
  },
  {
    label: "National Exam Engine",
    module: "national-exams",
    items: [
      {
        title: "National Exams Studio",
        href: "/national-exams",
        icon: Award,
      },
      {
        title: "Question Importer",
        href: "/national-exams/import",
        icon: UploadCloud,
      },
    ],
  },
  {
    label: "Tutor Marketplace",
    module: "tutors",
    items: [
      {
        title: "Verification Queue",
        href: "/tutors",
        icon: UserCheck,
      },
      {
        title: "Tutors Directory",
        href: "/tutors/directory",
        icon: Users,
      },
      {
        title: "Tutor Ratings",
        href: "/tutors/reviews",
        icon: Star,
      },
    ],
  },
  {
    label: "Users & Access Control",
    module: "users",
    items: [
      {
        title: "Staff & RBAC Roles",
        href: "/settings/rbac",
        icon: ShieldCheck,
      },
    ],
  },
  {
    label: "CMS & Marketing",
    module: "marketing",
    items: [
      {
        title: "Latest Updates",
        href: "/marketing/updates",
        icon: BellRing,
      },
      {
        title: "Platform Testimonials",
        href: "/marketing/reviews",
        icon: MessageSquareQuote,
      },
      {
        title: "Legal & FAQ Pages",
        href: "/marketing/pages",
        icon: FileText,
      },
    ],
  },
  {
    label: "Support & Communications",
    module: "support",
    items: [
      {
        title: "Support Inbox",
        href: "/support/contacts",
        icon: Inbox,
      },
      {
        title: "Contact Info Settings",
        href: "/settings/contact",
        icon: PhoneCall,
      },
    ],
  },
  {
    label: "System & Governance",
    module: "settings",
    items: [
      {
        title: "Admin Profile & Security",
        href: "/settings/profile",
        icon: ShieldCheck,
      },
      {
        title: "Immutable Audit Trail",
        href: "/settings/audit-logs",
        icon: History,
      },
    ],
  },
]

interface SidebarProps {
  className?: string
  onNavigate?: () => void
}

export function Sidebar({ className, onNavigate }: SidebarProps) {
  const pathname = usePathname()
  const { user, effectiveRole, logout } = useAuth()
  const role = effectiveRole || user?.role || "academic_editor"

  // Filter navigation groups by staff role permissions
  const activeNavGroups = React.useMemo(() => {
    return navGroups.filter((g) => hasModuleAccess(role, g.module))
  }, [role])

  const allNavHrefs = React.useMemo(
    () => activeNavGroups.flatMap((g) => g.items.map((i) => i.href)),
    [activeNavGroups]
  )

  const brandHref = getDefaultRouteForRole(role)

  return (
    <aside
      className={cn(
        "flex h-full w-72 flex-col border-r border-slate-200 bg-white text-slate-900",
        className
      )}
    >
      {/* Brand Header */}
      <div className="flex h-16 shrink-0 items-center justify-between border-b border-slate-200 px-6">
        <Link
          href={brandHref}
          className="flex flex-col font-bold tracking-tight text-slate-950 hover:opacity-90 transition-opacity"
          onClick={onNavigate}
        >
          <span className="text-xl font-black leading-tight tracking-tight text-slate-950">Astegni</span>
          <span className="text-[10px] font-bold tracking-wider uppercase text-slate-400">
            Admin Platform
          </span>
        </Link>
      </div>

      {/* Nav Groups Container */}
      <div className="flex-1 overflow-y-auto px-4 py-4 space-y-6">
        {activeNavGroups.map((group) => (
          <div key={group.label} className="space-y-1.5">
            <h4 className="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">
              {group.label}
            </h4>
            <div className="space-y-0.5">
              {group.items.map((item) => {
                const Icon = item.icon
                const isActive =
                  item.href === "/"
                    ? pathname === "/"
                    : pathname === item.href ||
                      (pathname.startsWith(item.href + "/") &&
                        !allNavHrefs.some(
                          (otherHref) =>
                            otherHref !== item.href &&
                            otherHref.startsWith(item.href) &&
                            (pathname === otherHref || pathname.startsWith(otherHref + "/"))
                        ))

                return (
                  <Link
                    key={item.href}
                    href={item.href}
                    onClick={onNavigate}
                    className={cn(
                      "group flex items-center justify-between rounded-xl px-3 py-2 text-sm font-medium transition-all",
                      isActive
                        ? "bg-black text-white shadow-sm font-bold ring-1 ring-black"
                        : "text-slate-700 hover:bg-slate-100 hover:text-black"
                    )}
                  >
                    <div className="flex items-center gap-3">
                      <Icon
                        className={cn(
                          "h-4 w-4 shrink-0 transition-colors",
                          isActive
                            ? "text-white"
                            : "text-slate-500 group-hover:text-black"
                        )}
                      />
                      <span>{item.title}</span>
                    </div>

                    {item.badge ? (
                      <Badge
                        variant={item.badge.variant || "secondary"}
                        className={cn(
                          "text-[10px] px-1.5 py-0 font-semibold uppercase tracking-wider",
                          isActive &&
                            "bg-white/20 text-white border-white/20"
                        )}
                      >
                        {item.badge.text}
                      </Badge>
                    ) : isActive ? (
                      <ChevronRight className="h-3.5 w-3.5 text-white opacity-80" />
                    ) : null}
                  </Link>
                )
              })}
            </div>
          </div>
        ))}
      </div>

      {/* Sidebar Footer with Sign Out */}
      <div className="shrink-0 border-t border-slate-200 p-3 space-y-2 bg-slate-50/70">
        <div className="flex items-center gap-2.5 px-2 py-1">
          <div className="h-8 w-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
            {user?.name ? user.name.charAt(0).toUpperCase() : "A"}
          </div>
          <div className="flex flex-col min-w-0 flex-1">
            <span className="text-xs font-semibold text-slate-900 truncate">
              {user?.name || "Administrator"}
            </span>
            <span className="text-[10px] text-slate-500 truncate capitalize font-medium">
              {role.replace(/_/g, " ")}
            </span>
          </div>
        </div>

        <button
          onClick={logout}
          className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors border border-transparent hover:border-red-200 cursor-pointer"
        >
          <LogOut className="h-4 w-4 shrink-0 text-red-500" />
          <span>Sign Out of Console</span>
        </button>
      </div>
    </aside>
  )
}
