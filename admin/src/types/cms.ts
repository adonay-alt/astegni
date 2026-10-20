export interface LatestUpdateItem {
  id: number
  title: string
  title_en?: string | null
  title_am?: string | null
  description: string
  description_en?: string | null
  description_am?: string | null
  image_path?: string | null
  image_url?: string | null
  is_active: boolean
  published_at?: string | null
  created_at?: string
  updated_at?: string
}

export type PlatformUpdate = LatestUpdateItem

export interface CmsPageSection {
  id: string | number
  page_id?: number
  heading?: string | null
  heading_en?: string | null
  heading_am?: string | null
  body?: string | null
  body_en?: string | null
  body_am?: string | null
  question?: string | null
  question_en?: string | null
  question_am?: string | null
  answer?: string | null
  answer_en?: string | null
  answer_am?: string | null
  sort_order: number
  is_published?: boolean
  created_at?: string
  updated_at?: string
  // Legacy / fallback mock compatibility
  title?: string
  content?: string
}

export interface CmsPage {
  id: number
  title: string
  title_en?: string | null
  title_am?: string | null
  subtitle?: string | null
  subtitle_en?: string | null
  subtitle_am?: string | null
  slug: string
  type: 'legal' | 'faq' | 'general'
  is_published: boolean
  last_updated?: string
  updated_at?: string
  sections_count?: number
  sections: CmsPageSection[]
}
