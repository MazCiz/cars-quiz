"use client";

import { useState } from "react";
import Link from "next/link";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

export default function SuggestPage() {
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
    <main className="min-h-screen bg-neutral-50 dark:bg-neutral-950 flex items-center justify-center p-6">
      <div className="w-full max-w-md">
        <div className="mb-6 text-center">
          <h1 className="text-2xl font-semibold tracking-tight">Suggest a question</h1>
          <p className="text-sm text-neutral-500">
            Got a question we should ask? We&apos;ll save it for review — it won&apos;t appear in the quiz.
          </p>
        </div>

        <div className="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-sm">
          {status === "done" ? (
            <div className="text-sm text-green-700 dark:text-green-400 text-center py-4">
              Thanks — your suggestion was saved.{" "}
              <button onClick={() => setStatus("idle")} className="underline">Suggest another</button>
            </div>
          ) : (
            <form onSubmit={submit} className="flex flex-col gap-3">
              <label htmlFor="question" className="text-xs font-medium text-neutral-500">Your question</label>
              <textarea
                id="question"
                name="question"
                required
                minLength={5}
                maxLength={500}
                value={text}
                onChange={(e) => setText(e.target.value)}
                placeholder="e.g. How important is towing capacity to you?"
                rows={3}
                className="w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-transparent px-3 py-2 text-sm focus:outline-none focus:border-neutral-500"
              />
              <label htmlFor="email" className="text-xs font-medium text-neutral-500">Email (optional)</label>
              <input
                id="email"
                name="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="you@example.com"
                className="w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-transparent px-3 py-2 text-sm focus:outline-none focus:border-neutral-500"
              />
              {status === "error" && <p className="text-xs text-red-600 dark:text-red-400">{error}</p>}
              <button
                type="submit"
                disabled={status === "submitting"}
                className="mt-1 rounded-lg bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 text-sm font-medium px-4 py-2.5 disabled:opacity-50"
              >
                {status === "submitting" ? "Sending…" : "Submit suggestion"}
              </button>
            </form>
          )}
        </div>

        <p className="mt-4 text-center text-xs text-neutral-400">
          <Link href="/" className="hover:underline">Home</Link>
          {" · "}
          <Link href="/metrics" className="hover:underline">Quiz stats</Link>
        </p>
      </div>
    </main>
  );
}
