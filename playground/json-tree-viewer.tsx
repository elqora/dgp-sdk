import React, { useState } from "react";

interface JsonTreeViewerProps {
  data: any;
  title?: string;
  initialExpandedDepth?: number;
  className?: string;
}

export function JsonTreeViewer({
  data,
  title = "JSON DTO Payload",
  initialExpandedDepth = 2,
  className = "",
}: JsonTreeViewerProps) {
  const [expandAllKey, setExpandAllKey] = useState<number>(0);
  const [isAllExpanded, setIsAllExpanded] = useState<boolean>(initialExpandedDepth > 1);
  const [copied, setCopied] = useState<boolean>(false);

  const handleCopy = () => {
    navigator.clipboard.writeText(JSON.stringify(data, null, 2));
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const handleToggleExpandAll = () => {
    setIsAllExpanded(!isAllExpanded);
    setExpandAllKey((prev) => prev + 1);
  };

  return (
    <div className={`border border-border/80 rounded-xl bg-card overflow-hidden text-xs font-mono shadow-xs ${className}`}>
      {/* Header Bar */}
      <div className="bg-muted/40 px-3 py-2 border-b border-border/60 flex items-center justify-between font-sans">
        <span className="font-bold text-xs text-foreground uppercase tracking-wider">
          {title}
        </span>
        <div className="flex items-center gap-2">
          <button
            onClick={handleToggleExpandAll}
            className="px-2 py-0.5 text-[11px] rounded bg-muted hover:bg-muted/80 text-muted-foreground hover:text-foreground transition font-mono border border-border/40"
          >
            {isAllExpanded ? "Collapse All" : "Expand All"}
          </button>
          <button
            onClick={handleCopy}
            className="px-2 py-0.5 text-[11px] rounded bg-primary/10 hover:bg-primary/20 text-primary transition font-mono font-medium border border-primary/20"
          >
            {copied ? "✓ Copied!" : "Copy JSON"}
          </button>
        </div>
      </div>

      {/* Tree Content */}
      <div className="p-3 overflow-auto max-h-[500px] leading-relaxed select-text">
        <JsonNode
          key={expandAllKey}
          value={data}
          isLast={true}
          defaultExpanded={isAllExpanded}
          depth={0}
        />
      </div>
    </div>
  );
}

interface JsonNodeProps {
  label?: string;
  value: any;
  isLast: boolean;
  defaultExpanded: boolean;
  depth: number;
}

function JsonNode({ label, value, isLast, defaultExpanded, depth }: JsonNodeProps) {
  const [expanded, setExpanded] = useState<boolean>(defaultExpanded || depth < 2);

  const isObject = value !== null && typeof value === "object" && !Array.isArray(value);
  const isArray = Array.isArray(value);
  const isComplex = isObject || isArray;

  const comma = isLast ? "" : ",";

  if (!isComplex) {
    let valueColor = "text-emerald-400";
    if (typeof value === "number") valueColor = "text-sky-400";
    if (typeof value === "boolean") valueColor = "text-amber-400";
    if (value === null) valueColor = "text-rose-400";

    return (
      <div className="pl-4 py-0.5 whitespace-nowrap">
        {label && <span className="text-muted-foreground mr-1">"{label}":</span>}
        <span className={valueColor}>
          {typeof value === "string" ? `"${value}"` : String(value)}
        </span>
        <span className="text-muted-foreground">{comma}</span>
      </div>
    );
  }

  const keys = isObject ? Object.keys(value) : [];
  const itemCount = isArray ? value.length : keys.length;
  const openBracket = isArray ? "[" : "{";
  const closeBracket = isArray ? "]" : "}";

  return (
    <div className="py-0.5">
      <div className="flex items-center gap-1 group">
        <button
          onClick={() => setExpanded(!expanded)}
          className="w-4 h-4 rounded text-[10px] text-muted-foreground hover:bg-muted flex items-center justify-center transition"
        >
          {expanded ? "▼" : "▶"}
        </button>

        {label && <span className="text-muted-foreground">"{label}": </span>}

        <span
          onClick={() => setExpanded(!expanded)}
          className="cursor-pointer font-bold text-foreground hover:underline decoration-dashed"
        >
          {openBracket}
        </span>

        {!expanded && (
          <span
            onClick={() => setExpanded(!expanded)}
            className="cursor-pointer text-[10px] text-muted-foreground bg-muted/60 px-1.5 py-0.5 rounded font-sans italic"
          >
            {itemCount} {isArray ? "items" : "keys"}
          </span>
        )}

        {!expanded && (
          <span className="text-foreground font-bold">
            {closeBracket}
            {comma}
          </span>
        )}
      </div>

      {expanded && (
        <div className="border-l border-border/40 ml-2.5 pl-2 my-0.5">
          {isArray
            ? value.map((item: any, idx: number) => (
                <JsonNode
                  key={idx}
                  value={item}
                  isLast={idx === value.length - 1}
                  defaultExpanded={defaultExpanded}
                  depth={depth + 1}
                />
              ))
            : keys.map((key: string, idx: number) => (
                <JsonNode
                  key={key}
                  label={key}
                  value={value[key]}
                  isLast={idx === keys.length - 1}
                  defaultExpanded={defaultExpanded}
                  depth={depth + 1}
                />
              ))}
        </div>
      )}

      {expanded && (
        <div className="pl-4 text-foreground font-bold">
          {closeBracket}
          {comma}
        </div>
      )}
    </div>
  );
}
