import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Preloader } from '../shared/components/Preloader';
import { adminGet, adminPut } from './api';

interface GeneralSettings {
  registration_override:boolean;
  disable_registration_form:boolean;
  disable_guest_ticket_creation:boolean;
  client_user_default_role:string;
  role_options:Array<{slug:string;name:string}>;
  support_portal_page_id:number;
  support_portal_url:string;
  shortcode_mode:boolean;
  footer_copyright_text:string;
  remove_powered_by_branding:boolean;
  wordpress_auth_enabled:boolean;
  wordpress_login_url:string;
  wordpress_registration_url:string;
  wordpress_profile_enabled:boolean;
  sequential_track_id_enabled:boolean;
  sequential_track_id_prefix:string;
  sequential_track_id_length:number;
  ticket_list_auto_refresh_enabled:boolean;
  ticket_list_auto_refresh_interval:number;
  smart_need_reply_sorting_enabled:boolean;
  dashboard_logo_attachment_id:number;
  dashboard_logo_url:string;
  portal_logo_attachment_id:number;
  portal_logo_url:string;
  default_logo_url:string;
  file_upload_enabled:boolean;
  file_upload_max_size_mb:number;
  file_upload_allowed_groups:string[];
  attachment_popup_preview_enabled:boolean;
  ticket_status_labels:Record<'open'|'pending'|'answered'|'resolved'|'closed',string>;
  style_palette:string;
  style_palettes:Record<string,{name:string;primary:string;accent:string;dark:string}>;
  custom_css:string;
  page_options:Array<{id:number;title:string;url:string}>;
  wordpress_registration_enabled:boolean;
  registration_enabled:boolean;
}

type GeneralDraft = Pick<GeneralSettings,
  'registration_override'|'disable_registration_form'|'disable_guest_ticket_creation'|
  'client_user_default_role'|'support_portal_page_id'|'shortcode_mode'|
  'footer_copyright_text'|'remove_powered_by_branding'|'wordpress_auth_enabled'|
  'wordpress_login_url'|'wordpress_registration_url'
  |'wordpress_profile_enabled'
  |'sequential_track_id_enabled'|'sequential_track_id_prefix'|'sequential_track_id_length'
  |'ticket_list_auto_refresh_enabled'|'ticket_list_auto_refresh_interval'
  |'smart_need_reply_sorting_enabled'
  |'dashboard_logo_attachment_id'|'dashboard_logo_url'
  |'portal_logo_attachment_id'|'portal_logo_url'
  |'file_upload_enabled'|'file_upload_max_size_mb'|'file_upload_allowed_groups'|'attachment_popup_preview_enabled'
  |'ticket_status_labels'
  |'style_palette'|'custom_css'
>;

const fileGroups=[
  ['photos','Photos','JPG, JPEG, PNG, WEBP, GIF'],['videos','Videos','MP4, WEBM, MOV, AVI, OGV'],
  ['audios','Audios','MP3, WAV, AAC, OGG, FLAC, M4A, WMA'],['docs','Docs','DOC, DOCX, XLS, XLSX'],
  ['text','Text','TXT'],['csv','CSV','CSV'],['pdf','PDF','PDF'],['zip','Zip','ZIP'],
  ['json','JSON','JSON'],['models','3D Models','STL'],['medical','Medical Images','DCM'],
] as const;

interface MediaAttachment { id:number; type:string; url:string; }
interface MediaFrame {
  on:(event:'select',callback:()=>void)=>void;
  open:()=>void;
  state:()=>{get:(key:'selection')=>{first:()=>{toJSON:()=>MediaAttachment}}};
}
type MediaWindow = Window & typeof globalThis & {wp?:{media:(options:Record<string,unknown>)=>MediaFrame}};

