import { Preloader } from '../../shared/components/Preloader';

interface PortalStateProps {
  title?: string;
  message: string;
  loading?: boolean;
}

export function PortalState({ title, message, loading }: PortalStateProps) {
  return (
    <main className="sbay-state" role={loading ? undefined : 'alert'}>
      {loading ? <Preloader label={message} /> : null}
      {title ? <h1>{title}</h1> : null}
      {!loading ? <p>{message}</p> : null}
      {!loading ? (
        <button type="button" onClick={() => window.location.reload()}>
          Try again
        </button>
      ) : null}
    </main>
  );
}
