import * as React from "react";
import { PlanDto, ActionButtonDto, NextActionDto } from "../types/sdk";
import { SegmentVariant } from "../common/segment-bar";
import { ClientPlanCard } from "./client-plan-card";
import { ClientDeliveryCard } from "./client-delivery-card";

export interface ClientPlanViewerProps {
  plan: PlanDto;
  onActionClick?: (button: ActionButtonDto, nextAction?: NextActionDto | null) => void;
  onChargeClick?: (charge: any, event: React.MouseEvent) => void;
  collapsibleDeliveries?: boolean;
  collapsibleSegments?: boolean;
  segmentVariant?: SegmentVariant;
  className?: string;
}

type SectionConfig = {
  key: string;
  label: string;
  color: string;
  dotClass: string;
  bgClass: string;
};

const STAGE_SECTIONS: SectionConfig[] = [
  {
    key: "initialization",
    label: "Initialization & Setup",
    color: "text-warning",
    dotClass: "bg-warning",
    bgClass: "bg-warning/5 border-warning/20",
  },
  {
    key: "fulfillment",
    label: "Order Fulfillment",
    color: "text-info",
    dotClass: "bg-info",
    bgClass: "bg-info/5 border-info/20",
  },
  {
    key: "completion",
    label: "Completion",
    color: "text-success",
    dotClass: "bg-success",
    bgClass: "bg-success/5 border-success/20",
  },
];

export function ClientPlanViewer({
  plan,
  onActionClick,
  onChargeClick,
  collapsibleDeliveries = false,
  collapsibleSegments = true,
  segmentVariant = "bar",
  className = "",
}: ClientPlanViewerProps) {
  const publicDeliveries = (plan.deliveries || []).filter((d) => d.is_public);

  // Group deliveries: known stages first, then "other"
  const knownStages = new Set(STAGE_SECTIONS.map((s) => s.key));
  const otherDeliveries = publicDeliveries.filter((d) => !knownStages.has((d.stage || "").toLowerCase()));

  return (
    <div className={`space-y-5 ${className}`}>
      <ClientPlanCard plan={plan} onActionClick={onActionClick} onChargeClick={onChargeClick} />

      {STAGE_SECTIONS.map((section) => {
        const deliveries = publicDeliveries.filter(
          (d) => (d.stage || "").toLowerCase() === section.key
        );
        if (deliveries.length === 0) return null;

        return (
          <div key={section.key} className="space-y-2">
            {/* Section Header */}
            <div className={`flex items-center gap-2 px-2 py-1.5 rounded-lg border text-xs ${section.bgClass}`}>
              <span className={`w-1.5 h-1.5 rounded-full flex-none ${section.dotClass}`} />
              <span className={`font-semibold uppercase tracking-wider ${section.color}`}>
                {section.label}
              </span>
              <span className={`ml-auto text-[10px] font-mono px-1.5 py-0.5 rounded-full bg-white/60 dark:bg-black/20 border border-current/20 ${section.color}`}>
                {deliveries.length}
              </span>
            </div>

            {/* Delivery Cards */}
            <div className="space-y-2 pl-2">
              {deliveries.map((delivery, idx) => (
                <ClientDeliveryCard
                  key={delivery.key || idx}
                  delivery={delivery}
                  onActionClick={onActionClick}
                  onChargeClick={onChargeClick}
                  collapsible={collapsibleDeliveries}
                  collapsibleSegments={collapsibleSegments}
                  segmentVariant={segmentVariant}
                />
              ))}
            </div>
          </div>
        );
      })}

      {/* Other stages */}
      {otherDeliveries.length > 0 && (
        <div className="space-y-2">
          <div className="flex items-center gap-2 px-2 py-1.5 rounded-lg border text-xs bg-muted/20 border-border/30">
            <span className="w-1.5 h-1.5 rounded-full flex-none bg-muted-foreground/50" />
            <span className="font-semibold uppercase tracking-wider text-muted-foreground">Other Steps</span>
            <span className="ml-auto text-[10px] font-mono text-muted-foreground">{otherDeliveries.length}</span>
          </div>
          <div className="space-y-2 pl-2">
            {otherDeliveries.map((delivery, idx) => (
              <ClientDeliveryCard
                key={delivery.key || idx}
                delivery={delivery}
                onActionClick={onActionClick}
                onChargeClick={onChargeClick}
                collapsible={collapsibleDeliveries}
                collapsibleSegments={collapsibleSegments}
                segmentVariant={segmentVariant}
              />
            ))}
          </div>
        </div>
      )}

      {publicDeliveries.length === 0 && (
        <div className="py-10 text-center rounded-xl border border-dashed border-border/40 text-xs text-muted-foreground">
          <div className="text-2xl mb-2">📦</div>
          No active steps available for this order.
        </div>
      )}
    </div>
  );
}
