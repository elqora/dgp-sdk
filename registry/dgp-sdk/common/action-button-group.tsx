import * as React from "react";
import { ActionButtonDto, NextActionDto } from "../types/sdk";

export interface ActionButtonGroupProps {
  buttons?: ActionButtonDto[];
  onActionClick?: (button: ActionButtonDto, nextAction?: NextActionDto | null) => void;
  size?: "xs" | "sm" | "md";
  className?: string;
}

export function ActionButtonGroup({
  buttons,
  onActionClick,
  size = "xs",
  className = "",
}: ActionButtonGroupProps) {
  if (!buttons || buttons.length === 0) {
    return null;
  }

  const sizeMap = {
    xs: "px-2 py-0.5 text-[10px] rounded gap-1",
    sm: "px-2.5 py-1 text-xs rounded-md gap-1",
    md: "px-3 py-1.5 text-xs rounded-md gap-1.5 font-medium",
  };

  const btnSize = sizeMap[size];

  return (
    <div className={`flex flex-wrap items-center gap-1.5 ${className}`}>
      {buttons.map((btn, index) => {
        const isDisabled = Boolean(btn.disabled);

        let styleClasses: string;
        if (btn.style === "primary") {
          styleClasses = [
            "bg-primary text-primary-foreground",
            "hover:brightness-110 active:brightness-95",
            "shadow-[0_1px_4px_hsl(var(--primary)/0.35)]",
            "hover:shadow-[0_2px_10px_hsl(var(--primary)/0.45)]",
            "border border-primary/0",
          ].join(" ");
        } else if (btn.style === "danger") {
          styleClasses = [
            "bg-destructive/10 text-destructive border border-destructive/30",
            "hover:bg-destructive hover:text-destructive-foreground",
            "dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/30",
            "dark:hover:bg-rose-500 dark:hover:text-white",
          ].join(" ");
        } else {
          styleClasses = [
            "bg-secondary/60 text-secondary-foreground",
            "border border-border/80",
            "hover:bg-secondary hover:border-border",
            "dark:bg-white/5 dark:border-white/10 dark:hover:bg-white/10",
          ].join(" ");
        }

        return (
          <button
            key={index}
            disabled={isDisabled}
            title={btn.tooltip || btn.disabled_reason || undefined}
            onClick={(e) => {
              e.stopPropagation();
              if (!isDisabled && onActionClick) {
                onActionClick(btn, btn.next_action);
              }
            }}
            className={`
              font-medium transition-all duration-150 flex items-center
              disabled:opacity-40 disabled:cursor-not-allowed disabled:pointer-events-none
              ${btnSize} ${styleClasses}
            `}
          >
            {btn.icon && <span className="text-[0.9em] leading-none">{btn.icon}</span>}
            <span>{btn.label || btn.value}</span>
          </button>
        );
      })}
    </div>
  );
}
