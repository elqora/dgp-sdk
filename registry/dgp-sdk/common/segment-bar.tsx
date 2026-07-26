import * as React from "react";
import { DeliveryProgressDto, ActionButtonDto, NextActionDto } from "../types/sdk";
import { ChargeIndicator } from "./charge-indicator";
import { ActionButtonGroup } from "./action-button-group";

export type SegmentVariant = "bar" | "stepper" | "milestone" | "compact";

export interface SegmentBarProps {
  progress?: DeliveryProgressDto | null;
  showPublicOnly?: boolean;
  variant?: SegmentVariant;
  collapsibleSegments?: boolean;
  defaultSegmentsExpanded?: boolean;
  onChargeClick?: (charge: any, event: React.MouseEvent) => void;
  onActionClick?: (button: ActionButtonDto, nextAction?: NextActionDto | null) => void;
  className?: string;
}

function ChevronIcon({ open }: { open: boolean }) {
  return (
    <svg
      className={`w-3 h-3 transition-transform duration-200 ${open ? "rotate-180" : "rotate-0"}`}
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
    <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
    </svg>
  );
}

/** Format status text naturally (e.g., 'completed' -> 'Completed') */
function formatStatus(status?: string | null): string {
  if (!status) return "Queued";
  return status
    .split("_")
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join(" ");
}

/** Rich mini progress bar for an individual segment */
function SegmentRowProgressBar({
  progress,
  status,
}: {
  progress?: Omit<DeliveryProgressDto, "segments"> | null;
  status?: string | null;
}) {
  const percent =
    progress?.percent ??
    (status === "completed" ? 100 : status === "processing" ? 50 : status === "failed" ? 100 : 0);

  const barGradient =
    status === "completed"
      ? "progress-gradient-success"
      : status === "processing"
      ? "progress-gradient animate-pulse-glow"
      : status === "failed"
      ? "bg-gradient-to-r from-red-600 to-rose-500"
      : "bg-muted-foreground/30";

  const labelText =
    progress?.current !== undefined && progress?.target !== undefined
      ? `${progress.current}/${progress.target}${progress.unit ? ` ${progress.unit}` : ""}`
      : `${percent}%`;

  return (
    <div className="space-y-1 w-full pt-1">
      <div className="flex items-center justify-between text-[11px] text-muted-foreground">
        <span>Segment Progress</span>
        <span className="font-semibold text-foreground">{labelText}</span>
      </div>
      <div className="relative h-2.5 w-full bg-slate-200 dark:bg-slate-800/90 rounded-full overflow-hidden border border-border/30">
        <div
          className={`absolute inset-y-0 left-0 rounded-full transition-all duration-500 ease-out ${barGradient}`}
          style={{ width: `${percent}%` }}
        />
      </div>
    </div>
  );
}

