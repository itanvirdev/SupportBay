import { FormEvent, useEffect, useState } from "react";
import { apiGet, apiPost, apiPostForm } from "../../api/client";
import { getConfig } from "../../core/config";
import { PortalCopyright } from "../../components/PortalCopyright";
import { FilePicker } from "../../components/FilePicker";
import { RichTextEditor } from "../../../shared/editor/RichTextEditor";
import { recaptchaToken } from "../../core/recaptcha";
import { Card, Form, Input, Button, Space, Typography, Switch, Select } from "antd";

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
			<Card bordered={false} className="sbay-auth-card">
				<div className="sbay-auth-card-header">
					<div className="sbay-auth-brand">
						<img src={config.portalLogoUrl} alt={config.siteName} />
					</div>
				</div>
				<div className="sbay-auth-card-nav">
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
				</div>
				<div className="sbay-auth-card-body">
					{config.availabilityNotices.map(notice => (
						<aside className={`sbay-availability-notice is-${notice.type}`} role="status" key={notice.type}>
							{notice.message}
						</aside>
					))}
					{guestTicket ? (
						<section className="sbay-guest-ticket-success" role="status">
							<Typography.Title level={3}>Ticket submitted</Typography.Title>
							<Typography.Text>
								Your ticket <strong>#{guestTicket.track_id}</strong> was created successfully. We sent the ticket confirmation to your email address.
							</Typography.Text>
							{guestTicket.account_created ? (
								<Typography.Text>
									A customer account was also created for you. Check your email to set your password.
								</Typography.Text>
							) : null}
							<Button type="primary" onClick={() => navigate("/support/login/")}>
								Go to Login
							</Button>
						</section>
					) : (
						<>
							<Typography.Title level={3}>{headingLabel}</Typography.Title>
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
							<Form layout="vertical" className="sbay-auth-form" onFinish={submit}>
								{mode === "register" || mode === "guest" ? (
									<>
										<div className="sbay-name-fields">
											<Form.Item label="First Name" required>
												<Input
													id="firstName"
													value={firstName}
													onChange={event => setFirstName(event.target.value)}
													autoComplete="given-name"
													maxLength={100}
												/>
											</Form.Item>
											<Form.Item label="Last Name" required>
												<Input
													id="lastName"
													value={lastName}
													onChange={event => setLastName(event.target.value)}
													autoComplete="family-name"
													maxLength={100}
												/>
											</Form.Item>
										</div>
										<Form.Item label="Email Address" required>
											<Input
												id="email"
												type="email"
												value={email}
												onChange={event => setEmail(event.target.value)}
												autoComplete="email"
											/>
										</Form.Item>
									</>
								) : (
									<Form.Item label="Username or Email Address" required>
										<Input id="login" value={login} onChange={event => setLogin(event.target.value)} autoComplete="username" />
									</Form.Item>
								)}
								{mode === "guest" ? (
									<>
										<Form.Item label="Subject" required>
											<Input
												id="subject"
												value={subject}
												onChange={event => setSubject(event.target.value)}
												maxLength={255}
											/>
										</Form.Item>
										<Form.Item label="Description" required>
											<RichTextEditor value={description} onChange={setDescription} disabled={busy} />
										</Form.Item>
										{config.fileUploadEnabled ? (
											<FilePicker files={files} onChange={next => setFiles(next.slice(0, 1))} disabled={busy} maxSizeMb={config.fileUploadMaxSizeMb} allowedExtensions={config.fileUploadAllowedExtensions} />
										) : null}
									</>
								) : (
									<Form.Item label="Password" required>
										<Input.Password
											id="password"
											value={password}
											onChange={event => setPassword(event.target.value)}
											autoComplete={mode === "login" ? "current-password" : "new-password"}
											minLength={mode === "register" ? 8 : undefined}
										/>
									</Form.Item>
								)}
								{mode === "register" ? (
									<>
										<Form.Item label="Confirm Password" required>
											<Input.Password
												id="confirmPassword"
												value={confirmPassword}
												onChange={event => setConfirmPassword(event.target.value)}
												autoComplete="new-password"
												minLength={8}
											/>
										</Form.Item>
										{registrationFields.map(field => {
											const value = customFields[field.id] ?? '';
											const update = (next: string) => setCustomFields(current => ({ ...current, [field.id]: next }));
											if (field.type === 'textarea') {
												return (
													<Form.Item key={field.id} label={field.name} required={field.is_required}>
														<Input.TextArea
															id={`field-${field.id}`}
															rows={4}
															placeholder={field.placeholder ?? ''}
															value={value}
															onChange={event => update(event.target.value)}
														/>
													</Form.Item>
												);
											}
											if (field.type === 'select') {
												return (
													<Form.Item key={field.id} label={field.name} required={field.is_required}>
														<Select
															placeholder={`Select ${field.name}`}
															value={value || undefined}
															onChange={(next: string) => update(next)}
															options={field.options.map(option => ({ label: option, value: option }))}
														/>
													</Form.Item>
												);
											}
											if (field.type === 'checkbox') {
												return (
													<Form.Item key={field.id} valuePropName="checked">
														<Switch
															id={`field-${field.id}`}
															checked={value === '1'}
															onChange={checked => update(checked ? '1' : '0')}
														/>
														<label htmlFor={`field-${field.id}`}>{field.name}</label>
													</Form.Item>
												);
											}
											return (
												<Form.Item key={field.id} label={field.name} required={field.is_required}>
													<Input
														id={`field-${field.id}`}
														type={field.type}
														placeholder={field.placeholder ?? ''}
														value={value}
														onChange={event => update(event.target.value)}
													/>
												</Form.Item>
											);
										})}
									</>
								) : null}
								{error ? (
									<Typography.Text type="danger" className="sbay-form-error" role="alert">
										{error}
									</Typography.Text>
								) : null}
								{mode === "login" ? (
									<Space className="sbay-auth-row">
										<Switch checked={remember} onChange={checked => setRemember(checked)} />
										<Typography.Text>Remember me</Typography.Text>
										<Space>
											<Button type="primary" htmlType="submit" disabled={busy}>
												{busy ? "Logging in…" : "Login"}
											</Button>
										</Space>
									</Space>
								) : (
									<Form.Item>
										<Button type="primary" htmlType="submit" block disabled={busy || (mode === "guest" && description.replace(/<[^>]*>/g, '').trim() === '')}>
											{busy ? (mode === "register" ? "Creating account…" : "Submitting ticket…") : mode === "register" ? "Register" : "Create Ticket"}
										</Button>
									</Form.Item>
								)}
							</Form>
						</>
					)}
					{mode === "login" ? (
						<div className="sbay-auth-footer">
							<div className="sbay-auth-links">
								<Typography.Text type="secondary" className="sbay-auth-muted">
									Lost your password?
								</Typography.Text>
								<Button type="link" onClick={() => navigate("/support/reset-password/")}>
									Reset Password
								</Button>
							</div>
						</div>
					) : null}
					{mode === "guest" && config.registrationEnabled ? (
						<Space direction="vertical" className="sbay-auth-register-prompt">
							<Typography.Text>
								Don't have an account?{' '}
								<Button type="link" onClick={() => navigate("/support/register/")}>
									Register Now
								</Button>
							</Typography.Text>
						</Space>
					) : null}
				</div>
			</Card>
			<PortalCopyright />
		</main>
	);
}