import { z } from "zod";
import { CONTRACT_TYPES, EMPLOYMENT_STATUSES, ROLES } from "@/db/schema";

// Egyptian phone numbers like +201xxxxxxxxx or 01xxxxxxxxx (11 digits).
const phoneRegex = /^(\+?\d{10,15})$/;
// Egyptian national ID: 14 digits (basic check; full Luhn-like check optional).
const nationalIdRegex = /^\d{14}$/;

export const employeeBaseSchema = z.object({
  firstName: z.string().min(1, "First name required").max(128),
  lastName: z.string().min(1, "Last name required").max(128),
  email: z.string().email("Valid email required"),
  phone: z
    .string()
    .regex(phoneRegex, "Phone should be 10–15 digits, optional + prefix")
    .optional()
    .or(z.literal("")),
  nationalId: z
    .string()
    .regex(nationalIdRegex, "Egyptian national ID is 14 digits")
    .optional()
    .or(z.literal("")),
  dateOfBirth: z.string().optional().or(z.literal("")),
  gender: z.enum(["M", "F"]).optional(),
  maritalStatus: z.string().optional().or(z.literal("")),
  nationality: z.string().optional().or(z.literal("")),
  address: z.string().max(500).optional().or(z.literal("")),
  emergencyContactName: z.string().max(255).optional().or(z.literal("")),
  emergencyContactPhone: z
    .string()
    .regex(phoneRegex, "Invalid phone")
    .optional()
    .or(z.literal("")),
});

export const employeeEmploymentSchema = z.object({
  employeeCode: z.string().min(1, "Employee code required").max(32),
  positionId: z.string().uuid().optional().or(z.literal("")),
  departmentId: z.string().uuid().optional().or(z.literal("")),
  officeId: z.string().uuid().optional().or(z.literal("")),
  managerId: z.string().uuid().optional().or(z.literal("")),
  hiringDate: z.string().optional().or(z.literal("")),
  contractType: z.enum(CONTRACT_TYPES).optional(),
  contractStartDate: z.string().optional().or(z.literal("")),
  contractEndDate: z.string().optional().or(z.literal("")),
  employmentStatus: z.enum(EMPLOYMENT_STATUSES).default("active"),
  baseSalaryEgp: z.coerce
    .number()
    .nonnegative("Salary cannot be negative")
    .default(0),
});

export const employeeAccessSchema = z.object({
  role: z.enum(ROLES).default("employee"),
  initialPassword: z
    .string()
    .min(8, "Password must be at least 8 characters")
    .max(128),
});

export const createEmployeeSchema = employeeBaseSchema
  .merge(employeeEmploymentSchema)
  .merge(employeeAccessSchema);

export const updateEmployeeSchema = employeeBaseSchema
  .merge(employeeEmploymentSchema)
  .partial()
  .extend({
    id: z.string().uuid(),
  });

export const updateOwnBasicSchema = z.object({
  id: z.string().uuid(),
  phone: employeeBaseSchema.shape.phone,
  address: employeeBaseSchema.shape.address,
  emergencyContactName: employeeBaseSchema.shape.emergencyContactName,
  emergencyContactPhone: employeeBaseSchema.shape.emergencyContactPhone,
});

export type CreateEmployeeInput = z.infer<typeof createEmployeeSchema>;
export type UpdateEmployeeInput = z.infer<typeof updateEmployeeSchema>;
export type UpdateOwnBasicInput = z.infer<typeof updateOwnBasicSchema>;
