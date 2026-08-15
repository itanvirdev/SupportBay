import { useState } from 'react';
import { NotificationTemplateWorkspace } from './NotificationTemplateWorkspace';
import { ProviderWorkspace } from './ProviderWorkspace';
import { NotificationLogWorkspace } from './NotificationLogWorkspace';

type SettingsSection = 'notifications' | 'logs' | 'integrations';

export function SettingsWorkspace() {
  const [section, setSection] = useState<SettingsSection>('notifications');

  return <section className="sbay-settings-workspace">
    <nav aria-label="Settings sections">
      <span>General</span><span>Security</span><span>User Roles</span><span>Categories</span>
      <button type="button" className={section === 'notifications' ? 'is-active' : ''} onClick={() => setSection('notifications')}>Email Notifications</button>
      <button type="button" className={section === 'logs' ? 'is-active' : ''} onClick={() => setSection('logs')}>Delivery Logs</button>
      <button type="button" className={section === 'integrations' ? 'is-active' : ''} onClick={() => setSection('integrations')}>Integrations</button>
    </nav>
    <div>{section === 'notifications' ? <NotificationTemplateWorkspace /> : section === 'logs' ? <NotificationLogWorkspace /> : <ProviderWorkspace />}</div>
  </section>;
}
