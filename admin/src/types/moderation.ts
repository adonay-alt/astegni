export type ReviewStatus = 'pending' | 'approved' | 'rejected'

export interface PlatformReviewUser {
  id: number
  first_name?: string
  last_name?: string
  email?: string
  photo_url?: string | null
  profile_photo_url?: string | null
  student_profile?: {
    id: number
    photo?: string | null
    grade_id?: number | null
    grade?: {
      id: number
      name: string
    } | null
  } | null
  tutor_profile?: {
    id: number
    image?: string | null
  } | null
}

export interface PlatformReview {
  id: number
  user_id: number
  rating: number
  comment?: string | null
  review_text?: string
  status: ReviewStatus
  created_at: string
  updated_at?: string
  user?: PlatformReviewUser | null
  student_name?: string
  student_avatar?: string
  grade_name?: string
}

export type ContactStatus = 'unread' | 'read' | 'replied'

export interface ContactMessage {
  id: number
  name: string
  email: string
  phone?: string
  subject: string
  message: string
  status: ContactStatus
  created_at: string
  reply_text?: string
  replied_at?: string
}

export interface BroadcastNotification {
  id: number
  title: string
  message: string
  target_audience: 'all' | 'grade_9' | 'grade_10' | 'grade_11' | 'grade_12' | 'tutors'
  type: 'announcement' | 'exam_alert' | 'maintenance' | 'feature'
  sent_by: string
  sent_at: string
  delivered_count: number
}
