import {
  mysqlTable,
  varchar,
  int,
  bigint,
  boolean,
  timestamp,
  date,
  text,
  json,
  decimal,
  uniqueIndex,
  index,
  mysqlEnum,
} from "drizzle-orm/mysql-core";
import { relations } from "drizzle-orm";

// ---------------------------------------------------------------------------
// Roles & enums
// ---------------------------------------------------------------------------

export const ROLES = ["admin", "hr", "manager", "employee"] as const;
export type Role = (typeof ROLES)[number];

export const CONTRACT_TYPES = [
  "probation",
  "fixed",
  "unlimited",
  "part_time",
  "internship",
  "project",
] as const;

export const EMPLOYMENT_STATUSES = [
  "active",
  "suspended",
  "terminated",
  "retired",
] as const;

export const ATTENDANCE_STATUSES = [
  "ontime",
  "late",
  "absent",
  "day_off",
  "holiday",
  "on_leave",
] as const;

export const LEAVE_TYPES = [
  "annual",
  "sick",
  "permission",
  "casual",
  "maternity",
  "paternity",
  "study",
  "emergency",
] as const;

export const LEAVE_STATUSES = [
  "pending",
  "manager_approved",
  "approved",
  "rejected",
  "cancelled",
] as const;

// ---------------------------------------------------------------------------
// Core tables (prototype scope)
// ---------------------------------------------------------------------------