const draftFrom = (settings:GeneralSettings):GeneralDraft => ({
  registration_override:settings.registration_override,
  disable_registration_form:settings.disable_registration_form,
  disable_guest_ticket_creation:settings.disable_guest_ticket_creation,
  client_user_default_role:settings.client_user_default_role,
  support_portal_page_id:settings.support_portal_page_id,
  shortcode_mode:settings.shortcode_mode,
  footer_copyright_text:settings.footer_copyright_text,
  remove_powered_by_branding:settings.remove_powered_by_branding,
  wordpress_auth_enabled:settings.wordpress_auth_enabled,
  wordpress_login_url:settings.wordpress_login_url,
  wordpress_registration_url:settings.wordpress_registration_url,
  wordpress_profile_enabled:settings.wordpress_profile_enabled,
  sequential_track_id_enabled:settings.sequential_track_id_enabled,
  sequential_track_id_prefix:settings.sequential_track_id_prefix,
  sequential_track_id_length:settings.sequential_track_id_length,
  ticket_list_auto_refresh_enabled:settings.ticket_list_auto_refresh_enabled,
  ticket_list_auto_refresh_interval:settings.ticket_list_auto_refresh_interval,
  smart_need_reply_sorting_enabled:settings.smart_need_reply_sorting_enabled,
  dashboard_logo_attachment_id:settings.dashboard_logo_attachment_id,
  dashboard_logo_url:settings.dashboard_logo_url,
  portal_logo_attachment_id:settings.portal_logo_attachment_id,
  portal_logo_url:settings.portal_logo_url,
  file_upload_enabled:settings.file_upload_enabled,
  file_upload_max_size_mb:settings.file_upload_max_size_mb,
  file_upload_allowed_groups:settings.file_upload_allowed_groups,
  attachment_popup_preview_enabled:settings.attachment_popup_preview_enabled,
  ticket_status_labels:settings.ticket_status_labels,
  style_palette:settings.style_palette,
  custom_css:settings.custom_css,
});

