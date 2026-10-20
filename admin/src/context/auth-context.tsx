"use client"

import React, { createContext, useContext, useEffect, useState, ReactNode } from "react"
import { useRouter } from "next/navigation"
import Cookies from "js-cookie"
import { AdminUser, AdminRole, LoginCredentials, AuthResponse } from "@/types/auth"
import { apiClient, TOKEN_COOKIE_KEY, USER_STORAGE_KEY, LEGACY_TOKEN_KEY, LEGACY_USER_KEY, invalidateApiCache, getActiveToken } from "@/lib/api-client"
import { normalizeRole } from "@/lib/rbac"

interface AuthContextType {
  user: AdminUser | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
  simulatedRole: AdminRole | null
  setSimulatedRole: (role: AdminRole | null) => void
  effectiveRole: AdminRole
  login: (credentials: LoginCredentials) => Promise<{ success: boolean; message?: string; user?: AdminUser }>
  logout: () => Promise<void>
  updateCurrentUser: (userUpdates: Partial<AdminUser>) => void
  refreshCurrentUser: () => Promise<void>
}

const AuthContext = createContext<AuthContextType | undefined>(undefined)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AdminUser | null>(null)
  const [token, setToken] = useState<string | null>(null)
  const [isLoading, setIsLoading] = useState<boolean>(true)
  const [simulatedRole, setSimulatedRoleState] = useState<AdminRole | null>(null)
  const router = useRouter()

  // Initialize simulatedRole from sessionStorage on mount
  useEffect(() => {
    try {
      const storedSimulated = sessionStorage.getItem("asteghi_simulated_role") as AdminRole | null
      if (storedSimulated) {
        setSimulatedRoleState(storedSimulated)
      }
    } catch (e) {}
  }, [])

  const setSimulatedRole = (role: AdminRole | null) => {
    setSimulatedRoleState(role)
    try {
      if (role) {
        sessionStorage.setItem("asteghi_simulated_role", role)
      } else {
        sessionStorage.removeItem("asteghi_simulated_role")
      }
    } catch (e) {}
  }

  const isSuperAdmin = normalizeRole(user?.role) === "super_admin"
  const effectiveRole: AdminRole = isSuperAdmin && simulatedRole ? simulatedRole : normalizeRole(user?.role)

  // Clear simulated role if current user is not a super_admin
  useEffect(() => {
    if (user && normalizeRole(user.role) !== "super_admin") {
      setSimulatedRoleState(null)
      try {
        sessionStorage.removeItem("asteghi_simulated_role")
      } catch (e) {}
    }
  }, [user])

  // Initialize session from cookies/storage on mount
  useEffect(() => {
    try {
      const storedToken = getActiveToken()
      const storedUser =
        localStorage.getItem(USER_STORAGE_KEY) ||
        sessionStorage.getItem(USER_STORAGE_KEY) ||
        localStorage.getItem(LEGACY_USER_KEY) ||
        sessionStorage.getItem(LEGACY_USER_KEY)

      if (storedToken) {
        setToken(storedToken)
        if (storedUser) {
          try {
            setUser(JSON.parse(storedUser))
          } catch (e) {
            console.error("Failed to parse stored user", e)
          }
        }

        // Validate token identity against backend asynchronously
        apiClient
          .get("/user", {
            headers: { Authorization: `Bearer ${storedToken}` },
          })
          .then((res) => {
            const uData = res.data as Record<string, unknown>
            const uNested = (uData.data as Record<string, unknown>) || {}
            const verifiedUser = (uNested.user || uData.user) as Partial<AdminUser> | undefined
            if (verifiedUser && verifiedUser.email) {
              setUser((prev) => (prev ? { ...prev, ...verifiedUser } : null))
            }
          })
          .catch((err) => {
            if (err?.response?.status === 401) {
              Cookies.remove(TOKEN_COOKIE_KEY)
              Cookies.remove(LEGACY_TOKEN_KEY)
              localStorage.removeItem(TOKEN_COOKIE_KEY)
              localStorage.removeItem(LEGACY_TOKEN_KEY)
              localStorage.removeItem(USER_STORAGE_KEY)
              localStorage.removeItem(LEGACY_USER_KEY)
              sessionStorage.removeItem(TOKEN_COOKIE_KEY)
              sessionStorage.removeItem(LEGACY_TOKEN_KEY)
              sessionStorage.removeItem(USER_STORAGE_KEY)
              sessionStorage.removeItem(LEGACY_USER_KEY)
              setToken(null)
              setUser(null)
            }
          })
      }
    } finally {
      setIsLoading(false)
    }
  }, [])

  const updateCurrentUser = (userUpdates: Partial<AdminUser>) => {
    setUser((prev) => {
      if (!prev) return null
      const updated = { ...prev, ...userUpdates }
      if (localStorage.getItem(TOKEN_COOKIE_KEY)) {
        localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(updated))
      }
      if (sessionStorage.getItem(TOKEN_COOKIE_KEY)) {
        sessionStorage.setItem(USER_STORAGE_KEY, JSON.stringify(updated))
      }
      return updated
    })
  }

  const refreshCurrentUser = async () => {
    try {
      const res = await apiClient.get("/user")
      const uData = res.data as Record<string, unknown>
      const uNested = (uData.data as Record<string, unknown>) || {}
      const rawUser = (uNested.user || uData.user || uNested || uData) as Record<string, unknown>
      if (rawUser && rawUser.email) {
        setUser((prev) => {
          if (!prev) return null
          const updated: AdminUser = {
            ...prev,
            name: (rawUser.name as string) || `${rawUser.first_name || ""} ${rawUser.last_name || ""}`.trim() || prev.name,
            email: (rawUser.email as string) || prev.email,
            avatar_url: (rawUser.avatar_url as string) || (rawUser.photo_url as string) || prev.avatar_url,
          }
          if (localStorage.getItem(TOKEN_COOKIE_KEY)) {
            localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(updated))
          }
          if (sessionStorage.getItem(TOKEN_COOKIE_KEY)) {
            sessionStorage.setItem(USER_STORAGE_KEY, JSON.stringify(updated))
          }
          return updated
        })
      }
    } catch (e) {
      console.error("Failed to refresh user", e)
    }
  }

  const login = async (
    credentials: LoginCredentials
  ): Promise<{ success: boolean; message?: string; user?: AdminUser }> => {
    try {
      const response = await apiClient.post("/login", {
        email: credentials.email,
        password: credentials.password,
      })

      // Extract user object and token resiliently
      const resData = response.data as Record<string, unknown>
      const nestedData = (resData.data as Record<string, unknown>) || {}

      interface RawUserShape {
        id?: number
        name?: string
        first_name?: string
        last_name?: string
        email?: string
        is_admin?: boolean | number | string
        role?: string
        avatar_url?: string | null
        grade?: number | null
      }

      const token = (
        resData.token ||
        resData.access_token ||
        nestedData.token ||
        nestedData.access_token
      ) as string | undefined

      if (!token) {
        return {
          success: false,
          message: (resData.message as string) || "Failed to retrieve authentication token.",
        }
      }

      // Identify the user via Bearer token (single source of truth)
      let rawUser: RawUserShape | null = null
      try {
        const userRes = await apiClient.get("/user", {
          headers: { Authorization: `Bearer ${token}` },
        })
        const uData = userRes.data as Record<string, unknown>
        const uNested = (uData.data as Record<string, unknown>) || {}
        rawUser = (uNested.user || uData.user || uNested || uData) as RawUserShape
      } catch {
        // Fallback: try /profile with Bearer token
        try {
          const profileRes = await apiClient.get("/profile", {
            headers: { Authorization: `Bearer ${token}` },
          })
          const pData = profileRes.data as Record<string, unknown>
          const pNested = (pData.data as Record<string, unknown>) || {}
          rawUser = (pData.user || pNested.user || pData) as RawUserShape
        } catch {
          // Fallback: check if payload contained user for backward compatibility
          rawUser = (resData.user || nestedData.user) as RawUserShape | null
        }
      }

      if (!rawUser || typeof rawUser !== "object") {
        return {
          success: false,
          message: "Failed to resolve user identity from authentication token.",
        }
      }

      // Check administrator privileges: both is_admin and role
      const validAdminRoles = [
        "super_admin",
        "admin",
        "academic_editor",
        "academic",
        "tutor_moderator",
        "support_moderator",
        "tutor",
        "support_agent",
        "finance_auditor",
      ]

      const isAdmin =
        rawUser.is_admin === 1 ||
        rawUser.is_admin === true ||
        rawUser.is_admin === "1" ||
        (typeof rawUser.role === "string" && validAdminRoles.includes(rawUser.role))

      if (!isAdmin) {
        return {
          success: false,
          message: "Access denied. Administrator privileges required.",
        }
      }

      // Map rawUser into the AdminUser model with normalized role
      const mappedRole: AdminRole = normalizeRole(rawUser.role as string)

      const adminUser: AdminUser = {
        id: Number(rawUser.id) || 1,
        name:
          rawUser.name ||
          `${rawUser.first_name || ""} ${rawUser.last_name || ""}`.trim() ||
          "Administrator",
        email: rawUser.email || credentials.email,
        role: mappedRole,
        is_admin: true,
        avatar_url: rawUser.avatar_url || null,
        grade: rawUser.grade || null,
      }

      const isHttps = typeof window !== "undefined" && window.location.protocol === "https:"

      if (credentials.remember ?? true) {
        // 7-day persistent session in cookies and localStorage
        Cookies.set(TOKEN_COOKIE_KEY, token, { expires: 7, secure: isHttps, sameSite: "lax" })
        Cookies.set(LEGACY_TOKEN_KEY, token, { expires: 7, secure: isHttps, sameSite: "lax" })
        localStorage.setItem(TOKEN_COOKIE_KEY, token)
        localStorage.setItem(LEGACY_TOKEN_KEY, token)
        localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(adminUser))
        localStorage.setItem(LEGACY_USER_KEY, JSON.stringify(adminUser))
        sessionStorage.removeItem(TOKEN_COOKIE_KEY)
        sessionStorage.removeItem(LEGACY_TOKEN_KEY)
        sessionStorage.removeItem(USER_STORAGE_KEY)
        sessionStorage.removeItem(LEGACY_USER_KEY)
      } else {
        // Session-only cookie (no expiry date, removed when browser/tab closes) & sessionStorage only
        Cookies.set(TOKEN_COOKIE_KEY, token, { secure: isHttps, sameSite: "lax" })
        Cookies.set(LEGACY_TOKEN_KEY, token, { secure: isHttps, sameSite: "lax" })
        sessionStorage.setItem(TOKEN_COOKIE_KEY, token)
        sessionStorage.setItem(LEGACY_TOKEN_KEY, token)
        sessionStorage.setItem(USER_STORAGE_KEY, JSON.stringify(adminUser))
        sessionStorage.setItem(LEGACY_USER_KEY, JSON.stringify(adminUser))
        localStorage.removeItem(TOKEN_COOKIE_KEY)
        localStorage.removeItem(LEGACY_TOKEN_KEY)
        localStorage.removeItem(USER_STORAGE_KEY)
        localStorage.removeItem(LEGACY_USER_KEY)
      }

      setToken(token)
      setUser(adminUser)

      return { success: true, user: adminUser }
    } catch (error: unknown) {
      let errorMessage = "Invalid email or password."
      if (typeof error === "object" && error !== null && "response" in error) {
        const axiosErr = error as { response?: { data?: { message?: string } } }
        errorMessage = axiosErr.response?.data?.message || errorMessage
      } else if (error instanceof Error) {
        errorMessage = error.message
      }
      return { success: false, message: errorMessage }
    }
  }

  const logout = async (): Promise<void> => {
    try {
      await apiClient.post("/logout").catch(() => {})
    } finally {
      // Completely wipe all cookies and storage
      Cookies.remove(TOKEN_COOKIE_KEY)
      Cookies.remove(LEGACY_TOKEN_KEY)
      Cookies.remove("admin_token")
      Cookies.remove("asteghi_admin_token")
      localStorage.removeItem(TOKEN_COOKIE_KEY)
      localStorage.removeItem(LEGACY_TOKEN_KEY)
      localStorage.removeItem(USER_STORAGE_KEY)
      localStorage.removeItem(LEGACY_USER_KEY)
      sessionStorage.removeItem(TOKEN_COOKIE_KEY)
      sessionStorage.removeItem(LEGACY_TOKEN_KEY)
      sessionStorage.removeItem(USER_STORAGE_KEY)
      sessionStorage.removeItem(LEGACY_USER_KEY)
      try {
        sessionStorage.removeItem("asteghi_simulated_role")
      } catch (e) {}
      setUser(null)
      setToken(null)
      setSimulatedRoleState(null)
      invalidateApiCache()
      window.location.href = "/login"
    }
  }

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        isAuthenticated: !!token,
        isLoading,
        simulatedRole,
        setSimulatedRole,
        effectiveRole,
        login,
        logout,
        updateCurrentUser,
        refreshCurrentUser,
      }}
    >
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error("useAuth must be used within an AuthProvider")
  }
  return context
}
