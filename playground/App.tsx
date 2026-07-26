import React, { useState, useEffect } from "react";
import "./playground.css";
import {
  ClientPlanViewer,
  AdminPlanViewer,
  AdminManagementViewer,
  OrderChargeStateViewer,
  ChargeIndicator,
  StatusBadge,
  SegmentBar,
  SegmentVariant,
  ActionButtonGroup,
} from "../registry/dgp-sdk";

import {
  mockClientPlan,
  mockAdminPlan,
  mockFulfillmentDelivery2,
  mockOrderManagement,
  mockOrderChargeState,
  mockChargePlatformFee,
  mockChargeDeliveryUnits,
} from "../registry/dgp-sdk/data/mock-data";

import { JsonTreeViewer } from "./json-tree-viewer";

type Tab = "client" | "admin" | "charges" | "components";

const TABS: { id: Tab; label: string; icon: string }[] = [
  { id: "client", label: "Client View", icon: "👤" },
  { id: "admin", label: "Admin View", icon: "⚙️" },
  { id: "charges", label: "Charges", icon: "💳" },
  { id: "components", label: "Primitives", icon: "🧩" },
];

const VIEWPORTS = [
  { label: "Desktop", value: "100%" },
  { label: "Tablet", value: "768px" },
  { label: "Mobile", value: "400px" },
];

