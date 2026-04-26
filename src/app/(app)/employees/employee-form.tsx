"use client";

import { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  createEmployeeAction,
  updateEmployeeAction,
} from "@/lib/actions/employees";
import {
  CONTRACT_TYPES,
  EMPLOYMENT_STATUSES,
  ROLES,
  type Department,
  type Office,
  type Position,
} from "@/db/schema";

type ManagerOption = { id: string; firstName: string; lastName: string };

type Mode = "create" | "edit";

type CommonFields = {
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  nationalId: string;
  dateOfBirth: string;
  gender: "" | "M" | "F";
  maritalStatus: string;
  nationality: string;
  address: string;
  emergencyContactName: string;
  emergencyContactPhone: string;
  employeeCode: string;
  positionId: string;
  departmentId: string;
  officeId: string;
  managerId: string;
  hiringDate: string;
  contractType: "" | (typeof CONTRACT_TYPES)[number];
  contractStartDate: string;
  contractEndDate: string;
  employmentStatus: (typeof EMPLOYMENT_STATUSES)[number];
  baseSalaryEgp: string;
  role: (typeof ROLES)[number];
  initialPassword: string;
};

const blankForm: CommonFields = {
  firstName: "",
  lastName: "",
  email: "",
  phone: "",
  nationalId: "",
  dateOfBirth: "",
  gender: "",
  maritalStatus: "",
  nationality: "Egyptian",
  address: "",
  emergencyContactName: "",
  emergencyContactPhone: "",
  employeeCode: "",
  positionId: "",
  departmentId: "",
  officeId: "",
  managerId: "",
  hiringDate: "",
  contractType: "",
  contractStartDate: "",
  contractEndDate: "",
  employmentStatus: "active",
  baseSalaryEgp: "0",
  role: "employee",
  initialPassword: "",
};

