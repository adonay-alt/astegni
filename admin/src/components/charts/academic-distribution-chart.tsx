"use client"

import React, { useState, useEffect, useCallback, useRef } from "react"
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from "recharts"
import { apiClient } from "@/lib/api-client"
import { Loader2 } from "lucide-react"

export interface CohortItem {
  grade: string
  natural?: number
  social?: number
  common?: number
}

interface AcademicDistributionChartProps {
  onDataLoaded?: (data: {
    totalEnrolled: number
    primaryConcentration: string
  }) => void
}

export function AcademicDistributionChart({
  onDataLoaded,
}: AcademicDistributionChartProps) {
  const [data, setData] = useState<CohortItem[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [mounted, setMounted] = useState(false)

  const onDataLoadedRef = useRef(onDataLoaded)
  useEffect(() => {
    onDataLoadedRef.current = onDataLoaded
  })

  const fetchCohortData = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      // Call Laravel API directly: GET /admin/cohort-distribution
      const res = await apiClient.get("/admin/cohort-distribution")

      if (
        res.data &&
        res.data.status === "success" &&
        Array.isArray(res.data.data) &&
        res.data.data.length > 0
      ) {
        setData(res.data.data)
        onDataLoadedRef.current?.({
          totalEnrolled: res.data.total_enrolled ?? 0,
          primaryConcentration: res.data.primary_concentration ?? "",
        })
      } else {
        // API returned success but empty data — honest zero state
        setData([
          { grade: "Grade 12", natural: 0, social: 0 },
          { grade: "Grade 11", natural: 0, social: 0 },
          { grade: "Grade 10", common: 0 },
          { grade: "Grade 9", common: 0 },
        ])
        onDataLoadedRef.current?.({
          totalEnrolled: 0,
          primaryConcentration: "No student cohort enrolled in database yet",
        })
      }
    } catch (err: any) {
      console.error("Error fetching cohort distribution:", err)
      setError(
        err.response?.data?.message ||
          "Failed to load cohort data from backend."
      )
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    setMounted(true)
    fetchCohortData()
  }, [fetchCohortData])

  if (!mounted || loading) {
    return (
      <div className="flex h-[280px] min-h-[280px] w-full flex-col items-center justify-center gap-3 rounded-xl border border-slate-100 bg-slate-50/70">
        <Loader2 className="h-7 w-7 animate-spin text-blue-600" />
        <span className="text-xs font-semibold text-slate-600">
          Loading live cohort data from backend...
        </span>
      </div>
    )
  }

  if (error) {
    return (
      <div className="flex h-[280px] min-h-[280px] w-full items-center justify-center rounded-xl border border-rose-100 bg-rose-50/60 p-6 text-center text-xs font-medium text-rose-700">
        {error}
      </div>
    )
  }

  const maxVal = Math.max(
    ...data.map((d) =>
      Math.max(d.natural || 0, d.social || 0, d.common || 0)
    ),
    5
  )

  const formatYAxis = (val: number) => {
    if (maxVal >= 1000) {
      return val === 0 ? "0k" : `${(val / 1000).toFixed(val % 1000 === 0 ? 0 : 1)}k`
    }
    return `${val}`
  }

  return (
    <div className="space-y-4">
      <div className="h-[280px] min-h-[280px] w-full pt-2">
        <ResponsiveContainer width="100%" height="100%" minHeight={280}>
          <BarChart
            data={data}
            margin={{ top: 15, right: 15, left: -20, bottom: 5 }}
            barGap={6}
          >
            <CartesianGrid
              strokeDasharray="3 3"
              vertical={false}
              stroke="#e2e8f0"
              opacity={0.8}
            />
            <XAxis
              dataKey="grade"
              tickLine={false}
              axisLine={{ stroke: "#e2e8f0" }}
              tick={{ fontSize: 12, fill: "#334155", fontWeight: 600 }}
            />
            <YAxis
              tickLine={false}
              axisLine={false}
              tick={{ fontSize: 11, fill: "#64748b", fontWeight: 500 }}
              tickFormatter={formatYAxis}
              allowDecimals={false}
              domain={[0, (dataMax: number) => Math.max(dataMax + 1, 5)]}
            />
            <Tooltip
              cursor={{ fill: "rgba(241, 245, 249, 0.6)" }}
              contentStyle={{
                backgroundColor: "#ffffff",
                borderColor: "#e2e8f0",
                borderRadius: "0.75rem",
                boxShadow: "0 10px 25px -5px rgba(15, 23, 42, 0.1), 0 8px 10px -6px rgba(15, 23, 42, 0.05)",
                color: "#0f172a",
                fontSize: "12px",
                fontWeight: "600",
              }}
              formatter={(value: any, name: any) => {
                let cohortName = name
                if (name === "natural") cohortName = "Natural Science Cohort"
                if (name === "social") cohortName = "Social Science Cohort"
                if (name === "common") cohortName = "General Core Cohort"
                return [Number(value).toLocaleString(), cohortName]
              }}
            />
            <Bar
              dataKey="common"
              name="common"
              fill="#0284c7"
              radius={[4, 4, 0, 0]}
              maxBarSize={32}
            />
            <Bar
              dataKey="natural"
              name="natural"
              fill="#2563eb"
              radius={[4, 4, 0, 0]}
              maxBarSize={32}
            />
            <Bar
              dataKey="social"
              name="social"
              fill="#7c3aed"
              radius={[4, 4, 0, 0]}
              maxBarSize={32}
            />
          </BarChart>
        </ResponsiveContainer>
      </div>

      {/* Cohort Legend */}
      <div className="flex flex-wrap items-center justify-center gap-6 pt-3 border-t border-slate-100 text-xs">
        <div className="flex items-center gap-2">
          <span className="h-3 w-3 rounded-xs bg-[#0284c7] shadow-xs" />
          <span className="font-semibold text-slate-700">General Core Cohort</span>
        </div>
        <div className="flex items-center gap-2">
          <span className="h-3 w-3 rounded-xs bg-[#2563eb] shadow-xs" />
          <span className="font-semibold text-slate-700">Natural Science Cohort</span>
        </div>
        <div className="flex items-center gap-2">
          <span className="h-3 w-3 rounded-xs bg-[#7c3aed] shadow-xs" />
          <span className="font-semibold text-slate-700">Social Science Cohort</span>
        </div>
      </div>
    </div>
  )
}
