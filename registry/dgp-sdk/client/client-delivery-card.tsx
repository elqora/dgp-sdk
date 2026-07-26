import * as React from "react";
import { DeliveryDto, ActionButtonDto, NextActionDto } from "../types/sdk";
import { StatusBadge } from "../common/status-badge";
import { SegmentBar, SegmentVariant } from "../common/segment-bar";
import { ActionButtonGroup } from "../common/action-button-group";
import { ChargeIndicator } from "../common/charge-indicator";

export interface ClientDeliveryCardProps {
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

/** Derive a per-stage left-border accent using semantic tokens */
function stageAccent(stage?: string | null): string {
  switch ((stage || "").toLowerCase()) {
    case "initialization": return "before:bg-warning";
    case "fulfillment": return "before:bg-info";
    case "completion": return "before:bg-success";
    case "post_delivery": return "before:bg-accent-foreground";
    default: return "before:bg-primary";
  }
}

export function ClientDeliveryCard({
  delivery,
  onActionClick,
  onChargeClick,
  collapsible = false,
  defaultExpanded = true,
  collapsibleSegments = true,
  defaultSegmentsExpanded = false,
  segmentVariant = "bar",
  className = "",
}: ClientDeliveryCardProps) {
  const [isExpanded, setIsExpanded] = React.useState(defaultExpanded);

  if (!delivery.is_public) return null;

  const accent = stageAccent(delivery.stage);
  const isProcessing = delivery.status === "processing" || delivery.status === "running";
  const hasCharge = Boolean(delivery.charge || (delivery.charges && delivery.charges.length > 0));

  return (
    <div
      className={`
        relative overflow-hidden rounded-xl border bg-card text-card-foreground
        card-glow card-glow-hover
        before:absolute before:inset-y-0 before:left-0 before:w-0.5 ${accent}
        ${isProcessing ? "border-info/25" : "border-border/50"}
        ${className}
      `}
    >
      {/* Header */}
      <div
        onClick={() => collapsible && setIsExpanded(!isExpanded)}
        className={`flex items-start justify-between gap-3 px-4 pt-4 pb-3 ${collapsible ? "cursor-pointer select-none" : ""}`}
      >
        <div className="space-y-1 flex-1 min-w-0 pl-1">
          <div className="flex flex-wrap items-center gap-1.5">
            <span className="font-semibold text-sm tracking-tight text-foreground truncate">
              {delivery.label || delivery.name || delivery.key}
            </span>
            {delivery.stage && (
              <span className="text-[9px] font-semibold uppercase tracking-widest px-1.5 py-0.5 rounded-full border border-border/40 bg-muted/60 text-muted-foreground">
                {delivery.stage}
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
          {delivery.note && (!collapsible || !isExpanded) && (
            <p className="text-[11px] text-muted-foreground line-clamp-1 leading-relaxed">
              {delivery.note}
            </p>
          )}
        </div>

        <div className="flex items-center gap-2 flex-none">
          <StatusBadge status={delivery.status} size="sm" />
          {collapsible && (
            <span className={`
              p-1 rounded-md transition-all text-muted-foreground
              hover:bg-muted hover:text-foreground
            `}>
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
        <div className="animate-slide-down px-4 pb-4 space-y-3 pl-5">
          {delivery.note && (
            <p className="text-[11px] text-muted-foreground leading-relaxed">
              {delivery.note}
            </p>
          )}

          {delivery.progress && (
            <SegmentBar
              progress={delivery.progress}
              showPublicOnly={true}
              variant={segmentVariant}
              collapsibleSegments={collapsibleSegments}
              defaultSegmentsExpanded={defaultSegmentsExpanded}
              onChargeClick={onChargeClick}
            />
          )}

          {delivery.buttons && delivery.buttons.length > 0 && (
            <div className="flex justify-end pt-1">
              <ActionButtonGroup buttons={delivery.buttons} onActionClick={onActionClick} size="xs" />
            </div>
          )}
        </div>
      )}
    </div>
  );
}
