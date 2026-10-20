export interface ContactSetting {
  id?: number
  email: string
  phone: string
  secondary_phone?: string | null
  created_at?: string
  updated_at?: string
}

export interface ContactSettingResponse {
  status: "success" | "error"
  message?: string
  data: ContactSetting
}
