import { StubPage } from "@/components/common/stub-page";

export default function AttendancePage() {
  return (
    <StubPage
      title="Attendance"
      description="Past check-ins and history."
      bullets={[
        "List of past check-ins (date, in time, out time, location, status, late minutes)",
        "Filterable by date range",
        "HR/Admin can edit individual records",
      ]}
    />
  );
}