export function GeneralWorkspace(){
  const [settings,setSettings]=useState<GeneralSettings|null>(null);
  const [draft,setDraft]=useState<GeneralDraft|null>(null);
  const [saving,setSaving]=useState(false);
  const [error,setError]=useState<string|null>(null);
  const [notice,setNotice]=useState<string|null>(null);
  const [activeTab,setActiveTab]=useState<'main'|'logo'|'file'|'status'|'style'>('main');

  useEffect(()=>{
    adminGet<GeneralSettings>('settings/general')
      .then(response=>{setSettings(response.data);setDraft(draftFrom(response.data));})
      .catch(reason=>setError(reason instanceof Error?reason.message:'General settings could not be loaded.'));
  },[]);

  const changed=useMemo(()=>settings!==null&&draft!==null&&JSON.stringify(draft)!==JSON.stringify(draftFrom(settings)),[draft,settings]);
  const update=(changes:Partial<GeneralDraft>)=>setDraft(current=>current?{...current,...changes}:current);
  const discard=()=>{if(!settings)return;setDraft(draftFrom(settings));setError(null);setNotice(null);};
  const save=async(event:FormEvent)=>{
    event.preventDefault();
    if(!draft||!changed)return;
    setSaving(true);setError(null);setNotice(null);
    try{
      const response=await adminPut<GeneralSettings>('settings/general',draft);
      setSettings(response.data);setDraft(draftFrom(response.data));setNotice('General settings saved.');
      if(activeTab==='logo'){
        const dashboardLogo=document.querySelector<HTMLImageElement>('.sbay-admin-php-brand img');
        if(dashboardLogo)dashboardLogo.src=response.data.dashboard_logo_url;
      }
    }catch(reason){setError(reason instanceof Error?reason.message:'General settings could not be saved.');}
    finally{setSaving(false);}
  };
  const chooseLogo=(kind:'dashboard'|'portal')=>{
    const media=(window as MediaWindow).wp?.media;
    if(!media){setError('The WordPress media library is unavailable.');return;}
    const frame=media({title:`Choose ${kind} logo`,button:{text:'Use this logo'},library:{type:'image'},multiple:false});
    frame.on('select',()=>{
      const attachment=frame.state().get('selection').first().toJSON();
      if(attachment.type!=='image'){setError('Please select an image.');return;}
      setError(null);
      update(kind==='dashboard'
        ?{dashboard_logo_attachment_id:attachment.id,dashboard_logo_url:attachment.url}
        :{portal_logo_attachment_id:attachment.id,portal_logo_url:attachment.url});
    });
    frame.open();
  };
  const removeLogo=(kind:'dashboard'|'portal')=>{
    if(!settings)return;
    update(kind==='dashboard'
      ?{dashboard_logo_attachment_id:0,dashboard_logo_url:settings.default_logo_url}
      :{portal_logo_attachment_id:0,portal_logo_url:settings.default_logo_url});
  };

  if(!settings||!draft)return <section className="sbay-general-settings"><header><small>SupportBay configuration</small><h2>General</h2></header>{error?<p className="sbay-admin-error" role="alert">{error}</p>:null}<Preloader label="Loading general settings…" /></section>;

  const effectiveRegistration=!draft.disable_registration_form&&(draft.registration_override||settings.wordpress_registration_enabled);
  const portalUrl=settings.page_options.find(page=>page.id===draft.support_portal_page_id)?.url??settings.support_portal_url;

  return <section className="sbay-general-settings">
    <header><small>SupportBay configuration</small><h2>General</h2></header>
    {error?<p className="sbay-admin-error" role="alert">{error}</p>:null}
    {notice?<p className="sbay-admin-success" role="status">{notice}</p>:null}
    <form className="sbay-general-card" onSubmit={save}>
      <nav><button type="button" className={activeTab==='main'?'is-active':''} onClick={()=>setActiveTab('main')}>Main</button><button type="button" className={activeTab==='logo'?'is-active':''} onClick={()=>setActiveTab('logo')}>Logo</button><button type="button" className={activeTab==='file'?'is-active':''} onClick={()=>setActiveTab('file')}>File</button><button type="button" className={activeTab==='status'?'is-active':''} onClick={()=>setActiveTab('status')}>Status</button><button type="button" className={activeTab==='style'?'is-active':''} onClick={()=>setActiveTab('style')}>Style</button></nav>
      {activeTab==='main'?<div className="sbay-general-main">
        <label className="sbay-general-toggle"><input type="checkbox" role="switch" disabled={saving} checked={draft.registration_override} onChange={event=>update({registration_override:event.target.checked})}/><span>Override WordPress registration setting.</span><span className="sbay-setting-help" tabIndex={0} aria-label="Registration override help">?<span role="tooltip">Allow SupportBay to create user accounts even if WordPress “Anyone can register” is off.<br/><br/>Turn OFF to strictly follow WordPress&apos;s global setting.</span></span></label>
        <label className="sbay-general-toggle"><input type="checkbox" role="switch" disabled={saving} checked={draft.disable_registration_form} onChange={event=>update({disable_registration_form:event.target.checked})}/><span>Disable registration form.</span></label>
        <label className="sbay-general-toggle"><input type="checkbox" role="switch" disabled={saving} checked={draft.disable_guest_ticket_creation} onChange={event=>update({disable_guest_ticket_creation:event.target.checked})}/><span>Disable guest ticket creation.</span></label>
        <label className="sbay-general-select"><span>Client User Default Role</span><select disabled={saving} value={draft.client_user_default_role} onChange={event=>update({client_user_default_role:event.target.value})} aria-describedby="sbay-client-role-help">{settings.role_options.map(role=><option value={role.slug} key={role.slug}>{role.name}</option>)}</select><small id="sbay-client-role-help">This WordPress role is assigned to every new SupportBay registration. Subscriber is recommended for customers.</small></label>
        <label className="sbay-general-select"><span>Support Portal Page</span><select disabled={saving} value={draft.support_portal_page_id} onChange={event=>update({support_portal_page_id:Number(event.target.value)})}><option value="0">No portal page selected</option>{settings.page_options.map(page=><option value={page.id} key={page.id}>{page.title}</option>)}</select></label>
        <p className="sbay-portal-visit">Visit: <a href={portalUrl} target="_blank" rel="noreferrer">Support Portal</a></p>
        <label className="sbay-general-toggle"><input type="checkbox" role="switch" disabled={saving} checked={draft.shortcode_mode} onChange={event=>update({shortcode_mode:event.target.checked})}/><span>Enable <code>[supportbay]</code> shortcode on other pages.</span></label>
        {draft.shortcode_mode?<p className="sbay-shortcode-notice">Add <code>[supportbay]</code> to any other WordPress page. The selected Support Portal Page continues to work independently.</p>:null}
        <label className="sbay-general-select"><span>Footer Copyright Text</span><input type="text" disabled={saving} value={draft.footer_copyright_text} onChange={event=>update({footer_copyright_text:event.target.value})}/><small>Use <code>{'{year}'}</code> for the current year and <code>{'{site_name}'}</code> for the clickable site name.</small></label>
        <label className="sbay-general-toggle"><input type="checkbox" role="switch" disabled={saving} checked={draft.remove_powered_by_branding} onChange={event=>update({remove_powered_by_branding:event.target.checked})}/><span>Remove &apos;powered by&apos; branding.</span></label>
        <label className="sbay-general-toggle"><input type="checkbox" role="switch" disabled={saving} checked={draft.wordpress_auth_enabled} onChange={event=>update({wordpress_auth_enabled:event.target.checked})}/><span>Enable WordPress login &amp; registration.</span></label>
        {draft.wordpress_auth_enabled?<div className="sbay-wordpress-auth-links">
          <label className="sbay-general-select"><span>Login Page Link</span><input type="url" disabled={saving} value={draft.wordpress_login_url} placeholder="Native WordPress login" onChange={event=>update({wordpress_login_url:event.target.value})}/><small>Leave empty to use the native WordPress login page.</small></label>
          <label className="sbay-general-select"><span>Registration Link</span><input type="url" disabled={saving} value={draft.wordpress_registration_url} placeholder="Native WordPress registration" onChange={event=>update({wordpress_registration_url:event.target.value})}/><small>Leave empty to use the native WordPress registration page.</small></label>
        </div>:null}
        <label className="sbay-general-toggle"><input type="checkbox" role="switch" disabled={saving} checked={draft.wordpress_profile_enabled} onChange={event=>update({wordpress_profile_enabled:event.target.checked})}/><span>Enable WordPress profile link.</span></label>
        <label className="sbay-general-toggle"><input type="checkbox" role="switch" disabled={saving} checked={draft.sequential_track_id_enabled} onChange={event=>update({sequential_track_id_enabled:event.target.checked})}/><span>Enable Sequential Ticket Track ID.</span></label>
        {draft.sequential_track_id_enabled?<div className="sbay-sequential-track-settings">
          <label className="sbay-general-select"><span>Prefix</span><input type="text" maxLength={20} disabled={saving} value={draft.sequential_track_id_prefix} placeholder="TKT-" onChange={event=>update({sequential_track_id_prefix:event.target.value.toUpperCase().replace(/[^A-Z0-9_-]/g,'')})}/><small>Optional letters, numbers, hyphens, or underscores.</small></label>
          <label className="sbay-general-select"><span>Length</span><input type="number" min={6} max={32} disabled={saving} value={draft.sequential_track_id_length} onChange={event=>update({sequential_track_id_length:Math.min(32,Math.max(6,Number(event.target.value)||6))})}/><small>Number of random uppercase hexadecimal characters.</small></label>
          <p>Preview: <strong>{draft.sequential_track_id_prefix}{'1D4FF13D'.repeat(4).slice(0,draft.sequential_track_id_length)}</strong></p>
        </div>:null}
        <label className="sbay-general-toggle"><input type="checkbox" role="switch" disabled={saving} checked={draft.ticket_list_auto_refresh_enabled} onChange={event=>update({ticket_list_auto_refresh_enabled:event.target.checked})}/><span>Enable ticket list auto-refresh.</span></label>
        {draft.ticket_list_auto_refresh_enabled?<label className="sbay-general-select sbay-auto-refresh-interval"><span><i aria-hidden="true">*</i> Auto refresh interval <span className="sbay-setting-help" tabIndex={0} aria-label="Auto refresh interval help">?<span role="tooltip">Minimum value is 5 seconds.</span></span></span><span><input type="number" min={5} max={3600} disabled={saving} value={draft.ticket_list_auto_refresh_interval} onChange={event=>update({ticket_list_auto_refresh_interval:Math.min(3600,Math.max(5,Number(event.target.value)||5))})}/><em>Seconds</em></span></label>:null}
        <label className="sbay-general-toggle"><input type="checkbox" role="switch" disabled={saving} checked={draft.smart_need_reply_sorting_enabled} onChange={event=>update({smart_need_reply_sorting_enabled:event.target.checked})}/><span>Enable smart sorting for need reply filter.</span></label>
        <p>Effective registration: <strong>{effectiveRegistration?'Enabled':'Disabled'}</strong></p>
        <footer className="sbay-general-actions"><button type="submit" disabled={saving||!changed||!draft.footer_copyright_text.trim()}>{saving?'Saving…':'Save Changes'}</button><button type="button" disabled={saving||!changed} onClick={discard}>Discard</button></footer>
      </div>:activeTab==='logo'?<div className="sbay-general-logo-settings">
        <article className="sbay-logo-setting">
          <div><h3>Dashboard Logo</h3><p>Displayed in the SupportBay WordPress administration header.</p></div>
          <figure><img src={draft.dashboard_logo_url} alt="Dashboard logo preview"/></figure>
          <div className="sbay-logo-setting__actions"><button type="button" disabled={saving} onClick={()=>chooseLogo('dashboard')}>Upload</button><button type="button" className="is-danger" disabled={saving||draft.dashboard_logo_attachment_id===0} onClick={()=>removeLogo('dashboard')}>Remove</button></div>
        </article>
        <article className="sbay-logo-setting">
          <div><h3>Portal Logo</h3><p>Upload the main logo displayed on the front-end ticket page.</p></div>
          <figure><img src={draft.portal_logo_url} alt="Portal logo preview"/></figure>
          <div className="sbay-logo-setting__actions"><button type="button" disabled={saving} onClick={()=>chooseLogo('portal')}>Upload</button><button type="button" className="is-danger" disabled={saving||draft.portal_logo_attachment_id===0} onClick={()=>removeLogo('portal')}>Remove</button></div>
        </article>
        <footer className="sbay-general-actions"><button type="submit" disabled={saving||!changed}>{saving?'Saving…':'Save Changes'}</button><button type="button" disabled={saving||!changed} onClick={discard}>Discard</button></footer>
      </div>:activeTab==='file'?<div className="sbay-general-file-settings">
        <label className="sbay-general-toggle"><input type="checkbox" role="switch" disabled={saving} checked={draft.file_upload_enabled} onChange={event=>update({file_upload_enabled:event.target.checked,...(!event.target.checked?{attachment_popup_preview_enabled:false}:{})})}/><span>Click to enable file upload.</span></label>
        <p>Turn this ON to allow customers to upload files while submitting tickets.</p>
        <label className="sbay-general-select"><span>Max File Size</span><span className="sbay-file-size-input"><input type="number" min={1} max={100} disabled={saving||!draft.file_upload_enabled} value={draft.file_upload_max_size_mb} onChange={event=>update({file_upload_max_size_mb:Math.min(100,Math.max(1,Number(event.target.value)||1))})}/><em>MB</em></span><small>Set the maximum size for each customer upload.</small></label>
        <fieldset disabled={saving||!draft.file_upload_enabled}><legend>Allowed File Types</legend><p>Define permissible file formats.</p><div className="sbay-file-groups">{fileGroups.map(([slug,label,extensions])=><label key={slug}><input type="checkbox" checked={draft.file_upload_allowed_groups.includes(slug)} onChange={event=>update({file_upload_allowed_groups:event.target.checked?[...draft.file_upload_allowed_groups,slug]:draft.file_upload_allowed_groups.filter(group=>group!==slug)})}/><span><strong>{label}</strong><small>{extensions}</small></span></label>)}</div></fieldset>
        <label className="sbay-general-toggle"><input type="checkbox" role="switch" disabled={saving||!draft.file_upload_enabled} checked={draft.attachment_popup_preview_enabled} onChange={event=>update({attachment_popup_preview_enabled:event.target.checked})}/><span>Enable popup for view photos &amp; PDF.</span></label>
        <footer className="sbay-general-actions"><button type="submit" disabled={saving||!changed}>{saving?'Saving…':'Save Changes'}</button><button type="button" disabled={saving||!changed} onClick={discard}>Discard</button></footer>
      </div>:activeTab==='status'?<div className="sbay-general-status-settings"><header><h3>Rename Ticket Status Labels</h3><p>These labels can be updated to suit your workflow. Internal status values remain unchanged.</p></header><div>{(Object.keys(draft.ticket_status_labels) as Array<keyof typeof draft.ticket_status_labels>).map(status=><label className="sbay-general-select" key={status}><span>{status[0].toUpperCase()+status.slice(1)} status</span><input type="text" maxLength={50} disabled={saving} value={draft.ticket_status_labels[status]} onChange={event=>update({ticket_status_labels:{...draft.ticket_status_labels,[status]:event.target.value}})}/></label>)}</div><footer className="sbay-general-actions"><button type="submit" disabled={saving||!changed||Object.values(draft.ticket_status_labels).some(label=>label.trim()==='')}>{saving?'Saving…':'Save Changes'}</button><button type="button" disabled={saving||!changed} onClick={discard}>Discard</button></footer></div>:<div className="sbay-general-style-settings">
        <header><h3>Color Palette</h3><p>Choose a predefined color palette for the SupportBay interface.</p></header>
        <div className="sbay-style-palettes">{Object.entries(settings.style_palettes).map(([slug,palette])=><label className={draft.style_palette===slug?'is-selected':''} key={slug}><input type="radio" name="style_palette" value={slug} disabled={saving} checked={draft.style_palette===slug} onChange={()=>update({style_palette:slug})}/><span className="sbay-style-swatches" aria-hidden="true"><i style={{backgroundColor:palette.primary}}/><i style={{backgroundColor:palette.accent}}/><i style={{backgroundColor:palette.dark}}/></span><strong>{palette.name}</strong></label>)}</div>
        <label className="sbay-general-select sbay-custom-css"><span>Custom CSS</span><textarea rows={14} maxLength={20000} disabled={saving} value={draft.custom_css} onChange={event=>update({custom_css:event.target.value})} placeholder={'.sbay-shell {\n  /* Your styles */\n}'}/><small>Loaded only on SupportBay admin and portal screens. HTML, imports, scripts, and unsafe CSS expressions are removed.</small></label>
        <footer className="sbay-general-actions"><button type="submit" disabled={saving||!changed}>{saving?'Saving…':'Save Changes'}</button><button type="button" disabled={saving||!changed} onClick={discard}>Discard</button></footer>
      </div>}
    </form>
  </section>;
}
