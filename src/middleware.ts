import { NextResponse } from "next/server";
import { auth } from "@/lib/auth";

const PUBLIC_PATHS = new Set(["/login", "/api/auth"]);

function isPublic(pathname: string): boolean {
  if (PUBLIC_PATHS.has(pathname)) return true;
  if (pathname.startsWith("/api/auth/")) return true;
  if (pathname.startsWith("/_next/")) return true;
  if (pathname.startsWith("/uploads/")) return true;
  if (pathname === "/favicon.ico") return true;
  return false;
}

export default auth((req) => {
  const { pathname } = req.nextUrl;
  if (isPublic(pathname)) return NextResponse.next();

  if (!req.auth) {
    const loginUrl = new URL("/login", req.nextUrl.origin);
    loginUrl.searchParams.set("from", pathname);
    return NextResponse.redirect(loginUrl);
  }

  return NextResponse.next();
});

export const config = {
  matcher: ["/((?!_next/static|_next/image|favicon.ico).*)"],
};
