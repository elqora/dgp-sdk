import * as React from "react";
import { ChargeDto, ActionButtonDto, NextActionDto } from "../types/sdk";
import { StatusBadge } from "../common/status-badge";
import { ActionButtonGroup } from "../common/action-button-group";

export interface AdminChargeCardProps {
  charge: ChargeDto;
  onActionClick?: (button: ActionButtonDto, nextAction?: NextActionDto | null) => void;
  className?: string;
}

function formatMoney(money?: { amount: string; currency: string } | null): string {
  if (!money) return "—";
  const num = parseFloat(money.amount);
  if (isNaN(num)) return `${money.amount} ${money.currency}`;
  return new Intl.NumberFormat(undefined, {
    style: "currency",
    currency: money.currency,
    minimumFractionDigits: 2,
  }).format(num);
}

function formatDate(iso?: string | null): string {
  if (!iso) return "—";
  try {
    return new Intl.DateTimeFormat(undefined, {
      dateStyle: "medium",
      timeStyle: "short",
    }).format(new Date(iso));
  } catch {
    return iso;
  }
}

function paymentStatusColor(status: string) {
  switch (status) {
    case "paid": return "text-success bg-success/10 border-success/25";
    case "failed":
    case "canceled": return "text-danger bg-danger/10 border-danger/25";
    case "refunded": return "text-warning bg-warning/10 border-warning/25";
    default: return "text-muted-foreground bg-muted border-border/40";
  }
}

export function AdminChargeCard({
  charge,
  onActionClick,
  className = "",
}: AdminChargeCardProps) {
  const [showPayments, setShowPayments] = React.useState(false);
  const hasPayments = charge.payments && charge.payments.length > 0;

  return (
    <div
      className={`
        relative overflow-hidden rounded-xl border bg-card text-card-foreground text-xs card-glow
        before:absolute before:inset-y-0 before:left-0 before:w-0.5
        ${charge.status === "paid"
          ? "before:bg-success border-success/20"
          : charge.status === "partially_paid"
          ? "before:bg-warning border-warning/20"
          : charge.status === "failed" || charge.status === "canceled"
          ? "before:bg-danger border-danger/20"
          : "before:bg-primary/40 border-border/60"}
        ${className}
      `}
    >
      <div className="px-4 py-3 pl-5 space-y-3">
        {/* Header */}
        <div className="flex items-start justify-between gap-3">
          <div className="space-y-1 min-w-0">
            <div className="flex flex-wrap items-center gap-1.5">
              <span className="font-semibold text-sm tracking-tight text-foreground font-sans">
                {charge.label}
              </span>
              <code className="text-[9px] font-mono px-1.5 py-0.5 rounded bg-muted/80 dark:bg-white/5 text-muted-foreground border border-border/40">
                {charge.key}
              </code>
              {charge.id && (
                <code className="text-[9px] font-mono px-1.5 py-0.5 rounded bg-muted/80 dark:bg-white/5 text-muted-foreground border border-border/40">
                  #{charge.id}
                </code>
              )}
            </div>
            {/* Target context */}
            {charge.target && (
              <p className="text-[10px] font-mono text-muted-foreground">
                Target:{" "}
                <code className="text-foreground/80">{charge.target.type}</code>
                {" · "}
                <code className="text-foreground/80">{charge.target.key ?? charge.target.id}</code>
                {charge.target.parent && (
                  <> → <code className="text-foreground/80">{charge.target.parent.key ?? charge.target.parent.id}</code></>
                )}
              </p>
            )}
          </div>
          <StatusBadge status={charge.status} size="sm" />
        </div>

        {/* Financials grid */}
        <div className="grid grid-cols-3 gap-2 p-2.5 rounded-lg bg-muted/30 dark:bg-white/3 border border-border/30">
          {[
            { label: "Charged", value: formatMoney(charge.amount), highlight: false },
            { label: "Paid", value: formatMoney(charge.paid_amount), highlight: charge.status === "paid" },
            { label: "Balance Due", value: formatMoney(charge.balance_due), highlight: parseFloat(charge.balance_due?.amount ?? "0") > 0 },
          ].map(({ label, value, highlight }) => (
            <div key={label} className="space-y-0.5 text-center">
              <p className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">{label}</p>
              <p className={`text-xs font-bold font-mono tabular-nums ${highlight ? "text-success" : "text-foreground"}`}>
                {value}
              </p>
            </div>
          ))}
        </div>

        {/* Dates row */}
        <div className="flex flex-wrap items-center gap-x-4 gap-y-1 font-mono text-[10px] text-muted-foreground">
          {charge.due_at && (
            <span>Due: <code className="text-foreground/80">{formatDate(charge.due_at)}</code></span>
          )}
          {charge.paid_at && (
            <span>Paid At: <code className="text-success">{formatDate(charge.paid_at)}</code></span>
          )}
        </div>

        {/* Footer bar */}
        <div className="flex items-center justify-between gap-3 pt-2 border-t border-border/30 font-sans">
          {hasPayments && (
            <button
              onClick={() => setShowPayments(!showPayments)}
              className={`
                inline-flex items-center gap-1 text-[10px] font-mono px-2 py-1 rounded-md border transition-all
                ${showPayments
                  ? "bg-primary/10 border-primary/30 text-primary"
                  : "border-border/40 text-muted-foreground hover:border-border hover:text-foreground"}
              `}
            >
              <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
              {charge.payments.length} Payment{charge.payments.length !== 1 ? "s" : ""}
            </button>
          )}
          {!hasPayments && <span />}

          {charge.buttons && charge.buttons.length > 0 && (
            <ActionButtonGroup buttons={charge.buttons} onActionClick={onActionClick} size="xs" />
          )}
        </div>

        {/* Payment records expansion */}
        {showPayments && hasPayments && (
          <div className="animate-slide-down space-y-0.5 rounded-xl overflow-hidden border border-border/40">
            {charge.payments.map((payment, idx) => (
              <div
                key={payment.key || idx}
                className={`flex items-center justify-between px-3 py-2 text-[10px] font-mono ${idx !== 0 ? "border-t border-border/30" : ""}`}
              >
                <div className="flex items-center gap-3 min-w-0">
                  <code className="text-muted-foreground">{payment.key}</code>
                  {payment.method && (
                    <span className="text-[9px] uppercase tracking-widest px-1.5 py-0.5 rounded-full bg-muted border border-border/40 text-muted-foreground">
                      {payment.method}
                    </span>
                  )}
                  {payment.reference && (
                    <code className="text-muted-foreground/60 truncate max-w-[100px]">{payment.reference}</code>
                  )}
                </div>
                <div className="flex items-center gap-2 flex-none">
                  <span className="font-semibold text-foreground">{formatMoney(payment.amount)}</span>
                  <span className={`text-[9px] font-semibold uppercase tracking-widest px-1.5 py-0.5 rounded-full border ${paymentStatusColor(payment.status)}`}>
                    {payment.status}
                  </span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
