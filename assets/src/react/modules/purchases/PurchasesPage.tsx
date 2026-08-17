import { useEffect, useState } from 'react';
import { portalApi } from '../../api/portal';
import type { PortalVerification } from '../../api/types';
import { formatDate } from '../../core/date';
import { Preloader } from '../../../shared/components/Preloader';

export function PurchasesPage() {
  const [purchases, setPurchases] = useState<PortalVerification[] | null>(null);

  useEffect(() => {
    portalApi.verifications().then(setPurchases);
  }, []);

  return (
    <section className="sbay-page">
      <header className="sbay-page__header">
        <div>
          <span className="sbay-kicker">Product access</span>
          <h1>Verified purchases</h1>
          <p>Your connected products, licenses, and support coverage.</p>
        </div>
        <span className="sbay-page__total">{purchases?.length ?? 0} connected</span>
      </header>

      {!purchases ? (
        <Preloader label="Loading verified purchases…" />
      ) : purchases.length === 0 ? (
        <p className="sbay-empty">No verified purchases are connected yet.</p>
      ) : (
        <div className="sbay-purchase-grid">
          {purchases.map((purchase) => (
            <article key={purchase.id}>
              <div className="sbay-purchase-card__top">
                <span className="sbay-product-mark">
                  {(purchase.product_name ?? 'P').charAt(0)}
                </span>
                <span className="sbay-verified">{purchase.status}</span>
              </div>
              <span className="sbay-kicker">{purchase.provider}</span>
              <h2>{purchase.product_name ?? 'Verified product'}</h2>
              <p>{purchase.license_type ?? 'License information unavailable'}</p>
              <dl>
                <div><dt>Purchased</dt><dd>{formatDate(purchase.purchased_at)}</dd></div>
                <div><dt>Support until</dt><dd>{formatDate(purchase.support_expires_at)}</dd></div>
              </dl>
            </article>
          ))}
        </div>
      )}
    </section>
  );
}
