import * as React from "react";
import { DeliveryDto, ActionButtonDto, NextActionDto } from "../types/sdk";
import { StatusBadge } from "../common/status-badge";
import { SegmentBar, SegmentVariant } from "../common/segment-bar";
import { ActionButtonGroup } from "../common/action-button-group";
import { ChargeIndicator } from "../common/charge-indicator";

export interface AdminDeliveryCardProps {
  delivery: DeliveryDto;
  onActionClick?: (button: ActionButtonDto, nextAction?: NextActionDto | null) => void;
  onChargeClick?: (charge: any, event: React.MouseEvent) => void;
  collapsible?: boolean;
  defaultExpanded?: boolean;
  collapsibleSegments?: boolean;
  defaultSegmentsExpanded?: boolean;
  segmentVariant?: SegmentVariant;
  className?: string;
}

export function AdminDeliveryCard({
  delivery,
  onActionClick,
  onChargeClick,
  collapsible = false,
  defaultExpanded = true,
  collapsibleSegments = true,
  defaultSegmentsExpanded = false,
  segmentVariant = "bar",
  className = "",
}: AdminDeliveryCardProps) {
  const [isExpanded, setIsExpanded] = React.useState(defaultExpanded);
  const [showMeta, setShowMeta] = React.useState(false);

  const isInternal = !delivery.is_public;
  const hasCharge = Boolean(delivery.charge || (delivery.charges && delivery.charges.length > 0));

  return (
    <div
      className={`
        relative rounded-xl border bg-card text-card-foreground text-xs
        card-glow transition-all duration-150
        ${isInternal
          ? "border-warning/30 before:absolute before:inset-y-0 before:left-0 before:w-0.5 before:rounded-l-xl before:bg-warning/70"
          : "border-border/60 before:absolute before:inset-y-0 before:left-0 before:w-0.5 before:rounded-l-xl before:bg-muted-foreground/30"}
        ${className}
      `}
    >
      {/* Header */}
      <div
        onClick={() => collapsible && setIsExpanded(!isExpanded)}
        className={`flex items-start justify-between gap-3 px-4 py-3 pl-5 ${collapsible ? "cursor-pointer select-none" : ""}`}
      >
        <div className="space-y-1 flex-1 min-w-0">
          <div className="flex flex-wrap items-center gap-1.5">
            <span className="font-semibold text-sm tracking-tight text-foreground font-sans">
              {delivery.label || delivery.name || delivery.key}
            </span>
            <code className="text-[9px] font-mono px-1.5 py-0.5 rounded bg-muted/80 dark:bg-white/5 text-muted-foreground border border-border/40">
              {delivery.key}
            </code>
            {isInternal && (
              <span className="text-[9px] font-semibold uppercase tracking-widest px-1.5 py-0.5 rounded-full bg-warning/15 text-warning border border-warning/30">
                Internal
              </span>
            )}
            {hasCharge && (
              <ChargeIndicator
                charge={delivery.charge}
                charges={delivery.charges}
                onChargeClick={onChargeClick}
                size="xs"
              />
            )}
          </div>
          <div className="flex items-center gap-3 font-mono text-[10px] text-muted-foreground">
            <span>Stage: <code className="text-foreground/80">{delivery.stage || "—"}</code></span>
            <span>Kind: <code className="text-foreground/80">{delivery.kind || "—"}</code></span>
          </div>
        </div>

        <div className="flex items-center gap-2 flex-none">
          <StatusBadge status={delivery.status} size="sm" />
          {collapsible && (
            <span className="p-1 rounded-md transition text-muted-foreground hover:bg-muted hover:text-foreground">
              <svg
                className={`w-3 h-3 transition-transform duration-200 ${isExpanded ? "rotate-180" : "rotate-0"}`}
                fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}
              >
                <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </span>
          )}
        </div>
      </div>

      {/* Body */}
      {(!collapsible || isExpanded) && (
        <div className="animate-slide-down px-4 pb-4 pl-5 space-y-3">
          {delivery.note && (
            <div className="flex items-start gap-2 p-2.5 rounded-lg bg-muted/40 dark:bg-white/3 border border-border/30 font-sans">
              <span className="text-muted-foreground/60 mt-0.5">ℹ</span>
              <p className="text-[11px] text-muted-foreground leading-relaxed">{delivery.note}</p>
            </div>
          )}

          {delivery.progress && (
            <div className="font-sans">
              <SegmentBar
                progress={delivery.progress}
                showPublicOnly={false}
                variant={segmentVariant}
                collapsibleSegments={collapsibleSegments}
                defaultSegmentsExpanded={defaultSegmentsExpanded}
                onChargeClick={onChargeClick}
                onActionClick={onActionClick}
              />
            </div>
          )}

          {/* Footer bar */}
          <div className="flex items-center justify-between gap-3 pt-2 border-t border-border/30 font-sans">
            <button
              onClick={(e) => {
                e.stopPropagation();
                setShowMeta(!showMeta);
              }}
              className={`
                inline-flex items-center gap-1 text-[10px] font-mono px-2 py-1 rounded-md border transition-all
                ${showMeta
                  ? "bg-primary/10 border-primary/30 text-primary"
                  : "border-border/40 text-muted-foreground hover:border-border hover:text-foreground bg-transparent"}
              `}
            >
              <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
              </svg>
              {showMeta ? "Hide Meta" : "Inspect Meta"}
            </button>

            {delivery.buttons && delivery.buttons.length > 0 && (
              <ActionButtonGroup buttons={delivery.buttons} onActionClick={onActionClick} size="xs" />
            )}
          </div>

          {showMeta && (
            <pre className="animate-slide-down p-3 bg-muted/60 dark:bg-black/30 rounded-lg text-[10px] font-mono overflow-auto max-h-48 border border-border/40 text-muted-foreground">
              {JSON.stringify({ meta: delivery.meta, next_action: delivery.next_action }, null, 2)}
            </pre>
          )}
        </div>
      )}
    </div>
  );
}
