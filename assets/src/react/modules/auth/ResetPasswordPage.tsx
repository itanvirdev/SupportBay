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
				<header>
					<div className="sbay-auth-brand">
						<img src={config.portalLogoUrl} alt={config.siteName} />
					</div>
				</header>
				<nav>
					<a href={config.homeUrl} aria-label="Home">
						<span aria-hidden="true">⌂</span>
					</a>
					<button type="button" onClick={() => navigate("/support/login/")}>
						<span aria-hidden="true">♙</span> Login
					</button>
					{config.guestTicketCreationEnabled ? (
						<button type="button" onClick={() => navigate("/support/guest-ticket/")} className="sbay-guest-ticket-link">
							<span aria-hidden="true">＋</span> Create Ticket as a Guest
						</button>
					) : null}
				</nav>
				{config.availabilityNotices.map((notice) => (
					<aside className={`sbay-availability-notice is-${notice.type}`} role="status" key={notice.type}>
						{notice.message}
					</aside>
				))}
				<form onSubmit={submit}>
					<h1>Reset Password</h1>
					<p>Enter your username or email address and we'll email you a link to reset your password.</p>
					<label>
						<span>Username or Email Address</span>
						<input
							value={login}
							onChange={(event) => setLogin(event.target.value)}
							autoComplete="username"
							required
						/>
					</label>
					{error ? (
						<p className="sbay-form-error" role="alert">
							{error}
						</p>
					) : null}
					{success ? (
						<p className="sbay-form-success" role="status">
							{success}
						</p>
					) : null}
					<button className="sbay-primary-button" disabled={busy}>
						{busy ? "Sending…" : "Get New Password"}
					</button>
				</form>
				{config.registrationEnabled ? (
					<div className="sbay-auth-register-prompt">
						Don't have an account? <a onClick={() => navigate("/support/register/")}>Register Now</a>
					</div>
				) : null}
			</section>
			<PortalCopyright />
		</main>
	);
}