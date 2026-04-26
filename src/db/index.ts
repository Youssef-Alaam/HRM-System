import { drizzle } from "drizzle-orm/mysql2";
import mysql from "mysql2/promise";
import * as schema from "./schema";
import { getEnv } from "@/lib/env";

declare global {
  var __yzhDbPool: mysql.Pool | undefined;
}

function getPool(): mysql.Pool {
  if (!global.__yzhDbPool) {
    const env = getEnv();
    global.__yzhDbPool = mysql.createPool({
      uri: env.DATABASE_URL,
      connectionLimit: 10,
      waitForConnections: true,
    });
  }
  return global.__yzhDbPool;
}

export const db = drizzle(getPool(), { schema, mode: "default" });
export { schema };
