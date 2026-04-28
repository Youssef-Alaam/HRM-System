import { redirect } from "next/navigation";
import { auth } from "@/lib/auth";
import { Sidebar } from "@/components/layout/sidebar";
import { Topbar } from "@/components/layout/topbar";

export default async function AppLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const session = await auth();
  if (!session?.user) redirect("/login");

  return (
    <div className="flex h-screen bg-[var(--background)]">
      <Sidebar
        role={session.user.role}
        firstName={session.user.firstName}
        lastName={session.user.lastName}
      />
      <div className="flex-1 flex flex-col min-w-0">
        <Topbar
          firstName={session.user.firstName}
          lastName={session.user.lastName}
          role={session.user.role}
        />
        <main className="flex-1 overflow-auto">
          <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
            {children}
          </div>
        </main>
      </div>
    </div>
  );
}
