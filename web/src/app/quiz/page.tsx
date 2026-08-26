"use client";

import { useEffect, useRef, useState } from "react";
import Link from "next/link";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

type Option = { value: string; label: string };
type Question = { key: string; text: string; options: Option[] };
type Answer = { value: string; timeMs: number };
type Phase = "loading" | "error" | "quiz" | "submitting" | "result";

export default function QuizPage() {
  const [phase, setPhase] = useState<Phase>("loading");
  const [questions, setQuestions] = useState<Question[]>([]);
  const [index, setIndex] = useState(0);
  const [answers, setAnswers] = useState<Record<string, Answer>>({});
  const [result, setResult] = useState<{ text: string; source: string; totalMs: number } | null>(null);
  const [error, setError] = useState<string>("");

  // High-resolution timestamp (ms) of when the current question was shown, so we
  // can record how long the user spent answering it.
  const questionStart = useRef<number>(0);

  useEffect(() => {
    (async () => {
      try {
        const res = await fetch(`${API_URL}/quiz/questions`, { cache: "no-store" });
        if (!res.ok) throw new Error(`API ${res.status}`);
        const data = await res.json();
        setQuestions(data.questions ?? []);
        setPhase("quiz");
      } catch {
        setError(`Couldn't reach the API at ${API_URL}. Is the Laravel server running?`);
        setPhase("error");
      }
    })();
  }, []);

  // Reset the per-question timer whenever a new question is shown (including on
  // Back navigation — the recorded time is the most recent visit).
  useEffect(() => {
    if (phase === "quiz") questionStart.current = performance.now();
  }, [index, phase]);

  async function choose(value: string) {
    const elapsed = Math.max(0, Math.round(performance.now() - questionStart.current));
    const q = questions[index];
    const next = { ...answers, [q.key]: { value, timeMs: elapsed } };
    setAnswers(next);

    if (index < questions.length - 1) {
      setIndex(index + 1);
      return;
    }

    // Last question answered — submit the completed attempt with per-answer times.
    setPhase("submitting");
    const payload = {
      answers: questions.map((qq) => ({
        key: qq.key,
        value: next[qq.key].value,
        time_spent_ms: next[qq.key].timeMs,
      })),
    };
    try {
      const res = await fetch(`${API_URL}/quiz/submit`, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify(payload),
      });
      if (!res.ok) throw new Error(`API ${res.status}`);
      const data = await res.json();
      setResult({ text: data.recommendation, source: data.source, totalMs: data.total_time_ms ?? 0 });
      setPhase("result");
    } catch {
      setError("Something went wrong generating your recommendation. Please try again.");
      setPhase("error");
    }
  }

  function restart() {
    setAnswers({});
    setIndex(0);
    setResult(null);
    setPhase("quiz");
  }

  return (
    <main className="min-h-screen bg-neutral-50 dark:bg-neutral-950 flex items-center justify-center p-6">
      <div className="w-full max-w-xl">
        <div className="mb-6 text-center">
          <h1 className="text-2xl font-semibold tracking-tight">Cars Quiz</h1>
          <p className="text-sm text-neutral-500">Answer 10 quick questions to find the car that suits you.</p>
        </div>

        <div className="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-sm">
          {phase === "loading" && <p className="text-sm text-neutral-500 py-10 text-center">Loading questions…</p>}

          {phase === "error" && (
            <div className="py-8 text-center">
              <p className="text-sm text-red-600 dark:text-red-400">{error}</p>
              <Link href="/" className="mt-4 inline-block text-sm text-neutral-500 hover:underline">
                ← Back home
              </Link>
            </div>
          )}

          {phase === "quiz" && questions.length > 0 && (
            <div>
              {/* Progress */}
              <div className="mb-5">
                <div className="flex justify-between text-xs text-neutral-500 mb-1">
                  <span>Question {index + 1} of {questions.length}</span>
                  <span>{Math.round((index / questions.length) * 100)}%</span>
                </div>
                <div className="h-1.5 rounded-full bg-neutral-200 dark:bg-neutral-800 overflow-hidden">
                  <div
                    className="h-full bg-neutral-900 dark:bg-neutral-100 transition-all"
                    style={{ width: `${(index / questions.length) * 100}%` }}
                  />
                </div>
              </div>

              <h2 className="text-lg font-medium mb-4">{questions[index].text}</h2>

              <div className="flex flex-col gap-2">
                {questions[index].options.map((opt) => {
                  const selected = answers[questions[index].key]?.value === opt.value;
                  return (
                    <button
                      key={opt.value}
                      onClick={() => choose(opt.value)}
                      className={`text-left rounded-lg border px-4 py-3 text-sm transition-colors ${
                        selected
                          ? "border-neutral-900 dark:border-neutral-100 bg-neutral-100 dark:bg-neutral-800"
                          : "border-neutral-200 dark:border-neutral-800 hover:border-neutral-400 dark:hover:border-neutral-600"
                      }`}
                    >
                      {opt.label}
                    </button>
                  );
                })}
              </div>

              {index > 0 && (
                <button
                  onClick={() => setIndex(index - 1)}
                  className="mt-4 text-xs text-neutral-500 hover:underline"
                >
                  ← Back
                </button>
              )}
            </div>
          )}

          {phase === "submitting" && (
            <div className="py-12 text-center">
              <div className="mx-auto mb-4 h-8 w-8 animate-spin rounded-full border-2 border-neutral-300 border-t-neutral-900 dark:border-neutral-700 dark:border-t-neutral-100" />
              <p className="text-sm text-neutral-500">Finding the car that suits you…</p>
            </div>
          )}

          {phase === "result" && result && (
            <div>
              <div className="text-xs uppercase tracking-wide text-neutral-500 mb-2">Your recommendation</div>
              <p className="text-[15px] leading-relaxed whitespace-pre-line">{result.text}</p>
              <div className="mt-5 flex items-center justify-between">
                <span className="text-xs text-neutral-400">
                  {result.source === "ai" ? "Generated by Claude" : "Rule-based suggestion"}
                  {" · "}completed in {(result.totalMs / 1000).toFixed(1)}s
                </span>
                <button
                  onClick={restart}
                  className="text-sm font-medium rounded-lg bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 px-4 py-2"
                >
                  Retake quiz
                </button>
              </div>
            </div>
          )}
        </div>

        <p className="mt-4 text-center text-xs text-neutral-400">
          <Link href="/" className="hover:underline">Home</Link>
          {" · "}
          <Link href="/metrics" className="hover:underline">Stats & suggest a question</Link>
        </p>
      </div>
    </main>
  );
}
