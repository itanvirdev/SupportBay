import { useEffect, useState } from 'react';
import { adminGet, adminPut } from './api';
import { Preloader } from '../shared/components/Preloader';

interface GeneralSettings {registration_override:boolean;disable_registration_form:boolean;disable_guest_ticket_creation:boolean;client_user_default_role:string;role_options:Array<{slug:string;name:string}>;support_portal_page_id:number;support_portal_url:string;shortcode_mode:boolean;page_options:Array<{id:number;title:string;url:string}>;wordpress_registration_enabled:boolean;registration_enabled:boolean}

export function GeneralWorkspace(){
  const [settings,setSettings]=useState<GeneralSettings|null>(null);
  const [saving,setSaving]=useState(false);
  const [error,setError]=useState<string|null>(null);
  const [notice,setNotice]=useState<string|null>(null);
  useEffect(()=>{adminGet<GeneralSettings>('settings/general').then(response=>setSettings(response.data)).catch(reason=>setError(reason instanceof Error?reason.message:'General settings could not be loaded.'));},[]);
  const update=async(changes:Partial<GeneralSettings>)=>{if(!settings)return;const previous=settings;setSettings({...settings,...changes});setSaving(true);setError(null);setNotice(null);try{const response=await adminPut<GeneralSettings>('settings/general',changes);setSettings(response.data);setNotice('General settings saved.');}catch(reason){setSettings(previous);setError(reason instanceof Error?reason.message:'General settings could not be saved.');}finally{setSaving(false);}};
  return <section className="sbay-general-settings"><header><small>SupportBay configuration</small><h2>General</h2></header>{error?<p className="sbay-admin-error" role="alert">{error}</p>:null}{notice?<p className="sbay-admin-success" role="status">{notice}</p>:null}{settings?<div className="sbay-general-card"><nav><strong>Main</strong><span>Logo</span><span>File</span><span>Status</span></nav><div className="sbay-general-main">
    <label className="sbay-general-toggle"><input type="checkbox" role="switch" disabled={saving} checked={settings.registration_override} onChange={event=>void update({registration_override:event.target.checked})}/><span>Override WordPress registration setting.</span><span className="sbay-setting-help" tabIndex={0} aria-label="Registration override help">?<span role="tooltip">Allow SupportBay to create user accounts even if WordPress “Anyone can register” is off.<br/><br/>Turn OFF to strictly follow WordPress&apos;s global setting.</span></span></label>
    <label className="sbay-general-toggle"><input type="checkbox" role="switch" disabled={saving} checked={settings.disable_registration_form} onChange={event=>void update({disable_registration_form:event.target.checked})}/><span>Disable registration form.</span></label>
    <label className="sbay-general-toggle"><input type="checkbox" role="switch" disabled={saving} checked={settings.disable_guest_ticket_creation} onChange={event=>void update({disable_guest_ticket_creation:event.target.checked})}/><span>Disable guest ticket creation.</span></label>
    <label className="sbay-general-select"><span>Client User Default Role</span><select disabled={saving} value={settings.client_user_default_role} onChange={event=>void update({client_user_default_role:event.target.value})} aria-describedby="sbay-client-role-help">{settings.role_options.map(role=><option value={role.slug} key={role.slug}>{role.name}</option>)}</select><small id="sbay-client-role-help">This WordPress role is assigned to every new SupportBay registration. Subscriber is recommended for customers.</small></label>
    <label className="sbay-general-select"><span>Support Portal Page</span><select disabled={saving} value={settings.support_portal_page_id} onChange={event=>void update({support_portal_page_id:Number(event.target.value)})}><option value="0">No portal page selected</option>{settings.page_options.map(page=><option value={page.id} key={page.id}>{page.title}</option>)}</select></label>
    <p className="sbay-portal-visit">Visit: <a href={settings.support_portal_url} target="_blank" rel="noreferrer">Support Portal</a></p>
    <label className="sbay-general-toggle"><input type="checkbox" role="switch" disabled={saving} checked={settings.shortcode_mode} onChange={event=>void update({shortcode_mode:event.target.checked})}/><span>Enable <code>[supportbay]</code> shortcode on other pages.</span></label>
    {settings.shortcode_mode?<p className="sbay-shortcode-notice">Add <code>[supportbay]</code> to any other WordPress page. The selected Support Portal Page continues to work independently.</p>:null}
    <p>Effective registration: <strong>{settings.registration_enabled?'Enabled':'Disabled'}</strong></p>
  </div></div>:<Preloader label="Loading general settings…" />}</section>;
}
