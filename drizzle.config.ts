import { config as loadEnv } from "dotenv";
import { defineConfig } from "drizzle-kit";

// Load .env.local first (Next.js convention), fall back to .env.
loadEnv({ path: ".env.local" });
loadEnv({ path: ".env" });

export default defineConfig({
  schema: "./src/db/schema.ts",
  out: "./drizzle/migrations",
  dialect: "mysql",
  dbCredentials: {
    url: process.env.DATABASE_URL!,
  },
  verbose: true,
  strict: true,
});
