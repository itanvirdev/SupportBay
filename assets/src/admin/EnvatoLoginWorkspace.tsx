import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Preloader } from '../shared/components/Preloader';
import { RequestState } from '../shared/components/RequestState';
import { adminGet, adminPost, adminPut } from './api';

interface ProviderItem { id:number; slug:string; name:string; status:'enabled'|'disabled'; }
interface ConfigurationField {
  key:string; label:string; type:'text'|'secret'|'url'|'toggle'|'readonly';
  required:boolean; description:string|null; value:string; configured:boolean|null; group:'main'|'oauth';
}
interface ConfigurationForm { provider:string; configured:boolean; fields:ConfigurationField[]; }

const configuredSecretMask = '••••••••••••';

const formValues = (form:ConfigurationForm) => Object.fromEntries(
  form.fields.map(field=>[
    field.key,
    field.type==='secret'&&field.configured
      ? configuredSecretMask
      : String(field.value??''),
  ]),
);

const permissions = [
  'View and search Envato sites', 'View the user’s Envato Account username', 'View the user’s email address',
  'View the user’s account profile details', 'View the user’s account financial history',
  'Download the user’s purchased items', 'View the user’s items’ sales history',
  'Verify purchases of the user’s items', 'List purchases the user has made',
  'Verify purchases the user has made', 'View the user’s purchases of the app creator’s items',
];

interface EnvatoLoginWorkspaceProps { tab:'main'|'oauth'; onTabChange:(tab:'main'|'oauth')=>void; }

