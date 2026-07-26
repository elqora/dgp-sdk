import * as React from "react";
import { ChargeDto, ChargeStatusViewDto } from "../types/sdk";

export type ChargeAny = ChargeDto | ChargeStatusViewDto;

export interface ChargeIndicatorProps {
  charge?: ChargeAny | null;
  charges?: ChargeAny[] | null;
  onChargeClick?: (charge: ChargeAny, event: React.MouseEvent) => void;
  size?: "xs" | "sm" | "md";
  showDetailsPopover?: boolean;
  children?: React.ReactNode;
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

function getChargeStatusConfig(status: string) {
  switch ((status || "").toLowerCase()) {
    case "paid":
      return {
        bg: "bg-success/15 hover:bg-success/25 text-success border-success/30",
        label: "Paid",
        dot: "bg-success",
      };
    case "partially_paid":
      return {
        bg: "bg-warning/15 hover:bg-warning/25 text-warning border-warning/30",
        label: "Partial",
        dot: "bg-warning animate-pulse",
      };
    case "invoiced":
    case "pending":
      return {
        bg: "bg-info/15 hover:bg-info/25 text-info border-info/30",
        label: "Due",
        dot: "bg-info",
      };
    case "failed":
    case "canceled":
    case "cancelled":
    case "refunded":
      return {
        bg: "bg-danger/15 hover:bg-danger/25 text-danger border-danger/30",
        label: "Unpaid",
        dot: "bg-danger",
      };
    default:
      return {
        bg: "bg-muted hover:bg-muted/80 text-muted-foreground border-border/40",
        label: status || "Charge",
        dot: "bg-muted-foreground/60",
      };
  }
}

export function ChargeIndicator({
  charge,
  charges,
  onChargeClick,
  size = "sm",
  showDetailsPopover = true,
  children,
  className = "",
}: ChargeIndicatorProps) {
  const [isOpen, setIsOpen] = React.useState(false);
  const popoverRef = React.useRef<HTMLDivElement>(null);

  // Normalize charge list
  const chargeList: ChargeAny[] = React.useMemo(() => {
    if (charges && charges.length > 0) return charges;
    if (charge) return [charge];
    return [];
  }, [charge, charges]);

  // Close popover when clicking outside
  React.useEffect(() => {
    if (!isOpen) return;
    function handleClickOutside(event: MouseEvent) {
      if (popoverRef.current && !popoverRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [isOpen]);

  if (chargeList.length === 0) {
    return null;
  }

  const primaryCharge = chargeList[0];
  const cfg = getChargeStatusConfig(primaryCharge.status);

  // Derive display amount or balance due
  const balanceDue = "balance_due" in primaryCharge ? primaryCharge.balance_due : (primaryCharge as ChargeDto).balance_due;
  const displayAmount = balanceDue && parseFloat(balanceDue.amount) > 0
    ? `${formatMoney(balanceDue)} due`
    : formatMoney(primaryCharge.amount);

  const sizeClasses = size === "xs"
    ? "px-1.5 py-0.5 text-[9px] gap-1"
    : size === "sm"
    ? "px-2 py-0.5 text-[10px] gap-1.5"
    : "px-2.5 py-1 text-xs gap-2";

  const handleClick = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (onChargeClick) {
      onChargeClick(primaryCharge, e);
    }
    if (showDetailsPopover) {
      setIsOpen((prev) => !prev);
    }
  };

  return (
    <div className={`relative inline-block ${className}`} ref={popoverRef}>
      {/* Trigger Button or Custom Children */}
      {children ? (
        <div onClick={handleClick} className="cursor-pointer select-none">
          {children}
        </div>
      ) : (
        <button
          type="button"
          onClick={handleClick}
          title={`Charge: ${primaryCharge.key} (${cfg.label}) — Click for details`}
          className={`
            inline-flex items-center font-mono font-medium border rounded-full
            transition-all duration-150 cursor-pointer select-none shadow-2xs
            ${sizeClasses} ${cfg.bg}
          `}
        >
          {/* Card / Finance Icon */}
          <svg className="w-2.5 h-2.5 flex-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
          </svg>
          <span className="font-semibold">{displayAmount}</span>
          {chargeList.length > 1 && (
            <span className="text-[80%] opacity-80 bg-current/10 px-1 rounded-full">
              +{chargeList.length - 1}
            </span>
          )}
        </button>
      )}

      {/* Lightweight Popover Charge Details Summary */}
      {isOpen && showDetailsPopover && (
        <div
          onClick={(e) => e.stopPropagation()}
          className="
            absolute right-0 top-full mt-1.5 z-50 w-64 p-3 rounded-xl border border-border/80
            bg-popover text-popover-foreground shadow-lg font-sans text-xs animate-slide-down
          "
        >
          <div className="flex items-center justify-between border-b border-border/40 pb-2 mb-2">
            <div className="flex items-center gap-1.5 font-mono text-[10px] text-muted-foreground">
              <span className={`w-1.5 h-1.5 rounded-full ${cfg.dot}`} />
              <span className="font-bold uppercase tracking-wider text-foreground">
                {"label" in primaryCharge ? (primaryCharge as ChargeDto).label || primaryCharge.key : primaryCharge.key}
              </span>
            </div>
            <button
              onClick={() => setIsOpen(false)}
              className="text-muted-foreground hover:text-foreground text-xs p-0.5 leading-none"
            >
              ✕
            </button>
          </div>

          <div className="space-y-2">
            <div className="grid grid-cols-2 gap-2 p-2 rounded-lg bg-muted/40 font-mono text-[11px]">
              <div>
                <p className="text-[9px] uppercase tracking-wider text-muted-foreground">Amount</p>
                <p className="font-bold text-foreground">{formatMoney(primaryCharge.amount)}</p>
              </div>
              <div>
                <p className="text-[9px] uppercase tracking-wider text-muted-foreground">Balance Due</p>
                <p className={`font-bold ${balanceDue && parseFloat(balanceDue.amount) > 0 ? "text-warning" : "text-success"}`}>
                  {balanceDue ? formatMoney(balanceDue) : "$0.00"}
                </p>
              </div>
            </div>

            {primaryCharge.target && (
              <div className="text-[10px] font-mono text-muted-foreground">
                Pinned to: <code className="text-foreground">{primaryCharge.target.type}:{primaryCharge.target.key ?? primaryCharge.target.id}</code>
              </div>
            )}

            {"due_at" in primaryCharge && (primaryCharge as ChargeDto).due_at && (
              <div className="text-[10px] font-mono text-muted-foreground">
                Due Date: <code className="text-foreground">{(primaryCharge as ChargeDto).due_at}</code>
              </div>
            )}

            {"paid_at" in primaryCharge && primaryCharge.paid_at && (
              <div className="text-[10px] font-mono text-success">
                Paid At: <code>{primaryCharge.paid_at}</code>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
