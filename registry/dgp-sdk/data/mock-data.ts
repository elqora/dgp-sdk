import { PlanDto, DeliveryDto, OrderManagementDto, ChargeDto, OrderChargeStateDto } from "../types/sdk";

/**
 * Mock Delivery Data (Client-facing & Internal)
 */
export const mockInitializationDelivery: DeliveryDto = {
  id: "del_init_01",
  key: "account_setup",
  stage: "initialization",
  status: "completed",
  label: "Target Account & Credential Verification",
  kind: "verification",
  name: "Account Setup",
  is_public: true,
  note: "Account privacy check passed. Ready for order dispatch.",
  progress: {
    current: 1,
    target: 1,
    percent: 100,
    unit: "step",
    label: "Verification Completed",
    segments: [
      {
        key: "privacy_check",
        progress: { percent: 100 },
        label: "Privacy Public Check",
        status: "completed",
        sequence: 1,
        is_public: true,
      },
    ],
  },
  buttons: [
    {
      value: "view_details",
      kind: "text",
      label: "View Account Details",
      style: "default",
    },
  ],
  meta: {
    target_username: "@digital_growth",
    account_id: 948271,
  },
};

export const mockFulfillmentDelivery1: DeliveryDto = {
  id: "del_ful_01",
  key: "batch_delivery_1",
  stage: "fulfillment",
  status: "completed",
  label: "Delivery Batch 1 (300 Units)",
  kind: "dispatch",
  name: "Batch 1",
  is_public: true,
  note: "Initial batch delivered successfully.",
  progress: {
    current: 300,
    target: 300,
    percent: 100,
    unit: "units",
    label: "300 / 300 Completed",
    segments: [
      {
        key: "sub_batch_1a",
        progress: { percent: 100 },
        label: "Sub-batch 1A",
        status: "completed",
        sequence: 1,
        is_public: true,
      },
      {
        key: "sub_batch_1b",
        progress: { percent: 100 },
        label: "Sub-batch 1B",
        status: "completed",
        sequence: 2,
        is_public: true,
      },
    ],
  },
  buttons: [
    {
      value: "receipt",
      kind: "text",
      label: "View Batch Receipt",
      style: "default",
    },
  ],
  meta: {
    speed_rate: "50/hr",
    completed_at: "2026-07-26T14:30:00Z",
  },
};

export const mockFulfillmentDelivery2: DeliveryDto = {
  id: "del_ful_02",
  key: "batch_delivery_2",
  stage: "fulfillment",
  status: "processing",
  label: "Delivery Batch 2 (300 Units)",
  kind: "dispatch",
  name: "Batch 2 (In Progress)",
  is_public: true,
  note: "Delivery in progress. 160 of 300 units dispatched.",
  charge: {
    id: "chg_002",
    key: "delivery_units",
    label: "Delivery Units Fee",
    amount: { amount: "48.00", currency: "USD" },
    paid_amount: { amount: "24.00", currency: "USD" },
    balance_due: { amount: "24.00", currency: "USD" },
    status: "partially_paid",
    due_at: "2026-07-01T00:00:00Z",
    payments: [],
    buttons: [],
    meta: {},
  },
  progress: {
    current: 160,
    target: 300,
    percent: 53,
    unit: "units",
    label: "160 / 300 Units Dispatched",
    segments: [
      {
        key: "sub_batch_2a",
        progress: { percent: 100 },
        label: "Sub-batch 2A",
        status: "completed",
        sequence: 1,
        is_public: true,
      },
      {
        key: "sub_batch_2b",
        progress: { percent: 10 },
        label: "Sub-batch 2B",
        status: "processing",
        sequence: 2,
        is_public: true,
        charge: {
          id: "chg_seg_01",
          key: "segment_surcharge",
          label: "Speed Surcharge",
          amount: { amount: "5.00", currency: "USD" },
          status: "paid",
          paid_at: "2026-07-26T12:00:00Z",
          payments: [],
          buttons: [],
          meta: {},
        },
      },
    ],
  },
  buttons: [
    {
      value: "speed_up",
      kind: "text",
      label: "Boost Delivery Speed",
      style: "primary",
      next_action: {
        type: "popover",
        ui_entry: "speed_boost_form",
        ui_props: {},
        client_config: {},
      },
    },
    {
      value: "pause",
      kind: "text",
      label: "Pause Delivery",
      style: "default",
    },
  ],
  meta: {
    current_worker: "node-us-east-4",
    estimated_completion: "2026-07-26T19:00:00Z",
  },
};

