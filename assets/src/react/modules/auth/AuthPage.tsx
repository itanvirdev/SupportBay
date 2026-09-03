import { FormEvent, useEffect, useState } from "react";
import { apiGet, apiPost, apiPostForm } from "../../api/client";
import { getConfig } from "../../core/config";
import { PortalCopyright } from "../../components/PortalCopyright";
import { FilePicker } from "../../components/FilePicker";
import { RichTextEditor } from "../../../shared/editor/RichTextEditor";
import { recaptchaToken } from "../../core/recaptcha";

interface AuthPageProps {
	mode: "login" | "register" | "guest";
	navigate: (path: string) => void;
}
interface RegistrationField {id:number;name:string;type:string;options:string[];placeholder:string|null;is_required:boolean}

export function AuthPage({ mode, navigate }: AuthPageProps) {
	const config = getConfig();
	const [login, setLogin] = useState("");
	const [firstName, setFirstName] = useState("");
	const [lastName, setLastName] = useState("");
	const [email, setEmail] = useState("");
	const [password, setPassword] = useState("");
	const [confirmPassword, setConfirmPassword] = useState("");
	const [subject, setSubject] = useState("");
	const [description, setDescription] = useState("");
	const [files, setFiles] = useState<File[]>([]);
	const [remember, setRemember] = useState(false);
	const [showPassword, setShowPassword] = useState(false);
	const [showConfirmation, setShowConfirmation] = useState(false);
	const [busy, setBusy] = useState(false);
	const [error, setError] = useState<string | null>(null);
	const [guestTicket, setGuestTicket] = useState<{track_id:string;account_created:boolean}|null>(null);
	const [registrationFields,setRegistrationFields]=useState<RegistrationField[]>([]);
	const [customFields,setCustomFields]=useState<Record<number,string>>({});
	useEffect(()=>{if(mode==='register')apiGet<RegistrationField[]>('auth/registration-fields').then(setRegistrationFields).catch(()=>setRegistrationFields([]));},[mode]);

	const submit = async (event: FormEvent) => {
		event.preventDefault();
		if (mode === "register" && password !== confirmPassword) {
			setError("Passwords do not match.");
			return;
		}
		setBusy(true);
		setError(null);
		try {
			if (mode === "guest") {
				const token=await recaptchaToken("guest_ticket");
				const body = new FormData();
				body.append("first_name", firstName);
				body.append("last_name", lastName);
				body.append("email", email);
				body.append("subject", subject);
				body.append("content", description);
				body.append("recaptcha_token", token);
				if (files[0]) body.append("file", files[0]);
				const response = await apiPostForm<{ticket:{track_id:string};account_created:boolean}>("portal/guest-tickets", body);
				setGuestTicket({track_id:response.ticket.track_id,account_created:response.account_created});
				setBusy(false);
				return;
			}

			const token=await recaptchaToken(mode === "login" ? "login" : "registration");
			const response = mode === "login"
					? await apiPost<{ redirect: string }>("auth/login", { login, password, remember, recaptcha_token:token })
					: await apiPost<{ redirect: string }>("auth/register", {
							first_name: firstName,
							last_name: lastName,
							email,
							password,
							password_confirmation: confirmPassword,
							custom_fields: customFields,
							recaptcha_token: token,
						});
			window.location.assign(response.redirect);
		} catch (reason) {
			setError(reason instanceof Error ? reason.message : mode === "guest" ? "The ticket could not be submitted." : "Authentication failed.");
			setBusy(false);
		}
	};

	const headingLabel = mode === "login" ? "Login" : mode === "register" ? "Register" : "Create Ticket as a Guest";

	return (
		<main className="sbay-auth-page">
			<section className="sbay-auth-card">
				<header className="sbay-auth-card-header">
					<div className="sbay-auth-brand">
						<img src={config.portalLogoUrl} alt={config.siteName}/>
					</div>
				</header>
				<nav className="sbay-auth-card-nav">
					<div className="sbay-auth-card-nav-left">
						<a className="sbay-auth-nav-btn" href={config.homeUrl} aria-label="Home">
							<span className="sbay-auth-nav-btn-icon" aria-hidden="true">⌂</span>
						</a>
						{mode === "login" && config.registrationEnabled ? (
							<button type="button" className="sbay-auth-nav-btn" onClick={() => navigate("/support/register/")}>
								<span className="sbay-auth-nav-btn-icon" aria-hidden="true">♙</span> Register
							</button>
						) : null}
						{mode === "register" ? (
							<button type="button" className="sbay-auth-nav-btn" onClick={() => navigate("/support/login/")}>
								<span className="sbay-auth-nav-btn-icon" aria-hidden="true">♙</span> Login
							</button>
						) : null}
						{mode === "guest" ? (
							<button type="button" className="sbay-auth-nav-btn" onClick={() => navigate("/support/login/")}>
								<span className="sbay-auth-nav-btn-icon" aria-hidden="true">♙</span> Login
							</button>
						) : null}
					</div>
					<div className="sbay-auth-card-nav-right">
						{mode === "guest" && config.registrationEnabled ? (
							<button type="button" className="sbay-auth-nav-btn" onClick={() => navigate("/support/register/")}>
								<span className="sbay-auth-nav-btn-icon" aria-hidden="true">♙</span> Register
							</button>
						) : null}
						{config.guestTicketCreationEnabled && mode !== "guest" ? (
							<button type="button" className="sbay-auth-nav-btn sbay-auth-nav-btn-primary" onClick={() => navigate("/support/guest-ticket/")}>
								<span className="sbay-auth-nav-btn-icon" aria-hidden="true">＋</span> Create Ticket as a Guest
							</button>
						) : null}
					</div>
				</nav>
				<div className="sbay-auth-card-body">
					{config.availabilityNotices.map(notice => (
						<aside className={`sbay-availability-notice is-${notice.type}`} role="status" key={notice.type}>
							{notice.message}
						</aside>
					))}
					{guestTicket ? (
						<section className="sbay-guest-ticket-success" role="status">
							<h1>Ticket submitted</h1>
							<p>Your ticket <strong>#{guestTicket.track_id}</strong> was created successfully. We sent the ticket confirmation to your email address.</p>
							{guestTicket.account_created ? <p>A customer account was also created for you. Check your email to set your password.</p> : null}
							<button className="sbay-auth-btn sbay-auth-btn-primary" type="button" onClick={() => navigate('/support/login/')}>Go to Login</button>
						</section>
					) : (
						<>
							<h1>{headingLabel}</h1>
							{mode !== "guest" && config.oauthLoginProviders.length ? (
								<div className="sbay-auth-oauth">
									{config.oauthLoginProviders.map(provider => (
										<a href={provider.url} key={provider.slug}>
											<strong>{provider.name}</strong>
											<span>{mode === "register" ? `Register with ${provider.name}` : `Login with ${provider.name}`}</span>
										</a>
									))}
									<div className="sbay-auth-oauth-divider"><span>or</span></div>
								</div>
							) : null}
							<form className="sbay-auth-form" onSubmit={submit}>
								{mode === "register" || mode === "guest" ? (
									<>
										<div className="sbay-name-fields">
											<div className="sbay-auth-form-group">
												<label htmlFor="firstName">First Name</label>
												<input
													id="firstName"
													value={firstName}
													onChange={event => setFirstName(event.target.value)}
													autoComplete="given-name"
													required
													maxLength={100}
												/>
											</div>
											<div className="sbay-auth-form-group">
												<label htmlFor="lastName">Last Name</label>
												<input
													id="lastName"
													value={lastName}
													onChange={event => setLastName(event.target.value)}
													autoComplete="family-name"
													required
													maxLength={100}
												/>
											</div>
										</div>
										<div className="sbay-auth-form-group">
											<label htmlFor="email">Email Address</label>
											<input
												id="email"
												type="email"
												value={email}
												onChange={event => setEmail(event.target.value)}
												autoComplete="email"
												required
											/>
										</div>
									</>
								) : (
									<div className="sbay-auth-form-group">
										<label htmlFor="login">Username or Email Address</label>
										<input id="login" value={login} onChange={event => setLogin(event.target.value)} autoComplete="username" required />
									</div>
								)}
								{mode === "guest" ? (
									<>
										<div className="sbay-auth-form-group">
											<label htmlFor="subject">Subject</label>
											<input
												id="subject"
												value={subject}
												onChange={event => setSubject(event.target.value)}
												required
												maxLength={255}
											/>
										</div>
										<div className="sbay-auth-form-group">
											<label>Description</label>
											<RichTextEditor value={description} onChange={setDescription} disabled={busy}/>
										</div>
										{config.fileUploadEnabled ? (
											<FilePicker files={files} onChange={next => setFiles(next.slice(0, 1))} disabled={busy} maxSizeMb={config.fileUploadMaxSizeMb} allowedExtensions={config.fileUploadAllowedExtensions}/>
										) : null}
									</>
								) : (
									<div className="sbay-auth-form-group">
										<label htmlFor="password">Password</label>
										<div className="sbay-password-input">
											<input
												id="password"
												type={showPassword ? "text" : "password"}
												value={password}
												onChange={event => setPassword(event.target.value)}
												autoComplete={mode === "login" ? "current-password" : "new-password"}
												required
												minLength={mode === "register" ? 8 : undefined}
											/>
											<button
												type="button"
												className="sbay-password-toggle"
												aria-label={showPassword ? "Hide password" : "Show password"}
												onClick={() => setShowPassword(!showPassword)}
											>
												{showPassword ? "Hide" : "Show"}
											</button>
										</div>
									</div>
								)}
								{mode === "register" ? (
									<>
										<div className="sbay-auth-form-group">
											<label htmlFor="confirmPassword">Confirm Password</label>
											<div className="sbay-password-input">
												<input
													id="confirmPassword"
													type={showConfirmation ? "text" : "password"}
													value={confirmPassword}
													onChange={event => setConfirmPassword(event.target.value)}
													autoComplete="new-password"
													required
													minLength={8}
												/>
												<button
													type="button"
													className="sbay-password-toggle"
													aria-label={showConfirmation ? "Hide confirmed password" : "Show confirmed password"}
													onClick={() => setShowConfirmation(!showConfirmation)}
												>
													{showConfirmation ? "Hide" : "Show"}
												</button>
											</div>
										</div>
										{registrationFields.map(field => {
											const value = customFields[field.id] ?? '';
											const update = (next: string) => setCustomFields(current => ({ ...current, [field.id]: next }));
											if (field.type === 'textarea') {
												return (
													<div className="sbay-auth-form-group" key={field.id}>
														<label htmlFor={`field-${field.id}`}>{field.name}</label>
														<textarea
															id={`field-${field.id}`}
															rows={4}
															placeholder={field.placeholder ?? undefined}
															required={field.is_required}
															value={value}
															onChange={event => update(event.target.value)}
														/>
													</div>
												);
											}
											if (field.type === 'select') {
												return (
													<div className="sbay-auth-form-group" key={field.id}>
														<label htmlFor={`field-${field.id}`}>{field.name}</label>
														<select id={`field-${field.id}`} required={field.is_required} value={value} onChange={event => update(event.target.value)}>
															<option value="">Select {field.name}</option>
															{field.options.map(option => <option key={option} value={option}>{option}</option>)}
														</select>
													</div>
												);
											}
											if (field.type === 'checkbox') {
												return (
													<div className="sbay-auth-checkbox" key={field.id}>
														<input
															id={`field-${field.id}`}
															type="checkbox"
															required={field.is_required}
															checked={value === '1'}
															onChange={event => update(event.target.checked ? '1' : '0')}
														/>
														<label htmlFor={`field-${field.id}`}>{field.name}</label>
													</div>
												);
											}
											return (
												<div className="sbay-auth-form-group" key={field.id}>
													<label htmlFor={`field-${field.id}`}>{field.name}</label>
													<input
														id={`field-${field.id}`}
														type={field.type}
														placeholder={field.placeholder ?? undefined}
														required={field.is_required}
														value={value}
														onChange={event => update(event.target.value)}
													/>
												</div>
											);
										})}
									</>
								) : null}
								{error ? <p className="sbay-form-error" role="alert">{error}</p> : null}
								{mode === "login" ? (
									<div className="sbay-auth-row">
										<div className="sbay-auth-checkbox">
											<input id="remember" type="checkbox" checked={remember} onChange={event => setRemember(event.target.checked)} />
											<label htmlFor="remember">Remember me</label>
										</div>
										<button className="sbay-auth-btn sbay-auth-btn-primary" disabled={busy}>
											{busy ? "Logging in…" : "Login"}
										</button>
									</div>
								) : (
									<button className="sbay-auth-btn sbay-auth-btn-primary sbay-auth-btn-block" disabled={busy || (mode === "guest" && description.replace(/<[^>]*>/g, '').trim() === '')}>
										{busy ? (mode === "register" ? "Creating account…" : "Submitting ticket…") : mode === "register" ? "Register" : "Create Ticket"}
									</button>
								)}
							</form>
						</>
					)}
					{mode === "login" ? (
						<div className="sbay-auth-footer">
							<div className="sbay-auth-links">
								<span className="sbay-auth-muted">Lost your password?</span>
								<a onClick={() => navigate("/support/reset-password/")}>Reset Password</a>
							</div>
						</div>
					) : null}
					{mode === "guest" && config.registrationEnabled ? (
						<div className="sbay-auth-register-prompt">
							Don't have an account? <a onClick={() => navigate("/support/register/")}>Register Now</a>
						</div>
					) : null}
				</div>
			</section>
			<PortalCopyright />
		</main>
	);
}