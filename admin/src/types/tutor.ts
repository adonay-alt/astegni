export interface TutorDocument {
  type: string
  url: string
  name: string
}

export type TutorStatus = 'pending' | 'approved' | 'rejected' | 'suspended'

export interface TutorProfile {
  id: number
  user_id: number
  name: string
  email: string
  phone: string
  city: string
  gender: 'male' | 'female' | 'other'
  status: TutorStatus
  rating: number
  total_reviews: number
  subjects: string[]
  grades: string[]
  bio?: string
  avatar_url?: string
  documents: TutorDocument[]
  rejection_reason?: string
  created_at: string
}
