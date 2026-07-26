import * as React from "react";
import { PlanDto, ActionButtonDto, NextActionDto } from "../types/sdk";
import { StatusBadge } from "../common/status-badge";
import { ActionButtonGroup } from "../common/action-button-group";
import { ChargeIndicator } from "../common/charge-indicator";

export interface ClientPlanCardProps {
  plan: PlanDto;
  onActionClick?: (button: ActionButtonDto, nextAction?: NextActionDto | null) => void;
  onChargeClick?: (charge: any, event: React.MouseEvent) => void;
  className?: string;
}

export function ClientPlanCard({
  plan,
  onActionClick,
  onChargeClick,
  className = "",
}: ClientPlanCardProps) {
  const publicDeliveries = (plan.deliveries || []).filter((d) => d.is_public);
  const completedDeliveries = publicDeliveries.filter((d) => d.status === "completed").length;
  const totalDeliveries = publicDeliveries.length;
  const overallPercent = totalDeliveries > 0 ? Math.round((completedDeliveries / totalDeliveries) * 100) : 0;
  const hasCharge = Boolean(plan.charge || (plan.charges && plan.charges.length > 0));

  return (
    <div className={`relative overflow-hidden rounded-2xl border border-border/60 bg-card card-glow ${className}`}>
      {/* Gradient accent header strip */}
      <div className="absolute inset-x-0 top-0 h-0.5 progress-gradient" />

      <div className="p-5 space-y-4">
        {/* Header Row */}
        <div className="flex items-start justify-between gap-3">
          <div className="space-y-0.5">
            <div className="flex items-center gap-2 flex-wrap">
              <span className="text-[10px] font-mono font-medium text-muted-foreground uppercase tracking-widest">
                Order #{plan.order_id || plan.key}
              </span>
              <span className="text-[9px] font-mono font-semibold px-1.5 py-0.5 rounded-full bg-muted/60 text-muted-foreground border border-border/40">
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
            <h3 className="text-base font-bold tracking-tight text-foreground">
              Your Order Progress
            </h3>
          </div>
          <StatusBadge status={plan.status} />
        </div>

        {/* Progress Section */}
        <div className="space-y-2">
          <div className="flex items-center justify-between text-xs">
            <span className="text-muted-foreground font-medium">Overall Fulfillment</span>
            <div className="flex items-center gap-2">
              <span className="text-muted-foreground/70">{completedDeliveries}/{totalDeliveries} steps</span>
              <span className="font-bold tabular-nums text-foreground">{overallPercent}%</span>
            </div>
          </div>
          <div className="relative h-2 w-full bg-muted/60 dark:bg-white/5 rounded-full overflow-hidden">
            <div
              className="absolute inset-y-0 left-0 rounded-full transition-all duration-700 ease-out progress-gradient"
              style={{ width: `${overallPercent}%` }}
            />
          </div>

          {/* Step indicators */}
          {totalDeliveries > 0 && (
            <div className="flex gap-1 pt-0.5">
              {publicDeliveries.map((d, i) => (
                <div
                  key={d.key || i}
                  title={`${d.label || d.name || d.key}: ${d.status}`}
                  className={`h-0.5 flex-1 rounded-full transition-all duration-300 ${
                    d.status === "completed"
                      ? "bg-success"
                      : d.status === "processing"
                      ? "bg-primary animate-pulse-glow"
                      : "bg-muted-foreground/20"
                  }`}
                />
              ))}
            </div>
          )}
        </div>

        {/* Action Buttons */}
        {plan.buttons && plan.buttons.length > 0 && (
          <div className="flex justify-end pt-1 border-t border-border/30">
            <ActionButtonGroup buttons={plan.buttons} onActionClick={onActionClick} size="sm" />
          </div>
        )}
      </div>
    </div>
  );
}
