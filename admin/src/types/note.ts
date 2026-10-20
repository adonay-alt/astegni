export interface ChapterNote {
  id: number
  chapter_id: number
  chapter_title?: string
  subject_name?: string
  grade_name?: string
  title: string
  content: string // Markdown or formatted text
  is_ai_generated: boolean
  is_active?: boolean
  word_count?: number
  last_generated_at?: string | null
  created_at?: string
  updated_at?: string
}

export interface NoteFilterState {
  grade_id: string // "all" or grade ID string
  subject_id: string // "all" or subject ID string
  chapter_id: string // "all" or chapter ID string
  search_query: string
}
