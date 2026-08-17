import { FormEvent, useState } from 'react';
import { apiPost } from '../../api/client';
import { getConfig } from '../../core/config';

interface AuthPageProps { mode:'login'|'register'; navigate:(path:string)=>void }

export function AuthPage({mode,navigate}:AuthPageProps){
  const config=getConfig();
  const [login,setLogin]=useState('');
  const [firstName,setFirstName]=useState('');
  const [lastName,setLastName]=useState('');
  const [email,setEmail]=useState('');
  const [password,setPassword]=useState('');
  const [confirmPassword,setConfirmPassword]=useState('');
  const [remember,setRemember]=useState(false);
  const [showPassword,setShowPassword]=useState(false);
  const [showConfirmation,setShowConfirmation]=useState(false);
  const [busy,setBusy]=useState(false);
  const [error,setError]=useState<string|null>(null);

  const submit=async(event:FormEvent)=>{
    event.preventDefault();
    if(mode==='register'&&password!==confirmPassword){setError('Passwords do not match.');return;}
    setBusy(true);setError(null);
    try{
      const response=mode==='login'
        ?await apiPost<{redirect:string}>('auth/login',{login,password,remember})
        :await apiPost<{redirect:string}>('auth/register',{first_name:firstName,last_name:lastName,email,password,password_confirmation:confirmPassword});
      const requested=new URLSearchParams(window.location.search).get('redirect');
      const target=requested&&requested.startsWith('/support/')&&!requested.startsWith('/support/login')&&!requested.startsWith('/support/register')?requested:response.redirect;
      window.location.assign(target);
    }catch(reason){setError(reason instanceof Error?reason.message:'Authentication failed.');setBusy(false);}
  };

  return <main className="sbay-auth-page"><section className="sbay-auth-card">
    <header><div className="sbay-auth-brand"><span aria-hidden="true">S</span><strong>{config.siteName}</strong></div></header>
    <nav><a href={config.homeUrl} aria-label="Home"><span aria-hidden="true">⌂</span></a>{mode==='login'?<button type="button" onClick={()=>navigate('/support/register/')}><span aria-hidden="true">♙</span> Register</button>:<button type="button" onClick={()=>navigate('/support/login/')}><span aria-hidden="true">♙</span> Login</button>}</nav>
    <form onSubmit={submit}><h1>{mode==='login'?'Login':'Register'}</h1>
      {mode==='register'?<><div className="sbay-auth-name-fields"><label><span>First Name</span><input value={firstName} onChange={event=>setFirstName(event.target.value)} autoComplete="given-name" required maxLength={100}/></label><label><span>Last Name</span><input value={lastName} onChange={event=>setLastName(event.target.value)} autoComplete="family-name" required maxLength={100}/></label></div><label><span>Email Address</span><input type="email" value={email} onChange={event=>setEmail(event.target.value)} autoComplete="email" required/></label></>:<label><span>Username or Email Address</span><input value={login} onChange={event=>setLogin(event.target.value)} autoComplete="username" required/></label>}
      <label><span>Password</span><div className="sbay-password-input"><input type={showPassword?'text':'password'} value={password} onChange={event=>setPassword(event.target.value)} autoComplete={mode==='login'?'current-password':'new-password'} required minLength={mode==='register'?8:undefined}/><button type="button" aria-label={showPassword?'Hide password':'Show password'} onClick={()=>setShowPassword(!showPassword)}>{showPassword?'Hide':'Show'}</button></div></label>
      {mode==='register'?<label><span>Confirm Password</span><div className="sbay-password-input"><input type={showConfirmation?'text':'password'} value={confirmPassword} onChange={event=>setConfirmPassword(event.target.value)} autoComplete="new-password" required minLength={8}/><button type="button" aria-label={showConfirmation?'Hide confirmed password':'Show confirmed password'} onClick={()=>setShowConfirmation(!showConfirmation)}>{showConfirmation?'Hide':'Show'}</button></div></label>:<label className="sbay-auth-remember"><input type="checkbox" checked={remember} onChange={event=>setRemember(event.target.checked)}/><span>Remember me</span></label>}
      {error?<p className="sbay-form-error" role="alert">{error}</p>:null}<button className="sbay-primary-button" disabled={busy}>{busy?(mode==='login'?'Logging in…':'Creating account…'):(mode==='login'?'Login':'Register')}</button>
    </form>{mode==='login'?<footer>Lost your password? <a href={config.resetPasswordUrl}>Reset Password</a></footer>:null}
  </section><p className="sbay-auth-copyright">Support — {config.siteName}</p></main>;
}
