/**
 * Money DTO
 * Traced to PHP Class: Elqora\Dgp\Money\Money
 */
export interface MoneyDto {
  amount: string;
  currency: string;
}

/**
 * Action Button DTO
 * Traced to PHP Class: Elqora\Dgp\Actions\ActionButton
 */
export interface ActionButtonDto {
  value: string;
  kind: 'text' | 'icon' | 'text_icon';
  label?: string | null;
  icon?: string | null;
  style: 'default' | 'primary' | 'danger';
  tooltip?: string | null;
  disabled?: boolean | null;
  disabled_reason?: string | null;
  meta?: Record<string, any> | null;
  next_action?: NextActionDto | null;
}

/**
 * Action Target DTO
 * Traced to PHP Class: Elqora\Dgp\Actions\ActionTarget
 */
export interface ActionTargetDto {
  type: 'order' | 'plan' | 'initialization_delivery' | 'fulfillment_delivery' | 'charge' | 'management' | string;
  id: string | number;
  key?: string | null;
  meta?: Record<string, any> | null;
}

/**
 * Popover Specification DTO
 * Traced to PHP Class: Elqora\Dgp\Actions\PopoverSpec
 */
export interface PopoverSpecDto {
  size: string;
  placement: string;
  alignment: string;
  offset: number;
  collision_handler: boolean;
  dismissible: boolean;
  close_on_outside_interaction: boolean;
  close_on_escape: boolean;
  modal: boolean;
  width_constraints?: string | null;
  height_constraints?: string | null;
}

/**
 * Popup Specification DTO
 * Traced to PHP Class: Elqora\Dgp\Actions\PopupSpec
 */
export interface PopupSpecDto {
  variant: 'modal' | 'drawer' | 'sheet' | string;
  size: 'sm' | 'md' | 'lg' | 'xl' | 'full' | string;
  dismissible: boolean;
  close_on_backdrop: boolean;
  close_on_escape: boolean;
  placement: string;
}

/**
 * Inline Action DTO
 */
export interface InlineActionDto {
  type: 'inline';
  ui_entry: string;
  ui_props: Record<string, any>;
  client_config: Record<string, any>;
  container_key?: string | null;
  meta: Record<string, any>;
}

/**
 * Instructions Action DTO
 */
export interface InstructionsActionDto {
  type: 'instructions';
  title: string;
  steps: string[];
  description?: string | null;
  meta: Record<string, any>;
}

/**
 * Popover Action DTO
 */
export interface PopoverActionDto {
  type: 'popover';
  ui_entry: string;
  ui_props: Record<string, any>;
  client_config: Record<string, any>;
  anchor?: string | null;
  popover?: PopoverSpecDto | null;
}

/**
 * Popup Action DTO
 */
export interface PopupActionDto {
  type: 'popup';
  ui_entry: string;
  ui_props: Record<string, any>;
  client_config: Record<string, any>;
  popup?: PopupSpecDto | null;
}

/**
 * QR Code Action DTO
 */
export interface QrCodeActionDto {
  type: 'qr_code';
  data: string;
  label?: string | null;
  description?: string | null;
  meta: Record<string, any>;
}

/**
 * Redirect Action DTO
 */
export interface RedirectActionDto {
  type: 'redirect';
  url: string;
  external: boolean;
  label?: string | null;
  meta: Record<string, any>;
}

/**
 * Custom Action DTO
 */
export interface CustomActionDto {
  type: 'custom';
  custom_type: string;
  payload: Record<string, any>;
  meta: Record<string, any>;
}

/**
 * Next Action Union
 * Traced to PHP Contract: Elqora\Dgp\Actions\Contracts\NextAction
 */
export type NextActionDto =
  | InlineActionDto
  | InstructionsActionDto
  | PopoverActionDto
  | PopupActionDto
  | QrCodeActionDto
  | RedirectActionDto
  | CustomActionDto;

/**
 * Delivery Progress Segment DTO
 * Traced to PHP Class: Elqora\Dgp\Deliveries\DeliveryProgressSegment
 */
export interface DeliveryProgressSegmentDto {
  key: string;
  progress: Omit<DeliveryProgressDto, 'segments'>;
  label?: string | null;
  status?: string | null;
  sequence?: number | null;
  meta?: Record<string, any> | null;
  is_public: boolean;
  charge?: ChargeDto | ChargeStatusViewDto | null;
  charges?: (ChargeDto | ChargeStatusViewDto)[] | null;
}

/**
 * Delivery Progress DTO
 * Traced to PHP Class: Elqora\Dgp\Deliveries\DeliveryProgress
 */
export interface DeliveryProgressDto {
  current?: number | string | null;
  target?: number | string | null;
  percent?: number | null;
  unit?: string | null;
  label?: string | null;
  meta?: Record<string, any> | null;
  segments: DeliveryProgressSegmentDto[];
}

/**
 * Delivery DTO
 * Traced to PHP Class: Elqora\Dgp\Deliveries\Delivery
 */
