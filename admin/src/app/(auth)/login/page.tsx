"use client"

import React, { useState, useEffect, Suspense } from "react"
import { useRouter, useSearchParams } from "next/navigation"
import { useAuth } from "@/context/auth-context"
import { getDefaultRouteForRole, isRouteAllowed } from "@/lib/rbac"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { AlertTriangle, Loader2, Lock, Mail, ShieldCheck } from "lucide-react"

function LoginForm() {
  const { login, isAuthenticated, user } = useAuth()
  const router = useRouter()
  const searchParams = useSearchParams()

  const [email, setEmail] = useState("")
  const [password, setPassword] = useState("")
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  const isExpired = searchParams.get("expired") === "1"

  useEffect(() => {
    if (isAuthenticated && user) {
      router.replace(getDefaultRouteForRole(user.role))
    }
  }, [isAuthenticated, user, router])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)
    setIsSubmitting(true)

    try {
      const result = await login({ email, password })
      if (result.success) {
        const loggedUser = result.user || user
        const targetRoute = getDefaultRouteForRole(loggedUser?.role)
        const redirectParam = searchParams.get("redirect")

        if (redirectParam && isRouteAllowed(loggedUser?.role, redirectParam)) {
          router.push(redirectParam)
        } else {
          router.push(targetRoute)
        }
      } else {
        setError(result.message || "Invalid credentials or unauthorized admin account.")
      }
    } catch (err: unknown) {
      setError("An unexpected error occurred during authentication.")
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <Card className="border-slate-800 bg-slate-900/90 shadow-2xl backdrop-blur-xl">
      <CardHeader className="space-y-1 pb-4">
        <CardTitle className="text-lg font-bold text-white">Sign In</CardTitle>
        <CardDescription className="text-xs text-slate-400">
          Enter your authorized staff credentials to access the admin console.
        </CardDescription>
      </CardHeader>

      <form onSubmit={handleSubmit}>
        <CardContent className="space-y-4">
          {/* Session Expired Notice */}
          {isExpired && !error && (
            <div className="flex items-center gap-2 rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-300">
              <AlertTriangle className="h-4 w-4 shrink-0 text-amber-400" />
              <span>Your administrative session has expired. Please log in again.</span>
            </div>
          )}

          {/* Error Notice */}
          {error && (
            <div className="flex items-center gap-2 rounded-lg border border-red-500/30 bg-red-500/10 p-3 text-xs text-red-300">
              <AlertTriangle className="h-4 w-4 shrink-0 text-red-400" />
              <span>{error}</span>
            </div>
          )}

          {/* Email Input */}
          <div className="space-y-1.5">
            <Label htmlFor="email" className="text-xs font-semibold text-slate-200">
              Staff Email Address
            </Label>
            <div className="relative">
              <Mail className="absolute left-3 top-2.5 h-4 w-4 text-slate-500" />
              <Input
                id="email"
                type="email"
                required
                placeholder="admin@astegni.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                disabled={isSubmitting}
                className="border-slate-700 bg-slate-800/60 pl-9 text-xs text-white placeholder:text-slate-500 focus-visible:ring-blue-500"
              />
            </div>
          </div>

          {/* Password Input */}
          <div className="space-y-1.5">
            <div className="flex items-center justify-between">
              <Label htmlFor="password" className="text-xs font-semibold text-slate-200">
                Secret Password
              </Label>
            </div>
            <div className="relative">
              <Lock className="absolute left-3 top-2.5 h-4 w-4 text-slate-500" />
              <Input
                id="password"
                type="password"
                required
                placeholder="••••••••••••"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                disabled={isSubmitting}
                className="border-slate-700 bg-slate-800/60 pl-9 text-xs text-white placeholder:text-slate-500 focus-visible:ring-blue-500"
              />
            </div>
          </div>
        </CardContent>

        <CardFooter className="flex flex-col space-y-4 pt-2">
          <Button
            type="submit"
            disabled={isSubmitting}
            className="w-full bg-blue-600 font-semibold text-white shadow-md shadow-blue-600/30 hover:bg-blue-500"
          >
            {isSubmitting ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Verifying Identity...
              </>
            ) : (
              <>
                <ShieldCheck className="mr-2 h-4 w-4" />
                Authorize Staff Login
              </>
            )}
          </Button>
        </CardFooter>
      </form>
    </Card>
  )
}

export default function LoginPage() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-slate-900 via-slate-950 to-indigo-950 px-4 py-12 sm:px-6 lg:px-8">
      <div className="w-full max-w-md space-y-8">
        {/* Brand Header */}
        <div className="flex flex-col items-center text-center space-y-1">
          <h1 className="text-3xl font-black tracking-tight text-white sm:text-4xl">
            Astegni Control Center
          </h1>
          <p className="text-xs font-medium uppercase tracking-widest text-slate-400">
            Staff & Operational Management
          </p>
        </div>

        {/* Login Card with Suspense boundary */}
        <Suspense
          fallback={
            <Card className="border-slate-800 bg-slate-900/90 p-8 shadow-2xl backdrop-blur-xl">
              <div className="flex items-center justify-center py-10">
                <Loader2 className="h-6 w-6 animate-spin text-blue-500" />
              </div>
            </Card>
          }
        >
          <LoginForm />
        </Suspense>
      </div>
    </div>
  )
}
