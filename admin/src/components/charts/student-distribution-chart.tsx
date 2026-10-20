"use client"

import React from "react"
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from "recharts"

export interface GradeDistributionItem {
  grade: string
  general: number | null
  natural: number | null
  social: number | null
  total: number
}

interface Props {
  data: GradeDistributionItem[]
  isLoading?: boolean
}

// ─── Custom Tooltip ──────────────────────────────────────────────────────────
const CustomTooltip = ({ active, payload, label }: any) => {
  if (!active || !payload || !payload.length) return null

  const entries = payload.filter((p: any) => p.value !== null && p.value !== undefined)

  return (
    <div className="rounded-xl border border-slate-700 bg-slate-900 p-3.5 shadow-2xl min-w-[170px]">
      <p className="mb-2 text-sm font-bold text-white">{label}</p>
      {entries.map((entry: any, idx: number) => (
        <div key={idx} className="flex items-center justify-between gap-4 py-0.5">
          <span className="flex items-center gap-2 text-xs font-medium text-slate-300">
            <span
              className="h-2.5 w-2.5 rounded-sm"
              style={{ backgroundColor: entry.fill }}
            />
            {entry.name}
          </span>
          <span className="text-xs font-bold text-white">
            {Number(entry.value).toLocaleString()}
          </span>
        </div>
      ))}
      <div className="mt-2 border-t border-slate-700 pt-2 flex items-center justify-between">
        <span className="text-xs font-medium text-slate-400">Total</span>
        <span className="text-xs font-bold text-white">
          {entries.reduce((s: number, e: any) => s + (e.value ?? 0), 0).toLocaleString()}
        </span>
      </div>
    </div>
  )
}

// ─── Custom Legend ───────────────────────────────────────────────────────────
const CustomLegend = ({ payload }: any) => (
  <div className="flex flex-wrap items-center justify-center gap-5 pt-4">
    {payload?.map((entry: any, idx: number) => (
      <div key={idx} className="flex items-center gap-2">
        <span
          className="h-3 w-4 rounded-sm"
          style={{ backgroundColor: entry.color }}
        />
        <span className="text-xs font-semibold text-slate-300">{entry.value}</span>
      </div>
    ))}
  </div>
)

// ─── Component ───────────────────────────────────────────────────────────────
export function StudentDistributionChart({ data, isLoading }: Props) {
  if (isLoading) {
    return (
      <div className="h-80 w-full animate-pulse rounded-xl bg-slate-800/40" />
    )
  }

  if (!data || data.length === 0) {
    return (
      <div className="flex h-80 w-full flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-700 bg-slate-800/20">
        <p className="text-sm font-semibold text-slate-400">No student enrollment data yet</p>
        <p className="mt-1 text-xs text-slate-500">Students will appear here once enrolled in Grades 9–12</p>
      </div>
    )
  }

  return (
    <div className="h-80 w-full">
      <ResponsiveContainer width="100%" height="100%">
        <BarChart
          data={data}
          margin={{ top: 16, right: 24, left: -10, bottom: 4 }}
          barGap={6}
          barCategoryGap="32%"
        >
          <CartesianGrid
            strokeDasharray="3 3"
            stroke="#334155"
            opacity={0.45}
            vertical={false}
          />
          <XAxis
            dataKey="grade"
            stroke="#64748b"
            tick={{ fill: "#94a3b8", fontSize: 13, fontWeight: 700 }}
            tickLine={false}
            axisLine={{ stroke: "#334155" }}
          />
          <YAxis
            stroke="#64748b"
            tick={{ fill: "#94a3b8", fontSize: 11, fontWeight: 500 }}
            tickLine={false}
            axisLine={false}
            allowDecimals={false}
            domain={[0, (dataMax: number) => Math.max(dataMax + 1, 5)]}
          />
          <Tooltip
            content={<CustomTooltip />}
            cursor={{ fill: "rgba(148,163,184,0.06)" }}
          />
          <Legend content={<CustomLegend />} />

          {/*
            Bar 1 — Grade 9 & 10: single emerald green bar
            (natural/social are null for these grades → bar renders nothing)
          */}
          <Bar
            dataKey="general"
            name="General Core"
            fill="#10B981"
            radius={[6, 6, 0, 0]}
            maxBarSize={56}
          />

          {/*
            Bar 2 — Grade 11 & 12: Natural Science (purple)
            (general is null for these grades → bar renders nothing)
          */}
          <Bar
            dataKey="natural"
            name="Natural Science"
            fill="#A855F7"
            radius={[6, 6, 0, 0]}
            maxBarSize={44}
          />

          {/*
            Bar 3 — Grade 11 & 12: Social Science (blue)
          */}
          <Bar
            dataKey="social"
            name="Social Science"
            fill="#3B82F6"
            radius={[6, 6, 0, 0]}
            maxBarSize={44}
          />
        </BarChart>
      </ResponsiveContainer>
    </div>
  )
}
