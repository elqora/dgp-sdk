import * as React from "react";

export interface StatusBadgeProps {
  status: string;
  stage?: string;
  size?: "sm" | "md";
  className?: string;
}

type StatusConfig = {
  /** Tailwind classes using semantic tokens only */
  bg: string;
  text: string;
  border: string;
  dot: string;
  animated?: boolean;
};

function getStatusConfig(status: string): StatusConfig {
  switch (status.toLowerCase()) {
    case "completed":
    case "active":
    case "paid":
    case "ok":
    case "enabled":
      return {
        bg: "bg-success/10",
        text: "text-success",
        border: "border-success/30",
        dot: "bg-success",
      };
    case "processing":
    case "running":
    case "in_progress":
    case "invoiced":
      return {
        bg: "bg-info/10",
        text: "text-info",
        border: "border-info/30",
        dot: "bg-info",
        animated: true,
      };
    case "pending":
    case "draft":
    case "partially_paid":
    case "degraded":
      return {
        bg: "bg-warning/10",
        text: "text-warning",
        border: "border-warning/30",
        dot: "bg-warning",
      };
    case "failed":
    case "canceled":
    case "cancelled":
    case "abandoned":
    case "refunded":
    case "fail":
    case "locked":
    case "disabled":
      return {
        bg: "bg-danger/10",
        text: "text-danger",
        border: "border-danger/30",
        dot: "bg-danger",
      };
    default:
      return {
        bg: "bg-muted",
        text: "text-muted-foreground",
        border: "border-border/40",
        dot: "bg-muted-foreground/50",
      };
  }
}

export function StatusBadge({ status, stage, size = "md", className = "" }: StatusBadgeProps) {
  const cfg = getStatusConfig(status || "");

  const sizeClasses = size === "sm"
    ? "px-1.5 py-0.5 text-[9px] gap-1"
    : "px-2 py-0.5 text-[10px] gap-1.5";

  const dotSize = size === "sm" ? "w-1 h-1" : "w-1.5 h-1.5";

  return (
    <span
      className={`
        inline-flex items-center font-semibold border rounded-full uppercase tracking-widest
        ${sizeClasses} ${cfg.bg} ${cfg.text} ${cfg.border} ${className}
      `}
    >
      <span className={`rounded-full flex-none ${dotSize} ${cfg.dot} ${cfg.animated ? "animate-pulse" : ""}`} />
      <span>{status}</span>
      {stage && (
        <span className="opacity-50 normal-case font-normal ml-0.5">· {stage}</span>
      )}
    </span>
  );
}
