import * as React from "react";
import { PlanDto, ActionButtonDto, NextActionDto } from "../types/sdk";
import { SegmentVariant } from "../common/segment-bar";
import { StatusBadge } from "../common/status-badge";
import { AdminDeliveryCard } from "./admin-delivery-card";
import { ActionButtonGroup } from "../common/action-button-group";
import { ChargeIndicator } from "../common/charge-indicator";

export interface AdminPlanViewerProps {
  plan: PlanDto;
  onActionClick?: (button: ActionButtonDto, nextAction?: NextActionDto | null) => void;
  onChargeClick?: (charge: any, event: React.MouseEvent) => void;
  collapsibleDeliveries?: boolean;
  collapsibleSegments?: boolean;
  segmentVariant?: SegmentVariant;
  className?: string;
}

function StatChip({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="flex flex-col gap-0.5 px-3 py-2 rounded-lg border border-border/40 bg-muted/30 dark:bg-white/3 min-w-[80px]">
      <span className="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">{label}</span>
      <span className="text-sm font-bold font-mono text-foreground">{value}</span>
    </div>
  );
}

export function AdminPlanViewer({
  plan,
  onActionClick,
  onChargeClick,
  collapsibleDeliveries = false,
  collapsibleSegments = true,
  segmentVariant = "bar",
  className = "",
}: AdminPlanViewerProps) {
  const [showState, setShowState] = React.useState(false);
  const deliveries = plan.deliveries || [];
  const internalCount = deliveries.filter((d) => !d.is_public).length;
  const completedCount = deliveries.filter((d) => d.status === "completed").length;
  const hasCharge = Boolean(plan.charge || (plan.charges && plan.charges.length > 0));

  return (
    <div className={`space-y-4 ${className}`}>
      {/* Plan Overview Header Card */}
      <div className="relative overflow-hidden rounded-2xl border border-border/60 bg-card card-glow">
        {/* Warning-toned accent strip for admin views */}
        <div className="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-warning via-warning/60 to-warning" />

        <div className="p-5 space-y-4">
          {/* Header */}
          <div className="flex items-start justify-between gap-3">
            <div className="space-y-0.5">
              <div className="flex items-center gap-2 flex-wrap">
                <span className="text-[10px] font-mono font-medium text-muted-foreground uppercase tracking-widest">
                  Plan Key: {plan.key}
                </span>
                <span className="text-[9px] font-mono font-semibold px-1.5 py-0.5 rounded-full bg-warning/15 text-warning border border-warning/25">
                  Rev {plan.revision}
                </span>
                {hasCharge && (
                  <ChargeIndicator
                    charge={plan.charge}
                    charges={plan.charges}
                    onChargeClick={onChargeClick}
                    size="xs"
                  />
                )}
              </div>
              <h2 className="text-base font-bold tracking-tight text-foreground">
                Operator Plan Overview
              </h2>
              <p className="text-[11px] font-mono text-muted-foreground">
                Order: <code className="text-foreground">{plan.order_id || "N/A"}</code>
                &nbsp;·&nbsp;
                Plan ID: <code className="text-foreground">{plan.id || "N/A"}</code>
              </p>
            </div>
            <StatusBadge status={plan.status} />
          </div>

          {/* Stat chips */}
          <div className="flex flex-wrap gap-2">
            <StatChip label="Total" value={deliveries.length} />
            <StatChip label="Completed" value={completedCount} />
            <StatChip label="Internal" value={internalCount} />
            <StatChip label="Public" value={deliveries.length - internalCount} />
          </div>

          {/* Footer */}
          <div className="flex items-center justify-between pt-1 border-t border-border/30">
            <button
              onClick={() => setShowState(!showState)}
              className={`
                inline-flex items-center gap-1.5 text-[10px] font-mono px-2 py-1 rounded-md border transition-all
                ${showState
                  ? "bg-warning/10 border-warning/30 text-warning"
                  : "border-border/40 text-muted-foreground hover:border-border hover:text-foreground"}
              `}
            >
              <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              {showState ? "Hide State JSON" : "Inspect State JSON"}
            </button>

            {plan.buttons && plan.buttons.length > 0 && (
              <ActionButtonGroup buttons={plan.buttons} onActionClick={onActionClick} size="xs" />
            )}
          </div>

          {showState && (
            <pre className="animate-slide-down p-3 bg-muted/60 dark:bg-black/30 rounded-lg text-[10px] font-mono overflow-auto max-h-52 border border-border/40 text-muted-foreground">
              {JSON.stringify({ state: plan.state, meta: plan.meta, next_action: plan.next_action }, null, 2)}
            </pre>
          )}
        </div>
      </div>

      {/* Deliveries List */}
      <div className="space-y-2">
        <div className="flex items-center justify-between px-1">
          <h3 className="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
            All Execution Deliveries
          </h3>
          <span className="text-[10px] font-mono text-muted-foreground">
            {deliveries.length} total
          </span>
        </div>

        <div className="space-y-2">
          {deliveries.map((delivery, idx) => (
            <AdminDeliveryCard
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

        {deliveries.length === 0 && (
          <div className="py-8 text-center rounded-xl border border-dashed border-border/40 text-xs font-mono text-muted-foreground">
            No deliveries registered for this plan.
          </div>
        )}
      </div>
    </div>
  );
}
