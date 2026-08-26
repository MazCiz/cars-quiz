"use client";

import { useEffect, useState } from "react";
import Link from "next/link";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

type AnswerStat = { value: string; label: string; count: number };
type QuestionMetric = {
  key: string;
  text: string;
  avg_time_ms: number | null;
  responses: number;
  answers: AnswerStat[];
  top_answer: AnswerStat | null;
};
type Metrics = { total_attempts: number; questions: QuestionMetric[] };

function seconds(ms: number | null): string {
  if (ms == null) return "—";
  return `${(ms / 1000).toFixed(1)}s`;
}

export default function MetricsPage() {
  const [metrics, setMetrics] = useState<Metrics | null>(null);
  const [error, setError] = useState("");

  useEffect(() => {
    (async () => {
      try {
        const res = await fetch(`${API_URL}/quiz/metrics`, { cache: "no-store" });
        if (!res.ok) throw new Error(`API ${res.status}`);
        setMetrics(await res.json());
      } catch {
        setError(`Couldn't reach the API at ${API_URL}.`);
      }
    })();
  }, []);

  return (
    <main className="min-h-screen bg-neutral-50 dark:bg-neutral-950 p-6">
      <div className="mx-auto max-w-2xl">
        <div className="mb-6 flex items-end justify-between">
          <div>
            <h1 className="text-2xl font-semibold tracking-tight">Quiz metrics</h1>
            <p className="text-sm text-neutral-500">
              {metrics ? `${metrics.total_attempts} completed attempt${metrics.total_attempts === 1 ? "" : "s"}` : "…"}
            </p>
          </div>
          <Link href="/quiz" className="text-sm text-neutral-500 hover:underline">Take the quiz →</Link>
        </div>

        {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}

        {metrics && metrics.total_attempts === 0 && (
          <div className="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-8 text-center text-sm text-neutral-500">
            No attempts yet. <Link href="/quiz" className="text-neutral-900 dark:text-neutral-100 underline">Take the quiz</Link> to generate some data.
          </div>
        )}

        {metrics && metrics.total_attempts > 0 && (
          <div className="flex flex-col gap-3">
            {metrics.questions.map((q) => {
              const max = Math.max(1, ...q.answers.map((a) => a.count));
              return (
                <div
                  key={q.key}
                  className="rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-4"
                >
                  <div className="flex items-baseline justify-between gap-3">
                    <h2 className="text-sm font-medium">{q.text}</h2>
                    <span className="shrink-0 text-xs text-neutral-500 tabular-nums">
                      ~{seconds(q.avg_time_ms)} avg · {q.responses} answered
                    </span>
                  </div>

                  {q.responses === 0 ? (
                    <p className="mt-2 text-xs text-neutral-400">No responses yet.</p>
                  ) : (
                    <ul className="mt-3 flex flex-col gap-1.5">
                      {q.answers.map((a) => {
                        const pct = Math.round((a.count / q.responses) * 100);
                        const isTop = q.top_answer?.value === a.value;
                        return (
                          <li key={a.value} className="text-xs">
                            <div className="flex justify-between mb-0.5">
                              <span className={isTop ? "font-medium" : "text-neutral-600 dark:text-neutral-400"}>
                                {a.label}
                                {isTop && <span className="ml-1.5 text-[10px] uppercase tracking-wide text-neutral-400">most picked</span>}
                              </span>
                              <span className="text-neutral-500 tabular-nums">{a.count} · {pct}%</span>
                            </div>
                            <div className="h-1.5 rounded-full bg-neutral-100 dark:bg-neutral-800 overflow-hidden">
                              <div
                                className={`h-full ${isTop ? "bg-neutral-900 dark:bg-neutral-100" : "bg-neutral-400 dark:bg-neutral-600"}`}
                                style={{ width: `${(a.count / max) * 100}%` }}
                              />
                            </div>
                          </li>
                        );
                      })}
                    </ul>
                  )}
                </div>
              );
            })}
          </div>
        )}

        <SuggestForm />

        <p className="mt-6 text-center text-xs text-neutral-400">
          <Link href="/" className="hover:underline">Home</Link>
        </p>
      </div>
    </main>
  );
}

function SuggestForm() {
  const [text, setText] = useState("");
  const [email, setEmail] = useState("");
  const [status, setStatus] = useState<"idle" | "submitting" | "done" | "error">("idle");
  const [error, setError] = useState("");

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setStatus("submitting");
    setError("");
    try {
      const res = await fetch(`${API_URL}/quiz/suggestions`, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ text, email: email || undefined }),
      });
      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw new Error(data?.errors?.text?.[0] ?? "Please try again.");
      }
      setStatus("done");
      setText("");
      setEmail("");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Something went wrong.");
      setStatus("error");
    }
  }

  return (
    <div className="mt-8 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5">
      <h2 className="text-sm font-medium">Suggest a question</h2>
      <p className="text-xs text-neutral-500 mt-0.5 mb-3">
        Got a question we should ask? We&apos;ll save it for review (it won&apos;t appear in the quiz).
      </p>

      {status === "done" ? (
        <div className="text-sm text-green-700 dark:text-green-400">
          Thanks — your suggestion was saved.{" "}
          <button onClick={() => setStatus("idle")} className="underline">Suggest another</button>
        </div>
      ) : (
        <form onSubmit={submit} className="flex flex-col gap-2">
          <textarea
            required
            minLength={5}
            maxLength={500}
            value={text}
            onChange={(e) => setText(e.target.value)}
            placeholder="e.g. How important is towing capacity to you?"
            rows={3}
            className="w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-transparent px-3 py-2 text-sm focus:outline-none focus:border-neutral-500"
          />
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="Email (optional)"
            className="w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-transparent px-3 py-2 text-sm focus:outline-none focus:border-neutral-500"
          />
          {status === "error" && <p className="text-xs text-red-600 dark:text-red-400">{error}</p>}
          <button
            type="submit"
            disabled={status === "submitting"}
            className="self-start rounded-lg bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 text-sm font-medium px-4 py-2 disabled:opacity-50"
          >
            {status === "submitting" ? "Sending…" : "Submit suggestion"}
          </button>
        </form>
      )}
    </div>
  );
}
