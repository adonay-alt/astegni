export type ContactStatus = "unread" | "read" | "replied";

export interface ContactMessage {
  id: number;
  name: string;
  email: string;
  subject: string | null;
  message: string;
  status: ContactStatus;
  reply_message: string | null;
  replied_at: string | null;
  admin_id: number | null;
  created_at: string;
  updated_at: string;
}

export interface ContactCounts {
  total: number;
  unread: number;
  read: number;
  replied: number;
}

export interface ContactsResponse {
  status: "success" | "error";
  message?: string;
  data: ContactMessage[];
  counts: ContactCounts;
  pagination: {
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
  };
}