export default function App() {
  const [activeTab, setActiveTab] = useState<Tab>("client");
  const [darkMode, setDarkMode] = useState<boolean>(true);
  const [viewportWidth, setViewportWidth] = useState<string>("100%");
  const [segmentVariant, setSegmentVariant] = useState<SegmentVariant>("bar");
  const [collapsibleDeliveries, setCollapsibleDeliveries] = useState<boolean>(true);
  const [collapsibleSegments, setCollapsibleSegments] = useState<boolean>(true);
  const [toast, setToast] = useState<string | null>(null);

  useEffect(() => {
    document.documentElement.classList.toggle("dark", darkMode);
  }, [darkMode]);

  // Auto-dismiss toast
  useEffect(() => {
    if (!toast) return;
    const t = setTimeout(() => setToast(null), 4000);
    return () => clearTimeout(t);
  }, [toast]);

  const handleActionClick = (btn: any, nextAction: any) => {
    setToast(
      `"${btn.label || btn.value}" triggered${nextAction ? ` → ${nextAction.type}` : ""}`
    );
  };

  const handleChargeClick = (charge: any) => {
    setToast(
      `💳 Charge clicked: "${charge.label || charge.key}" (${charge.amount?.amount ?? ""} ${charge.amount?.currency ?? ""})`
    );
  };

  return (
    <div className="min-h-screen bg-background text-foreground flex flex-col font-sans">
      {/* ── Top Navbar ── */}
      <header className="sticky top-0 z-50 border-b border-border/60 bg-card/80 backdrop-blur-md px-5 py-3 flex items-center justify-between gap-4">
        {/* Brand */}
        <div className="flex items-center gap-3 min-w-0">
          <div className="w-8 h-8 rounded-xl bg-primary flex items-center justify-center text-primary-foreground font-black text-sm shadow-[0_0_0_3px_hsl(var(--primary)/0.15)] flex-none">
            D
          </div>
          <div className="min-w-0">
            <div className="flex items-center gap-2">
              <span className="font-bold text-sm tracking-tight truncate">dgp-sdk Playground</span>
              <span className="text-[9px] font-mono font-semibold px-1.5 py-0.5 rounded-full bg-primary/15 text-primary border border-primary/20 flex-none">
                React + TSX
              </span>
            </div>
            <p className="text-[10px] text-muted-foreground truncate">
              Interactive component simulator with live DTO payloads
            </p>
          </div>
        </div>

        {/* Right Controls */}
        <div className="flex items-center gap-2 flex-none">
          {/* Viewport switcher */}
          <div className="hidden sm:flex items-center gap-0.5 p-0.5 rounded-lg border border-border/60 bg-muted/40 text-[11px]">
            {VIEWPORTS.map((vp) => (
              <button
                key={vp.value}
                onClick={() => setViewportWidth(vp.value)}
                className={`px-2.5 py-1 rounded-md transition-all ${
                  viewportWidth === vp.value
                    ? "bg-card text-foreground font-semibold shadow-sm"
                    : "text-muted-foreground hover:text-foreground"
                }`}
              >
                {vp.label}
              </button>
            ))}
          </div>

          {/* Dark mode toggle */}
          <button
            onClick={() => setDarkMode(!darkMode)}
            className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border/60 bg-card text-[11px] font-medium hover:bg-muted transition"
          >
            <span>{darkMode ? "☀️" : "🌙"}</span>
            <span className="hidden sm:inline">{darkMode ? "Light" : "Dark"}</span>
          </button>
        </div>
      </header>

      {/* ── Control Bar ── */}
      <div className="border-b border-border/40 bg-muted/30 dark:bg-black/10 px-5 py-2 flex flex-wrap items-center gap-x-5 gap-y-2 text-[11px]">
        {/* Segment Variant */}
        <div className="flex items-center gap-2">
          <span className="text-muted-foreground font-medium">Segments:</span>
          <div className="flex items-center gap-0.5 p-0.5 rounded-lg border border-border/60 bg-card">
            {(["bar", "stepper", "milestone", "compact"] as SegmentVariant[]).map((v) => (
              <button
                key={v}
                onClick={() => setSegmentVariant(v)}
                className={`px-2 py-0.5 rounded-md font-mono capitalize transition-all ${
                  segmentVariant === v
                    ? "bg-primary text-primary-foreground font-semibold"
                    : "text-muted-foreground hover:text-foreground"
                }`}
              >
                {v}
              </button>
            ))}
          </div>
        </div>

        {/* Toggles */}
        <label className="flex items-center gap-1.5 cursor-pointer select-none text-muted-foreground hover:text-foreground transition">
          <input
            type="checkbox"
            checked={collapsibleSegments}
            onChange={(e) => setCollapsibleSegments(e.target.checked)}
            className="accent-primary w-3.5 h-3.5"
          />
          <span>Collapsible sub-segments</span>
        </label>

        <label className="flex items-center gap-1.5 cursor-pointer select-none text-muted-foreground hover:text-foreground transition">
          <input
            type="checkbox"
            checked={collapsibleDeliveries}
            onChange={(e) => setCollapsibleDeliveries(e.target.checked)}
            className="accent-primary w-3.5 h-3.5"
          />
          <span>Collapsible delivery cards</span>
        </label>
      </div>

      {/* ── Tab Bar ── */}
      <div className="border-b border-border/40 bg-card/50 px-5">
        <div className="flex gap-1 max-w-6xl mx-auto">
          {TABS.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`
                flex items-center gap-1.5 px-3 py-3 text-xs font-semibold border-b-2 transition-all
                ${activeTab === tab.id
                  ? "border-primary text-primary"
                  : "border-transparent text-muted-foreground hover:text-foreground hover:border-border/60"}
              `}
            >
              <span>{tab.icon}</span>
              <span>{tab.label}</span>
            </button>
          ))}
        </div>
      </div>

      {/* ── Toast ── */}
      {toast && (
        <div className="border-b border-primary/20 bg-primary/8 dark:bg-primary/10 px-5 py-2 flex items-center justify-between gap-3 text-[11px] font-mono text-primary">
          <div className="flex items-center gap-2">
            <span className="text-primary">⚡</span>
            <span>{toast}</span>
          </div>
          <button
            onClick={() => setToast(null)}
            className="opacity-50 hover:opacity-100 text-sm leading-none"
          >
            ×
          </button>
        </div>
      )}

      {/* ── Main Content ── */}
      <main className="flex-1 overflow-y-auto bg-muted/20 dark:bg-black/10 p-5">
        <div
          className="mx-auto transition-all duration-300 space-y-5"
          style={{ maxWidth: viewportWidth === "100%" ? "none" : viewportWidth, width: viewportWidth }}
        >
          {/* CLIENT TAB */}
          {activeTab === "client" && (
            <div className="space-y-5">
              <InfoBanner
                color="primary"
                title="Client Level Experience"
                body={<>Renders <Code>ClientPlanViewer</Code> and <Code>ClientDeliveryCard</Code> — public-facing, customer-friendly design with embedded <Code>ChargeIndicator</Code> pills.</>}
                meta={`Sub-segments: ${collapsibleSegments ? "Collapsible" : "Always Open"}`}
              />

              <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
                <div className="lg:col-span-2">
                  <ClientPlanViewer
                    plan={mockClientPlan}
                    onActionClick={handleActionClick}
                    onChargeClick={handleChargeClick}
                    collapsibleDeliveries={collapsibleDeliveries}
                    collapsibleSegments={collapsibleSegments}
                    segmentVariant={segmentVariant}
                  />
                </div>
                <div>
                  <JsonTreeViewer data={mockClientPlan} title="PlanDto (Client Scope)" />
                </div>
              </div>
            </div>
          )}

          {/* ADMIN TAB */}
          {activeTab === "admin" && (
            <div className="space-y-5">
              <InfoBanner
                color="warning"
                title="Admin / Operator Experience"
                body={<>Renders <Code>AdminPlanViewer</Code> &amp; <Code>AdminManagementViewer</Code> — full internal view including pinned charge indicators across plan and deliveries.</>}
                meta={`Sub-segments: ${collapsibleSegments ? "Collapsible" : "Always Open"}`}
              />

              <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
                <div className="lg:col-span-2 space-y-4">
                  <AdminManagementViewer management={mockOrderManagement} />
                  <AdminPlanViewer
                    plan={mockAdminPlan}
                    onActionClick={handleActionClick}
                    onChargeClick={handleChargeClick}
                    collapsibleDeliveries={collapsibleDeliveries}
                    collapsibleSegments={collapsibleSegments}
                    segmentVariant={segmentVariant}
                  />
                </div>
                <div className="space-y-3">
                  <JsonTreeViewer data={mockOrderManagement} title="OrderManagementDto" />
                  <JsonTreeViewer data={mockAdminPlan} title="PlanDto (Admin Scope)" />
                </div>
              </div>
            </div>
          )}

          {/* CHARGES TAB */}
          {activeTab === "charges" && (
            <div className="space-y-5">
              <InfoBanner
                color="primary"
                title="Charge Components"
                body={<>Renders <Code>OrderChargeStateViewer</Code> with both <Code>ClientChargeCard</Code> and <Code>AdminChargeCard</Code> — sourced from PHP <Code>OrderChargeState</Code> and <Code>Charge</Code> DTOs.</>}
              />

              <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
                <div className="lg:col-span-2 space-y-6">
                  {/* Client charge view */}
                  <div className="space-y-2">
                    <p className="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground px-1">Client Experience</p>
                    <OrderChargeStateViewer
                      chargeState={mockOrderChargeState}
                      charges={[mockChargePlatformFee, mockChargeDeliveryUnits]}
                      mode="client"
                      onActionClick={handleActionClick}
                    />
                  </div>

                  {/* Admin charge view */}
                  <div className="space-y-2">
                    <p className="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground px-1">Admin Experience</p>
                    <OrderChargeStateViewer
                      chargeState={mockOrderChargeState}
                      charges={[mockChargePlatformFee, mockChargeDeliveryUnits]}
                      mode="admin"
                      onActionClick={handleActionClick}
                    />
                  </div>
                </div>

                <div className="space-y-3">
                  <JsonTreeViewer data={mockOrderChargeState} title="OrderChargeStateDto" />
                  <JsonTreeViewer data={mockChargePlatformFee} title="ChargeDto (paid)" />
                  <JsonTreeViewer data={mockChargeDeliveryUnits} title="ChargeDto (partial)" />
                </div>
              </div>
            </div>
          )}

          {/* COMPONENTS TAB */}
          {activeTab === "components" && (
            <div className="space-y-5">
              <InfoBanner
                color="violet"
                title="Component Primitives"
                body="Individual subcomponents rendered in isolation for design reference and testing."
              />

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* StatusBadge */}
                <PrimitiveCard title="StatusBadge Variants">
                  <div className="flex flex-wrap gap-2">
                    {["completed", "processing", "pending", "failed", "canceled", "draft"].map((s) => (
                      <StatusBadge key={s} status={s} />
                    ))}
                  </div>
                  <div className="flex flex-wrap gap-2 pt-2 border-t border-border/30">
                    <span className="text-[10px] text-muted-foreground self-center">size="sm"</span>
                    {["completed", "processing", "failed"].map((s) => (
                      <StatusBadge key={s} status={s} size="sm" />
                    ))}
                  </div>
                </PrimitiveCard>

                {/* ActionButtonGroup */}
                <PrimitiveCard title="ActionButtonGroup">
                  <div className="space-y-3">
                    {(["xs", "sm", "md"] as const).map((sz) => (
                      <div key={sz} className="space-y-1">
                        <div className="text-[10px] font-mono text-muted-foreground">size="{sz}"</div>
                        <ActionButtonGroup
                          size={sz}
                          buttons={[
                            { value: "a", kind: "text", label: "Primary", style: "primary" },
                            { value: "b", kind: "text", label: "Default", style: "default" },
                            { value: "c", kind: "text", label: "Danger", style: "danger" },
                          ]}
                          onActionClick={handleActionClick}
                        />
                      </div>
                    ))}
                  </div>
                </PrimitiveCard>

                {/* ChargeIndicator */}
                <PrimitiveCard title="ChargeIndicator (Pinned Charge Pill)">
                  <div className="space-y-2">
                    <div className="text-[10px] font-mono text-muted-foreground">Status Pinned Badges (Click for popover preview):</div>
                    <div className="flex flex-wrap items-center gap-2">
                      <ChargeIndicator charge={mockChargePlatformFee} onChargeClick={handleChargeClick} />
                      <ChargeIndicator charge={mockChargeDeliveryUnits} onChargeClick={handleChargeClick} />
                      <ChargeIndicator
                        charge={{
                          key: "pending_invoice",
                          label: "Pending Invoice",
                          amount: { amount: "99.00", currency: "USD" },
                          status: "invoiced",
                          due_at: "2026-08-01",
                          payments: [],
                          buttons: [],
                          meta: {},
                        }}
                        onChargeClick={handleChargeClick}
                      />
                    </div>
                  </div>
                </PrimitiveCard>

                {/* SegmentBar all variants */}
                <PrimitiveCard title="SegmentBar Variants" className="md:col-span-2">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {(["bar", "stepper", "milestone", "compact"] as SegmentVariant[]).map((v) => (
                      <div key={v} className="space-y-2">
                        <div className="text-[10px] font-mono font-semibold text-primary">variant="{v}"</div>
                        <div className="p-3 rounded-xl border border-border/40 bg-muted/20">
                          <SegmentBar
                            variant={v}
                            progress={mockFulfillmentDelivery2.progress}
                            defaultSegmentsExpanded={true}
                          />
                        </div>
                      </div>
                    ))}
                  </div>
                </PrimitiveCard>
              </div>
            </div>
          )}
        </div>
      </main>
    </div>
  );
}

