interface PreloaderProps {
  label?: string;
  compact?: boolean;
}

export function Preloader({ label = 'Loading…', compact = false }: PreloaderProps) {
  return (
    <div className={`sbay-preloader${compact ? ' is-compact' : ''}`} role="status" aria-live="polite">
      <span className="sbay-preloader__spinner" aria-hidden="true" />
      <span>{label}</span>
    </div>
  );
}
