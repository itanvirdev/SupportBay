import { useEffect, useState } from 'react';
import { NotificationTemplateWorkspace } from './NotificationTemplateWorkspace';
import { EnvatoLoginWorkspace } from './EnvatoLoginWorkspace';
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
import { WeekendHolidayWorkspace } from './WeekendHolidayWorkspace';
import { AutoCloseWorkspace } from './AutoCloseWorkspace';
import { AssignRuleWorkspace } from './AssignRuleWorkspace';

type SettingsSection = 'general'|'security'|'roles'|'departments'|'categories'|'tags'|'custom-fields'|'assign-rules'|'sla'|'saved-replies'|'notifications'|'logs'|'weekend'|'auto-close'|'envato';
const sections:SettingsSection[]=['general','security','roles','departments','categories','tags','custom-fields','assign-rules','sla','saved-replies','notifications','logs','weekend','auto-close'];

function route(){
  const params=new URLSearchParams(window.location.search);
  if(params.get('settings')==='integrations'&&params.get('integration')==='envato')return {section:'envato' as const,tab:params.get('tab')==='login-with-envato'?'oauth' as const:'main' as const};
  const requested=params.get('settings') as SettingsSection|null;
  return {section:requested&&sections.includes(requested)?requested:'general' as SettingsSection,tab:'main' as const};
}

export function SettingsWorkspace(){
  const config=getAdminConfig();
  const initial=route();
  const [section,setSection]=useState<SettingsSection>(initial.section);
  const [envatoTab,setEnvatoTab]=useState<'main'|'oauth'>(initial.tab);
  const [integrationsOpen,setIntegrationsOpen]=useState(initial.section==='envato');
  const navigate=(next:SettingsSection,tab: 'main'|'oauth'=envatoTab,replace=false)=>{
    const url=new URL(window.location.href);
    if(next==='envato'){url.searchParams.set('settings','integrations');url.searchParams.set('integration','envato');url.searchParams.set('tab',tab==='oauth'?'login-with-envato':'main');}
    else{url.searchParams.set('settings',next);url.searchParams.delete('integration');if(next==='weekend')url.searchParams.set('tab','weekend');else url.searchParams.delete('tab');}
    window.history[replace?'replaceState':'pushState']({},'',url);setSection(next);setEnvatoTab(tab);if(next==='envato')setIntegrationsOpen(true);
  };
  useEffect(()=>{const pop=()=>{const next=route();setSection(next.section);setEnvatoTab(next.tab);setIntegrationsOpen(next.section==='envato');};window.addEventListener('popstate',pop);return()=>window.removeEventListener('popstate',pop);},[]);
  useEffect(()=>{if(!new URLSearchParams(window.location.search).has('settings'))navigate(section,envatoTab,true);},[]);
  return <section className="sbay-settings-workspace"><nav aria-label="Settings sections">
    <button type="button" className={section==='general'?'is-active':''} onClick={()=>navigate('general')}>General</button><button type="button" className={section==='security'?'is-active':''} onClick={()=>navigate('security')}>Security</button>
    {config.canManageRoles?<button type="button" className={section==='roles'?'is-active':''} onClick={()=>navigate('roles')}>User Roles</button>:null}
    {config.canManageDepartments?<button type="button" className={section==='departments'?'is-active':''} onClick={()=>navigate('departments')}>Departments</button>:null}
    {config.canManageCategories?<button type="button" className={section==='categories'?'is-active':''} onClick={()=>navigate('categories')}>Categories</button>:null}
    {config.canManageTags?<button type="button" className={section==='tags'?'is-active':''} onClick={()=>navigate('tags')}>Tags</button>:null}
    {config.canManageCustomFields?<button type="button" className={section==='custom-fields'?'is-active':''} onClick={()=>navigate('custom-fields')}>Custom Fields</button>:null}
    <button type="button" className={section==='assign-rules'?'is-active':''} onClick={()=>navigate('assign-rules')}>Assign Rules</button>
    {config.canManageSavedReplies?<button type="button" className={section==='saved-replies'?'is-active':''} onClick={()=>navigate('saved-replies')}>Saved Replies</button>:null}
    <button type="button" className={section==='sla'?'is-active':''} onClick={()=>navigate('sla')}>Ticket SLA</button><button type="button" className={section==='notifications'?'is-active':''} onClick={()=>navigate('notifications')}>Email Notifications</button><button type="button" className={section==='logs'?'is-active':''} onClick={()=>navigate('logs')}>Delivery Logs</button><button type="button" className={section==='weekend'?'is-active':''} onClick={()=>navigate('weekend')}>Weekend &amp; Holiday</button><button type="button" className={section==='auto-close'?'is-active':''} onClick={()=>navigate('auto-close')}>Auto Close &amp; Delete</button><button type="button" className={section==='envato'?'is-active':''} aria-expanded={integrationsOpen} onClick={()=>setIntegrationsOpen(!integrationsOpen)}>Integrations <span>{integrationsOpen?'▾':'▸'}</span></button>{integrationsOpen?<button type="button" className={`sbay-settings-subnav ${section==='envato'?'is-active':''}`} onClick={()=>navigate('envato')}>Envato</button>:null}
  </nav><div>{section==='general'?<GeneralWorkspace/>:section==='security'?<SecurityWorkspace/>:section==='roles'?<RoleWorkspace/>:section==='departments'?<DepartmentWorkspace/>:section==='categories'?<CategoryWorkspace/>:section==='tags'?<TagWorkspace/>:section==='custom-fields'?<CustomFieldWorkspace/>:section==='assign-rules'?<AssignRuleWorkspace/>:section==='sla'?<TicketSlaWorkspace/>:section==='saved-replies'?<SavedReplyWorkspace/>:section==='notifications'?<NotificationTemplateWorkspace/>:section==='logs'?<NotificationLogWorkspace/>:section==='weekend'?<WeekendHolidayWorkspace/>:section==='auto-close'?<AutoCloseWorkspace/>:<EnvatoLoginWorkspace tab={envatoTab} onTabChange={tab=>navigate('envato',tab)}/>}</div></section>;
}
