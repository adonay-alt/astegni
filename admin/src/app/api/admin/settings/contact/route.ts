import { NextRequest, NextResponse } from "next/server"

const BACKEND_API_URL = process.env.NEXT_PUBLIC_API_URL || "https://apiv2.asteghi.com/api"

export async function GET(request: NextRequest) {
  try {
    const authHeader = request.headers.get("authorization") || ""
    const cookieHeader = request.headers.get("cookie") || ""

    const response = await fetch(`${BACKEND_API_URL}/admin/settings/contact`, {
      headers: {
        Accept: "application/json",
        ...(authHeader ? { Authorization: authHeader } : {}),
        ...(cookieHeader ? { Cookie: cookieHeader } : {}),
      },
      cache: "no-store",
    })

    const data = await response.json()
    return NextResponse.json(data, { status: response.status })
  } catch (error: any) {
    return NextResponse.json(
      { status: "error", message: "Failed to fetch contact settings: " + error.message },
      { status: 502 }
    )
  }
}

export async function PUT(request: NextRequest) {
  try {
    const body = await request.json()
    const authHeader = request.headers.get("authorization") || ""
    const cookieHeader = request.headers.get("cookie") || ""

    const response = await fetch(`${BACKEND_API_URL}/admin/settings/contact`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        ...(authHeader ? { Authorization: authHeader } : {}),
        ...(cookieHeader ? { Cookie: cookieHeader } : {}),
      },
      body: JSON.stringify(body),
    })

    const data = await response.json()
    return NextResponse.json(data, { status: response.status })
  } catch (error: any) {
    return NextResponse.json(
      { status: "error", message: "Failed to update contact settings: " + error.message },
      { status: 502 }
    )
  }
}
