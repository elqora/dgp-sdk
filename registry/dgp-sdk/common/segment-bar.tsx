import * as React from "react";
import { DeliveryProgressDto } from "../types/sdk";
import { ChargeIndicator } from "./charge-indicator";

export type SegmentVariant = "bar" | "stepper" | "milestone" | "compact";

export interface SegmentBarProps {
  progress?: DeliveryProgressDto | null;
  showPublicOnly?: boolean;
  variant?: SegmentVariant;
  collapsibleSegments?: boolean;
  defaultSegmentsExpanded?: boolean;
  onChargeClick?: (charge: any, event: React.MouseEvent) => void;
  className?: string;
}

function ChevronIcon({ open }: { open: boolean }) {
  return (
    <svg
      className={`w-2.5 h-2.5 transition-transform duration-200 ${open ? "rotate-180" : "rotate-0"}`}
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor"
      strokeWidth={2.5}
    >
      <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
    </svg>
  );
}

function CheckIcon() {
  return (
    <svg className="w-2 h-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
    </svg>
  );
}

export function SegmentBar({
  progress,
  showPublicOnly = false,
  variant = "bar",
  collapsibleSegments = true,
  defaultSegmentsExpanded = false,
  onChargeClick,
  className = "",
}: SegmentBarProps) {
  const [segmentsExpanded, setSegmentsExpanded] = React.useState(defaultSegmentsExpanded);

  if (!progress) return null;

  const segments = (progress.segments || []).filter(
    (seg) => !showPublicOnly || seg.is_public
  );

  const overallPercent =
    progress.percent ??
    (progress.current && progress.target
      ? Math.min(100, Math.round((Number(progress.current) / Number(progress.target)) * 100))
      : 0);

  // Only show segment toggle when there ARE segments to collapse
  const hasSegments = segments.length > 0;
  const canCollapse = hasSegments && collapsibleSegments;
  const isSegmentsVisible = hasSegments && (!collapsibleSegments || segmentsExpanded);

  // Semantic progress track color based on completion
  const progressBarClass =
    overallPercent >= 100
      ? "progress-gradient-success"
      : overallPercent > 50
      ? "progress-gradient"
      : overallPercent > 0
      ? "progress-gradient-warning"
      : "bg-muted-foreground/20";

  return (
    <div className={`space-y-2 ${className}`}>
      {/* Parent Progress Header */}
      <div className="space-y-1.5">
        <div className="flex items-center justify-between gap-2">
          <div className="flex items-center gap-2 min-w-0">
            <span className="text-xs font-medium text-foreground/80 truncate">
              {progress.label || "Progress"}
            </span>
            {/* Only render toggle button when there ARE sub-segments */}
            {canCollapse && (
              <button
                type="button"
                onClick={(e) => {
                  e.stopPropagation();
                  setSegmentsExpanded(!segmentsExpanded);
                }}
                className={`
                  inline-flex items-center gap-1 text-[10px] font-mono px-1.5 py-0.5 rounded-full
                  border transition-all duration-150 flex-none
                  ${segmentsExpanded
                    ? "bg-primary/10 border-primary/30 text-primary"
                    : "bg-muted/60 border-border/40 text-muted-foreground hover:text-foreground hover:border-border/80"}
                `}
              >
                {segments.length}
                <ChevronIcon open={segmentsExpanded} />
              </button>
            )}
          </div>
          <span className="text-xs font-semibold font-mono text-foreground tabular-nums flex-none">
            {progress.current !== undefined && progress.target !== undefined
              ? `${progress.current}/${progress.target}${progress.unit ? ` ${progress.unit}` : ""}`
              : `${overallPercent}%`}
          </span>
        </div>

        {/* Parent Track */}
        <div className="relative h-1.5 w-full bg-muted/70 dark:bg-white/5 rounded-full overflow-hidden">
          <div
            className={`absolute inset-y-0 left-0 rounded-full transition-all duration-500 ease-out ${progressBarClass}`}
            style={{ width: `${overallPercent}%` }}
          />
        </div>
      </div>

      {/* Collapsible Segment Breakdown */}
      {isSegmentsVisible && (
        <div className="animate-slide-down">
          {/* COMPACT */}
          {variant === "compact" && (
            <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] font-mono px-2 py-1.5 rounded-lg bg-muted/40 dark:bg-white/3 border border-border/30">
              {segments.map((seg, idx) => {
                const isOk = seg.status === "completed";
                const isRunning = seg.status === "processing";
                return (
                  <span key={seg.key || idx} className="flex items-center gap-1">
                    <span className={`w-1 h-1 rounded-full flex-none ${
                      isOk ? "bg-success" : isRunning ? "bg-info animate-pulse" : "bg-muted-foreground/40"
                    }`} />
                    <span className="text-muted-foreground">{seg.label || seg.key}</span>
                    <span className={`font-semibold ${
                      isOk ? "text-success" : isRunning ? "text-info" : "text-muted-foreground/70"
                    }`}>
                      {seg.status || "queued"}
                    </span>
                  </span>
                );
              })}
            </div>
          )}

          {/* STEPPER */}
          {variant === "stepper" && (
            <div className="flex items-center gap-0 w-full overflow-x-auto py-1">
              {segments.map((seg, idx) => {
                const isCompleted = seg.status === "completed" || (seg.progress?.percent ?? 0) === 100;
                const isProcessing = !isCompleted && (
                  seg.status === "processing" ||
                  ((seg.progress?.percent ?? 0) > 0 && (seg.progress?.percent ?? 0) < 100)
                );

                return (
                  <React.Fragment key={seg.key || idx}>
                    <div
                      className="flex flex-col items-center gap-0.5"
                      title={`${seg.label || seg.key}: ${seg.status || "pending"}`}
                    >
                      <div className={`
                        w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold border-2 transition-all duration-200
                        ${isCompleted
                          ? "bg-success border-success text-success-foreground shadow-[0_0_0_3px_hsl(var(--success)/0.2)]"
                          : isProcessing
                          ? "bg-info/10 border-info text-info shadow-[0_0_0_3px_hsl(var(--info)/0.15)]"
                          : "bg-muted border-border text-muted-foreground"}
                      `}>
                        {isCompleted ? <CheckIcon /> : idx + 1}
                      </div>
                      <span className="text-[9px] text-muted-foreground truncate max-w-[60px] text-center leading-tight">
                        {seg.label || seg.key}
                      </span>
                    </div>
                    {idx < segments.length - 1 && (
                      <div className="flex-1 mx-1 h-0.5 mb-3.5 bg-border/40 min-w-[12px] relative overflow-hidden rounded-full">
                        {isCompleted && <div className="absolute inset-0 progress-gradient-success rounded-full" />}
                        {isProcessing && <div className="absolute inset-0 w-1/2 progress-gradient rounded-full" />}
                      </div>
                    )}
                  </React.Fragment>
                );
              })}
            </div>
          )}

          {/* MILESTONE */}
          {variant === "milestone" && (
            <div className="space-y-0.5 rounded-xl overflow-hidden border border-border/40 bg-muted/20 dark:bg-white/2">
              {segments.map((seg, idx) => {
                const isCompleted = seg.status === "completed";
                const isProcessing = seg.status === "processing";

                return (
                  <div
                    key={seg.key || idx}
                    className={`
                      flex items-center justify-between px-3 py-2 text-xs transition-colors
                      ${idx !== 0 ? "border-t border-border/30" : ""}
                      ${isCompleted ? "bg-success/5" : isProcessing ? "bg-info/5" : "hover:bg-muted/40"}
                    `}
                  >
                    <div className="flex items-center gap-2.5 min-w-0">
                      <div className={`
                        w-4 h-4 rounded-full flex items-center justify-center text-[8px] font-bold flex-none border
                        ${isCompleted
                          ? "bg-success border-success text-success-foreground"
                          : isProcessing
                          ? "bg-info/15 border-info/60 text-info"
                          : "bg-muted border-border text-muted-foreground"}
                      `}>
                        {isCompleted ? <CheckIcon /> : idx + 1}
                      </div>
                      <span className={`font-medium truncate ${
                        isCompleted ? "text-foreground/50 line-through decoration-muted-foreground/40" : "text-foreground"
                      }`}>
                        {seg.label || seg.key}
                      </span>
                    </div>
                    <div className="flex items-center gap-2 flex-none ml-2">
                      {(seg.charge || (seg.charges && seg.charges.length > 0)) && (
                        <ChargeIndicator
                          charge={seg.charge}
                          charges={seg.charges}
                          onChargeClick={onChargeClick}
                          size="xs"
                        />
                      )}
                      <span className={`
                        text-[9px] font-semibold uppercase tracking-widest px-1.5 py-0.5 rounded-full
                        ${isCompleted
                          ? "bg-success/15 text-success"
                          : isProcessing
                          ? "bg-info/15 text-info"
                          : "bg-muted text-muted-foreground"}
                      `}>
                        {seg.status || "queued"}
                      </span>
                    </div>
                  </div>
                );
              })}
            </div>
          )}

          {/* BAR (Segmented Multi-Bar) */}
          {variant === "bar" && (
            <div className="flex gap-1 h-1.5 w-full rounded-full overflow-hidden">
              {segments.map((seg, idx) => {
                const segPercent =
                  seg.progress?.percent ??
                  (seg.status === "completed" ? 100 : seg.status === "processing" ? 55 : 0);

                const barClass =
                  seg.status === "completed"
                    ? "bg-success"
                    : seg.status === "processing"
                    ? "bg-info animate-pulse-glow"
                    : seg.status === "failed"
                    ? "bg-danger"
                    : "bg-muted-foreground/20";

                return (
                  <div
                    key={seg.key || idx}
                    title={`${seg.label || seg.key}: ${seg.status || `${segPercent}%`}`}
                    style={{ width: `${100 / segments.length}%` }}
                    className="h-full rounded-full bg-muted/60 dark:bg-white/5 overflow-hidden relative"
                  >
                    <div
                      className={`absolute inset-y-0 left-0 transition-all duration-500 ${barClass}`}
                      style={{ width: `${segPercent}%` }}
                    />
                  </div>
                );
              })}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
