import { NextRequest, NextResponse } from "next/server"

const BACKEND_API_URL = process.env.NEXT_PUBLIC_API_URL || "https://apiv2.asteghi.com/api"

export async function PATCH(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    const { id } = await params
    const authHeader = request.headers.get("authorization") || ""
    const cookieHeader = request.headers.get("cookie") || ""

    const response = await fetch(`${BACKEND_API_URL}/admin/contacts/${id}/read`, {
      method: "PATCH",
      headers: {
        Accept: "application/json",
        ...(authHeader ? { Authorization: authHeader } : {}),
        ...(cookieHeader ? { Cookie: cookieHeader } : {}),
      },
    })

    const data = await response.json()
    return NextResponse.json(data, { status: response.status })
  } catch (error: any) {
    return NextResponse.json(
      { status: "error", message: "Failed to mark contact as read: " + error.message },
      { status: 502 }
    )
  }
}
