import * as React from "react";
import { ChargeDto, ActionButtonDto, NextActionDto } from "../types/sdk";
import { StatusBadge } from "../common/status-badge";
import { ActionButtonGroup } from "../common/action-button-group";

export interface ClientChargeCardProps {
  charge: ChargeDto;
  onActionClick?: (button: ActionButtonDto, nextAction?: NextActionDto | null) => void;
  className?: string;
}

/** Format a MoneyDto value as a human-readable currency string */
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

/** Derive a payment fill percentage from a charge */
function chargePercent(charge: ChargeDto): number {
  if (charge.status === "paid") return 100;
  if (!charge.amount?.amount) return 0;
  const total = parseFloat(charge.amount.amount);
  const paid = parseFloat(charge.paid_amount?.amount ?? "0");
  if (total <= 0) return 0;
  return Math.min(100, Math.round((paid / total) * 100));
}

function formatDate(iso?: string | null): string {
  if (!iso) return "—";
  try {
    return new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(new Date(iso));
  } catch {
    return iso;
  }
}

export function ClientChargeCard({
  charge,
  onActionClick,
  className = "",
}: ClientChargeCardProps) {
  const percent = chargePercent(charge);
  const isPaid = charge.status === "paid";
  const isPartial = charge.status === "partially_paid";
  const isPending = charge.status === "pending" || charge.status === "invoiced";
  const isFailed = charge.status === "failed" || charge.status === "canceled" || charge.status === "refunded";

  const progressClass = isPaid
    ? "progress-gradient-success"
    : isPartial
    ? "progress-gradient-warning"
    : isFailed
    ? "bg-danger"
    : "bg-muted-foreground/20";

  return (
    <div
      className={`
        relative overflow-hidden rounded-xl border bg-card text-card-foreground card-glow card-glow-hover
        before:absolute before:inset-y-0 before:left-0 before:w-0.5
        ${isPaid ? "before:bg-success border-success/20" : isPartial ? "before:bg-warning border-warning/20" : isFailed ? "before:bg-danger border-danger/20" : "before:bg-muted-foreground/30 border-border/50"}
        ${className}
      `}
    >
      <div className="px-4 pt-4 pb-3 pl-5 space-y-3">
        {/* Header */}
        <div className="flex items-start justify-between gap-3">
          <div className="space-y-0.5 min-w-0">
            <p className="text-xs font-medium text-muted-foreground truncate">
              {charge.target
                ? `${charge.target.type} · ${charge.target.key ?? charge.target.id}`
                : "Order Charge"}
            </p>
            <h4 className="font-semibold text-sm tracking-tight text-foreground truncate">
              {charge.label}
            </h4>
          </div>
          <div className="flex items-center gap-2 flex-none">
            <StatusBadge status={charge.status} size="sm" />
          </div>
        </div>

        {/* Amount summary */}
        <div className="grid grid-cols-3 gap-2 text-center">
          <div className="space-y-0.5">
            <p className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">Total</p>
            <p className="text-sm font-bold tabular-nums text-foreground">{formatMoney(charge.amount)}</p>
          </div>
          <div className="space-y-0.5">
            <p className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">Paid</p>
            <p className={`text-sm font-bold tabular-nums ${isPaid ? "text-success" : "text-foreground"}`}>
              {formatMoney(charge.paid_amount ?? (isPaid ? charge.amount : null))}
            </p>
          </div>
          <div className="space-y-0.5">
            <p className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">Balance Due</p>
            <p className={`text-sm font-bold tabular-nums ${!isPaid && parseFloat(charge.balance_due?.amount ?? "0") > 0 ? "text-warning" : "text-foreground"}`}>
              {formatMoney(charge.balance_due ?? (isPaid ? { amount: "0.00", currency: charge.amount.currency } : null))}
            </p>
          </div>
        </div>

        {/* Payment progress bar */}
        {!isFailed && (
          <div className="space-y-1">
            <div className="relative h-1.5 w-full bg-muted/60 dark:bg-white/5 rounded-full overflow-hidden">
              <div
                className={`absolute inset-y-0 left-0 rounded-full transition-all duration-700 ease-out ${progressClass}`}
                style={{ width: `${percent}%` }}
              />
            </div>
            <div className="flex justify-between text-[10px] text-muted-foreground font-mono">
              <span>{isPending ? "Awaiting payment" : isPartial ? `${percent}% paid` : "Fully paid"}</span>
              {charge.due_at && !isPaid && (
                <span>Due: {formatDate(charge.due_at)}</span>
              )}
              {charge.paid_at && isPaid && (
                <span className="text-success">Paid {formatDate(charge.paid_at)}</span>
              )}
            </div>
          </div>
        )}

        {/* Action buttons */}
        {charge.buttons && charge.buttons.length > 0 && (
          <div className="flex justify-end pt-1 border-t border-border/30">
            <ActionButtonGroup buttons={charge.buttons} onActionClick={onActionClick} size="xs" />
          </div>
        )}
      </div>
    </div>
  );
}
