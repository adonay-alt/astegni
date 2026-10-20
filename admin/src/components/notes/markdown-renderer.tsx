"use client"

import React, { useState } from "react"
import { Copy, Check, Terminal } from "lucide-react"

interface MarkdownRendererProps {
  content?: string | null
  className?: string
}

export function MarkdownRenderer({ content, className = "" }: MarkdownRendererProps) {
  const [copiedIndex, setCopiedIndex] = useState<number | null>(null)

  // CRITICAL GUARD: Prevent runtime crash if content is not a string
  if (!content || typeof content !== "string" || content.trim() === "") {
    return (
      <div className="rounded-lg border border-slate-200 bg-slate-50/50 p-6 text-center text-sm text-slate-500 italic dark:border-slate-800 dark:bg-slate-900/50">
        This note has no written content yet. Click &quot;Edit&quot; to write notes.
      </div>
    )
  }

  const safeContent = content

  const handleCopy = (text: string, index: number) => {
    navigator.clipboard.writeText(text)
    setCopiedIndex(index)
    setTimeout(() => setCopiedIndex(null), 2000)
  }

  // Parse lines into structured blocks
  const parseBlocks = (text: string) => {
    if (!text || typeof text !== "string") return []
    const lines = text.split(/\r?\n/)
    const blocks: React.ReactNode[] = []
    let i = 0

    while (i < lines.length) {
      const line = lines[i]

      // Code block
      if (line.trim().startsWith("```")) {
        const lang = line.trim().slice(3).trim()
        const codeLines: string[] = []
        i++
        while (i < lines.length && !lines[i].trim().startsWith("```")) {
          codeLines.push(lines[i])
          i++
        }
        i++ // consume closing ```
        const codeText = codeLines.join("\n")
        const blockIdx = blocks.length

        blocks.push(
          <div key={`code-${blockIdx}`} className="my-4 overflow-hidden rounded-xl border border-slate-700 bg-slate-900 text-slate-100 shadow-sm">
            <div className="flex items-center justify-between border-b border-slate-800 bg-slate-950/80 px-4 py-2 text-xs text-slate-400">
              <span className="flex items-center gap-1.5 font-mono uppercase tracking-wider">
                <Terminal className="h-3.5 w-3.5 text-blue-400" />
                {lang || "code"}
              </span>
              <button
                onClick={() => handleCopy(codeText, blockIdx)}
                className="flex items-center gap-1 rounded px-2 py-0.5 text-xs text-slate-400 transition hover:bg-slate-800 hover:text-slate-200"
              >
                {copiedIndex === blockIdx ? (
                  <>
                    <Check className="h-3.5 w-3.5 text-emerald-400" />
                    <span className="text-emerald-400">Copied</span>
                  </>
                ) : (
                  <>
                    <Copy className="h-3.5 w-3.5" />
                    <span>Copy</span>
                  </>
                )}
              </button>
            </div>
            <pre className="overflow-x-auto p-4 font-mono text-xs leading-relaxed text-slate-200">
              <code>{codeText}</code>
            </pre>
          </div>
        )
        continue
      }

      // Headings
      if (line.startsWith("# ")) {
        blocks.push(
          <h1 key={`h1-${i}`} className="mt-8 mb-4 border-b border-slate-200 pb-2 text-2xl font-bold tracking-tight text-slate-900 dark:border-slate-800 dark:text-slate-100">
            {renderInline(line.slice(2))}
          </h1>
        )
        i++
        continue
      }
      if (line.startsWith("## ")) {
        blocks.push(
          <h2 key={`h2-${i}`} className="mt-6 mb-3 text-xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
            {renderInline(line.slice(3))}
          </h2>
        )
        i++
        continue
      }
      if (line.startsWith("### ")) {
        blocks.push(
          <h3 key={`h3-${i}`} className="mt-5 mb-2 text-lg font-semibold text-slate-800 dark:text-slate-200">
            {renderInline(line.slice(4))}
          </h3>
        )
        i++
        continue
      }
      if (line.startsWith("#### ")) {
        blocks.push(
          <h4 key={`h4-${i}`} className="mt-4 mb-2 text-base font-semibold text-slate-800 dark:text-slate-200">
            {renderInline(line.slice(5))}
          </h4>
        )
        i++
        continue
      }

      // Horizontal Rule
      if (/^(\*\*\*|---|___)$/.test(line.trim())) {
        blocks.push(<hr key={`hr-${i}`} className="my-6 border-slate-200 dark:border-slate-800" />)
        i++
        continue
      }

      // Blockquote / Alerts
      if (line.startsWith("> ")) {
        const quoteLines: string[] = []
        while (i < lines.length && lines[i].startsWith("> ")) {
          quoteLines.push(lines[i].slice(2))
          i++
        }
        const quoteContent = quoteLines.join(" ")
        const isNoteAlert = quoteContent.includes("[!NOTE]") || quoteContent.includes("[!TIP]")
        const isWarningAlert = quoteContent.includes("[!WARNING]") || quoteContent.includes("[!CAUTION]")

        let alertClass = "border-l-4 border-blue-500 bg-blue-50/50 text-blue-900 dark:bg-blue-950/20 dark:text-blue-200"
        if (isWarningAlert) {
          alertClass = "border-l-4 border-amber-500 bg-amber-50/50 text-amber-900 dark:bg-amber-950/20 dark:text-amber-200"
        }

        const cleanText = quoteContent.replace(/\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]/g, "").trim()

        blocks.push(
          <div key={`quote-${i}`} className={`my-4 rounded-r-lg p-4 text-sm leading-relaxed ${alertClass}`}>
            {renderInline(cleanText)}
          </div>
        )
        continue
      }

      // Unordered List
      if (/^[\*\-]\s+/.test(line.trim())) {
        const listItems: string[] = []
        while (i < lines.length && /^[\*\-]\s+/.test(lines[i].trim())) {
          listItems.push(lines[i].trim().replace(/^[\*\-]\s+/, ""))
          i++
        }
        blocks.push(
          <ul key={`ul-${i}`} className="my-3 ml-6 list-disc space-y-1 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
            {listItems.map((item, idx) => (
              <li key={idx}>{renderInline(item)}</li>
            ))}
          </ul>
        )
        continue
      }

      // Ordered List
      if (/^\d+\.\s+/.test(line.trim())) {
        const listItems: string[] = []
        while (i < lines.length && /^\d+\.\s+/.test(lines[i].trim())) {
          listItems.push(lines[i].trim().replace(/^\d+\.\s+/, ""))
          i++
        }
        blocks.push(
          <ol key={`ol-${i}`} className="my-3 ml-6 list-decimal space-y-1 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
            {listItems.map((item, idx) => (
              <li key={idx}>{renderInline(item)}</li>
            ))}
          </ol>
        )
        continue
      }

      // Markdown Table
      if (line.trim().startsWith("|") && line.trim().endsWith("|")) {
        const tableLines: string[] = []
        while (i < lines.length && lines[i].trim().startsWith("|") && lines[i].trim().endsWith("|")) {
          tableLines.push(lines[i].trim())
          i++
        }
        if (tableLines.length >= 2) {
          const headers = tableLines[0]
            .split("|")
            .slice(1, -1)
            .map((h) => h.trim())
          const rows = tableLines.slice(2).map((r) =>
            r
              .split("|")
              .slice(1, -1)
              .map((c) => c.trim())
          )

          blocks.push(
            <div key={`table-${i}`} className="my-4 overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800">
              <table className="min-w-full divide-y divide-slate-200 text-left text-xs dark:divide-slate-800">
                <thead className="bg-slate-50 dark:bg-slate-900/50">
                  <tr>
                    {headers.map((h, idx) => (
                      <th key={idx} className="px-4 py-2.5 font-semibold text-slate-900 dark:text-slate-100">
                        {renderInline(h)}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 bg-white dark:divide-slate-800/50 dark:bg-slate-950">
                  {rows.map((row, rIdx) => (
                    <tr key={rIdx} className="hover:bg-slate-50/50 dark:hover:bg-slate-900/30">
                      {row.map((cell, cIdx) => (
                        <td key={cIdx} className="px-4 py-2 text-slate-600 dark:text-slate-300">
                          {renderInline(cell)}
                        </td>
                      ))}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )
          continue
        }
      }

      // Empty Line
      if (!line.trim()) {
        i++
        continue
      }

      // Standard Paragraph
      blocks.push(
        <p key={`p-${i}`} className="my-2.5 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
          {renderInline(line)}
        </p>
      )
      i++
    }

    return blocks
  }

  // Render inline formatting: Bold, Italic, Code, Math
  const renderInline = (text: string): React.ReactNode => {
    if (!text || typeof text !== "string") return text || null
    // Regex splits by: `code`, **bold**, *italic*
    const parts = text.split(/(`[^`]+`|\*\*[^*]+\*\*|\*[^*]+\*)/g)

    return parts.map((part, idx) => {
      if (!part) return null

      // Inline code
      if (part.startsWith("`") && part.endsWith("`")) {
        return (
          <code
            key={idx}
            className="mx-0.5 rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-pink-600 dark:bg-slate-800 dark:text-pink-400"
          >
            {part.slice(1, -1)}
          </code>
        )
      }

      // Bold
      if (part.startsWith("**") && part.endsWith("**")) {
        return (
          <strong key={idx} className="font-semibold text-slate-900 dark:text-slate-100">
            {part.slice(2, -2)}
          </strong>
        )
      }

      // Italic
      if (part.startsWith("*") && part.endsWith("*")) {
        return (
          <em key={idx} className="italic text-slate-700 dark:text-slate-300">
            {part.slice(1, -1)}
          </em>
        )
      }

      return <span key={idx}>{part}</span>
    })
  }

  if (!safeContent.trim()) {
    return (
      <div className="py-12 text-center text-xs text-slate-400">
        No content available for this note.
      </div>
    )
  }

  return (
    <div className={`prose-clean max-w-none ${className}`}>
      {parseBlocks(safeContent)}
    </div>
  )
}
