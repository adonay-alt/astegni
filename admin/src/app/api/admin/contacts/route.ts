import { NextRequest, NextResponse } from "next/server"

const BACKEND_API_URL = process.env.NEXT_PUBLIC_API_URL || "https://apiv2.asteghi.com/api"

export async function GET(request: NextRequest) {
  try {
    const searchParams = request.nextUrl.searchParams.toString()
    const url = `${BACKEND_API_URL}/admin/contacts${searchParams ? `?${searchParams}` : ""}`

    const authHeader = request.headers.get("authorization") || ""
    const cookieHeader = request.headers.get("cookie") || ""

    const response = await fetch(url, {
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
      { status: "error", message: "Failed to forward contacts request to backend API: " + error.message },
      { status: 502 }
    )
  }
}
