import { FormEvent, useState } from "react";
import { apiPost } from "../../api/client";
import { getConfig } from "../../core/config";
import { PortalCopyright } from "../../components/PortalCopyright";
import { recaptchaToken } from "../../core/recaptcha";
import { Card, Form, Input, Button, Space, Typography } from "antd";

interface ResetPasswordPageProps {
	navigate: (path: string) => void;
}

export function ResetPasswordPage({ navigate }: ResetPasswordPageProps) {
	const config = getConfig();
	const [login, setLogin] = useState("");
	const [busy, setBusy] = useState(false);
	const [error, setError] = useState<string | null>(null);
	const [success, setSuccess] = useState<string | null>(null);

	const submit = async () => {
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
				</div>
				<div className="sbay-auth-card-body">
					<Typography.Title level={3}>Reset Password</Typography.Title>
					<Typography.Text>
						Enter your username or email address and we'll email you a link to reset your password.
					</Typography.Text>
					<Form layout="vertical" className="sbay-auth-form" onFinish={submit}>
						<Form.Item
							label="Username or Email Address"
							hasFeedback
						>
							<Input
								id="reset-login"
								value={login}
								onChange={(e) => setLogin(e.target.value)}
								autoComplete="username"
								placeholder="Enter your username or email"
							/>
						</Form.Item>
						{error ? (
							<Typography.Text type="danger" className="sbay-form-error" role="alert">
								{error}
							</Typography.Text>
						) : null}
						{success ? (
							<Typography.Text type="success" className="sbay-form-success" role="status">
								{success}
							</Typography.Text>
						) : null}
						<Form.Item>
							<Button type="primary" htmlType="submit" block disabled={busy}>
								{busy ? "Sending…" : "Get New Password"}
							</Button>
						</Form.Item>
					</Form>
					{config.registrationEnabled ? (
						<Space direction="vertical" className="sbay-auth-register-prompt">
							<Typography.Text>
								Don't have an account?{' '}
								<Button type="link" className="sbay-auth-link" onClick={() => navigate("/support/register/")}>
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