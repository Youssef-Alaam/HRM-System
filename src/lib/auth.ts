import NextAuth, { type DefaultSession } from "next-auth";
import Credentials from "next-auth/providers/credentials";
import bcrypt from "bcryptjs";
import { eq } from "drizzle-orm";
import { z } from "zod";
import { db } from "@/db";
import { employees, userCredentials } from "@/db/schema";
import type { Role } from "@/db/schema";
import { getEnv } from "@/lib/env";

declare module "next-auth" {
  interface Session {
    user: {
      id: string;
      employeeId: string;
      role: Role;
      orgId: string;
      firstName: string;
      lastName: string;
    } & DefaultSession["user"];
  }

  interface User {
    id?: string;
    employeeId: string;
    role: Role;
    orgId: string;
    firstName: string;
    lastName: string;
  }
}

type SessionTokenExtras = {
  employeeId: string;
  role: Role;
  orgId: string;
  firstName: string;
  lastName: string;
};

const credentialsSchema = z.object({
  email: z.string().email(),
  password: z.string().min(1),
});

const FAILED_LOGIN_LIMIT = 5;
const LOCKOUT_MINUTES = 15;

const env = getEnv();

export const { handlers, auth, signIn, signOut } = NextAuth({
  secret: env.AUTH_SECRET,
  trustHost: true,
  session: {
    strategy: "jwt",
    maxAge: 12 * 60 * 60, // 12-hour absolute lifetime per PRD §6.1
  },
  pages: {
    signIn: "/login",
  },
  providers: [
    Credentials({
      credentials: {
        email: { label: "Email", type: "email" },
        password: { label: "Password", type: "password" },
      },
      async authorize(rawCredentials) {
        const parsed = credentialsSchema.safeParse(rawCredentials);
        if (!parsed.success) return null;

        const { email, password } = parsed.data;
        const normalizedEmail = email.toLowerCase().trim();

        const credentialRow = await db.query.userCredentials.findFirst({
          where: eq(userCredentials.email, normalizedEmail),
        });
        if (!credentialRow) return null;

        if (
          credentialRow.lockedUntil &&
          credentialRow.lockedUntil > new Date()
        ) {
          throw new Error("Account temporarily locked. Try again later.");
        }

        const passwordOk = await bcrypt.compare(
          password,
          credentialRow.passwordHash,
        );

        if (!passwordOk) {
          const nextAttempts = credentialRow.failedLoginAttempts + 1;
          const shouldLock = nextAttempts >= FAILED_LOGIN_LIMIT;
          await db
            .update(userCredentials)
            .set({
              failedLoginAttempts: shouldLock ? 0 : nextAttempts,
              lockedUntil: shouldLock
                ? new Date(Date.now() + LOCKOUT_MINUTES * 60 * 1000)
                : credentialRow.lockedUntil,
            })
            .where(eq(userCredentials.id, credentialRow.id));
          return null;
        }

        const employeeRow = await db.query.employees.findFirst({
          where: eq(employees.userCredentialId, credentialRow.id),
        });
        if (!employeeRow) return null;
        if (employeeRow.employmentStatus !== "active") return null;
        if (employeeRow.deletedAt) return null;

        await db
          .update(userCredentials)
          .set({
            failedLoginAttempts: 0,
            lockedUntil: null,
            lastLoginAt: new Date(),
          })
          .where(eq(userCredentials.id, credentialRow.id));

        return {
          id: credentialRow.id,
          employeeId: employeeRow.id,
          email: employeeRow.email,
          name: `${employeeRow.firstName} ${employeeRow.lastName}`,
          role: employeeRow.role,
          orgId: employeeRow.orgId,
          firstName: employeeRow.firstName,
          lastName: employeeRow.lastName,
        };
      },
    }),
  ],
  callbacks: {
    async jwt({ token, user }) {
      if (user) {
        const t = token as typeof token & SessionTokenExtras;
        t.employeeId = user.employeeId;
        t.role = user.role;
        t.orgId = user.orgId;
        t.firstName = user.firstName;
        t.lastName = user.lastName;
      }
      return token;
    },
    async session({ session, token }) {
      if (token && session.user) {
        const t = token as typeof token & SessionTokenExtras;
        session.user.id = (token.sub as string) ?? "";
        session.user.employeeId = t.employeeId;
        session.user.role = t.role;
        session.user.orgId = t.orgId;
        session.user.firstName = t.firstName;
        session.user.lastName = t.lastName;
      }
      return session;
    },
  },
});
