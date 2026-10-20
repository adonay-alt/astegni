export type ExamGradeLevel = 8 | 12;
export type OptionKey = 'A' | 'B' | 'C' | 'D';
export type QuestionOptionKey = OptionKey;

export interface QuestionOption {
  key: QuestionOptionKey;
  text: string;
}

export interface ExamQuestionOptions {
  A: string;
  B: string;
  C: string;
  D: string;
}

export interface ExamQuestion {
  id: number;
  exam_year_id: number;
  subject_id: number;
  subject_name?: string;
  stream?: 'natural' | 'social' | 'common' | null;
  question_number: number;
  question_text: string;
  diagram_url?: string | null;
  options: ExamQuestionOptions;
  correct_answer: OptionKey;
  explanation: string;
  created_at?: string;
  updated_at?: string;
}

export type StreamSlug = 'natural' | 'social' | 'common';

export interface ExamSubject {
  id: number;
  name: string;
  code: string;
  stream?: StreamSlug | null;
  stream_id?: number | null;
  stream_name?: string | null;
  questions_count: number;
}

export interface SubjectAvailableYear {
  id: number;
  year_id?: number;
  nes_id?: number;
  subject_id: number;
  exam_id?: number;
  exam_title?: string;
  exam_short?: string;
  year_ec: number;
  year_gc: number;
  questions_count: number;
  is_published?: boolean;
}

export interface NationalExamSubjectOverview {
  id: number;
  name: string;
  code?: string;
  stream: StreamSlug;
  stream_id?: number | null;
  stream_name?: string | null;
  total_years: number;
  total_questions: number;
  available_years: SubjectAvailableYear[];
}

export interface ExamYear {
  id: number;
  exam_id: number;
  grade_level: ExamGradeLevel;
  year_ec: number; // e.g. 2016
  year_gc: number; // e.g. 2024
  year?: number;
  is_published: boolean;
  total_questions: number;
  subjects: ExamSubject[];
  // Backwards-compatible mock fields
  total_subjects?: number;
  questions_count?: number;
}

export interface NationalExam {
  id: number;
  name: string;
  code: string;
  target_grade: number;
  description: string;
  active_years_count: number;
}

/** Lightweight exam-program descriptor returned by the overview endpoint */
export interface ExamProgram {
  id: number;
  title: string;
  short?: string;
}
