"use server";

import { revalidatePath } from "next/cache";
import { eq } from "drizzle-orm";
import { randomUUID } from "node:crypto";
import bcrypt from "bcryptjs";
import { db } from "@/db";
import { employees, userCredentials } from "@/db/schema";
import {
  createEmployeeSchema,
  updateEmployeeSchema,
  updateOwnBasicSchema,
} from "@/lib/validation/employee";
import {
  AuthorizationError,
  canEditEmployee,
  canEditOwnBasicFields,
  requireRole,
  requireUser,
} from "@/lib/rbac";
import { recordAudit } from "@/lib/audit";

export type ActionResult<T = void> =
  | { ok: true; data?: T }
  | { ok: false; error: string; fieldErrors?: Record<string, string> };

function emptyToNull(v: string | null | undefined): string | null {
  if (v === undefined || v === null || v === "") return null;
  return v;
}

export async function createEmployeeAction(
  input: unknown,
): Promise<ActionResult<{ id: string }>> {
  const actor = await requireRole("admin", "hr");
  const parsed = createEmployeeSchema.safeParse(input);
  if (!parsed.success) {
    const fieldErrors: Record<string, string> = {};
    for (const issue of parsed.error.issues) {
      fieldErrors[issue.path.join(".") || "_"] = issue.message;
    }
    return { ok: false, error: "Validation failed", fieldErrors };
  }
  const data = parsed.data;
  const email = data.email.toLowerCase().trim();

  // Pre-check uniqueness for friendlier errors than DB constraint failures.
  const existing = await db.query.employees.findFirst({
    where: eq(employees.email, email),
  });
  if (existing) {
    return {
      ok: false,
      error: "An employee with this email already exists",
      fieldErrors: { email: "Already in use" },
    };
  }

  const credentialId = randomUUID();
  const employeeId = randomUUID();
  const passwordHash = await bcrypt.hash(data.initialPassword, 10);

  await db.insert(userCredentials).values({
    id: credentialId,
    email,
    passwordHash,
  });

  await db.insert(employees).values({
    id: employeeId,
    orgId: actor.orgId,
    userCredentialId: credentialId,
    employeeCode: data.employeeCode,
    email,
    firstName: data.firstName,
    lastName: data.lastName,
    phone: emptyToNull(data.phone),
    nationalId: emptyToNull(data.nationalId),
    dateOfBirth: emptyToNull(data.dateOfBirth),
    gender: data.gender ?? null,
    maritalStatus: emptyToNull(data.maritalStatus),
    nationality: data.nationality || "Egyptian",
    address: emptyToNull(data.address),
    emergencyContactName: emptyToNull(data.emergencyContactName),
    emergencyContactPhone: emptyToNull(data.emergencyContactPhone),
    positionId: emptyToNull(data.positionId),
    departmentId: emptyToNull(data.departmentId),
    officeId: emptyToNull(data.officeId),
    managerId: emptyToNull(data.managerId),
    hiringDate: emptyToNull(data.hiringDate),
    contractType: data.contractType ?? null,
    contractStartDate: emptyToNull(data.contractStartDate),
    contractEndDate: emptyToNull(data.contractEndDate),
    employmentStatus: data.employmentStatus,
    baseSalaryPiasters: Math.round(data.baseSalaryEgp * 100),
    role: data.role,
  });

  await recordAudit(actor, {
    action: "create",
    entityType: "employee",
    entityId: employeeId,
    changes: { after: { email, firstName: data.firstName, lastName: data.lastName, role: data.role } },
  });

  revalidatePath("/employees");
  return { ok: true, data: { id: employeeId } };
}

export async function updateEmployeeAction(
  input: unknown,
): Promise<ActionResult> {
  const actor = await requireUser();
  const parsed = updateEmployeeSchema.safeParse(input);
  if (!parsed.success) {
    return { ok: false, error: "Validation failed" };
  }
  const data = parsed.data;
  const target = await db.query.employees.findFirst({
    where: eq(employees.id, data.id),
  });
  if (!target) return { ok: false, error: "Employee not found" };
  if (!canEditEmployee(actor, target)) {
    throw new AuthorizationError();
  }

  const before = { ...target };
  const patch: Record<string, unknown> = {};
  for (const [key, value] of Object.entries(data)) {
    if (key === "id") continue;
    if (value === undefined) continue;
    if (key === "baseSalaryEgp") {
      patch.baseSalaryPiasters = Math.round(Number(value) * 100);
      continue;
    }
    patch[key] = value === "" ? null : value;
  }

  await db
    .update(employees)
    .set({ ...patch, updatedAt: new Date() })
    .where(eq(employees.id, data.id));

  await recordAudit(actor, {
    action: "update",
    entityType: "employee",
    entityId: data.id,
    changes: { before, after: patch },
  });

  revalidatePath("/employees");
  revalidatePath(`/employees/${data.id}`);
  return { ok: true };
}

export async function updateOwnBasicAction(
  input: unknown,
): Promise<ActionResult> {
  const actor = await requireUser();
  const parsed = updateOwnBasicSchema.safeParse(input);
  if (!parsed.success) {
    return { ok: false, error: "Validation failed" };
  }
  const data = parsed.data;
  if (!canEditOwnBasicFields(actor, { id: data.id })) {
    throw new AuthorizationError();
  }
  await db
    .update(employees)
    .set({
      phone: emptyToNull(data.phone),
      address: emptyToNull(data.address),
      emergencyContactName: emptyToNull(data.emergencyContactName),
      emergencyContactPhone: emptyToNull(data.emergencyContactPhone),
      updatedAt: new Date(),
    })
    .where(eq(employees.id, data.id));

  await recordAudit(actor, {
    action: "update",
    entityType: "employee",
    entityId: data.id,
    changes: { fields: ["phone", "address", "emergencyContactName", "emergencyContactPhone"] },
  });

  revalidatePath(`/employees/${data.id}`);
  return { ok: true };
}

export async function softDeleteEmployeeAction(
  id: string,
): Promise<ActionResult> {
  const actor = await requireRole("admin", "hr");
  const target = await db.query.employees.findFirst({
    where: eq(employees.id, id),
  });
  if (!target) return { ok: false, error: "Employee not found" };
  if (target.orgId !== actor.orgId) throw new AuthorizationError();

  await db
    .update(employees)
    .set({ deletedAt: new Date(), employmentStatus: "terminated" })
    .where(eq(employees.id, id));

  await recordAudit(actor, {
    action: "delete",
    entityType: "employee",
    entityId: id,
  });

  revalidatePath("/employees");
  return { ok: true };
}