export interface DeliveryDto {
  id?: string | number | null;
  key: string;
  stage: 'initialization' | 'fulfillment' | string;
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'canceled' | string;
  label: string;
  kind: string;
  name?: string | null;
  is_public: boolean;
  note?: string | null;
  progress?: DeliveryProgressDto | null;
  plan_id?: string | number | null;
  start_id?: string | number | null;
  buttons: ActionButtonDto[];
  next_action?: NextActionDto | null;
  meta: Record<string, any>;
  charge?: ChargeDto | ChargeStatusViewDto | null;
  charges?: (ChargeDto | ChargeStatusViewDto)[] | null;
}

/**
 * Charge Target DTO
 * Traced to PHP Class: Elqora\Dgp\Charges\ChargeTarget
 */
export interface ChargeTargetDto {
  type: 'plan' | 'segment' | 'delivery' | string;
  id?: string | number | null;
  key?: string | null;
  parent?: ChargeTargetDto | null;
  meta: Record<string, any>;
}

/**
 * Charge Payment DTO
 * Traced to PHP Class: Elqora\Dgp\Charges\ChargePayment
 */
export interface ChargePaymentDto {
  key: string;
  amount: MoneyDto;
  status: 'pending' | 'paid' | 'failed' | 'refunded' | 'canceled' | string;
  paid_at?: string | null;
  method?: string | null;
  reference?: string | null;
  meta?: Record<string, any> | null;
}

/**
 * Charge DTO
 * Traced to PHP Class: Elqora\Dgp\Charges\Charge
 */
export interface ChargeDto {
  id?: string | number | null;
  key: string;
  target?: ChargeTargetDto | null;
  label: string;
  amount: MoneyDto;
  status: 'pending' | 'invoiced' | 'partially_paid' | 'paid' | 'failed' | 'refunded' | 'canceled' | string;
  paid_amount?: MoneyDto | null;
  balance_due?: MoneyDto | null;
  payments: ChargePaymentDto[];
  due_at?: string | null;
  paid_at?: string | null;
  buttons: ActionButtonDto[];
  next_action?: NextActionDto | null;
  meta: Record<string, any>;
}

/**
 * Charge Status View DTO (summary — used inside OrderChargeState)
 * Traced to PHP Class: Elqora\Dgp\Charges\ChargeStatusView
 */
export interface ChargeStatusViewDto {
  id: string | number;
  key: string;
  status: 'pending' | 'invoiced' | 'partially_paid' | 'paid' | 'failed' | 'refunded' | 'canceled' | string;
  amount: MoneyDto;
  paid: MoneyDto;
  balance_due: MoneyDto;
  satisfied: boolean;
  paid_at?: string | null;
  target?: ChargeTargetDto | null;
  meta: Record<string, any>;
}

/**
 * Order Charge State DTO (top-level aggregate for an order's charges)
 * Traced to PHP Class: Elqora\Dgp\Charges\OrderChargeState
 */
export interface OrderChargeStateDto {
  order_id: string | number;
  charges: ChargeStatusViewDto[];
  total: MoneyDto;
  paid: MoneyDto;
  balance_due: MoneyDto;
  satisfied: boolean;
  meta: Record<string, any>;
}

/**
 * Scoreboard Item DTO
 */
export interface ScoreboardItemDto {
  key: string;
  value: any;
  title?: string | null;
  description?: string | null;
  unit?: string | null;
  meta: Record<string, any>;
}

/**
 * Scoreboard DTO
 */
export interface ScoreboardDto {
  items: ScoreboardItemDto[];
  meta: Record<string, any>;
}

/**
 * Leaderboard Entry DTO
 */
export interface LeaderboardEntryDto {
  service_id: string | number;
  rank: number;
  score?: number | null;
  title?: string | null;
  meta: Record<string, any>;
}

/**
 * Leaderboard DTO
 */
export interface LeaderboardDto {
  entries: LeaderboardEntryDto[];
  meta: Record<string, any>;
}

/**
 * Chart DTO
 */
export interface ChartDto {
  key: string;
  type: string;
  family: string;
  title: string;
  description?: string | null;
  data: any;
  meta?: Record<string, any> | null;
}

/**
 * Analysis DTO
 */
export interface AnalysisDto {
  analysis_key: string;
  chart: ChartDto;
}

/**
 * Plan DTO
 * Traced to PHP Class: Elqora\Dgp\Runtime\Plan
 */
export interface PlanDto {
  id?: string | number | null;
  key: string;
  state: Record<string, any>;
  status: 'draft' | 'active' | 'completed' | 'failed' | 'cancelled' | 'abandoned' | string;
  deliveries: DeliveryDto[];
  buttons: ActionButtonDto[];
  next_action?: NextActionDto | null;
  meta: Record<string, any>;
  revision: number;
  order_id?: string | number | null;
  charge?: ChargeDto | ChargeStatusViewDto | null;
  charges?: (ChargeDto | ChargeStatusViewDto)[] | null;
}

