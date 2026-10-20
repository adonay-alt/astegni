interface CacheEntry<T> {
  data: T
  timestamp: number
}

const memoryCache = new Map<string, CacheEntry<any>>()
const DEFAULT_TTL = 5 * 60 * 1000 // 5 minutes cache lifetime

export function getCached<T>(key: string): T | null {
  const entry = memoryCache.get(key)
  if (!entry) return null

  // If expired, clear and return null
  if (Date.now() - entry.timestamp > DEFAULT_TTL) {
    memoryCache.delete(key)
    return null
  }

  return entry.data as T
}

export function setCached<T>(key: string, data: T): void {
  memoryCache.set(key, { data, timestamp: Date.now() })
}

export function clearCache(prefix?: string): void {
  if (!prefix) {
    memoryCache.clear()
    return
  }
  for (const key of memoryCache.keys()) {
    if (key.startsWith(prefix)) {
      memoryCache.delete(key)
    }
  }
}
