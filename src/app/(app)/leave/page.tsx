import { StubPage } from "@/components/stub-page";

export default function MyLeavePage() {
  return (
    <StubPage
      title="My Leave"
      description="Your leave history, balances, and requests."
      bullets={[
        "Balance tiles per leave type",
        "List of past + upcoming leave",
        "Quick action: Request leave (next session)",
      ]}
    />
  );
}
