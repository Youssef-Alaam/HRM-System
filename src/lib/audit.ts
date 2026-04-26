import { headers } from "next/headers";
import { randomUUID } from "node:crypto";
import { db } from "@/db";
import { auditLogs } from "@/db/schema";
import type { SessionUser } from "@/lib/rbac";

export type AuditAction =
  | "create"
  | "update"
  | "delete"
  | "login"
  | "logout"
  | "approve"
  | "reject"
  | "check_in"
  | "check_out";

export type AuditPayload = {
  action: AuditAction;
  entityType: string;
  entityId?: string | null;
  changes?: Record<string, unknown> | null;
};

export async function recordAudit(
  actor: SessionUser | null,
  payload: AuditPayload,
): Promise<void> {
  let ipAddress: string | null = null;
  let userAgent: string | null = null;
  try {
    const h = await headers();
    ipAddress =
      h.get("x-forwarded-for")?.split(",")[0]?.trim() ??
      h.get("x-real-ip") ??
      null;
    userAgent = h.get("user-agent");
  } catch {
    // headers() not available in this context (e.g. seed script) — ignore.
  }

  await db.insert(auditLogs).values({
    id: randomUUID(),
    userId: actor?.employeeId ?? null,
    action: payload.action,
    entityType: payload.entityType,
    entityId: payload.entityId ?? null,
    changesJson: payload.changes ?? null,
    ipAddress,
    userAgent: userAgent ? userAgent.slice(0, 500) : null,
  });
}
