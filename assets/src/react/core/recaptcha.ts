import { getConfig } from './config';

declare global {
  interface Window {
    grecaptcha?: {ready(callback:()=>void):void;execute(siteKey:string,options:{action:string}):Promise<string>};
  }
}

let loader: Promise<void>|null=null;

function load(siteKey:string):Promise<void>{
  if(window.grecaptcha)return Promise.resolve();
  if(loader)return loader;
  loader=new Promise((resolve,reject)=>{
    const script=document.createElement('script');
    script.src=`https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(siteKey)}`;
    script.async=true;script.defer=true;script.onload=()=>resolve();script.onerror=()=>reject(new Error('Security verification could not be loaded.'));
    document.head.appendChild(script);
  });
  return loader;
}

export async function recaptchaToken(action:'login'|'registration'|'guest_ticket'):Promise<string>{
  const config=getConfig();
  const enabled=action==='login'?config.recaptchaLoginEnabled:action==='registration'?config.recaptchaRegistrationEnabled:config.recaptchaGuestTicketEnabled;
  if(!enabled)return '';
  await load(config.recaptchaSiteKey);
  return new Promise((resolve,reject)=>window.grecaptcha?.ready(()=>window.grecaptcha?.execute(config.recaptchaSiteKey,{action}).then(resolve,reject)) ?? reject(new Error('Security verification is unavailable.')));
}
