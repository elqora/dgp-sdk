import * as React from "react";
import { OrderChargeStateDto, ChargeDto, ActionButtonDto, NextActionDto } from "../types/sdk";
import { ClientChargeCard } from "../client/client-charge-card";
import { AdminChargeCard } from "../admin/admin-charge-card";

export interface OrderChargeStateViewerProps {
  /** The top-level aggregate from PHP OrderChargeState::toArray() */
  chargeState: OrderChargeStateDto;
  /**
   * Optional full Charge DTOs (from Charge::toArray()) to render detailed cards.
   * When provided, these are matched by key to the ChargeStatusView summaries.
   * When omitted, the viewer renders compact summary rows instead.
   */
  charges?: ChargeDto[];
  mode?: "client" | "admin";
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

export function OrderChargeStateViewer({
  chargeState,
  charges,
  mode = "client",
  onActionClick,
  className = "",
}: OrderChargeStateViewerProps) {
  const isAdmin = mode === "admin";
  const totalNum = parseFloat(chargeState.total?.amount ?? "0");
  const paidNum = parseFloat(chargeState.paid?.amount ?? "0");
  const overallPercent = totalNum > 0 ? Math.min(100, Math.round((paidNum / totalNum) * 100)) : 0;

  const progressClass = chargeState.satisfied
    ? "progress-gradient-success"
    : overallPercent > 0
    ? "progress-gradient-warning"
    : "bg-muted-foreground/20";

  return (
    <div className={`space-y-4 ${className}`}>
      {/* Aggregate Summary Card */}
      <div className="relative overflow-hidden rounded-2xl border border-border/60 bg-card card-glow">
        {/* Top accent strip — success when satisfied, warning otherwise */}
        <div className={`absolute inset-x-0 top-0 h-0.5 ${chargeState.satisfied ? "progress-gradient-success" : "progress-gradient-warning"}`} />

        <div className="p-5 space-y-4">
          {/* Header */}
          <div className="flex items-start justify-between gap-3">
            <div className="space-y-0.5">
              {isAdmin && (
                <p className="text-[10px] font-mono font-medium text-muted-foreground uppercase tracking-widest">
                  Order #{chargeState.order_id}
                </p>
              )}
              <h3 className="text-base font-bold tracking-tight text-foreground">
                {isAdmin ? "Charge State" : "Payment Summary"}
              </h3>
            </div>
            <span className={`
              inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-semibold uppercase tracking-widest border
              ${chargeState.satisfied
                ? "bg-success/10 text-success border-success/30"
                : "bg-warning/10 text-warning border-warning/30"}
            `}>
              <span className={`w-1.5 h-1.5 rounded-full ${chargeState.satisfied ? "bg-success" : "bg-warning animate-pulse"}`} />
              {chargeState.satisfied ? "Settled" : "Outstanding"}
            </span>
          </div>

          {/* Totals grid */}
          <div className="grid grid-cols-3 gap-3">
            {[
              { label: "Total Charged", value: formatMoney(chargeState.total) },
              { label: "Total Paid", value: formatMoney(chargeState.paid), success: chargeState.satisfied },
              { label: "Balance Due", value: formatMoney(chargeState.balance_due), warn: !chargeState.satisfied && parseFloat(chargeState.balance_due?.amount ?? "0") > 0 },
            ].map(({ label, value, success, warn }) => (
              <div key={label} className="text-center px-3 py-2.5 rounded-lg bg-muted/30 dark:bg-white/3 border border-border/30 space-y-0.5">
                <p className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">{label}</p>
                <p className={`text-sm font-bold tabular-nums ${success ? "text-success" : warn ? "text-warning" : "text-foreground"}`}>
                  {value}
                </p>
              </div>
            ))}
          </div>

          {/* Overall payment progress bar */}
          <div className="space-y-1.5">
            <div className="flex items-center justify-between text-xs">
              <span className="text-muted-foreground font-medium">Payment Progress</span>
              <span className="font-semibold tabular-nums text-foreground font-mono">
                {overallPercent}%
              </span>
            </div>
            <div className="relative h-2 w-full bg-muted/60 dark:bg-white/5 rounded-full overflow-hidden">
              <div
                className={`absolute inset-y-0 left-0 rounded-full transition-all duration-700 ease-out ${progressClass}`}
                style={{ width: `${overallPercent}%` }}
              />
            </div>
            {/* Per-charge strip indicators */}
            {chargeState.charges.length > 1 && (
              <div className="flex gap-1 pt-0.5">
                {chargeState.charges.map((c) => (
                  <div
                    key={c.key}
                    title={`${c.key}: ${c.status}`}
                    className={`h-0.5 flex-1 rounded-full transition-all duration-300 ${
                      c.satisfied ? "bg-success"
                      : c.status === "partially_paid" ? "bg-warning"
                      : c.status === "failed" || c.status === "canceled" ? "bg-danger"
                      : "bg-muted-foreground/20"
                    }`}
                  />
                ))}
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Individual charge cards */}
      {charges && charges.length > 0 && (
        <div className="space-y-2">
          {isAdmin && (
            <div className="flex items-center justify-between px-1">
              <p className="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
                Charge Records
              </p>
              <span className="text-[10px] font-mono text-muted-foreground">{charges.length} total</span>
            </div>
          )}
          <div className="space-y-2">
            {charges.map((charge, idx) =>
              isAdmin ? (
                <AdminChargeCard
                  key={charge.key || idx}
                  charge={charge}
                  onActionClick={onActionClick}
                />
              ) : (
                <ClientChargeCard
                  key={charge.key || idx}
                  charge={charge}
                  onActionClick={onActionClick}
                />
              )
            )}
          </div>
        </div>
      )}

      {/* Fallback: ChargeStatusView summary rows when no full ChargeDto list provided */}
      {(!charges || charges.length === 0) && chargeState.charges.length > 0 && (
        <div className="rounded-xl border border-border/40 overflow-hidden">
          {chargeState.charges.map((c, idx) => (
            <div
              key={c.key || idx}
              className={`flex items-center justify-between px-4 py-2.5 text-xs ${idx !== 0 ? "border-t border-border/30" : ""} bg-card`}
            >
              <div className="flex items-center gap-2 min-w-0">
                <span className={`w-1.5 h-1.5 rounded-full flex-none ${
                  c.satisfied ? "bg-success"
                  : c.status === "partially_paid" ? "bg-warning animate-pulse"
                  : c.status === "failed" || c.status === "canceled" ? "bg-danger"
                  : "bg-muted-foreground/40"
                }`} />
                <span className="font-medium text-foreground truncate">{c.key}</span>
                {c.target && (
                  <span className="text-[9px] text-muted-foreground font-mono truncate">
                    {c.target.type}:{c.target.key ?? c.target.id}
                  </span>
                )}
              </div>
              <div className="flex items-center gap-3 flex-none font-mono">
                <span className="text-muted-foreground">{formatMoney(c.amount)}</span>
                <span className={`font-semibold ${c.satisfied ? "text-success" : "text-foreground"}`}>
                  {c.satisfied ? "Settled" : formatMoney(c.balance_due) + " due"}
                </span>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
