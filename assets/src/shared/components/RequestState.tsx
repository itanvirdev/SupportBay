import { Preloader } from './Preloader';

interface RequestStateProps {
  title: string;
  message: string;
  loading?: boolean;
  compact?: boolean;
  retry?: () => void;
  actionLabel?: string;
  action?: () => void;
  kind?: 'error' | 'empty';
}

export function RequestState({
  title,
  message,
  loading = false,
  compact = false,
  retry,
  actionLabel,
  action,
  kind,
}: RequestStateProps) {
  if (loading) {
    return <Preloader label={message} compact={compact} />;
  }

  const resolvedKind = kind ?? (retry ? 'error' : 'empty');

  return (
    <div className={`sbay-request-state is-${resolvedKind}${compact ? ' is-compact' : ''}`} role={resolvedKind === 'error' ? 'alert' : 'status'}>
      <span className="sbay-request-state__icon" aria-hidden="true">{resolvedKind === 'error' ? '!' : '○'}</span>
      <div>
        <strong>{title}</strong>
        <p>{message}</p>
      </div>
      {retry ? <button type="button" onClick={retry}>Try again</button> : null}
      {action && actionLabel ? <button type="button" onClick={action}>{actionLabel}</button> : null}
    </div>
  );
}
