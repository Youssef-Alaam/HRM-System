import { redirect } from "next/navigation";
import { auth } from "@/lib/auth";
import { EmployeeDashboard } from "./_dashboards/employee-dashboard";
import { ManagerDashboard } from "./_dashboards/manager-dashboard";
import { HrDashboard } from "./_dashboards/hr-dashboard";
import { AdminDashboard } from "./_dashboards/admin-dashboard";

export default async function HomePage() {
  const session = await auth();
  if (!session?.user) redirect("/login");

  switch (session.user.role) {
    case "admin":
      return <AdminDashboard userId={session.user.employeeId} />;
    case "hr":
      return <HrDashboard orgId={session.user.orgId} />;
    case "manager":
      return <ManagerDashboard managerId={session.user.employeeId} />;
    default:
      return <EmployeeDashboard employeeId={session.user.employeeId} />;
  }
}
