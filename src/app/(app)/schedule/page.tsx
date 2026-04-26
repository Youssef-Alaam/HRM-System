import { StubPage } from "@/components/stub-page";

export default function SchedulePage() {
  return (
    <StubPage
      title="My Schedule"
      description="Your shift today and your weekly calendar."
      bullets={[
        "Today's shift: read from your assigned workweek + shift times",
        "Sign in / Sign out: GPS check + selfie capture (next session)",
        "Live hours counter while you're signed in",
        "Weekly calendar of scheduled days",
      ]}
    />
  );
}
