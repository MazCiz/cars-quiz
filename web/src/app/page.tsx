// Scaffold landing page: calls the Laravel API's /ping (server-side) to show the
// whole stack — Next.js → Laravel → Postgres — is wired together. Replace with
// the real Cars Quiz UI.

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

type Ping = { status: string; service: string; database: string; timestamp: string };

async function getApiStatus(): Promise<Ping | null> {
  try {
    const res = await fetch(`${API_URL}/ping`, { cache: "no-store" });
    if (!res.ok) return null;
    return (await res.json()) as Ping;
  } catch {
    return null;
  }
}

export default async function Home() {
  const ping = await getApiStatus();
  const apiUp = ping?.status === "ok";
  const dbUp = ping?.database === "ok";

  const rows: { label: string; ok: boolean; note: string }[] = [
    { label: "Next.js frontend", ok: true, note: "this page" },
    { label: "Laravel REST API", ok: apiUp, note: apiUp ? ping!.service : "not reachable at " + API_URL },
    { label: "PostgreSQL", ok: dbUp, note: dbUp ? "connected" : "unreachable" },
  ];

  return (
    <main className="min-h-screen flex items-center justify-center bg-neutral-50 dark:bg-neutral-950 p-6">
      <div className="w-full max-w-md rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-8 shadow-sm">
        <h1 className="text-2xl font-semibold tracking-tight">Cars Quiz</h1>
        <p className="mt-1 text-sm text-neutral-500">Next.js · Laravel REST API · PostgreSQL</p>

        <ul className="mt-6 flex flex-col gap-2">
          {rows.map((r) => (
            <li
              key={r.label}
              className="flex items-center justify-between rounded-lg border border-neutral-200 dark:border-neutral-800 px-4 py-3"
            >
              <div>
                <div className="text-sm font-medium">{r.label}</div>
                <div className="text-xs text-neutral-500">{r.note}</div>
              </div>
              <span
                className={`text-xs font-semibold px-2 py-1 rounded-full ${
                  r.ok
                    ? "bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400"
                    : "bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400"
                }`}
              >
                {r.ok ? "connected" : "down"}
              </span>
            </li>
          ))}
        </ul>

        <p className="mt-6 text-xs text-neutral-400">
          {apiUp
            ? "All set — start building. Edit src/app/page.tsx (web) and routes/api.php (api)."
            : "Start the API: in ../api run `php artisan serve`, and `docker compose up -d` for Postgres."}
        </p>
      </div>
    </main>
  );
}