/* ── Local helpers ── */

function Code({ children }: { children: React.ReactNode }) {
  return (
    <code className="font-mono text-foreground bg-muted/70 px-1 py-0.5 rounded text-[10px]">
      {children}
    </code>
  );
}

function InfoBanner({
  color,
  title,
  body,
  meta,
}: {
  color: "primary" | "warning" | "violet";
  title: string;
  body: React.ReactNode;
  meta?: string;
}) {
  const colors = {
    primary: "border-primary/25 bg-primary/5 [&_strong]:text-primary",
    warning: "border-warning/25 bg-warning/8 dark:bg-warning/10 [&_strong]:text-warning",
    violet: "border-accent-foreground/25 bg-accent [&_strong]:text-accent-foreground",
  }[color];

  return (
    <div className={`px-4 py-3 rounded-xl border flex items-start justify-between gap-3 text-xs text-muted-foreground ${colors}`}>
      <div>
        <strong className="font-semibold">{title}:</strong> {body}
      </div>
      {meta && (
        <span className="text-[10px] font-mono flex-none opacity-70">{meta}</span>
      )}
    </div>
  );
}

function PrimitiveCard({
  title,
  children,
  className = "",
}: {
  title: string;
  children: React.ReactNode;
  className?: string;
}) {
  return (
    <div className={`p-4 rounded-xl border border-border/60 bg-card card-glow space-y-3 ${className}`}>
      <h4 className="text-xs font-bold text-foreground">{title}</h4>
      {children}
    </div>
  );
}
