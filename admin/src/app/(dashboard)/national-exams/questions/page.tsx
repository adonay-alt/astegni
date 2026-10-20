"use client"

import { useEffect } from "react"
import { useRouter } from "next/navigation"

export default function ExamQuestionsPage() {
  const router = useRouter()

  useEffect(() => {
    router.replace("/national-exams")
  }, [router])

  return null
}
