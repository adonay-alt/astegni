export type OptionKey = 'A' | 'B' | 'C' | 'D'

export interface QuizQuestion {
  id: number
  quiz_id: number
  question_number: number
  question_text: string
  options: {
    A: string
    B: string
    C: string
    D: string
  }
  correct_answer: OptionKey
  explanation: string
  created_at?: string
  updated_at?: string
}

export interface ChapterQuiz {
  id: number
  chapter_id: number
  chapter_title?: string
  subject_name?: string
  grade_name?: string
  title: string
  description?: string
  is_premium?: boolean
  is_ai_generated: boolean
  questions_count: number
  questions: QuizQuestion[]
  last_generated_at?: string | null
}
