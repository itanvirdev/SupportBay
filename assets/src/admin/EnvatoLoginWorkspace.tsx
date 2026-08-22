import { useEffect, useMemo, useState } from 'react';
import { Preloader } from '../shared/components/Preloader';
import { adminGet, adminPost, adminPut } from './api';

interface ProviderItem { id:number; slug:string; name:string; status:'enabled'|'disabled'; }
interface ConfigurationField { key:string; label:string; type:'text'|'secret'|'url'|'toggle'|'readonly'; required:boolean; description:string|null; value:string; configured:boolean|null; }
interface ConfigurationForm { provider:string; configured:boolean; fields:ConfigurationField[]; }

export function EnvatoLoginWorkspace(){
  const [provider,setProvider]=useState<ProviderItem|null>(null);
  const [configuration,setConfiguration]=useState<ConfigurationForm|null>(null);
  const [values,setValues]=useState<Record<string,string>>({});
  const [savedValues,setSavedValues]=useState<Record<string,string>>({});
  const [saving,setSaving]=useState(false);
  const [error,setError]=useState<string|null>(null);
  const [notice,setNotice]=useState<string|null>(null);

  const load=async()=>{
    setError(null);
    try {
      const providers=await adminGet<ProviderItem[]>('providers');
      const envato=providers.data.find(item=>item.slug==='envato')??null;
      setProvider(envato);
      if(!envato)return;
      const response=await adminGet<ConfigurationForm>(`providers/${envato.id}/configuration`);
      const next=Object.fromEntries(response.data.fields.map(field=>[field.key,String(field.value??'')]));
      setConfiguration(response.data);setValues(next);setSavedValues(next);
    }catch(reason){setError(reason instanceof Error?reason.message:'Envato settings could not be loaded.');}
  };
  useEffect(()=>{void load();},[]);
  const changed=useMemo(()=>JSON.stringify(values)!==JSON.stringify(savedValues),[values,savedValues]);
  const save=async(event:React.FormEvent)=>{
    event.preventDefault();if(!provider)return;setSaving(true);setError(null);setNotice(null);
    try{
      const response=await adminPut<ConfigurationForm>(`providers/${provider.id}/configuration`,{settings:values});
      if(values.oauth_login_enabled==='1'&&provider.status!=='enabled'){
        await adminPost(`providers/${provider.id}/enable`,{});setProvider({...provider,status:'enabled'});
      }
      const next=Object.fromEntries(response.data.fields.map(field=>[field.key,String(field.value??'')]));
      setConfiguration(response.data);setValues(next);setSavedValues(next);setNotice('Envato login settings saved.');
    }catch(reason){setError(reason instanceof Error?reason.message:'Envato settings could not be saved.');}
    finally{setSaving(false);}
  };
  const copy=async(value:string)=>{try{
    if(navigator.clipboard?.writeText){await navigator.clipboard.writeText(value);}
    else{const textarea=document.createElement('textarea');textarea.value=value;textarea.style.position='fixed';textarea.style.opacity='0';document.body.appendChild(textarea);textarea.select();const copied=document.execCommand('copy');textarea.remove();if(!copied)throw new Error('Copy failed');}
    setError(null);setNotice('Confirmation URL copied.');
  }catch{setError('Confirmation URL could not be copied.');}};

  return <section className="sbay-integration-settings">
    <header><small>Authentication integration</small><h2>Envato</h2><p>Configure Envato login and registration for the customer portal.</p></header>
    {error?<p className="sbay-admin-error" role="alert">{error}</p>:null}{notice?<p className="sbay-admin-success" role="status">{notice}</p>:null}
    <div className="sbay-envato-settings-card">{!provider?<p>Envato integration is not installed.</p>:!configuration?<Preloader label="Loading Envato settings…" compact/>:<form onSubmit={save}><nav><button type="button" className="is-active">Login with Envato</button></nav><div className="sbay-envato-login-settings">{configuration.fields.filter(field=>field.type==='toggle'||values.oauth_login_enabled==='1').map(field=>field.type==='toggle'?<label className="sbay-general-toggle" key={field.key}><input type="checkbox" role="switch" disabled={saving} checked={values[field.key]==='1'} onChange={event=>setValues({...values,[field.key]:event.target.checked?'1':'0'})}/><span>{field.label}</span></label>:<label className="sbay-general-select" key={field.key}><span>{field.label}{field.required?' *':''}</span><span className={field.type==='readonly'?'sbay-copy-field':''}><input name={`sbay_envato_${field.key}`} autoComplete="off" spellCheck={false} type={field.type==='secret'?'password':field.type==='url'?'url':'text'} readOnly={field.type==='readonly'} disabled={saving} value={values[field.key]??''} placeholder={field.type==='secret'&&field.configured?'Saved — leave blank to keep':''} required={field.required&&!(field.type==='secret'&&field.configured)} onChange={event=>setValues({...values,[field.key]:event.target.value})}/>{field.type==='readonly'?<button type="button" title="Copy confirmation URL" aria-label="Copy confirmation URL" onClick={()=>void copy(values[field.key]??'')}>⧉</button>:null}</span>{field.description?<small>{field.description}</small>:null}</label>)}</div><footer className="sbay-general-actions"><button type="submit" disabled={saving||!changed}>{saving?'Saving…':'Save Changes'}</button><button type="button" disabled={saving||!changed} onClick={()=>setValues(savedValues)}>Discard</button></footer></form>}</div>
  </section>;
}