export function EnvatoLoginWorkspace({tab,onTabChange}:EnvatoLoginWorkspaceProps){
  const [provider,setProvider]=useState<ProviderItem|null>(null);
  const [configuration,setConfiguration]=useState<ConfigurationForm|null>(null);
  const [values,setValues]=useState<Record<string,string>>({});
  const [savedValues,setSavedValues]=useState<Record<string,string>>({});
  const [saving,setSaving]=useState(false);
  const [error,setError]=useState<string|null>(null);
  const [notice,setNotice]=useState<string|null>(null);

  const load=async()=>{setError(null);try{
    const providers=await adminGet<ProviderItem[]>('providers');
    const envato=providers.data.find(item=>item.slug==='envato')??null;setProvider(envato);if(!envato)return;
    const response=await adminGet<ConfigurationForm>(`providers/${envato.id}/configuration`);
    const next=formValues(response.data);
    setConfiguration(response.data);setValues(next);setSavedValues(next);
  }catch(reason){setError(reason instanceof Error?reason.message:'Envato settings could not be loaded.');}};
  useEffect(()=>{void load();},[]);

  const changed=useMemo(()=>JSON.stringify(values)!==JSON.stringify(savedValues),[values,savedValues]);
  const save=async(event:FormEvent)=>{event.preventDefault();if(!provider)return;setSaving(true);setError(null);setNotice(null);try{
    const settings=Object.fromEntries(Object.entries(values).map(([key,value])=>[
      key,
      value===configuredSecretMask?'':value,
    ]));
    const response=await adminPut<ConfigurationForm>(`providers/${provider.id}/configuration`,{settings});
    if((values.oauth_login_enabled==='1'||values.purchase_verification_enabled==='1')&&provider.status!=='enabled'){
      await adminPost(`providers/${provider.id}/enable`,{});setProvider({...provider,status:'enabled'});
    }
    const next=formValues(response.data);
    setConfiguration(response.data);setValues(next);setSavedValues(next);setNotice('Envato settings saved.');
  }catch(reason){setError(reason instanceof Error?reason.message:'Envato settings could not be saved.');}finally{setSaving(false);}};
  const copy=async(value:string)=>{try{
    if(navigator.clipboard?.writeText){await navigator.clipboard.writeText(value);}else{
      const textarea=document.createElement('textarea');textarea.value=value;textarea.style.position='fixed';textarea.style.opacity='0';
      document.body.appendChild(textarea);textarea.select();const copied=document.execCommand('copy');textarea.remove();if(!copied)throw new Error('Copy failed');
    }
    setError(null);setNotice('Confirmation URL copied.');
  }catch{setError('Confirmation URL could not be copied.');}};

  const fields=configuration?.fields.filter(field=>field.group===tab)??[];
  const enableKey=tab==='main'?'purchase_verification_enabled':'oauth_login_enabled';
  const enabled=values[enableKey]==='1';
  const toggle=(field:ConfigurationField)=><label className="sbay-general-toggle" key={field.key}><input type="checkbox" role="switch" disabled={saving} checked={values[field.key]==='1'} onChange={event=>setValues({...values,[field.key]:event.target.checked?'1':'0'})}/><span>{field.label}</span></label>;
  const input=(field:ConfigurationField)=><label className="sbay-general-select" key={field.key}><span>{field.label}{field.required?' *':''}</span><span className={field.type==='readonly'?'sbay-copy-field':''}><input name={`sbay_envato_${field.key}`} autoComplete={field.type==='secret'?'new-password':'off'} spellCheck={false} type={field.type==='secret'?'password':field.type==='url'?'url':'text'} readOnly={field.type==='readonly'} disabled={saving} value={values[field.key]??''} placeholder={field.type==='secret'?'Enter the Secret Application Key':''} required={enabled&&field.required&&!(field.type==='secret'&&field.configured)} onFocus={()=>{if(field.type==='secret'&&values[field.key]===configuredSecretMask)setValues({...values,[field.key]:''});}} onBlur={()=>{if(field.type==='secret'&&field.configured&&values[field.key]==='')setValues({...values,[field.key]:configuredSecretMask});}} onChange={event=>setValues({...values,[field.key]:event.target.value})}/>{field.type==='readonly'?<button type="button" title="Copy confirmation URL" aria-label="Copy confirmation URL" onClick={()=>void copy(values[field.key]??'')}>⧉</button>:null}</span>{field.description?<small>{field.description}</small>:null}</label>;
  const enableField=fields.find(field=>field.key===enableKey);
  const detailFields=fields.filter(field=>field.key!==enableKey);

  return <section className="sbay-integration-settings">
    <header><small>Authentication and purchase integration</small><h2>Envato</h2><p>Configure Envato purchase verification, login, and registration.</p></header>
    {error?<p className="sbay-admin-error" role="alert">{error}</p>:null}{notice?<p className="sbay-admin-success" role="status">{notice}</p>:null}
    <div className="sbay-envato-settings-card">{error&&!configuration?<RequestState compact title="Envato settings unavailable" message={error} retry={()=>void load()}/>:!provider?<RequestState compact title="Envato integration unavailable" message="The Envato integration is not registered on this installation."/>:!configuration?<Preloader label="Loading Envato settings…" compact/>:<form onSubmit={save}>
      <nav><button type="button" className={tab==='main'?'is-active':''} onClick={()=>onTabChange('main')}>Main</button><button type="button" className={tab==='oauth'?'is-active':''} onClick={()=>onTabChange('oauth')}>Login with Envato</button></nav>
      <div className="sbay-envato-login-settings">
        {enableField?toggle(enableField):null}
        {tab==='main'&&enabled?<section className="sbay-envato-token-guide"><h3>Step 2: Create an Envato API Token</h3><p>Visit <a href="https://build.envato.com/" target="_blank" rel="noreferrer">Envato API Website</a> and sign in to your account. Navigate to <strong>My Apps</strong>, scroll to <strong>Your Personal Tokens</strong>, then select <strong>Create a new token</strong>.</p><h4>Permissions to select while creating the token:</h4><ul>{permissions.map(permission=><li key={permission}>✓ {permission}</li>)}</ul></section>:null}
        {enabled?detailFields.map(field=>field.type==='toggle'?toggle(field):input(field)):null}
      </div>
      <footer className="sbay-general-actions"><button type="submit" disabled={saving||!changed}>{saving?'Saving…':'Save Changes'}</button><button type="button" disabled={saving||!changed} onClick={()=>setValues(savedValues)}>Discard</button></footer>
    </form>}</div>
  </section>;
}
