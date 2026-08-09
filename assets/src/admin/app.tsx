import { StrictMode, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { adminGet } from './api';
import { getAdminConfig } from './config';
import './styles.scss';

interface DashboardSummary {
  tickets: number;
  customers: number;
  departments: number;
  providers: number;
  verifications: number;
}

const resources = ['Tickets', 'Customers', 'Departments', 'Providers', 'Verifications'];

function total(meta: Record<string, unknown>, data: unknown[]): number {
  return typeof meta.total === 'number' ? meta.total : data.length;
}

function AdminApp() {
  const config = getAdminConfig();
  const [summary, setSummary] = useState<DashboardSummary | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    Promise.all([
      adminGet<unknown[]>('tickets?per_page=1'),
      adminGet<unknown[]>('customers?per_page=1'),
      adminGet<unknown[]>('departments'),
      adminGet<unknown[]>('providers'),
      adminGet<unknown[]>('verifications?per_page=1'),
    ]).then(([tickets, customers, departments, providers, verifications]) => {
      setSummary({
        tickets: total(tickets.meta, tickets.data),
        customers: total(customers.meta, customers.data),
        departments: total(departments.meta, departments.data),
        providers: total(providers.meta, providers.data),
        verifications: total(verifications.meta, verifications.data),
      });
    }).catch((reason: unknown) => {
      setError(reason instanceof Error ? reason.message : 'Dashboard data could not be loaded.');
    });
  }, []);

  return (
    <div className="sbay-admin-shell">
      <aside className="sbay-admin-sidebar">
        <div className="sbay-admin-brand"><span>S</span>SupportBay</div>
        <nav aria-label="SupportBay administration">
          {resources.map((resource, index) => (
            <a className={index === 0 ? 'is-active' : undefined} href="#" key={resource}>{resource}</a>
          ))}
        </nav>
      </aside>
      <main className="sbay-admin-main">
        <header>
          <div><small>{config.siteName}</small><h1>Support overview</h1></div>
          <span>{config.userName || 'Administrator'}</span>
        </header>
        <p className="sbay-admin-intro">Monitor the support operation from one workspace.</p>
        {error ? <div className="sbay-admin-error" role="alert">{error}</div> : null}
        <section className="sbay-admin-stats" aria-label="SupportBay totals">
          {resources.map((resource) => {
            const key = resource.toLowerCase() as keyof DashboardSummary;
            return (
              <article key={resource}>
                <span>{resource}</span>
                <strong>{summary ? summary[key] : '—'}</strong>
                <small>{summary ? 'Current total' : 'Loading…'}</small>
              </article>
            );
          })}
        </section>
        <section className="sbay-admin-panel">
          <div><small>Workspace status</small><h2>Administrator API connected</h2></div>
          <p>Ticket operations, customer records, departments, providers, and purchase verifications are ready for the next workspace screens.</p>
        </section>
      </main>
    </div>
  );
}

const root = document.getElementById('supportbay-admin-app');

if (root) {
  createRoot(root).render(<StrictMode><AdminApp /></StrictMode>);
}
