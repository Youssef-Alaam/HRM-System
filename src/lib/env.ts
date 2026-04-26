import { z } from "zod";

const envSchema = z.object({
  DATABASE_URL: z.string().min(1, "DATABASE_URL is required"),
  AUTH_SECRET: z.string().min(16, "AUTH_SECRET must be at least 16 chars"),
  AUTH_URL: z.string().url().optional(),
  NEXT_PUBLIC_APP_NAME: z.string().default("YZH-HR"),
  NEXT_PUBLIC_DEFAULT_TIMEZONE: z.string().default("Africa/Cairo"),
  STORAGE_DRIVER: z.enum(["local", "s3"]).default("local"),
  LOCAL_UPLOAD_DIR: z.string().default("./public/uploads"),
});

export type Env = z.infer<typeof envSchema>;

let cached: Env | null = null;

export function getEnv(): Env {
  if (cached) return cached;
  const parsed = envSchema.safeParse(process.env);
  if (!parsed.success) {
    const issues = parsed.error.issues
      .map((i) => `${i.path.join(".")}: ${i.message}`)
      .join("\n  ");
    throw new Error(`Invalid environment variables:\n  ${issues}`);
  }
  cached = parsed.data;
  return cached;
}
