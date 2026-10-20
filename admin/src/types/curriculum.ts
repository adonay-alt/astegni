export interface Grade {
  id: number
  name: string
  level: number
  description: string
  total_subjects: number
  has_streams: boolean
}

export interface AcademicStream {
  id: number
  name: 'Natural Science' | 'Social Science'
  grade_id: number
  code: string
  description: string
}

export type StreamSlug = 'natural' | 'social' | 'common';

export interface SubjectStreamOption {
  value: StreamSlug;
  label: string;
  badgeClass: string;
  icon: string;
}

export interface Subject {
  id: number
  name: string
  title?: string
  code: string
  grade_id: number
  grade_name: string
  stream: StreamSlug | null
  stream_id?: number | null
  stream_name?: string | null
  stream_slug?: StreamSlug
  chapters_count: number
  icon_name: string
}

export type AiNotesStatus = 'ready' | 'draft' | 'not_generated'

export interface ChapterTextbook {
  id: number;
  chapter_id: number;
  title: string;
  file_path: string;
  file_url: string;
  file_size_mb?: number | null;
  created_at?: string;
}

export interface ChapterResource {
  id: number;
  chapter_id: number;
  grade_id?: number;
  subject_id?: number;
  title: string;
  description?: string | null;
  file_path: string;
  file_url: string;
  file_type?: string;
  file_size_mb?: number | null;
  created_at?: string;
  price?: number | string | null;
  is_locked?: boolean;
  grade?: { id: number; name: string };
  subject?: { id: number; name?: string; title?: string };
  chapter?: { id: number; title: string; chapter_number?: number };
}

export interface ChapterVideo {
  id: number;
  chapter_id: number;
  title: string;
  video_url: string;
  duration?: string | null;
  description?: string | null;
  thumbnail_url?: string | null;
  platform?: 'youtube' | 'vimeo' | 'direct' | 'other';
  created_at?: string;
}

export interface Chapter {
  id: number
  subject_id: number
  subject_name: string
  grade_name: string
  chapter_number: number
  title: string
  description: string
  textbook_pdf_url: string | null
  textbook_size_mb: number | null
  ai_notes_status: AiNotesStatus
  ai_notes_word_count: number | null
  ai_last_generated_at: string | null
  quiz_questions_count: number
  attachments_count: number
  is_featured?: boolean
  textbooks_count?: number
  resources_count?: number
  videos_count?: number
  has_textbook?: boolean
}

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
  is_ai_generated: boolean
  questions_count: number
  questions: QuizQuestion[]
  last_generated_at?: string | null
}

export interface Quiz {
  id: number
  chapter_id: number
  chapter_title: string
  title: string
  total_questions: number
  is_premium: boolean
  attempts_count: number
}

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
