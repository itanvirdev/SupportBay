import { HomeOutlined, PlusOutlined, UserOutlined } from "@ant-design/icons";
import { Alert, Button, Card, Col, Divider, Flex, Form, Image, Input, Row, Space, Spin, theme, Tooltip } from "antd";
import Link from "antd/es/typography/Link";
import Text from "antd/es/typography/Text";
import Title from "antd/es/typography/Title";
import { useState } from "react";
import { apiPost } from "../../api/client";
import { PortalCopyright } from "../../components/PortalCopyright";
import { getConfig } from "../../core/config";
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

	const { token } = theme.useToken();

	return (
		<main className="sbay-auth-page">
			{/* loading state */}
			{busy ? (
				<Spin
					styles={{
						indicator: {
							color: token.colorPrimary,
						},
					}}
					fullscreen
				/>
			) : null}

			<Flex className="sbay-flex" align="center" justify="center" vertical style={{ maxWidth: "620px", width: "100%" }}>
				<Flex className="sbay-auth-page-content" align="center" justify="center" vertical>
					<Card className="sbay-auth-card">
						<Row>
							{/* Logo */}
							<Col span={24} style={{ marginBottom: "24px" }}>
								<Flex align="center" justify="center">
									<Link href={config.homeUrl}>
										<Image
											className="sbay-portal-logo"
											src={config.portalLogoUrl}
											alt={config.siteName}
											width={"auto"}
											height={40}
											preview={false}
										/>
									</Link>
								</Flex>
							</Col>

							{/* Auth navigation */}
							<Col span={24}>
								<Divider style={{ margin: "0px 0px 12px" }} />
								<Row gutter={"small"} justify={"space-between"}>
									<Col>
										<Flex wrap gap={"small"}>
											<Tooltip title="Home">
												<Button className="sbay-home" icon={<HomeOutlined />} href={config.homeUrl} />
											</Tooltip>

											<Button
												className="sbay-login"
												icon={<UserOutlined />}
												onClick={() => navigate("/support/login/")}
											>
												Login
											</Button>
										</Flex>
									</Col>

									{config.guestTicketCreationEnabled ? (
										<Col>
											<Flex gap={"small"}>
												<Button
													className="sbay-create-guest-ticket"
													icon={<PlusOutlined />}
													type="primary"
													ghost
													onClick={() => navigate("/support/guest-ticket/")}
												>
													Create Ticket as a Guest
												</Button>
											</Flex>
										</Col>
									) : null}
								</Row>
								<Divider style={{ margin: "12px 0px 24px 0px" }} />
							</Col>
						</Row>

						<Row>
							<Col span={24}>
								{/* Auth title */}
								<Title className="sbay-auth-title" level={5} style={{ marginBottom: "5px" }}>
									Reset Password
								</Title>
								<Flex style={{ marginBottom: "24px" }}>
									<Text>Enter your username or email address and we'll email you a link to reset your password.</Text>
								</Flex>

								<Col span={24}>
									<Form layout="vertical" onFinish={submit} autoComplete="off">
										<Row gutter={16}>
											{/* username or email address */}
											<Col span={24}>
												<Form.Item
													label="Username or Email Address"
													name="reset-login"
													rules={[{ required: true, message: "Username or email address is required." }]}
												>
													<Input
														id="reset-login"
														value={login}
														onChange={e => setLogin(e.target.value)}
														autoComplete="username"
													/>
												</Form.Item>
											</Col>

											{/* Form error alert */}
											{error ? (
												<Col span={24}>
													<Alert title={error} type="error" />
												</Col>
											) : null}

											{/* Form success alert */}
											{success ? (
												<Col span={24}>
													<Alert title={success} type="success" />
												</Col>
											) : null}

											<Col span={24}>
												<Space style={{ display: "flex", justifyContent: "end" }}>
													<Form.Item label={null}>
														<Button type="primary" htmlType="submit" block disabled={busy}>
															{busy ? "Sending…" : "Get New Password"}
														</Button>
													</Form.Item>
												</Space>
											</Col>
										</Row>
									</Form>

									{/* Register */}
									<Divider style={{ margin: "0px 0px 24px" }} />
									<Flex justify="center" gap={"small"}>
										<Text>Don't have an account?</Text>

										<Link
											className="sbay-anchor"
											onClick={() => navigate("/support/register/")}
											style={{ color: token.colorPrimary }}
										>
											Register Now
										</Link>
									</Flex>
								</Col>
							</Col>
						</Row>
					</Card>

					{/* Copyright Text */}
					<PortalCopyright />
				</Flex>
			</Flex>
		</main>
	);
}
