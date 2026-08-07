interface PortalStateProps {
  title?: string;
  message: string;
  loading?: boolean;
}

export function PortalState({ title, message, loading }: PortalStateProps) {
  return (
    <main className="sbay-state" role={loading ? undefined : 'alert'}>
      {loading ? <div className="sbay-loader" /> : null}
      {title ? <h1>{title}</h1> : null}
      <p>{message}</p>
      {!loading ? (
        <button type="button" onClick={() => window.location.reload()}>
          Try again
        </button>
      ) : null}
    </main>
  );
}
