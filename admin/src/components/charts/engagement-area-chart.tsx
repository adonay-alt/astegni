"use client"

import React, { useState, useEffect } from "react"
import {
  AreaChart,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Legend,
} from "recharts"

export interface EngagementDataPoint {
  date: string
  full_date?: string
  chapter_views: number
  quiz_attempts: number
  total_activity?: number
}

interface EngagementAreaChartProps {
  data?: EngagementDataPoint[]
  isLoading?: boolean
}

export function EngagementAreaChart({ data = [], isLoading = false }: EngagementAreaChartProps) {
  const [mounted, setMounted] = useState(false)

  useEffect(() => {
    setMounted(true)
  }, [])

  if (!mounted || isLoading) {
    return (
      <div className="h-[280px] w-full animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-900 flex items-center justify-center">
        <span className="text-xs text-slate-400 font-medium">Loading engagement telemetry...</span>
      </div>
    )
  }

  if (!data || data.length === 0) {
    return (
      <div className="h-[280px] w-full rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 flex items-center justify-center">
        <span className="text-xs text-slate-400 font-medium">No activity telemetry recorded in the last 30 days</span>
      </div>
    )
  }

  return (
    <div className="h-[280px] w-full pt-4">
      <ResponsiveContainer width="100%" height="100%">
        <AreaChart
          data={data}
          margin={{ top: 10, right: 10, left: -20, bottom: 0 }}
        >
          <defs>
            <linearGradient id="colorViews" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor="#2563eb" stopOpacity={0.35} />
              <stop offset="95%" stopColor="#2563eb" stopOpacity={0.0} />
            </linearGradient>
            <linearGradient id="colorQuizzes" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor="#4f46e5" stopOpacity={0.35} />
              <stop offset="95%" stopColor="#4f46e5" stopOpacity={0.0} />
            </linearGradient>
          </defs>
          <CartesianGrid
            strokeDasharray="3 3"
            vertical={false}
            stroke="currentColor"
            className="text-slate-200 dark:text-slate-800"
            opacity={0.6}
          />
          <XAxis
            dataKey="date"
            tickLine={false}
            axisLine={false}
            tick={{ fontSize: 11, fill: "currentColor" }}
            className="text-slate-500 dark:text-slate-400"
          />
          <YAxis
            tickLine={false}
            axisLine={false}
            tick={{ fontSize: 11, fill: "currentColor" }}
            className="text-slate-500 dark:text-slate-400"
            tickFormatter={(v) => (v >= 1000 ? `${(v / 1000).toFixed(1)}k` : `${v}`)}
          />
          <Tooltip
            contentStyle={{
              backgroundColor: "rgba(15, 23, 42, 0.95)",
              borderColor: "rgba(51, 65, 85, 0.5)",
              borderRadius: "0.75rem",
              boxShadow: "0 10px 15px -3px rgba(0, 0, 0, 0.2)",
              color: "#fff",
              fontSize: "12px",
            }}
          />
          <Legend
            verticalAlign="top"
            align="right"
            iconType="circle"
            wrapperStyle={{ fontSize: "11px", paddingBottom: "8px" }}
          />
          <Area
            type="monotone"
            dataKey="chapter_views"
            name="Chapter & Subject Views"
            stroke="#2563eb"
            strokeWidth={2.5}
            fillOpacity={1}
            fill="url(#colorViews)"
          />
          <Area
            type="monotone"
            dataKey="quiz_attempts"
            name="Quiz Drills Completed"
            stroke="#4f46e5"
            strokeWidth={2.5}
            fillOpacity={1}
            fill="url(#colorQuizzes)"
          />
        </AreaChart>
      </ResponsiveContainer>
    </div>
  )
}