export const mockInternalAdminDelivery: DeliveryDto = {
  id: "del_admin_09",
  key: "internal_fraud_and_telemetry_check",
  stage: "initialization",
  status: "processing",
  label: "Internal Risk & Telemetry Inspection",
  kind: "telemetry",
  name: "Internal Risk Check",
  is_public: false, // Internal Only!
  note: "Evaluating worker health and rate limit thresholds.",
  progress: {
    current: 2,
    target: 4,
    percent: 50,
    unit: "checks",
    label: "2 / 4 Checks Cleared",
    segments: [
      {
        key: "ip_reputation",
        progress: { percent: 100 },
        label: "IP Network Check",
        status: "completed",
        sequence: 1,
        is_public: false,
      },
      {
        key: "rate_limit_quota",
        progress: { percent: 50 },
        label: "Rate Limit Quota Check",
        status: "processing",
        sequence: 2,
        is_public: false,
      },
    ],
  },
  buttons: [
    {
      value: "bypass_check",
      kind: "text",
      label: "Bypass Risk Gate (Admin)",
      style: "danger",
    },
  ],
  meta: {
    risk_score: 0.12,
    flagged: false,
    internal_node_ip: "10.0.4.19",
  },
};

/**
 * Mock Plan Data
 */
export const mockClientPlan: PlanDto = {
  id: "plan_88392",
  key: "twitter_retweets_plan_v1",
  order_id: "ordref_ux3egd0bthbaudyx",
  revision: 1,
  status: "active",
  state: {
    quantity: 900,
    service_name: "Twitter Retweets - UI Test",
    speed: "normal",
  },
  deliveries: [
    mockInitializationDelivery,
    mockFulfillmentDelivery1,
    mockFulfillmentDelivery2,
  ],
  buttons: [
    {
      value: "reorder",
      kind: "text",
      label: "Order Again",
      style: "primary",
    },
    {
      value: "download_receipt",
      kind: "text",
      label: "View Receipt",
      style: "default",
    },
  ],
  meta: {
    created_at: "2026-06-12T10:00:00Z",
  },
};

export const mockAdminPlan: PlanDto = {
  id: "plan_88392",
  key: "twitter_retweets_plan_v1",
  order_id: "ordref_ux3egd0bthbaudyx",
  revision: 2,
  status: "active",
  charge: {
    id: "chg_001",
    key: "platform_fee",
    label: "Platform Service Fee",
    amount: { amount: "12.00", currency: "USD" },
    status: "paid",
    paid_at: "2026-06-12T10:05:00Z",
    payments: [],
    buttons: [],
    meta: {},
  },
  state: {
    quantity: 900,
    service_name: "Twitter Retweets - UI Test",
    handler_service_id: "srv_tw_retweets_premium",
    max_retries: 3,
    retry_count: 1,
  },
  deliveries: [
    mockInitializationDelivery,
    mockInternalAdminDelivery,
    mockFulfillmentDelivery1,
    mockFulfillmentDelivery2,
  ],
  buttons: [
    {
      value: "force_complete",
      kind: "text",
      label: "Force Complete Plan",
      style: "danger",
    },
    {
      value: "trigger_resync",
      kind: "text",
      label: "Sync Status with Handler",
      style: "primary",
    },
  ],
  meta: {
    handler_key: "smm_provider_alpha",
    last_synced_at: "2026-07-26T16:50:00Z",
    debug_mode: true,
  },
};

/**
 * Mock Order Management Data
 */
