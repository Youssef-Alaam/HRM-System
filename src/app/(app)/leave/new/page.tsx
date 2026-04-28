import { StubPage } from "@/components/common/stub-page";

export default function NewLeaveRequestPage() {
  return (
    <StubPage
      title="Request leave"
      description="Submit a leave request for manager approval."
      bullets={[
        "Form: type, dates, reason",
        "Live balance display",
        "Day-count auto-calc (skips public holidays per Decision 9)",
        "Weekend warning modal",
      ]}
    />
  );
}
