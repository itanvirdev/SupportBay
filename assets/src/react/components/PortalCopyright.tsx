import { Fragment } from 'react';
import { getConfig } from '../core/config';

export function PortalCopyright() {
  const config = getConfig();
  const currentYear = new Date().getFullYear();
  const copyright = config.footerCopyrightText.split('{year}').join(String(currentYear));
  const segments = copyright.split('{site_name}');

  return (
    <p className="sbay-auth-copyright">
      <span>
        {segments.map((segment, index) => (
          <Fragment key={`${segment}-${index}`}>
            {segment}
            {index < segments.length - 1 ? <a href={config.homeUrl}>{config.siteName}</a> : null}
          </Fragment>
        ))}
      </span>
      {!config.removePoweredByBranding ? <>
        <span aria-hidden="true"> | </span>
        <span>
          Powered by{' '}
          <a href="https://supportbay.com/" target="_blank" rel="noopener noreferrer">
            SupportBay
          </a>
        </span>
      </> : null}
    </p>
  );
}
