import { useState } from 'react';
import { NotificationTemplateWorkspace } from './NotificationTemplateWorkspace';
import { ProviderWorkspace } from './ProviderWorkspace';

type SettingsSection = 'notifications' | 'integrations';

export function SettingsWorkspace() {
  const [section, setSection] = useState<SettingsSection>('notifications');

  return <section className="sbay-settings-workspace">
    <nav aria-label="Settings sections">
      <span>General</span><span>Security</span><span>User Roles</span><span>Categories</span>
      <button type="button" className={section === 'notifications' ? 'is-active' : ''} onClick={() => setSection('notifications')}>Email Notifications</button>
      <button type="button" className={section === 'integrations' ? 'is-active' : ''} onClick={() => setSection('integrations')}>Integrations</button>
    </nav>
    <div>{section === 'notifications' ? <NotificationTemplateWorkspace /> : <ProviderWorkspace />}</div>
  </section>;
}
