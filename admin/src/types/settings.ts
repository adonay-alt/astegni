import { AdminRole } from "./auth"

export interface AdminAuditLog {
  id: number
  admin_id?: number
  admin_name: string
  admin_email: string
  action: string
  auditable_type: string
  auditable_type_full?: string
  auditable_id: number
  ip_address: string
  user_agent: string
  created_at: string
  time_ago?: string
  formatted_time?: string
  old_values: Record<string, unknown> | null
  new_values: Record<string, unknown> | null
}

export interface AuditLogPagination {
  total: number
  current_page: number
  last_page: number
  per_page: number
}

export interface StaffMember {
  id: number
  name: string
  first_name?: string
  last_name?: string
  email: string
  role: AdminRole
  is_admin?: boolean
  is_active?: boolean
  status?: 'active' | 'inactive'
  profile_photo_url?: string | null
  has_student_profile?: boolean
  has_tutor_profile?: boolean
  tutor_status?: string | null
  last_active_at?: string
  created_at?: string
  student_profile?: Record<string, unknown> | null
  tutor_profile?: Record<string, unknown> | null
}

export interface RoleDefinition {
  role: AdminRole
  name: string
  description: string
  color: 'rose' | 'blue' | 'amber' | 'emerald' | string
  is_staff: boolean
  count?: number
}

export interface RolePermission {
  module: string
  description?: string
  category?: string
  super_admin: boolean
  academic_editor: boolean
  tutor_moderator: boolean
  support_moderator?: boolean
}

export interface StaffApiResponse {
  status: string
  message?: string
  data: StaffMember[]
  roles?: RoleDefinition[]
  permissions_matrix?: RolePermission[]
  kpis?: {
    total_staff: number
    super_admins: number
    academic_editors: number
    tutor_moderators: number
    support_moderators?: number
  }
}
