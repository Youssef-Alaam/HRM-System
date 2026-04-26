import { auth } from "@/lib/auth";
import type { Role } from "@/db/schema";

export class AuthorizationError extends Error {
  constructor(message = "Not authorized") {
    super(message);
    this.name = "AuthorizationError";
  }
}

export class AuthenticationError extends Error {
  constructor(message = "Not authenticated") {
    super(message);
    this.name = "AuthenticationError";
  }
}

export type SessionUser = {
  id: string;
  employeeId: string;
  role: Role;
  orgId: string;
  firstName: string;
  lastName: string;
  email: string;
};

/**
 * Returns the session user, or throws AuthenticationError. Use this in Server
 * Actions and route handlers — it is the gatekeeper that replaces Supabase RLS.
 */
export async function requireUser(): Promise<SessionUser> {
  const session = await auth();
  if (!session?.user) throw new AuthenticationError();
  const u = session.user;
  return {
    id: u.id,
    employeeId: u.employeeId,
    role: u.role,
    orgId: u.orgId,
    firstName: u.firstName,
    lastName: u.lastName,
    email: u.email ?? "",
  };
}

export async function requireRole(...allowed: Role[]): Promise<SessionUser> {
  const user = await requireUser();
  if (!allowed.includes(user.role)) throw new AuthorizationError();
  return user;
}

export function isAdmin(user: SessionUser): boolean {
  return user.role === "admin";
}

export function isHr(user: SessionUser): boolean {
  return user.role === "hr" || user.role === "admin";
}

export function isManager(user: SessionUser): boolean {
  return user.role === "manager";
}

/**
 * Whether the actor can VIEW the target employee record.
 * Per PRD §4 permission matrix.
 */
export function canViewEmployee(
  actor: SessionUser,
  target: { id: string; managerId: string | null; orgId: string },
): boolean {
  if (actor.orgId !== target.orgId) return false;
  if (actor.role === "admin" || actor.role === "hr") return true;
  if (actor.employeeId === target.id) return true;
  if (actor.role === "manager" && target.managerId === actor.employeeId) {
    return true;
  }
  return false;
}

/**
 * Whether the actor can EDIT another employee's full profile (HR/Admin only).
 * Self-edit of basic fields uses a separate, narrower function.
 */
export function canEditEmployee(
  actor: SessionUser,
  target: { orgId: string },
): boolean {
  if (actor.orgId !== target.orgId) return false;
  return actor.role === "admin" || actor.role === "hr";
}

export function canEditOwnBasicFields(
  actor: SessionUser,
  target: { id: string },
): boolean {
  return actor.employeeId === target.id;
}

export function canApproveLeaveAsManager(
  actor: SessionUser,
  request: { employeeId: string; managerId: string | null },
): boolean {
  if (actor.role === "admin" || actor.role === "hr") return true;
  if (actor.role !== "manager") return false;
  return request.managerId === actor.employeeId;
}

export function canFinalApproveLeave(actor: SessionUser): boolean {
  return actor.role === "admin" || actor.role === "hr";
}

export function canManageSettings(actor: SessionUser): boolean {
  return actor.role === "admin";
}

export function canViewAuditLog(actor: SessionUser): boolean {
  return actor.role === "admin";
}

export function canEditAttendance(actor: SessionUser): boolean {
  return actor.role === "admin" || actor.role === "hr";
}
