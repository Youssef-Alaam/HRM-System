import { StubPage } from "@/components/stub-page";

export default function LeaveInboxPage() {
  return (
    <StubPage
      title="Leave approvals"
      description="Decide on pending leave requests."
      bullets={[
        "Manager: requests from your direct reports (status = pending)",
        "HR / Admin: requests after manager approval (status = manager_approved)",
        "Approve / reject with comment",
      ]}
    />
  );
}
