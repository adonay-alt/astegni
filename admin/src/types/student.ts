export type StudentStream = 'natural' | 'social' | null
export type StudentStatus = 'active' | 'suspended'

export interface StudentProfile {
  id: number
  user_id: number
  name: string
  email: string
  phone: string
  school?: string
  grade_id: number
  grade_name: string
  stream: StudentStream
  chapters_completed: number
  total_chapters: number
  quiz_attempts_count: number
  average_quiz_score: number
  last_active_at: string
  status: StudentStatus
  avatar_url?: string
  created_at: string
}