export const mockOrderManagement: OrderManagementDto = {
  order_id: "ordref_ux3egd0bthbaudyx",
  sections: [
    {
      id: "sec_overview",
      title: "Order Fulfillment Overview",
      type: "details",
      meta: {},
    },
  ],
  warnings: [
    {
      id: "warn_delay_notice",
      severity: "warning",
      title: "Provider Rate Limit Delay",
      message: "Delivery batch 2 is experiencing minor delays due to target platform rate limiting.",
      meta: {},
    },
  ],
  instructions: [
    {
      id: "inst_keep_account_public",
      title: "Important Delivery Instructions",
      steps: [
        "Do not change your social media account handle while delivery is active.",
        "Ensure your profile remains set to public visibility until all batches finish.",
      ],
      meta: {},
    },
  ],
  permissions: [
    { action: "cancel_order", allowed: false, reason: "Order is already in fulfillment phase.", meta: {} },
    { action: "request_speedup", allowed: true, meta: {} },
    { action: "refill_request", allowed: true, meta: {} },
  ],
  actions: [],
  refresh_policy: { interval_seconds: 30 },
  meta: {},
};
/**
 * Mock Charge Data
 * Reflects PHP Charge::toArray() and OrderChargeState::toArray() structure
 */
export const mockChargePlatformFee: ChargeDto = {
  id: "chg_001",
  key: "platform_fee",
  label: "Platform Service Fee",
  target: {
    type: "plan",
    key: "twitter_retweets_plan_v1",
    meta: {},
  },
  amount: { amount: "12.00", currency: "USD" },
  status: "paid",
  paid_amount: { amount: "12.00", currency: "USD" },
  balance_due: { amount: "0.00", currency: "USD" },
  payments: [
    {
      key: "pay_pf_001",
      amount: { amount: "12.00", currency: "USD" },
      status: "paid",
      paid_at: "2026-06-12T10:05:00Z",
      method: "card",
      reference: "ch_stripe_abc123",
    },
  ],
  due_at: "2026-06-12T10:00:00Z",
  paid_at: "2026-06-12T10:05:00Z",
  buttons: [
    { value: "view_receipt", kind: "text", label: "Receipt", style: "default" },
  ],
  meta: {},
};

export const mockChargeDeliveryUnits: ChargeDto = {
  id: "chg_002",
  key: "delivery_units",
  label: "Delivery Units — 900 Retweets",
  target: {
    type: "delivery",
    key: "batch_delivery_2",
    parent: { type: "plan", key: "twitter_retweets_plan_v1", meta: {} },
    meta: {},
  },
  amount: { amount: "48.00", currency: "USD" },
  status: "partially_paid",
  paid_amount: { amount: "24.00", currency: "USD" },
  balance_due: { amount: "24.00", currency: "USD" },
  payments: [
    {
      key: "pay_du_001",
      amount: { amount: "24.00", currency: "USD" },
      status: "paid",
      paid_at: "2026-06-12T10:05:00Z",
      method: "wallet",
      reference: "wlt_txn_xyz789",
    },
    {
      key: "pay_du_002",
      amount: { amount: "24.00", currency: "USD" },
      status: "pending",
      method: "card",
    },
  ],
  due_at: "2026-07-01T00:00:00Z",
  buttons: [
    { value: "pay_now", kind: "text", label: "Pay Balance", style: "primary" },
    { value: "request_invoice", kind: "text", label: "Invoice", style: "default" },
  ],
  meta: { installment: true },
};

export const mockOrderChargeState: OrderChargeStateDto = {
  order_id: "ordref_ux3egd0bthbaudyx",
  charges: [
    {
      id: "chg_001",
      key: "platform_fee",
      status: "paid",
      amount: { amount: "12.00", currency: "USD" },
      paid: { amount: "12.00", currency: "USD" },
      balance_due: { amount: "0.00", currency: "USD" },
      satisfied: true,
      paid_at: "2026-06-12T10:05:00Z",
      target: { type: "plan", key: "twitter_retweets_plan_v1", meta: {} },
      meta: {},
    },
    {
      id: "chg_002",
      key: "delivery_units",
      status: "partially_paid",
      amount: { amount: "48.00", currency: "USD" },
      paid: { amount: "24.00", currency: "USD" },
      balance_due: { amount: "24.00", currency: "USD" },
      satisfied: false,
      target: { type: "delivery", key: "batch_delivery_2", meta: {} },
      meta: {},
    },
  ],
  total: { amount: "60.00", currency: "USD" },
  paid: { amount: "36.00", currency: "USD" },
  balance_due: { amount: "24.00", currency: "USD" },
  satisfied: false,
  meta: {},
};
