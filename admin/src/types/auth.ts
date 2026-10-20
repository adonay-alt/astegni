export type AdminRole = 
  | 'super_admin'
  | 'academic_editor'
  | 'tutor_moderator'
  | 'support_moderator'
  | 'support_agent'
  | 'finance_auditor'

export interface AdminUser {
  id: number
  name: string
  first_name?: string
  last_name?: string
  email: string
  role: AdminRole
  is_admin: boolean
  avatar_url?: string | null
  grade?: number | null
  created_at?: string
  updated_at?: string
}

export interface LoginCredentials {
  email: string
  password: string
  remember?: boolean
}

export interface AuthResponse {
  status: 'success' | 'error'
  message?: string
  token?: string
  user?: AdminUser
  data?: {
    token?: string
    access_token?: string
    user?: AdminUser
  }
}