export const organizations = mysqlTable("organizations", {
  id: varchar("id", { length: 36 }).primaryKey(),
  name: varchar("name", { length: 255 }).notNull(),
  legalName: varchar("legal_name", { length: 255 }),
  country: varchar("country", { length: 64 }).default("Egypt").notNull(),
  currency: varchar("currency", { length: 8 }).default("EGP").notNull(),
  timezone: varchar("timezone", { length: 64 }).default("Africa/Cairo").notNull(),
  fiscalYearStart: varchar("fiscal_year_start", { length: 5 }).default("01-01"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

export const offices = mysqlTable(
  "offices",
  {
    id: varchar("id", { length: 36 }).primaryKey(),
    orgId: varchar("org_id", { length: 36 })
      .references(() => organizations.id, { onDelete: "cascade" })
      .notNull(),
    name: varchar("name", { length: 255 }).notNull(),
    address: varchar("address", { length: 500 }),
    city: varchar("city", { length: 128 }),
    country: varchar("country", { length: 64 }).default("Egypt"),
    latitude: decimal("latitude", { precision: 10, scale: 7 }),
    longitude: decimal("longitude", { precision: 10, scale: 7 }),
    allowedCheckInRadiusMeters: int("allowed_check_in_radius_meters").default(150),
    isActive: boolean("is_active").default(true).notNull(),
    deletedAt: timestamp("deleted_at"),
    createdAt: timestamp("created_at").defaultNow().notNull(),
  },
  (t) => ({
    orgIdx: index("offices_org_idx").on(t.orgId),
  }),
);

export const departments = mysqlTable(
  "departments",
  {
    id: varchar("id", { length: 36 }).primaryKey(),
    orgId: varchar("org_id", { length: 36 })
      .references(() => organizations.id, { onDelete: "cascade" })
      .notNull(),
    name: varchar("name", { length: 255 }).notNull(),
    parentDepartmentId: varchar("parent_department_id", { length: 36 }),
    headEmployeeId: varchar("head_employee_id", { length: 36 }),
    isActive: boolean("is_active").default(true).notNull(),
    deletedAt: timestamp("deleted_at"),
    createdAt: timestamp("created_at").defaultNow().notNull(),
  },
  (t) => ({
    orgIdx: index("departments_org_idx").on(t.orgId),
  }),
);

export const positions = mysqlTable(
  "positions",
  {
    id: varchar("id", { length: 36 }).primaryKey(),
    orgId: varchar("org_id", { length: 36 })
      .references(() => organizations.id, { onDelete: "cascade" })
      .notNull(),
    title: varchar("title", { length: 255 }).notNull(),
    departmentId: varchar("department_id", { length: 36 }).references(
      () => departments.id,
    ),
    level: varchar("level", { length: 64 }),
    isActive: boolean("is_active").default(true).notNull(),
    deletedAt: timestamp("deleted_at"),
    createdAt: timestamp("created_at").defaultNow().notNull(),
  },
  (t) => ({
    orgIdx: index("positions_org_idx").on(t.orgId),
  }),
);

// Auth credentials (replacement for Supabase auth.users since we're on MySQL).
export const userCredentials = mysqlTable(
  "user_credentials",
  {
    id: varchar("id", { length: 36 }).primaryKey(),
    email: varchar("email", { length: 255 }).notNull(),
    passwordHash: varchar("password_hash", { length: 255 }).notNull(),
    failedLoginAttempts: int("failed_login_attempts").default(0).notNull(),
    lockedUntil: timestamp("locked_until"),
    lastLoginAt: timestamp("last_login_at"),
    createdAt: timestamp("created_at").defaultNow().notNull(),
  },
  (t) => ({
    emailIdx: uniqueIndex("user_credentials_email_unique").on(t.email),
  }),
);

export const employees = mysqlTable(
  "employees",
  {
    id: varchar("id", { length: 36 }).primaryKey(),
    orgId: varchar("org_id", { length: 36 })
      .references(() => organizations.id, { onDelete: "cascade" })
      .notNull(),
    userCredentialId: varchar("user_credential_id", { length: 36 }).references(
      () => userCredentials.id,
    ),
    employeeCode: varchar("employee_code", { length: 32 }).notNull(),
    email: varchar("email", { length: 255 }).notNull(),
    firstName: varchar("first_name", { length: 128 }).notNull(),
    lastName: varchar("last_name", { length: 128 }).notNull(),
    phone: varchar("phone", { length: 32 }),
    nationalId: varchar("national_id", { length: 32 }),
    dateOfBirth: date("date_of_birth", { mode: "string" }),
    gender: mysqlEnum("gender", ["M", "F"]),
    maritalStatus: varchar("marital_status", { length: 32 }),
    nationality: varchar("nationality", { length: 64 }).default("Egyptian"),
    address: varchar("address", { length: 500 }),
    emergencyContactName: varchar("emergency_contact_name", { length: 255 }),
    emergencyContactPhone: varchar("emergency_contact_phone", { length: 32 }),
    photoUrl: varchar("photo_url", { length: 500 }),
    referencePhotoUrl: varchar("reference_photo_url", { length: 500 }),
    positionId: varchar("position_id", { length: 36 }).references(
      () => positions.id,
    ),
    departmentId: varchar("department_id", { length: 36 }).references(
      () => departments.id,
    ),
    officeId: varchar("office_id", { length: 36 }).references(() => offices.id),
    managerId: varchar("manager_id", { length: 36 }),
    hiringDate: date("hiring_date", { mode: "string" }),
    contractType: mysqlEnum("contract_type", CONTRACT_TYPES),
    contractStartDate: date("contract_start_date", { mode: "string" }),
    contractEndDate: date("contract_end_date", { mode: "string" }),
    employmentStatus: mysqlEnum("employment_status", EMPLOYMENT_STATUSES)
      .default("active")
      .notNull(),
    baseSalaryPiasters: bigint("base_salary_piasters", { mode: "number" })
      .default(0)
      .notNull(),
    annualLeaveBalanceDays: decimal("annual_leave_balance_days", {
      precision: 5,
      scale: 2,
    })
      .default("21.00")
      .notNull(),
    sickLeaveBalanceDays: decimal("sick_leave_balance_days", {
      precision: 5,
      scale: 2,
    })
      .default("180.00")
      .notNull(),
    permissionsBalanceMinutes: int("permissions_balance_minutes")
      .default(120)
      .notNull(),
    casualLeaveBalanceDays: decimal("casual_leave_balance_days", {
      precision: 5,
      scale: 2,
    })
      .default("6.00")
      .notNull(),
    emergencyCreditDays: decimal("emergency_credit_days", {
      precision: 5,
      scale: 2,
    })
      .default("0.00")
      .notNull(),
    compDayBalance: decimal("comp_day_balance", { precision: 5, scale: 2 })
      .default("0.00")
      .notNull(),
    isExpat: boolean("is_expat").default(false).notNull(),
    passportNumber: varchar("passport_number", { length: 64 }),
    passportExpiry: date("passport_expiry", { mode: "string" }),
    workPermitNumber: varchar("work_permit_number", { length: 64 }),
    workPermitExpiry: date("work_permit_expiry", { mode: "string" }),
    residencyPermitNumber: varchar("residency_permit_number", { length: 64 }),
    residencyPermitExpiry: date("residency_permit_expiry", { mode: "string" }),
    speaksArabic: boolean("speaks_arabic").default(true).notNull(),
    role: mysqlEnum("role", ROLES).default("employee").notNull(),
    workweekDays: json("workweek_days")
      .$type<string[]>()
      .default(["sun", "mon", "tue", "wed", "thu"]),
    shiftStartTime: varchar("shift_start_time", { length: 5 }).default("09:00"),
    shiftEndTime: varchar("shift_end_time", { length: 5 }).default("17:00"),
    timezone: varchar("timezone", { length: 64 }).default("Africa/Cairo"),
    version: int("version").default(0).notNull(),
    createdAt: timestamp("created_at").defaultNow().notNull(),
    updatedAt: timestamp("updated_at").defaultNow().onUpdateNow().notNull(),
    deletedAt: timestamp("deleted_at"),
  },
  (t) => ({
    emailIdx: uniqueIndex("employees_email_unique").on(t.email),
    employeeCodeIdx: uniqueIndex("employees_code_unique").on(t.employeeCode),
    nationalIdIdx: uniqueIndex("employees_national_id_unique").on(t.nationalId),
    orgIdx: index("employees_org_idx").on(t.orgId),
    managerIdx: index("employees_manager_idx").on(t.managerId),
    departmentIdx: index("employees_department_idx").on(t.departmentId),
  }),
);

export const holidays = mysqlTable(
  "holidays",
  {
    id: varchar("id", { length: 36 }).primaryKey(),
    orgId: varchar("org_id", { length: 36 })
      .references(() => organizations.id, { onDelete: "cascade" })
      .notNull(),
    name: varchar("name", { length: 255 }).notNull(),
    date: date("date", { mode: "string" }).notNull(),
    isRecurring: boolean("is_recurring").default(false).notNull(),
    createdAt: timestamp("created_at").defaultNow().notNull(),
  },
  (t) => ({
    orgDateIdx: index("holidays_org_date_idx").on(t.orgId, t.date),
  }),
);

export const attendanceRecords = mysqlTable(
  "attendance_records",
  {
    id: varchar("id", { length: 36 }).primaryKey(),
    employeeId: varchar("employee_id", { length: 36 })
      .references(() => employees.id, { onDelete: "cascade" })
      .notNull(),
    date: date("date", { mode: "string" }).notNull(),
    checkInTime: timestamp("check_in_time"),
    checkOutTime: timestamp("check_out_time"),
    checkInLatitude: decimal("check_in_latitude", { precision: 10, scale: 7 }),
    checkInLongitude: decimal("check_in_longitude", { precision: 10, scale: 7 }),
    checkInAccuracyMeters: int("check_in_accuracy_meters"),
    checkOutLatitude: decimal("check_out_latitude", { precision: 10, scale: 7 }),
    checkOutLongitude: decimal("check_out_longitude", { precision: 10, scale: 7 }),
    checkOutAccuracyMeters: int("check_out_accuracy_meters"),
    checkInSelfieUrl: varchar("check_in_selfie_url", { length: 500 }),
    checkOutSelfieUrl: varchar("check_out_selfie_url", { length: 500 }),
    checkInOfficeId: varchar("check_in_office_id", { length: 36 }).references(
      () => offices.id,
    ),
    checkOutOfficeId: varchar("check_out_office_id", { length: 36 }).references(
      () => offices.id,
    ),
    status: mysqlEnum("status", ATTENDANCE_STATUSES).default("ontime").notNull(),
    lateMinutes: int("late_minutes").default(0).notNull(),
    overtimeMinutes: int("overtime_minutes").default(0).notNull(),
    notes: text("notes"),
    editedByUserId: varchar("edited_by_user_id", { length: 36 }),
    editedAt: timestamp("edited_at"),
    version: int("version").default(0).notNull(),
    createdAt: timestamp("created_at").defaultNow().notNull(),
  },
  (t) => ({
    employeeDateIdx: uniqueIndex("attendance_employee_date_unique").on(
      t.employeeId,
      t.date,
    ),
    dateIdx: index("attendance_date_idx").on(t.date),
  }),
);

export const leaveRequests = mysqlTable(
  "leave_requests",
  {
    id: varchar("id", { length: 36 }).primaryKey(),
    employeeId: varchar("employee_id", { length: 36 })
      .references(() => employees.id, { onDelete: "cascade" })
      .notNull(),
    leaveType: mysqlEnum("leave_type", LEAVE_TYPES).notNull(),
    startDate: date("start_date", { mode: "string" }).notNull(),
    endDate: date("end_date", { mode: "string" }).notNull(),
    daysCount: decimal("days_count", { precision: 5, scale: 2 }).notNull(),
    includesWeekend: boolean("includes_weekend").default(false).notNull(),
    reason: text("reason"),
    status: mysqlEnum("status", LEAVE_STATUSES).default("pending").notNull(),
    managerId: varchar("manager_id", { length: 36 }),
    managerDecidedAt: timestamp("manager_decided_at"),
    managerComment: text("manager_comment"),
    hrId: varchar("hr_id", { length: 36 }),
    hrDecidedAt: timestamp("hr_decided_at"),
    hrComment: text("hr_comment"),
    medicalCertificateUrl: varchar("medical_certificate_url", { length: 500 }),
    examProofUrl: varchar("exam_proof_url", { length: 500 }),
    version: int("version").default(0).notNull(),
    createdAt: timestamp("created_at").defaultNow().notNull(),
  },
  (t) => ({
    employeeIdx: index("leave_employee_idx").on(t.employeeId),
    statusIdx: index("leave_status_idx").on(t.status),
    rangeIdx: index("leave_range_idx").on(t.startDate, t.endDate),
  }),
);

export const auditLogs = mysqlTable(
  "audit_logs",
  {
    id: varchar("id", { length: 36 }).primaryKey(),
    userId: varchar("user_id", { length: 36 }),
    action: varchar("action", { length: 64 }).notNull(),
    entityType: varchar("entity_type", { length: 64 }).notNull(),
    entityId: varchar("entity_id", { length: 64 }),
    changesJson: json("changes_json"),
    ipAddress: varchar("ip_address", { length: 64 }),
    userAgent: varchar("user_agent", { length: 500 }),
    createdAt: timestamp("created_at").defaultNow().notNull(),
  },
  (t) => ({
    userIdx: index("audit_user_idx").on(t.userId),
    entityIdx: index("audit_entity_idx").on(t.entityType, t.entityId),
    createdIdx: index("audit_created_idx").on(t.createdAt),
  }),
);

// ---------------------------------------------------------------------------
// Big-build placeholder tables (created empty per PRD §5)
// ---------------------------------------------------------------------------

export const employeeDocuments = mysqlTable("employee_documents", {
  id: varchar("id", { length: 36 }).primaryKey(),
  employeeId: varchar("employee_id", { length: 36 }).notNull(),
  documentType: varchar("document_type", { length: 64 }).notNull(),
  fileUrl: varchar("file_url", { length: 500 }).notNull(),
  expiryDate: date("expiry_date", { mode: "string" }),
  uploadedAt: timestamp("uploaded_at").defaultNow().notNull(),
});

export const notifications = mysqlTable("notifications", {
  id: varchar("id", { length: 36 }).primaryKey(),
  userId: varchar("user_id", { length: 36 }).notNull(),
  type: varchar("type", { length: 64 }).notNull(),
  title: varchar("title", { length: 255 }).notNull(),
  body: text("body"),
  linkUrl: varchar("link_url", { length: 500 }),
  readAt: timestamp("read_at"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

export const emailQueue = mysqlTable("email_queue", {
  id: varchar("id", { length: 36 }).primaryKey(),
  toAddress: varchar("to_address", { length: 255 }).notNull(),
  subject: varchar("subject", { length: 500 }).notNull(),
  bodyHtml: text("body_html").notNull(),
  status: varchar("status", { length: 32 }).default("queued").notNull(),
  attempts: int("attempts").default(0).notNull(),
  lastError: text("last_error"),
  sentAt: timestamp("sent_at"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

// ---------------------------------------------------------------------------
// Relations
// ---------------------------------------------------------------------------

export const employeesRelations = relations(employees, ({ one, many }) => ({
  org: one(organizations, {
    fields: [employees.orgId],
    references: [organizations.id],
  }),
  position: one(positions, {
    fields: [employees.positionId],
    references: [positions.id],
  }),
  department: one(departments, {
    fields: [employees.departmentId],
    references: [departments.id],
  }),
  office: one(offices, {
    fields: [employees.officeId],
    references: [offices.id],
  }),
  manager: one(employees, {
    fields: [employees.managerId],
    references: [employees.id],
    relationName: "manager_reports",
  }),
  reports: many(employees, { relationName: "manager_reports" }),
  credentials: one(userCredentials, {
    fields: [employees.userCredentialId],
    references: [userCredentials.id],
  }),
  attendance: many(attendanceRecords),
  leaveRequests: many(leaveRequests),
}));

export const departmentsRelations = relations(departments, ({ one, many }) => ({
  parent: one(departments, {
    fields: [departments.parentDepartmentId],
    references: [departments.id],
    relationName: "department_tree",
  }),
  children: many(departments, { relationName: "department_tree" }),
  positions: many(positions),
  employees: many(employees),
}));

export const positionsRelations = relations(positions, ({ one, many }) => ({
  department: one(departments, {
    fields: [positions.departmentId],
    references: [departments.id],
  }),
  employees: many(employees),
}));

export const officesRelations = relations(offices, ({ many }) => ({
  employees: many(employees),
}));

export const attendanceRecordsRelations = relations(
  attendanceRecords,
  ({ one }) => ({
    employee: one(employees, {
      fields: [attendanceRecords.employeeId],
      references: [employees.id],
    }),
    checkInOffice: one(offices, {
      fields: [attendanceRecords.checkInOfficeId],
      references: [offices.id],
    }),
  }),
);

export const leaveRequestsRelations = relations(leaveRequests, ({ one }) => ({
  employee: one(employees, {
    fields: [leaveRequests.employeeId],
    references: [employees.id],
  }),
}));

// Type exports
export type Employee = typeof employees.$inferSelect;
export type NewEmployee = typeof employees.$inferInsert;
export type Department = typeof departments.$inferSelect;
export type Position = typeof positions.$inferSelect;
export type Office = typeof offices.$inferSelect;
export type Organization = typeof organizations.$inferSelect;
export type Holiday = typeof holidays.$inferSelect;
export type AttendanceRecord = typeof attendanceRecords.$inferSelect;
export type LeaveRequest = typeof leaveRequests.$inferSelect;
export type AuditLog = typeof auditLogs.$inferSelect;
export type UserCredential = typeof userCredentials.$inferSelect;
