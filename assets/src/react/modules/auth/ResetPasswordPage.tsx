import { FormEvent, useState } from "react";
import { apiPost } from "../../api/client";
import { getConfig } from "../../core/config";
import { PortalCopyright } from "../../components/PortalCopyright";
import { recaptchaToken } from "../../core/recaptcha";

interface ResetPasswordPageProps {
	navigate: (path: string) => void;
}

export function ResetPasswordPage({ navigate }: ResetPasswordPageProps) {
	const config = getConfig();
	const [login, setLogin] = useState("");
	const [busy, setBusy] = useState(false);
	const [error, setError] = useState<string | null>(null);
	const [success, setSuccess] = useState<string | null>(null);

	const submit = async (event: FormEvent) => {
		event.preventDefault();
		setBusy(true);
		setError(null);
		setSuccess(null);
		try {
			const token = await recaptchaToken("login");
			await apiPost<{ message: string }>("auth/lost-password", { login, recaptcha_token: token });
			setSuccess("Check your email for the confirmation link, then visit the login page.");
			setLogin("");
		} catch (reason) {
			setError(reason instanceof Error ? reason.message : "Password reset request could not be processed.");
		} finally {
			setBusy(false);
		}
	};

	return (
		<main className="sbay-auth-page">
			<section className="sbay-auth-card">
				<header className="sbay-auth-card-header">
					<div className="sbay-auth-brand">
						<img src={config.portalLogoUrl} alt={config.siteName} />
					</div>
				</header>
				<nav className="sbay-auth-card-nav">
					<div className="sbay-auth-card-nav-left">
						<a className="sbay-auth-nav-btn" href={config.homeUrl} aria-label="Home">
							<span className="sbay-auth-nav-btn-icon" aria-hidden="true">⌂</span>
						</a>
						<button type="button" className="sbay-auth-nav-btn" onClick={() => navigate("/support/login/")}>
							<span className="sbay-auth-nav-btn-icon" aria-hidden="true">♙</span> Login
						</button>
					</div>
					<div className="sbay-auth-card-nav-right">
						{config.guestTicketCreationEnabled ? (
							<button type="button" className="sbay-auth-nav-btn sbay-auth-nav-btn-primary" onClick={() => navigate("/support/guest-ticket/")}>
								<span className="sbay-auth-nav-btn-icon" aria-hidden="true">＋</span> Create Ticket as a Guest
							</button>
						) : null}
					</div>
				</nav>
				<div className="sbay-auth-card-body">
					<h1>Reset Password</h1>
					<p>Enter your username or email address and we'll email you a link to reset your password.</p>
					<form className="sbay-auth-form" onSubmit={submit}>
						<div className="sbay-auth-form-group">
							<label htmlFor="reset-login">Username or Email Address</label>
							<input
								id="reset-login"
								value={login}
								onChange={(event) => setLogin(event.target.value)}
								autoComplete="username"
								required
							/>
						</div>
						{error ? (
							<p className="sbay-form-error" role="alert">{error}</p>
						) : null}
						{success ? (
							<p className="sbay-form-success" role="status">{success}</p>
						) : null}
						<button className="sbay-auth-btn sbay-auth-btn-primary sbay-auth-btn-block" disabled={busy}>
							{busy ? "Sending…" : "Get New Password"}
						</button>
					</form>
					{config.registrationEnabled ? (
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