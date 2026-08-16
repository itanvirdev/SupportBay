import { useState } from 'react';
import { NotificationTemplateWorkspace } from './NotificationTemplateWorkspace';
import { ProviderWorkspace } from './ProviderWorkspace';
import { NotificationLogWorkspace } from './NotificationLogWorkspace';
import { TicketSlaWorkspace } from './TicketSlaWorkspace';
import { SavedReplyWorkspace } from './SavedReplyWorkspace';
import { CategoryWorkspace } from './CategoryWorkspace';
import { getAdminConfig } from './config';

type SettingsSection = 'categories' | 'sla' | 'saved-replies' | 'notifications' | 'logs' | 'integrations';

export function SettingsWorkspace() {
  const config = getAdminConfig();
  const [section, setSection] = useState<SettingsSection>('notifications');

  return <section className="sbay-settings-workspace">
    <nav aria-label="Settings sections">
      <span>General</span><span>Security</span><span>User Roles</span>
      {config.canManageCategories?<button type="button" className={section === 'categories' ? 'is-active' : ''} onClick={() => setSection('categories')}>Categories</button>:null}
      {config.canManageSavedReplies?<button type="button" className={section === 'saved-replies' ? 'is-active' : ''} onClick={() => setSection('saved-replies')}>Saved Replies</button>:null}
      <button type="button" className={section === 'sla' ? 'is-active' : ''} onClick={() => setSection('sla')}>Ticket SLA</button>
      <button type="button" className={section === 'notifications' ? 'is-active' : ''} onClick={() => setSection('notifications')}>Email Notifications</button>
      <button type="button" className={section === 'logs' ? 'is-active' : ''} onClick={() => setSection('logs')}>Delivery Logs</button>
      <button type="button" className={section === 'integrations' ? 'is-active' : ''} onClick={() => setSection('integrations')}>Integrations</button>
    </nav>
    <div>{section === 'categories' ? <CategoryWorkspace /> : section === 'sla' ? <TicketSlaWorkspace /> : section === 'saved-replies' ? <SavedReplyWorkspace /> : section === 'notifications' ? <NotificationTemplateWorkspace /> : section === 'logs' ? <NotificationLogWorkspace /> : <ProviderWorkspace />}</div>
  </section>;
}
