import { config as loadEnv } from "dotenv";
loadEnv({ path: ".env.local" });
loadEnv({ path: ".env" });
import { randomUUID } from "node:crypto";
import bcrypt from "bcryptjs";
import { db } from "@/db";
import {
  organizations,
  offices,
  departments,
  positions,
  employees,
  holidays,
  userCredentials,
  attendanceRecords,
  leaveRequests,
} from "@/db/schema";

/**
 * Seed script — wipes core tables and recreates a small demo dataset:
 *   - 1 organization (YZH Solutions)
 *   - 1 office (Cairo HQ) with geofence
 *   - 4 departments, 6 positions, 9 holidays
 *   - 8 employees: 1 admin, 1 hr, 2 managers, 4 employees (one per role for test logins)
 *   - ~3 months of attendance for everyone
 *   - A handful of leave requests in various states
 *
 * Usage: pnpm seed
 */

const TODAY = new Date();
TODAY.setHours(0, 0, 0, 0);

function isoDate(d: Date): string {
  return d.toISOString().slice(0, 10);
}

function addDays(d: Date, n: number): Date {
  const x = new Date(d);
  x.setDate(x.getDate() + n);
  return x;
}

async function main() {
  console.log("Seeding YZH-HR demo data...");

  // Wipe (children before parents).
  await db.delete(attendanceRecords);
  await db.delete(leaveRequests);
  await db.delete(employees);
  await db.delete(positions);
  await db.delete(departments);
  await db.delete(holidays);
  await db.delete(offices);
  await db.delete(userCredentials);
  await db.delete(organizations);

  const orgId = randomUUID();
  await db.insert(organizations).values({
    id: orgId,
    name: "YZH Solutions",
    legalName: "YZH Solutions LLC",
    country: "Egypt",
    currency: "EGP",
    timezone: "Africa/Cairo",
  });

  const officeId = randomUUID();
  await db.insert(offices).values({
    id: officeId,
    orgId,
    name: "Cairo HQ",
    address: "Avenue Mall, 5th Settlement",
    city: "Cairo",
    country: "Egypt",
    latitude: "30.0131",
    longitude: "31.4419",
    allowedCheckInRadiusMeters: 150,
  });

  const deptIds = {
    eng: randomUUID(),
    design: randomUUID(),
    ops: randomUUID(),
    hr: randomUUID(),
  };
  await db.insert(departments).values([
    { id: deptIds.eng, orgId, name: "Engineering" },
    { id: deptIds.design, orgId, name: "Design" },
    { id: deptIds.ops, orgId, name: "Operations" },
    { id: deptIds.hr, orgId, name: "People" },
  ]);

  const posIds = {
    cto: randomUUID(),
    seniorEng: randomUUID(),
    midEng: randomUUID(),
    designLead: randomUUID(),
    opsManager: randomUUID(),
    hrManager: randomUUID(),
  };
  await db.insert(positions).values([
    {
      id: posIds.cto,
      orgId,
      title: "CTO",
      departmentId: deptIds.eng,
      level: "Director",
    },
    {
      id: posIds.seniorEng,
      orgId,
      title: "Senior Engineer",
      departmentId: deptIds.eng,
      level: "Senior",
    },
    {
      id: posIds.midEng,
      orgId,
      title: "Engineer",
      departmentId: deptIds.eng,
      level: "Mid",
    },
    {
      id: posIds.designLead,
      orgId,
      title: "Design Lead",
      departmentId: deptIds.design,
      level: "Lead",
    },
    {
      id: posIds.opsManager,
      orgId,
      title: "Operations Manager",
      departmentId: deptIds.ops,
      level: "Manager",
    },
    {
      id: posIds.hrManager,
      orgId,
      title: "HR Manager",
      departmentId: deptIds.hr,
      level: "Manager",
    },
  ]);

  const year = TODAY.getFullYear();
  await db.insert(holidays).values(
    [
      [`Coptic Christmas`, `${year}-01-07`],
      [`Sham El Nessim`, `${year}-04-21`],
      [`Sinai Liberation Day`, `${year}-04-25`],
      [`Labour Day`, `${year}-05-01`],
      [`Eid Al-Fitr (day 1)`, `${year}-04-10`],
      [`Eid Al-Adha (day 1)`, `${year}-06-16`],
      [`Revolution Day`, `${year}-07-23`],
      [`Armed Forces Day`, `${year}-10-06`],
      [`Prophet's Birthday`, `${year}-09-15`],
    ].map(([name, date]) => ({
      id: randomUUID(),
      orgId,
      name,
      date: date,
      isRecurring: true,
    })),
  );

  // Test accounts — password is the same for all to make the demo easy.
  const testPassword = "Password123!";
  const passwordHash = await bcrypt.hash(testPassword, 10);

  const credIds = {
    admin: randomUUID(),
    hr: randomUUID(),
    mgr1: randomUUID(),
    mgr2: randomUUID(),
    emp1: randomUUID(),
    emp2: randomUUID(),
    emp3: randomUUID(),
    emp4: randomUUID(),
  };
  await db.insert(userCredentials).values([
    { id: credIds.admin, email: "admin@yzh.test", passwordHash },
    { id: credIds.hr, email: "hr@yzh.test", passwordHash },
    { id: credIds.mgr1, email: "manager@yzh.test", passwordHash },
    { id: credIds.mgr2, email: "design.lead@yzh.test", passwordHash },
    { id: credIds.emp1, email: "alex@yzh.test", passwordHash },
    { id: credIds.emp2, email: "blake@yzh.test", passwordHash },
    { id: credIds.emp3, email: "casey@yzh.test", passwordHash },
    { id: credIds.emp4, email: "drew@yzh.test", passwordHash },
  ]);

  const empIds = {
    admin: randomUUID(),
    hr: randomUUID(),
    mgr1: randomUUID(),
    mgr2: randomUUID(),
    emp1: randomUUID(),
    emp2: randomUUID(),
    emp3: randomUUID(),
    emp4: randomUUID(),
  };

  await db.insert(employees).values([
    {
      id: empIds.admin,
      orgId,
      userCredentialId: credIds.admin,
      employeeCode: "EMP001",
      email: "admin@yzh.test",
      firstName: "Walid",
      lastName: "Allam",
      positionId: posIds.cto,
      departmentId: deptIds.eng,
      officeId,
      hiringDate: "2022-01-01",
      contractType: "unlimited",
      role: "admin",
      baseSalaryPiasters: 8_000_000,
      managerId: null,
    },
    {
      id: empIds.hr,
      orgId,
      userCredentialId: credIds.hr,
      employeeCode: "EMP002",
      email: "hr@yzh.test",
      firstName: "Jordan",
      lastName: "Mills",
      positionId: posIds.hrManager,
      departmentId: deptIds.hr,
      officeId,
      hiringDate: "2022-03-15",
      contractType: "unlimited",
      role: "hr",
      baseSalaryPiasters: 5_500_000,
      managerId: empIds.admin,
    },
    {
      id: empIds.mgr1,
      orgId,
      userCredentialId: credIds.mgr1,
      employeeCode: "EMP003",
      email: "manager@yzh.test",
      firstName: "Taylor",
      lastName: "Reed",
      positionId: posIds.seniorEng,
      departmentId: deptIds.eng,
      officeId,
      hiringDate: "2022-06-01",
      contractType: "unlimited",
      role: "manager",
      baseSalaryPiasters: 6_500_000,
      managerId: empIds.admin,
    },
    {
      id: empIds.mgr2,
      orgId,
      userCredentialId: credIds.mgr2,
      employeeCode: "EMP004",
      email: "design.lead@yzh.test",
      firstName: "Morgan",
      lastName: "Khan",
      positionId: posIds.designLead,
      departmentId: deptIds.design,
      officeId,
      hiringDate: "2023-01-10",
      contractType: "unlimited",
      role: "manager",
      baseSalaryPiasters: 6_000_000,
      managerId: empIds.admin,
    },
    {
      id: empIds.emp1,
      orgId,
      userCredentialId: credIds.emp1,
      employeeCode: "EMP005",
      email: "alex@yzh.test",
      firstName: "Alex",
      lastName: "Park",
      positionId: posIds.midEng,
      departmentId: deptIds.eng,
      officeId,
      hiringDate: "2023-09-04",
      contractType: "fixed",
      role: "employee",
      baseSalaryPiasters: 3_500_000,
      managerId: empIds.mgr1,
    },
    {
      id: empIds.emp2,
      orgId,
      userCredentialId: credIds.emp2,
      employeeCode: "EMP006",
      email: "blake@yzh.test",
      firstName: "Blake",
      lastName: "Stone",
      positionId: posIds.midEng,
      departmentId: deptIds.eng,
      officeId,
      hiringDate: "2024-02-19",
      contractType: "fixed",
      role: "employee",
      baseSalaryPiasters: 3_200_000,
      managerId: empIds.mgr1,
    },
    {
      id: empIds.emp3,
      orgId,
      userCredentialId: credIds.emp3,
      employeeCode: "EMP007",
      email: "casey@yzh.test",
      firstName: "Casey",
      lastName: "Hill",
      positionId: posIds.midEng,
      departmentId: deptIds.design,
      officeId,
      hiringDate: "2024-08-12",
      contractType: "probation",
      role: "employee",
      baseSalaryPiasters: 2_800_000,
      managerId: empIds.mgr2,
    },
    {
      id: empIds.emp4,
      orgId,
      userCredentialId: credIds.emp4,
      employeeCode: "EMP008",
      email: "drew@yzh.test",
      firstName: "Drew",
      lastName: "Lee",
      positionId: posIds.midEng,
      departmentId: deptIds.ops,
      officeId,
      hiringDate: "2025-01-15",
      contractType: "probation",
      role: "employee",
      baseSalaryPiasters: 2_600_000,
      managerId: empIds.admin,
    },
  ]);

  // ~3 months of attendance for active employees, weekdays only (Sun-Thu).
  const allEmpIds = Object.values(empIds);
  const records: (typeof attendanceRecords.$inferInsert)[] = [];
  for (let dayOffset = -90; dayOffset <= 0; dayOffset++) {
    const date = addDays(TODAY, dayOffset);
    const dow = date.getDay(); // 0=Sun, 6=Sat
    if (dow === 5 || dow === 6) continue; // skip Fri/Sat per default Egyptian workweek
    for (const empId of allEmpIds) {
      // Skip ~5% of days as absent.
      if (Math.random() < 0.05) continue;
      const lateMin = Math.random() < 0.15 ? Math.floor(Math.random() * 30) + 5 : 0;
      const status: "ontime" | "late" =
        lateMin > 0 ? "late" : "ontime";
      const checkIn = new Date(date);
      checkIn.setHours(9, lateMin, 0, 0);
      const checkOut = new Date(date);
      checkOut.setHours(17, Math.floor(Math.random() * 30), 0, 0);
      records.push({
        id: randomUUID(),
        employeeId: empId,
        date: isoDate(date),
        checkInTime: checkIn,
        checkOutTime: dayOffset === 0 && Math.random() < 0.5 ? null : checkOut,
        checkInLatitude: "30.0131",
        checkInLongitude: "31.4419",
        checkInOfficeId: officeId,
        status,
        lateMinutes: lateMin,
      });
    }
  }
  // Insert in chunks to avoid massive single inserts.
  const CHUNK = 500;
  for (let i = 0; i < records.length; i += CHUNK) {
    await db.insert(attendanceRecords).values(records.slice(i, i + CHUNK));
  }

  // A few sample leave requests in different states.
  const futureStart = addDays(TODAY, 14);
  const futureEnd = addDays(TODAY, 16);
  const pastStart = addDays(TODAY, -30);
  const pastEnd = addDays(TODAY, -28);
  await db.insert(leaveRequests).values([
    {
      id: randomUUID(),
      employeeId: empIds.emp1,
      leaveType: "annual",
      startDate: isoDate(futureStart),
      endDate: isoDate(futureEnd),
      daysCount: "3.00",
      status: "pending",
      managerId: empIds.mgr1,
      reason: "Long weekend with family",
    },
    {
      id: randomUUID(),
      employeeId: empIds.emp2,
      leaveType: "sick",
      startDate: isoDate(addDays(TODAY, 2)),
      endDate: isoDate(addDays(TODAY, 3)),
      daysCount: "2.00",
      status: "manager_approved",
      managerId: empIds.mgr1,
      managerDecidedAt: new Date(),
      managerComment: "Get well soon.",
      reason: "Flu",
    },
    {
      id: randomUUID(),
      employeeId: empIds.emp3,
      leaveType: "annual",
      startDate: isoDate(pastStart),
      endDate: isoDate(pastEnd),
      daysCount: "3.00",
      status: "approved",
      managerId: empIds.mgr2,
      managerDecidedAt: addDays(pastStart, -10),
      hrId: empIds.hr,
      hrDecidedAt: addDays(pastStart, -9),
      reason: "Personal",
    },
  ]);

  console.log("\nDone. Test accounts (password: " + testPassword + "):\n");
  console.log("  Admin     admin@yzh.test");
  console.log("  HR        hr@yzh.test");
  console.log("  Manager   manager@yzh.test");
  console.log("  Employee  alex@yzh.test\n");
  process.exit(0);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