/**
 * Start Result DTO
 * Traced to PHP Class: Elqora\Dgp\Runtime\StartResult
 */
export interface StartResultDto {
  id?: string | number | null;
  key: string;
  state: Record<string, any>;
  status: 'pending' | 'running' | 'completed' | 'failed' | 'cancelled' | 'abandoned' | string;
  deliveries: DeliveryDto[];
  buttons: ActionButtonDto[];
  next_action?: NextActionDto | null;
  meta: Record<string, any>;
  plan_id?: string | number | null;
  plan_key?: string | null;
  revision: number;
}

/**
 * Order Runtime View DTO
 * Traced to PHP Class: Elqora\Dgp\Runtime\OrderRuntimeView
 */
export interface OrderRuntimeViewDto {
  order_id: string | number;
  plans: PlanDto[];
  start_results: StartResultDto[];
  current_plan?: PlanDto | null;
  current_start_result?: StartResultDto | null;
  meta: Record<string, any>;
}

/**
 * Management Section DTO
 */
export interface ManagementSectionDto {
  id: string;
  title: string;
  type: 'default' | 'details' | 'debug' | 'sidebar' | string;
  description?: string | null;
  meta: Record<string, any>;
}

/**
 * Management Warning DTO
 */
export interface ManagementWarningDto {
  id: string;
  message: string;
  severity: 'info' | 'warning' | 'error' | string;
  title?: string | null;
  meta: Record<string, any>;
}

/**
 * Management Instruction DTO
 */
export interface ManagementInstructionDto {
  id: string;
  title: string;
  steps: string[];
  description?: string | null;
  meta: Record<string, any>;
}

/**
 * Management Permission DTO
 */
export interface ManagementPermissionDto {
  action: string;
  allowed: boolean;
  reason?: string | null;
  meta: Record<string, any>;
}

/**
 * Order Management DTO
 * Traced to PHP Class: Elqora\Dgp\Management\OrderManagement
 */
export interface OrderManagementDto {
  order_id: string | number;
  sections: ManagementSectionDto[];
  warnings: ManagementWarningDto[];
  instructions: ManagementInstructionDto[];
  permissions: ManagementPermissionDto[];
  actions: NextActionDto[];
  refresh_policy: Record<string, any>;
  meta: Record<string, any>;
}

/**
 * Service Meta DTO
 */
export interface ServiceMetaDto {
  raw: Record<string, any>;
  derived: Record<string, any>;
}

/**
 * Handler Service DTO
 */
export interface HandlerServiceDto {
  id: string | number;
  name: string;
  description?: string | null;
  category?: string | null;
  rate?: number | null;
  min: number;
  max: number;
  capabilities: string[];
  meta: ServiceMetaDto;
  state: 'enabled' | 'locked' | 'disabled' | string;
  state_reason?: string | null;
}

/**
 * Delivery Reference DTO
 */
export interface DeliveryReferenceDto {
  id?: string | number | null;
  key?: string | null;
}

/**
 * Audit Record DTO
 */
export interface AuditRecordDto {
  id?: string | number | null;
  key: string;
  level: 'info' | 'notice' | 'warning' | 'error' | 'critical' | string;
  message: string;
  occurred_at: string;
  order_id?: string | number | null;
  delivery?: DeliveryReferenceDto | null;
  category?: string | null;
  code?: string | null;
  context?: Record<string, any> | null;
  meta?: Record<string, any> | null;
}

/**
 * Private Asset DTO
 */
export interface PrivateAssetDto {
  key: string;
  name: string;
  media_type: string;
  size?: number | null;
  expires_at?: string | null;
  meta: Record<string, any>;
}

/**
 * Handler Balance DTO
 */
export interface HandlerBalanceDto {
  kind: 'finite' | 'unlimited' | string;
  available?: MoneyDto | null;
  reserved?: MoneyDto | null;
  total?: MoneyDto | null;
  checked_at?: string | null;
  meta: Record<string, any>;
}

/**
 * Handler Health DTO
 */
export interface HandlerHealthDto {
  status: 'ok' | 'degraded' | 'fail' | string;
  message?: string | null;
  checked_at?: string | null;
  meta: Record<string, any>;
}

/**
 * Analysis Definition DTO
 */
export interface AnalysisDefinitionDto {
  key: string;
  title: string;
  description?: string | null;
}

/**
 * Scoreboard Item Definition DTO
 */
export interface ScoreboardItemDefinitionDto {
  key: string;
  title: string;
  description?: string | null;
}

/**
 * Handler Manifest DTO
 */
export interface HandlerManifestDto {
  key: string;
  name: string;
  version: string;
  capabilities: string[];
  supported_service_schema_versions: string[];
  synchronization_modes: string[];
  webhook_support: boolean;
  supported_next_action_types: string[];
  limitations: Record<string, any>;
  feature_flags: Record<string, any>;
  meta?: Record<string, any> | null;
  analyses: AnalysisDefinitionDto[];
  scoreboard_items: ScoreboardItemDefinitionDto[];
  provides_leaderboard: boolean;
}
