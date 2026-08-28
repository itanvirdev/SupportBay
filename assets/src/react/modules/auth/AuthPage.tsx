import { FormEvent, useState } from "react";
import { apiPost, apiPostForm } from "../../api/client";
import { getConfig } from "../../core/config";
import { PortalCopyright } from "../../components/PortalCopyright";
import { FilePicker } from "../../components/FilePicker";
import { RichTextEditor } from "../../../shared/editor/RichTextEditor";
import { recaptchaToken } from "../../core/recaptcha";

interface AuthPageProps {
	mode: "login" | "register" | "guest";
	navigate: (path: string) => void;
}

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
							recaptcha_token: token,
						});
			window.location.assign(response.redirect);
		} catch (reason) {
			setError(reason instanceof Error ? reason.message : mode === "guest" ? "The ticket could not be submitted." : "Authentication failed.");
			setBusy(false);
		}
	};

	return (
		<main className="sbay-auth-page">
			<section className="sbay-auth-card">
				<header>
					<div className="sbay-auth-brand">
						<img src={config.portalLogoUrl} alt={config.siteName}/>
					</div>
				</header>
				<nav>
					<a href={config.homeUrl} aria-label="Home">
						<span aria-hidden="true">⌂</span>
					</a>
					{mode === "guest" && config.registrationEnabled ? <button type="button" onClick={()=>navigate("/support/register/")}><span aria-hidden="true">♙</span> Register</button> : mode === "login" && config.registrationEnabled ? (
						<button type="button" onClick={() => navigate("/support/register/")}>
							<span aria-hidden="true">♙</span> Register
						</button>
					) : mode === "register" ? (
						<button type="button" onClick={() => navigate("/support/login/")}>
							<span aria-hidden="true">♙</span> Login
						</button>
					) : null}
					{mode === "guest" ? <button className="sbay-returning-login" type="button" onClick={()=>navigate("/support/login/")}><span aria-hidden="true">♙</span> Returning user? Login</button>:null}
					{config.guestTicketCreationEnabled && mode !== "guest" ? <button className="sbay-guest-ticket-link" type="button" onClick={()=>navigate("/support/guest-ticket/")}><span aria-hidden="true">＋</span> Create Ticket as a Guest</button>:null}
				</nav>
				{config.availabilityNotices.map(notice=><aside className={`sbay-availability-notice is-${notice.type}`} role="status" key={notice.type}>{notice.message}</aside>)}
				{guestTicket ? <section className="sbay-guest-ticket-success" role="status"><h1>Ticket submitted</h1><p>Your ticket <strong>#{guestTicket.track_id}</strong> was created successfully. We sent the ticket confirmation to your email address.</p>{guestTicket.account_created?<p>A customer account was also created for you. Check your email to set your password.</p>:null}<button className="sbay-primary-button" type="button" onClick={()=>navigate('/support/login/')}>Go to Login</button></section> :
				<>{mode !== "guest" && config.oauthLoginProviders.length?<div className="sbay-provider-auth">{config.oauthLoginProviders.map(provider=><a href={provider.url} key={provider.slug}><strong>{provider.name}</strong><span>{mode==="register"?`Register with ${provider.name}`:`Login with ${provider.name}`}</span></a>)}<div><span>or</span></div></div>:null}<form onSubmit={submit}>
					<h1>{mode === "login" ? "Login" : mode === "register" ? "Register" : "Create Ticket as a Guest"}</h1>
					{mode === "register" || mode === "guest" ? (
						<>
							<div className="sbay-auth-name-fields">
								<label>
									<span>First Name</span>
									<input
										value={firstName}
										onChange={event => setFirstName(event.target.value)}
										autoComplete="given-name"
										required
										maxLength={100}
									/>
								</label>
								<label>
									<span>Last Name</span>
									<input
										value={lastName}
										onChange={event => setLastName(event.target.value)}
										autoComplete="family-name"
										required
										maxLength={100}
									/>
								</label>
							</div>
							<label>
								<span>Email Address</span>
								<input
									type="email"
									value={email}
									onChange={event => setEmail(event.target.value)}
									autoComplete="email"
									required
								/>
							</label>
						</>
					) : (
						<label>
							<span>Username or Email Address</span>
							<input value={login} onChange={event => setLogin(event.target.value)} autoComplete="username" required />
						</label>
					)}
					{mode === "guest" ? <><label><span>Subject</span><input value={subject} onChange={event=>setSubject(event.target.value)} required maxLength={255}/></label><div className="sbay-auth-editor"><span>Description</span><RichTextEditor value={description} onChange={setDescription} disabled={busy}/></div>{config.fileUploadEnabled?<FilePicker files={files} onChange={next=>setFiles(next.slice(0,1))} disabled={busy} maxSizeMb={config.fileUploadMaxSizeMb} allowedExtensions={config.fileUploadAllowedExtensions}/>:null}</> : <label>
						<span>Password</span>
						<div className="sbay-password-input">
							<input
								type={showPassword ? "text" : "password"}
								value={password}
								onChange={event => setPassword(event.target.value)}
								autoComplete={mode === "login" ? "current-password" : "new-password"}
								required
								minLength={mode === "register" ? 8 : undefined}
							/>
							<button
								type="button"
								aria-label={showPassword ? "Hide password" : "Show password"}
								onClick={() => setShowPassword(!showPassword)}
							>
								{showPassword ? "Hide" : "Show"}
							</button>
						</div>
					</label>}
					{mode === "register" ? (
						<label>
							<span>Confirm Password</span>
							<div className="sbay-password-input">
								<input
									type={showConfirmation ? "text" : "password"}
									value={confirmPassword}
									onChange={event => setConfirmPassword(event.target.value)}
									autoComplete="new-password"
									required
									minLength={8}
								/>
								<button
									type="button"
									aria-label={showConfirmation ? "Hide confirmed password" : "Show confirmed password"}
									onClick={() => setShowConfirmation(!showConfirmation)}
								>
									{showConfirmation ? "Hide" : "Show"}
								</button>
							</div>
						</label>
					) : mode === "login" ? (
						<label className="sbay-auth-remember">
							<input type="checkbox" checked={remember} onChange={event => setRemember(event.target.checked)} />
							<span>Remember me</span>
						</label>
					) : null}
					{error ? (
						<p className="sbay-form-error" role="alert">
							{error}
						</p>
					) : null}
					<button className="sbay-primary-button" disabled={busy || (mode === "guest" && description.replace(/<[^>]*>/g, '').trim() === '')}>
						{busy ? (mode === "login" ? "Logging in…" : mode === "register" ? "Creating account…" : "Submitting ticket…") : mode === "login" ? "Login" : mode === "register" ? "Register" : "Create Ticket"}
					</button>
				</form></>}
				{mode === "login" ? (
					<footer>
						Lost your password? <a href={config.resetPasswordUrl}>Reset Password</a>
					</footer>
				) : null}
			</section>
			<PortalCopyright />
		</main>
	);
}
