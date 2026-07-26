import * as React from "react";
import { OrderManagementDto } from "../types/sdk";

export interface AdminManagementViewerProps {
  management: OrderManagementDto;
  className?: string;
}

export function AdminManagementViewer({
  management,
  className = "",
}: AdminManagementViewerProps) {
  const warnings = management.warnings || [];
  const permissions = management.permissions || [];
  const instructions = management.instructions || [];

  return (
    <div className={`p-5 rounded-2xl border border-border bg-card text-card-foreground shadow-sm space-y-5 font-sans ${className}`}>
      <div className="border-b border-border/40 pb-3">
        <span className="text-xs font-mono font-bold uppercase tracking-wider text-muted-foreground">
          Management Inspector
        </span>
        <h3 className="text-lg font-bold tracking-tight">
          Order #{management.order_id} Management Diagnostics
        </h3>
      </div>

      {warnings.length > 0 && (
        <div className="space-y-2">
          <h4 className="text-xs font-bold font-mono uppercase text-rose-500 tracking-wider">
            Active Warnings ({warnings.length})
          </h4>
          <div className="space-y-2">
            {warnings.map((warn, idx) => (
              <div
                key={warn.id || idx}
                className="p-3 rounded-lg border border-rose-500/20 bg-rose-500/10 text-rose-700 dark:text-rose-300 text-xs space-y-1"
              >
                <div className="font-semibold flex items-center justify-between">
                  <span>[{warn.severity.toUpperCase()}] {warn.title || warn.id}</span>
                </div>
                <p className="opacity-90">{warn.message}</p>
              </div>
            ))}
          </div>
        </div>
      )}

      {permissions.length > 0 && (
        <div className="space-y-2">
          <h4 className="text-xs font-bold font-mono uppercase text-muted-foreground tracking-wider">
            Action Permissions Matrix ({permissions.length})
          </h4>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs font-mono">
            {permissions.map((perm, idx) => (
              <div
                key={idx}
                className="p-2.5 rounded-md border border-border/60 bg-muted/30 flex items-center justify-between"
              >
                <span>{perm.action}</span>
                <span
                  className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                    perm.allowed
                      ? "bg-emerald-500/20 text-emerald-600 dark:text-emerald-400"
                      : "bg-rose-500/20 text-rose-600 dark:text-rose-400"
                  }`}
                >
                  {perm.allowed ? "ALLOWED" : "DENIED"}
                </span>
              </div>
            ))}
          </div>
        </div>
      )}

      {instructions.length > 0 && (
        <div className="space-y-2">
          <h4 className="text-xs font-bold font-mono uppercase text-muted-foreground tracking-wider">
            Operational Instructions ({instructions.length})
          </h4>
          <div className="space-y-2">
            {instructions.map((inst, idx) => (
              <div key={inst.id || idx} className="p-3 rounded-lg border border-border bg-muted/20 text-xs space-y-2">
                <div className="font-semibold text-foreground">{inst.title}</div>
                {inst.description && <p className="text-muted-foreground">{inst.description}</p>}
                {inst.steps && inst.steps.length > 0 && (
                  <ol className="list-decimal pl-4 space-y-1 text-muted-foreground">
                    {inst.steps.map((step, sIdx) => (
                      <li key={sIdx}>{step}</li>
                    ))}
                  </ol>
                )}
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