export function SegmentBar({
  progress,
  showPublicOnly = false,
  variant = "bar",
  collapsibleSegments = true,
  defaultSegmentsExpanded = false,
  onChargeClick,
  onActionClick,
  className = "",
}: SegmentBarProps) {
  const [segmentsExpanded, setSegmentsExpanded] = React.useState(defaultSegmentsExpanded);
  const [activeStepIdx, setActiveStepIdx] = React.useState<number | null>(null);

  if (!progress) return null;

  const segments = (progress.segments || []).filter(
    (seg) => !showPublicOnly || seg.is_public
  );

  const overallPercent =
    progress.percent ??
    (progress.current && progress.target
      ? Math.min(100, Math.round((Number(progress.current) / Number(progress.target)) * 100))
      : 0);

  const hasSegments = segments.length > 0;
  const canCollapse = hasSegments && collapsibleSegments;
  const isSegmentsVisible = hasSegments && (!collapsibleSegments || segmentsExpanded);

  const parentBarGradient =
    overallPercent >= 100
      ? "progress-gradient-success"
      : overallPercent > 50
      ? "progress-gradient"
      : overallPercent > 0
      ? "progress-gradient-warning"
      : "bg-muted-foreground/30";

  return (
    <div className={`space-y-3 ${className}`}>
      {/* Parent Progress Header */}
      <div className="space-y-2">
        <div className="flex items-center justify-between gap-2">
          <div className="flex items-center gap-2 min-w-0">
            <span className="text-xs font-bold text-foreground">
              {progress.label || "Progress"}
            </span>
            {canCollapse && (
              <button
                type="button"
                onClick={(e) => {
                  e.stopPropagation();
                  setSegmentsExpanded(!segmentsExpanded);
                }}
                className={`
                  inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded-full
                  border transition-all duration-150 flex-none cursor-pointer
                  ${segmentsExpanded
                    ? "bg-primary/15 border-primary/40 text-primary font-bold shadow-xs"
                    : "bg-muted/80 border-border/60 text-muted-foreground hover:text-foreground hover:border-border"}
                `}
              >
                <span>{segments.length} {segments.length === 1 ? "segment" : "segments"}</span>
                <ChevronIcon open={segmentsExpanded} />
              </button>
            )}
          </div>
          <span className="text-xs font-bold text-foreground flex-none">
            {progress.current !== undefined && progress.target !== undefined
              ? `${progress.current}/${progress.target}${progress.unit ? ` ${progress.unit}` : ""}`
              : `${overallPercent}%`}
          </span>
        </div>

        {/* Main Progress Bar Track */}
        {hasSegments && variant === "bar" ? (
          <div className="flex gap-1.5 h-3 w-full rounded-full overflow-hidden bg-slate-200 dark:bg-slate-800/90 p-0.5 border border-border/50 shadow-inner">
            {segments.map((seg, idx) => {
              const segPercent =
                seg.progress?.percent ??
                (seg.status === "completed" ? 100 : seg.status === "processing" ? 55 : 0);

              const barGradient =
                seg.status === "completed"
                  ? "progress-gradient-success"
                  : seg.status === "processing"
                  ? "progress-gradient animate-pulse-glow"
                  : seg.status === "failed"
                  ? "bg-gradient-to-r from-red-600 to-rose-500"
                  : "bg-muted-foreground/30";

              return (
                <div
                  key={seg.key || idx}
                  title={`${seg.label || seg.key}: ${formatStatus(seg.status)} (${segPercent}%)`}
                  style={{ width: `${100 / segments.length}%` }}
                  className="h-full rounded-full bg-slate-300 dark:bg-slate-700/50 overflow-hidden relative"
                >
                  <div
                    className={`absolute inset-y-0 left-0 rounded-full transition-all duration-500 ${barGradient}`}
                    style={{ width: `${segPercent}%` }}
                  />
                </div>
              );
            })}
          </div>
        ) : (
          <div className="relative h-3 w-full bg-slate-200 dark:bg-slate-800/90 rounded-full overflow-hidden border border-border/50 shadow-inner">
            <div
              className={`absolute inset-y-0 left-0 rounded-full transition-all duration-500 ease-out ${parentBarGradient}`}
              style={{ width: `${overallPercent}%` }}
            />
          </div>
        )}
      </div>

      {/* Collapsible Segment Breakdown Container */}
      {isSegmentsVisible && (
        <div className="animate-slide-down pt-1">
          {/* BAR VARIANT */}
          {variant === "bar" && (
            <div className="space-y-2 rounded-xl border border-border/60 bg-muted/30 dark:bg-black/20 p-2.5">
              {segments.map((seg, idx) => {
                const isCompleted = seg.status === "completed" || (seg.progress?.percent ?? 0) === 100;
                const isProcessing = seg.status === "processing" || ((seg.progress?.percent ?? 0) > 0 && (seg.progress?.percent ?? 0) < 100);
                const isFailed = seg.status === "failed";

                return (
                  <div
                    key={seg.key || idx}
                    className={`
                      p-3 rounded-xl border transition-all duration-200 space-y-2.5
                      ${isCompleted
                        ? "bg-success/5 border-success/25 hover:border-success/40"
                        : isProcessing
                        ? "bg-info/5 border-info/30 hover:border-info/50"
                        : isFailed
                        ? "bg-danger/5 border-danger/30 hover:border-danger/50"
                        : "bg-card border-border/60 hover:border-border"}
                    `}
                  >
                    {/* Top Row: Icon + Title + Key | Charges + Status + Buttons */}
                    <div className="flex flex-wrap items-center justify-between gap-2">
                      <div className="flex items-center gap-2.5 min-w-0">
                        <div className={`
                          w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold flex-none border shadow-xs
                          ${isCompleted
                            ? "bg-success border-success text-white"
                            : isProcessing
                            ? "bg-info/20 border-info text-info"
                            : isFailed
                            ? "bg-danger/20 border-danger text-danger"
                            : "bg-muted border-border text-muted-foreground"}
                        `}>
                          {isCompleted ? <CheckIcon /> : idx + 1}
                        </div>
                        <div className="flex flex-wrap items-center gap-1.5 min-w-0">
                          <span className="text-xs font-bold text-foreground truncate">
                            {seg.label || seg.key}
                          </span>
                          {seg.key && seg.label && (
                            <span className="text-[10px] text-muted-foreground/80 bg-muted/80 px-1.5 py-0.5 rounded border border-border/40">
                              {seg.key}
                            </span>
                          )}
                        </div>
                      </div>

                      <div className="flex flex-wrap items-center gap-2 flex-none">
                        {(seg.charge || (seg.charges && seg.charges.length > 0)) && (
                          <ChargeIndicator
                            charge={seg.charge}
                            charges={seg.charges}
                            onChargeClick={onChargeClick}
                            size="xs"
                          />
                        )}

                        <span className={`
                          text-[10px] font-semibold px-2 py-0.5 rounded-full border
                          ${isCompleted
                            ? "bg-success/15 border-success/30 text-success"
                            : isProcessing
                            ? "bg-info/15 border-info/30 text-info"
                            : isFailed
                            ? "bg-danger/15 border-danger/30 text-danger"
                            : "bg-muted/80 border-border/50 text-muted-foreground"}
                        `}>
                          {formatStatus(seg.status)}
                        </span>

                        {seg.buttons && seg.buttons.length > 0 && (
                          <div className="flex-none">
                            <ActionButtonGroup
                              buttons={seg.buttons}
                              onActionClick={onActionClick}
                              size="xs"
                            />
                          </div>
                        )}
                      </div>
                    </div>

                    <SegmentRowProgressBar progress={seg.progress} status={seg.status} />
                  </div>
                );
              })}
            </div>
          )}

          {/* COMPACT VARIANT */}
          {variant === "compact" && (
            <div className="space-y-2 rounded-xl bg-muted/30 dark:bg-black/20 border border-border/60 p-2.5 text-xs">
              {segments.map((seg, idx) => {
                const isOk = seg.status === "completed";
                const isRunning = seg.status === "processing";

                return (
                  <div
                    key={seg.key || idx}
                    className="p-2.5 rounded-xl bg-card border border-border/40 hover:border-border space-y-2 transition-colors"
                  >
                    <div className="flex flex-wrap items-center justify-between gap-2">
                      <div className="flex items-center gap-1.5 min-w-0">
                        <span className={`w-2.5 h-2.5 rounded-full flex-none ${
                          isOk ? "bg-success" : isRunning ? "bg-info animate-pulse" : "bg-muted-foreground/40"
                        }`} />
                        <span className="font-bold text-foreground truncate">{seg.label || seg.key}</span>
                        <span className={`font-semibold text-[11px] ${
                          isOk ? "text-success" : isRunning ? "text-info" : "text-muted-foreground"
                        }`}>
                          ({formatStatus(seg.status)})
                        </span>
                      </div>

                      <div className="flex items-center gap-1.5 flex-none">
                        {(seg.charge || (seg.charges && seg.charges.length > 0)) && (
                          <ChargeIndicator
                            charge={seg.charge}
                            charges={seg.charges}
                            onChargeClick={onChargeClick}
                            size="xs"
                          />
                        )}
                        {seg.buttons && seg.buttons.length > 0 && (
                          <ActionButtonGroup
                            buttons={seg.buttons}
                            onActionClick={onActionClick}
                            size="xs"
                          />
                        )}
                      </div>
                    </div>

                    <SegmentRowProgressBar progress={seg.progress} status={seg.status} />
                  </div>
                );
              })}
            </div>
          )}

          {/* STEPPER VARIANT */}
          {variant === "stepper" && (
            <div className="space-y-3.5 rounded-xl border border-border/60 bg-muted/30 dark:bg-black/20 p-3.5">
              {/* Stepper Node Track */}
              <div className="flex items-center gap-0 w-full overflow-x-auto py-1">
                {segments.map((seg, idx) => {
                  const isCompleted = seg.status === "completed" || (seg.progress?.percent ?? 0) === 100;
                  const isProcessing = !isCompleted && (
                    seg.status === "processing" ||
                    ((seg.progress?.percent ?? 0) > 0 && (seg.progress?.percent ?? 0) < 100)
                  );
                  const isSelected = activeStepIdx === idx;

                  return (
                    <React.Fragment key={seg.key || idx}>
                      <button
                        type="button"
                        onClick={() => setActiveStepIdx(isSelected ? null : idx)}
                        className="flex flex-col items-center gap-1 group focus:outline-none flex-none cursor-pointer"
                        title={`Click to focus ${seg.label || seg.key}`}
                      >
                        <div className={`
                          w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-all duration-200
                          ${isCompleted
                            ? "bg-success border-success text-white shadow-xs"
                            : isProcessing
                            ? "bg-info/15 border-info text-info shadow-xs"
                            : "bg-muted border-border text-muted-foreground group-hover:border-foreground/40"}
                          ${isSelected ? "ring-2 ring-primary ring-offset-2" : ""}
                        `}>
                          {isCompleted ? <CheckIcon /> : idx + 1}
                        </div>
                        <span className={`text-[11px] truncate max-w-[80px] text-center leading-tight transition-colors ${
                          isSelected ? "font-bold text-primary" : "text-muted-foreground group-hover:text-foreground"
                        }`}>
                          {seg.label || seg.key}
                        </span>
                      </button>

                      {idx < segments.length - 1 && (
                        <div className="flex-1 mx-2 h-1 mb-4 bg-slate-200 dark:bg-slate-800 min-w-[20px] relative overflow-hidden rounded-full border border-border/30">
                          {isCompleted && <div className="absolute inset-0 progress-gradient-success rounded-full" />}
                          {isProcessing && <div className="absolute inset-0 w-1/2 progress-gradient rounded-full" />}
                        </div>
                      )}
                    </React.Fragment>
                  );
                })}
              </div>

              {/* Stepper Details Cards */}
              <div className="space-y-2 pt-1 border-t border-border/40">
                {segments.map((seg, idx) => {
                  if (activeStepIdx !== null && activeStepIdx !== idx) return null;
                  const isCompleted = seg.status === "completed";
                  const isProcessing = seg.status === "processing";

                  return (
                    <div
                      key={seg.key || idx}
                      className="p-3 rounded-xl bg-card border border-border/50 space-y-2.5"
                    >
                      <div className="flex flex-wrap items-center justify-between gap-2">
                        <div className="flex items-center gap-2 min-w-0">
                          <span className="text-xs font-bold text-foreground truncate">{seg.label || seg.key}</span>
                          <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-full border ${
                            isCompleted
                              ? "bg-success/15 border-success/30 text-success"
                              : isProcessing
                              ? "bg-info/15 border-info/30 text-info"
                              : "bg-muted border-border/50 text-muted-foreground"
                          }`}>
                            {formatStatus(seg.status)}
                          </span>
                        </div>

                        <div className="flex items-center gap-2 flex-none">
                          {(seg.charge || (seg.charges && seg.charges.length > 0)) && (
                            <ChargeIndicator
                              charge={seg.charge}
                              charges={seg.charges}
                              onChargeClick={onChargeClick}
                              size="xs"
                            />
                          )}
                          {seg.buttons && seg.buttons.length > 0 && (
                            <ActionButtonGroup
                              buttons={seg.buttons}
                              onActionClick={onActionClick}
                              size="xs"
                            />
                          )}
                        </div>
                      </div>

                      <SegmentRowProgressBar progress={seg.progress} status={seg.status} />
                    </div>
                  );
                })}
              </div>
            </div>
          )}

          {/* MILESTONE VARIANT */}
          {variant === "milestone" && (
            <div className="space-y-2 rounded-xl border border-border/60 bg-muted/30 dark:bg-black/20 p-2.5">
              {segments.map((seg, idx) => {
                const isCompleted = seg.status === "completed";
                const isProcessing = seg.status === "processing";
                const isFailed = seg.status === "failed";

                return (
                  <div
                    key={seg.key || idx}
                    className={`
                      p-3 rounded-xl border transition-all duration-200 space-y-2.5
                      ${isCompleted
                        ? "bg-success/5 border-success/20"
                        : isProcessing
                        ? "bg-info/5 border-info/25"
                        : isFailed
                        ? "bg-danger/5 border-danger/25"
                        : "bg-card border-border/50"}
                    `}
                  >
                    <div className="flex flex-wrap items-center justify-between gap-2">
                      <div className="flex items-center gap-2.5 min-w-0">
                        <div className={`
                          w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold flex-none border shadow-xs
                          ${isCompleted
                            ? "bg-success border-success text-white"
                            : isProcessing
                            ? "bg-info/15 border-info text-info"
                            : isFailed
                            ? "bg-danger/15 border-danger text-danger"
                            : "bg-muted border-border text-muted-foreground"}
                        `}>
                          {isCompleted ? <CheckIcon /> : idx + 1}
                        </div>
                        <span className={`font-bold text-xs truncate ${
                          isCompleted ? "text-foreground/70 line-through decoration-muted-foreground/40" : "text-foreground"
                        }`}>
                          {seg.label || seg.key}
                        </span>
                      </div>

                      <div className="flex items-center gap-2 flex-none justify-end">
                        {(seg.charge || (seg.charges && seg.charges.length > 0)) && (
                          <ChargeIndicator
                            charge={seg.charge}
                            charges={seg.charges}
                            onChargeClick={onChargeClick}
                            size="xs"
                          />
                        )}
                        <span className={`
                          text-[10px] font-semibold px-2 py-0.5 rounded-full border
                          ${isCompleted
                            ? "bg-success/15 border-success/30 text-success"
                            : isProcessing
                            ? "bg-info/15 border-info/30 text-info"
                            : isFailed
                            ? "bg-danger/15 border-danger/30 text-danger"
                            : "bg-muted border-border text-muted-foreground"}
                        `}>
                          {formatStatus(seg.status)}
                        </span>
                        {seg.buttons && seg.buttons.length > 0 && (
                          <ActionButtonGroup
                            buttons={seg.buttons}
                            onActionClick={onActionClick}
                            size="xs"
                          />
                        )}
                      </div>
                    </div>

                    <SegmentRowProgressBar progress={seg.progress} status={seg.status} />
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
