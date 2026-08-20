import { useState } from 'react';
import { NotificationTemplateWorkspace } from './NotificationTemplateWorkspace';
import { ProviderWorkspace } from './ProviderWorkspace';
import { NotificationLogWorkspace } from './NotificationLogWorkspace';
import { TicketSlaWorkspace } from './TicketSlaWorkspace';
import { SavedReplyWorkspace } from './SavedReplyWorkspace';
import { CategoryWorkspace } from './CategoryWorkspace';
import { TagWorkspace } from './TagWorkspace';
import { CustomFieldWorkspace } from './CustomFieldWorkspace';
import { RoleWorkspace } from './RoleWorkspace';
import { DepartmentWorkspace } from './DepartmentWorkspace';
import { GeneralWorkspace } from './GeneralWorkspace';
import { getAdminConfig } from './config';
import { SecurityWorkspace } from './SecurityWorkspace';

type SettingsSection = 'general'|'security'|'roles'|'departments'|'categories'|'tags'|'custom-fields'|'sla'|'saved-replies'|'notifications'|'logs'|'integrations';

export function SettingsWorkspace(){
  const config=getAdminConfig();
  const [section,setSection]=useState<SettingsSection>('general');
  return <section className="sbay-settings-workspace"><nav aria-label="Settings sections">
    <button type="button" className={section==='general'?'is-active':''} onClick={()=>setSection('general')}>General</button><button type="button" className={section==='security'?'is-active':''} onClick={()=>setSection('security')}>Security</button>
    {config.canManageRoles?<button type="button" className={section==='roles'?'is-active':''} onClick={()=>setSection('roles')}>User Roles</button>:null}
    {config.canManageDepartments?<button type="button" className={section==='departments'?'is-active':''} onClick={()=>setSection('departments')}>Departments</button>:null}
    {config.canManageCategories?<button type="button" className={section==='categories'?'is-active':''} onClick={()=>setSection('categories')}>Categories</button>:null}
    {config.canManageTags?<button type="button" className={section==='tags'?'is-active':''} onClick={()=>setSection('tags')}>Tags</button>:null}
    {config.canManageCustomFields?<button type="button" className={section==='custom-fields'?'is-active':''} onClick={()=>setSection('custom-fields')}>Custom Fields</button>:null}
    {config.canManageSavedReplies?<button type="button" className={section==='saved-replies'?'is-active':''} onClick={()=>setSection('saved-replies')}>Saved Replies</button>:null}
    <button type="button" className={section==='sla'?'is-active':''} onClick={()=>setSection('sla')}>Ticket SLA</button><button type="button" className={section==='notifications'?'is-active':''} onClick={()=>setSection('notifications')}>Email Notifications</button><button type="button" className={section==='logs'?'is-active':''} onClick={()=>setSection('logs')}>Delivery Logs</button><button type="button" className={section==='integrations'?'is-active':''} onClick={()=>setSection('integrations')}>Integrations</button>
  </nav><div>{section==='general'?<GeneralWorkspace/>:section==='security'?<SecurityWorkspace/>:section==='roles'?<RoleWorkspace/>:section==='departments'?<DepartmentWorkspace/>:section==='categories'?<CategoryWorkspace/>:section==='tags'?<TagWorkspace/>:section==='custom-fields'?<CustomFieldWorkspace/>:section==='sla'?<TicketSlaWorkspace/>:section==='saved-replies'?<SavedReplyWorkspace/>:section==='notifications'?<NotificationTemplateWorkspace/>:section==='logs'?<NotificationLogWorkspace/>:<ProviderWorkspace/>}</div></section>;
}
