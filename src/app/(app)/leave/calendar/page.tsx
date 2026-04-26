import { StubPage } from "@/components/stub-page";

export default function LeaveCalendarPage() {
  return (
    <StubPage
      title="Leave Calendar"
      description="Approved leaves across the company."
      bullets={[
        "Calendar grid view",
        "Scope: HR sees all, Manager sees team, Employee sees colleagues' day-off only",
        "No reasons shown to peers",
      ]}
    />
  );
}