export function EmployeeForm({
  mode,
  initial,
  departments,
  positions,
  offices,
  managers,
}: {
  mode: Mode;
  initial?: Partial<CommonFields> & { id?: string };
  departments: Department[];
  positions: Position[];
  offices: Office[];
  managers: ManagerOption[];
}) {
  const router = useRouter();
  const [pending, startTransition] = useTransition();
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [generalError, setGeneralError] = useState<string | null>(null);
  const [form, setForm] = useState<CommonFields>({
    ...blankForm,
    ...initial,
  } as CommonFields);

  function update<K extends keyof CommonFields>(key: K, value: CommonFields[K]) {
    setForm((f) => ({ ...f, [key]: value }));
  }

  function handleSubmit() {
    setErrors({});
    setGeneralError(null);

    startTransition(async () => {
      const payload = {
        ...form,
        baseSalaryEgp: Number(form.baseSalaryEgp || 0),
      };
      const action =
        mode === "create"
          ? createEmployeeAction(payload)
          : updateEmployeeAction({ ...payload, id: initial?.id });
      const result = await action;
      if (!result.ok) {
        setErrors(result.fieldErrors ?? {});
        setGeneralError(result.error);
        return;
      }
      const id =
        mode === "create" && "data" in result ? result.data?.id : initial?.id;
      router.push(id ? `/employees/${id}` : "/employees");
      router.refresh();
    });
  }

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Personal information</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <Field
            label="First name"
            required
            error={errors.firstName}
            input={
              <Input
                value={form.firstName}
                onChange={(e) => update("firstName", e.target.value)}
              />
            }
          />
          <Field
            label="Last name"
            required
            error={errors.lastName}
            input={
              <Input
                value={form.lastName}
                onChange={(e) => update("lastName", e.target.value)}
              />
            }
          />
          <Field
            label="Work email"
            required
            error={errors.email}
            input={
              <Input
                type="email"
                value={form.email}
                onChange={(e) => update("email", e.target.value)}
                disabled={mode === "edit"}
              />
            }
          />
          <Field
            label="Phone"
            error={errors.phone}
            input={
              <Input
                value={form.phone}
                onChange={(e) => update("phone", e.target.value)}
                placeholder="+2010..."
              />
            }
          />
          <Field
            label="National ID"
            error={errors.nationalId}
            input={
              <Input
                value={form.nationalId}
                onChange={(e) => update("nationalId", e.target.value)}
                placeholder="14 digits"
              />
            }
          />
          <Field
            label="Date of birth"
            error={errors.dateOfBirth}
            input={
              <Input
                type="date"
                value={form.dateOfBirth}
                onChange={(e) => update("dateOfBirth", e.target.value)}
              />
            }
          />
          <Field
            label="Gender"
            error={errors.gender}
            input={
              <select
                className="h-9 w-full rounded-md border border-[var(--input)] bg-[var(--background)] px-3 text-sm"
                value={form.gender}
                onChange={(e) =>
                  update("gender", e.target.value as CommonFields["gender"])
                }
              >
                <option value="">—</option>
                <option value="M">Male</option>
                <option value="F">Female</option>
              </select>
            }
          />
          <Field
            label="Marital status"
            input={
              <Input
                value={form.maritalStatus}
                onChange={(e) => update("maritalStatus", e.target.value)}
              />
            }
          />
          <Field
            label="Nationality"
            input={
              <Input
                value={form.nationality}
                onChange={(e) => update("nationality", e.target.value)}
              />
            }
          />
          <Field
            label="Address"
            className="sm:col-span-2"
            input={
              <Input
                value={form.address}
                onChange={(e) => update("address", e.target.value)}
              />
            }
          />
          <Field
            label="Emergency contact name"
            input={
              <Input
                value={form.emergencyContactName}
                onChange={(e) =>
                  update("emergencyContactName", e.target.value)
                }
              />
            }
          />
          <Field
            label="Emergency contact phone"
            input={
              <Input
                value={form.emergencyContactPhone}
                onChange={(e) =>
                  update("emergencyContactPhone", e.target.value)
                }
              />
            }
          />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Employment</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <Field
            label="Employee code"
            required
            error={errors.employeeCode}
            input={
              <Input
                value={form.employeeCode}
                onChange={(e) => update("employeeCode", e.target.value)}
                placeholder="EMP001"
              />
            }
          />
          <Field
            label="Department"
            input={
              <select
                className="h-9 w-full rounded-md border border-[var(--input)] bg-[var(--background)] px-3 text-sm"
                value={form.departmentId}
                onChange={(e) => update("departmentId", e.target.value)}
              >
                <option value="">—</option>
                {departments.map((d) => (
                  <option key={d.id} value={d.id}>
                    {d.name}
                  </option>
                ))}
              </select>
            }
          />
          <Field
            label="Position"
            input={
              <select
                className="h-9 w-full rounded-md border border-[var(--input)] bg-[var(--background)] px-3 text-sm"
                value={form.positionId}
                onChange={(e) => update("positionId", e.target.value)}
              >
                <option value="">—</option>
                {positions.map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.title}
                  </option>
                ))}
              </select>
            }
          />
          <Field
            label="Office"
            input={
              <select
                className="h-9 w-full rounded-md border border-[var(--input)] bg-[var(--background)] px-3 text-sm"
                value={form.officeId}
                onChange={(e) => update("officeId", e.target.value)}
              >
                <option value="">—</option>
                {offices.map((o) => (
                  <option key={o.id} value={o.id}>
                    {o.name}
                  </option>
                ))}
              </select>
            }
          />
          <Field
            label="Manager"
            input={
              <select
                className="h-9 w-full rounded-md border border-[var(--input)] bg-[var(--background)] px-3 text-sm"
                value={form.managerId}
                onChange={(e) => update("managerId", e.target.value)}
              >
                <option value="">— No manager —</option>
                {managers
                  .filter((m) => m.id !== initial?.id)
                  .map((m) => (
                    <option key={m.id} value={m.id}>
                      {m.firstName} {m.lastName}
                    </option>
                  ))}
              </select>
            }
          />
          <Field
            label="Hiring date"
            input={
              <Input
                type="date"
                value={form.hiringDate}
                onChange={(e) => update("hiringDate", e.target.value)}
              />
            }
          />
          <Field
            label="Contract type"
            input={
              <select
                className="h-9 w-full rounded-md border border-[var(--input)] bg-[var(--background)] px-3 text-sm"
                value={form.contractType}
                onChange={(e) =>
                  update("contractType", e.target.value as CommonFields["contractType"])
                }
              >
                <option value="">—</option>
                {CONTRACT_TYPES.map((c) => (
                  <option key={c} value={c}>
                    {c.replace("_", " ")}
                  </option>
                ))}
              </select>
            }
          />
          <Field
            label="Employment status"
            input={
              <select
                className="h-9 w-full rounded-md border border-[var(--input)] bg-[var(--background)] px-3 text-sm"
                value={form.employmentStatus}
                onChange={(e) =>
                  update(
                    "employmentStatus",
                    e.target.value as CommonFields["employmentStatus"],
                  )
                }
              >
                {EMPLOYMENT_STATUSES.map((s) => (
                  <option key={s} value={s}>
                    {s}
                  </option>
                ))}
              </select>
            }
          />
          <Field
            label="Contract start"
            input={
              <Input
                type="date"
                value={form.contractStartDate}
                onChange={(e) => update("contractStartDate", e.target.value)}
              />
            }
          />
          <Field
            label="Contract end"
            input={
              <Input
                type="date"
                value={form.contractEndDate}
                onChange={(e) => update("contractEndDate", e.target.value)}
              />
            }
          />
          <Field
            label="Base salary (EGP)"
            input={
              <Input
                type="number"
                min="0"
                step="0.01"
                value={form.baseSalaryEgp}
                onChange={(e) => update("baseSalaryEgp", e.target.value)}
              />
            }
          />
        </CardContent>
      </Card>

      {mode === "create" && (
        <Card>
          <CardHeader>
            <CardTitle>Access</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <Field
              label="Role"
              required
              input={
                <select
                  className="h-9 w-full rounded-md border border-[var(--input)] bg-[var(--background)] px-3 text-sm"
                  value={form.role}
                  onChange={(e) =>
                    update("role", e.target.value as CommonFields["role"])
                  }
                >
                  {ROLES.map((r) => (
                    <option key={r} value={r}>
                      {r}
                    </option>
                  ))}
                </select>
              }
            />
            <Field
              label="Initial password"
              required
              error={errors.initialPassword}
              input={
                <Input
                  type="text"
                  value={form.initialPassword}
                  onChange={(e) => update("initialPassword", e.target.value)}
                  placeholder="At least 8 characters"
                />
              }
            />
          </CardContent>
        </Card>
      )}

      {generalError && (
        <div className="rounded-md bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700">
          {generalError}
        </div>
      )}

      <div className="flex items-center justify-end gap-2">
        <Button
          variant="outline"
          type="button"
          onClick={() => router.back()}
          disabled={pending}
        >
          Cancel
        </Button>
        <Button type="button" onClick={handleSubmit} disabled={pending}>
          {pending && <Loader2 className="h-4 w-4 animate-spin" />}
          {mode === "create" ? "Create employee" : "Save changes"}
        </Button>
      </div>
    </div>
  );
}

function Field({
  label,
  required,
  error,
  input,
  className,
}: {
  label: string;
  required?: boolean;
  error?: string;
  input: React.ReactNode;
  className?: string;
}) {
  return (
    <div className={`space-y-1.5 ${className ?? ""}`}>
      <Label>
        {label}
        {required && <span className="text-[var(--destructive)] ml-0.5">*</span>}
      </Label>
      {input}
      {error && (
        <p className="text-xs text-[var(--destructive)]">{error}</p>
      )}
    </div>
  );
}
