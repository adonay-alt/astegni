import axios, { AxiosError, InternalAxiosRequestConfig } from "axios"
import Cookies from "js-cookie"

export const TOKEN_COOKIE_KEY = "admin_token"
export const USER_STORAGE_KEY = "admin_user"
export const LEGACY_TOKEN_KEY = "asteghi_admin_token"
export const LEGACY_USER_KEY = "asteghi_admin_user"

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || "https://apiv2.asteghi.com/api"

export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
})

// Helper to retrieve token from any active storage (Cookies -> localStorage -> sessionStorage)
export const getActiveToken = (): string | undefined => {
  if (typeof window === "undefined") {
    return Cookies.get(TOKEN_COOKIE_KEY) || Cookies.get(LEGACY_TOKEN_KEY) || undefined
  }
  return (
    Cookies.get(TOKEN_COOKIE_KEY) ||
    Cookies.get(LEGACY_TOKEN_KEY) ||
    localStorage.getItem(TOKEN_COOKIE_KEY) ||
    localStorage.getItem(LEGACY_TOKEN_KEY) ||
    sessionStorage.getItem(TOKEN_COOKIE_KEY) ||
    sessionStorage.getItem(LEGACY_TOKEN_KEY) ||
    undefined
  )
}

// Request interceptor: attach token
apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = getActiveToken()
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error: AxiosError) => {
    return Promise.reject(error)
  }
)

// =========================================================================
// HIGH-PERFORMANCE IN-MEMORY CLIENT CACHE (SWR / 60-Second TTL)
// =========================================================================
interface CacheEntry<T = unknown> {
  data: T
  timestamp: number
}

const apiCache = new Map<string, CacheEntry>()
const CACHE_TTL = 60 * 1000 // 60 seconds

export const cachedGet = async <T = any>(
  url: string,
  params?: any,
  forceRefresh = false
): Promise<T> => {
  const cacheKey = `${url}:${JSON.stringify(params || {})}`
  const cached = apiCache.get(cacheKey)

  // Return instantly from in-memory cache if still valid
  if (!forceRefresh && cached && Date.now() - cached.timestamp < CACHE_TTL) {
    return cached.data as T
  }

  const res = await apiClient.get<T>(url, { params })
  apiCache.set(cacheKey, { data: res.data, timestamp: Date.now() })
  return res.data
}

// Invalidate cache on demand or by URL prefix
export const invalidateApiCache = (urlPrefix?: string) => {
  if (!urlPrefix) {
    apiCache.clear()
    return
  }
  for (const key of apiCache.keys()) {
    if (key.startsWith(urlPrefix)) {
      apiCache.delete(key)
    }
  }
}

// Response interceptor: auto-invalidate cache on mutations + handle 401
apiClient.interceptors.response.use(
  (response) => {
    const method = response.config.method?.toUpperCase()
    if (["POST", "PUT", "PATCH", "DELETE"].includes(method || "")) {
      invalidateApiCache()
    }
    return response
  },
  (error: AxiosError) => {
    if (error.response?.status === 401 && typeof window !== "undefined") {
      // Clear all possible session tokens
      Cookies.remove(TOKEN_COOKIE_KEY)
      Cookies.remove(LEGACY_TOKEN_KEY)
      localStorage.removeItem(TOKEN_COOKIE_KEY)
      localStorage.removeItem(LEGACY_TOKEN_KEY)
      localStorage.removeItem(USER_STORAGE_KEY)
      localStorage.removeItem(LEGACY_USER_KEY)
      sessionStorage.removeItem(TOKEN_COOKIE_KEY)
      sessionStorage.removeItem(LEGACY_TOKEN_KEY)
      invalidateApiCache()

      // Avoid infinite redirect loop if already on login page
      if (!window.location.pathname.startsWith("/login")) {
        window.location.href = `/login?expired=1&redirect=${encodeURIComponent(
          window.location.pathname
        )}`
      }
    }
    return Promise.reject(error)
  }
)
