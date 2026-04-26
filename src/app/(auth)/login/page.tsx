import Link from "next/link";
import { LoginForm } from "./login-form";

export const metadata = {
  title: "Sign in · YZH-HR",
};

export default function LoginPage({
  searchParams,
}: {
  searchParams: Promise<{ from?: string; error?: string }>;
}) {
  return (
    <div className="min-h-screen flex flex-col bg-gradient-to-br from-[#0f1115] via-[#1a1d23] to-[#0f1115]">
      <header className="px-6 py-5 flex items-center justify-between">
        <Link href="/login" className="flex items-center gap-2">
          <span className="text-[var(--primary)] font-bold tracking-tight text-2xl">
            YZH
          </span>
          <span className="text-white/80 font-medium">HR</span>
        </Link>
        <span className="text-xs text-white/50">Internal · Prototype</span>
      </header>
      <main className="flex-1 flex items-center justify-center px-6 py-10">
        <div className="w-full max-w-md">
          <div className="rounded-xl bg-white shadow-2xl p-8">
            <h1 className="text-2xl font-semibold tracking-tight">
              Welcome back
            </h1>
            <p className="text-sm text-[var(--muted-foreground)] mt-1">
              Sign in to your YZH-HR account.
            </p>
            <div className="mt-6">
              <LoginFormWithParams searchParams={searchParams} />
            </div>
          </div>
          <p className="text-center text-xs text-white/40 mt-6">
            Trouble signing in? Contact HR.
          </p>
        </div>
      </main>
    </div>
  );
}

async function LoginFormWithParams({
  searchParams,
}: {
  searchParams: Promise<{ from?: string; error?: string }>;
}) {
  const params = await searchParams;
  return <LoginForm callbackUrl={params.from ?? "/"} initialError={params.error} />;
}
