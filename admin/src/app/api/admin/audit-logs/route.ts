import { NextRequest, NextResponse } from "next/server"

// Real audit log proxy to apiv2.asteghi.com (all mock data removed)
const BACKEND_API_URL = process.env.NEXT_PUBLIC_API_URL || "https://apiv2.asteghi.com/api"

export async function GET(request: NextRequest) {
  try {
    const searchParams = request.nextUrl.searchParams.toString()
    const url = `${BACKEND_API_URL}/admin/audit-logs${searchParams ? `?${searchParams}` : ""}`

    const authHeader = request.headers.get("authorization") || ""
    const response = await fetch(url, {
      headers: {
        Accept: "application/json",
        ...(authHeader ? { Authorization: authHeader } : {}),
      },
      cache: "no-store",
    })

    const data = await response.json()
    return NextResponse.json(data, { status: response.status })
  } catch (error: any) {
    return NextResponse.json(
      { status: "error", message: "Failed to forward audit logs request to backend API." },
      { status: 502 }
    )
  }
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json()
    const authHeader = request.headers.get("authorization") || ""
    const response = await fetch(`${BACKEND_API_URL}/admin/audit-logs`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        ...(authHeader ? { Authorization: authHeader } : {}),
      },
      body: JSON.stringify(body),
    })

    const data = await response.json()
    return NextResponse.json(data, { status: response.status })
  } catch (error: any) {
    return NextResponse.json(
      { status: "error", message: "Failed to forward audit event to backend API." },
      { status: 502 }
    )
  }
}
