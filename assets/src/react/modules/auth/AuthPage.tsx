import {
	HomeOutlined,
	LoginOutlined,
	PaperClipOutlined,
	PlusOutlined,
	UserAddOutlined,
	UserOutlined,
} from "@ant-design/icons";
import {
	Alert,
	Button,
	Card,
	Checkbox,
	Col,
	Divider,
	Flex,
	Form,
	Image,
	Input,
	message,
	Row,
	Select,
	Space,
	Spin,
	theme,
	Tooltip,
	Typography,
	Upload,
} from "antd";
import TextArea from "antd/es/input/TextArea";
import Dragger from "antd/es/upload/Dragger";
import type { UploadChangeParam } from "antd/es/upload/interface";
import { FormEvent, useEffect, useState } from "react";
import { RichTextEditor } from "../../../shared/editor/RichTextEditor";
import { apiGet, apiPost, apiPostForm } from "../../api/client";
import { PortalCopyright } from "../../components/PortalCopyright";
import { getConfig } from "../../core/config";
import { recaptchaToken } from "../../core/recaptcha";

const { Link, Title, Text } = Typography;

interface AuthPageProps {
	mode: "login" | "register" | "guest";
	navigate: (path: string) => void;
}
interface RegistrationField {
	id: number;
	name: string;
	type: string;
	options: string[];
	placeholder: string | null;
	is_required: boolean;
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
	const [guestTicket, setGuestTicket] = useState<{ track_id: string; account_created: boolean } | null>(null);
	const [registrationFields, setRegistrationFields] = useState<RegistrationField[]>([]);
	const [customFields, setCustomFields] = useState<Record<number, string>>({});
	useEffect(() => {
		if (mode === "register")
			apiGet<RegistrationField[]>("auth/registration-fields")
				.then(setRegistrationFields)
				.catch(() => setRegistrationFields([]));
	}, [mode]);

	const submit = async () => {
		if (mode === "register" && password !== confirmPassword) {
			setError("Passwords do not match.");
			return;
		}
		setBusy(true);
		setError(null);
		try {
			if (mode === "guest") {
				const token = await recaptchaToken("guest_ticket");
				const body = new FormData();
				body.append("first_name", firstName);
				body.append("last_name", lastName);
				body.append("email", email);
				body.append("subject", subject);
				body.append("content", description);
				body.append("recaptcha_token", token);
				if (files.length > 0) body.append("file", files[0]);
				const response = await apiPostForm<{ ticket: { track_id: string }; account_created: boolean }>(
					"portal/guest-tickets",
					body
				);
				setGuestTicket({ track_id: response.ticket.track_id, account_created: response.account_created });
				setBusy(false);
				return;
			}

			const token = await recaptchaToken(mode === "login" ? "login" : "registration");
			const response =
				mode === "login"
					? await apiPost<{ redirect: string }>("auth/login", { login, password, remember, recaptcha_token: token })
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
			setError(
				reason instanceof Error
					? reason.message
					: mode === "guest"
						? "The ticket could not be submitted."
						: "Authentication failed."
			);
			setBusy(false);
		}
	};

	const headingLabel = mode === "login" ? "Login" : mode === "register" ? "Register" : "Create Ticket as a Guest";
	const onClose: React.MouseEventHandler<HTMLButtonElement> = e => {
		// console.log(e, 'I was closed.');
	};
	const { token } = theme.useToken();

	const accept = config.fileUploadAllowedExtensions.map(extension => `.${extension}`).join(",");
	const uploadProps = {
		name: "file",
		maxCount: 5,
		accept: accept,
		beforeUpload(file: File) {
			const maxFileSize = file.size / 1024 / 1024 < config.fileUploadMaxSizeMb;
			if (!maxFileSize) {
				message.error(`${file.name} is larger than ${config.fileUploadMaxSizeMb}MB!`);
				return Upload.LIST_IGNORE;
			}
			return true;
		},
		onChange({ fileList }: UploadChangeParam) {
			const newFiles = fileList
				.filter(f => f.status === "uploading" || (f.originFileObj && f.status === "done"))
				.map(f => f.originFileObj as File);
			setFiles(newFiles);
		},
	};

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

											{(mode === "login" || mode === "guest") && config.registrationEnabled ? (
												<Button
													className="sbay-register"
													icon={<UserAddOutlined />}
													onClick={() => navigate("/support/register/")}
												>
													Register
												</Button>
											) : null}
											{mode === "register" ? (
												<Button
													className="sbay-login"
													icon={<UserOutlined />}
													onClick={() => navigate("/support/login/")}
												>
													Login
												</Button>
											) : null}
										</Flex>
									</Col>

									{config.guestTicketCreationEnabled ? (
										<Col>
											<Flex gap={"small"}>
												{mode === "guest" ? (
													<Button
														className="sbay-login-return"
														icon={<UserOutlined />}
														type="primary"
														ghost
														onClick={() => navigate("/support/login/")}
													>
														Returning User? Login
													</Button>
												) : null}

												{mode !== "guest" ? (
													<Button
														className="sbay-create-guest-ticket"
														icon={<PlusOutlined />}
														type="primary"
														ghost
														onClick={() => navigate("/support/guest-ticket/")}
													>
														Create Ticket as a Guest
													</Button>
												) : null}
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
								<Title className="sbay-auth-title" level={5} style={{ marginBottom: "24px" }}>
									{headingLabel}
								</Title>

								<Flex vertical gap={"small"} style={{ marginBottom: "24px" }}>
									{/* Alert: weekend or holiday */}
									{config.availabilityNotices.map(notice => (
										<Alert title={notice.message} key={notice.type} type="warning" />
									))}

									{/* Alert: after create guest ticket. */}
									{guestTicket ? (
										<Alert
											title={
												<>
													Success! Your ticket <strong>#{guestTicket.track_id}</strong> has been created.
													<br />
													Our support team will review it and respond soon.
													<br />
													Thank you for reaching out!
												</>
											}
											type="success"
											closable={{ closeIcon: true, onClose, "aria-label": "close" }}
										/>
									) : null}
								</Flex>

								{/* Login/Register with envato */}
								{mode !== "guest" && config.oauthLoginProviders.length ? (
									<Col span={24}>
										<Flex vertical>
											{config.oauthLoginProviders.map(provider => (
												<Button
													type="primary"
													size="large"
													variant="filled"
													icon={<LoginOutlined />}
													href={provider.url}
													key={provider.slug}
													style={{ background: "rgb(130, 180, 64)", borderColor: "rgb(130, 180, 64)" }}
												>
													{mode === "register" ? `Register with ${provider.name}` : `Login with ${provider.name}`}
												</Button>
											))}

											<Divider plain style={{ margin: "24px 0px" }}>
												or
											</Divider>
										</Flex>
									</Col>
								) : null}

								<Col span={24}>
									<Form layout="vertical" onFinish={submit} autoComplete="off">
										<Row gutter={16}>
											{mode === "register" || mode === "guest" ? (
												<>
													{/* First name */}
													<Col span={12}>
														<Form.Item
															label="First Name"
															name="firstName"
															rules={[{ required: true, message: "First Name is required." }]}
														>
															<Input
																id="firstName"
																value={firstName}
																onChange={event => setFirstName(event.target.value)}
																autoComplete="given-name"
																maxLength={100}
															/>
														</Form.Item>
													</Col>

													{/* Last name */}
													<Col span={12}>
														<Form.Item
															label="Last Name"
															name="lastName"
															rules={[{ required: true, message: "Last Name is required." }]}
														>
															<Input
																id="lastName"
																value={lastName}
																onChange={event => setLastName(event.target.value)}
																autoComplete="family-name"
																maxLength={100}
															/>
														</Form.Item>
													</Col>

													{/* Email address */}
													<Col span={24}>
														<Form.Item
															label="Email Address"
															name="email"
															rules={[{ required: true, message: "Email address is required." }]}
														>
															<Input
																id="email"
																type="email"
																value={email}
																onChange={event => setEmail(event.target.value)}
																autoComplete="email"
															/>
														</Form.Item>
													</Col>
												</>
											) : (
												// Username or Email address
												<Col span={24}>
													<Form.Item
														label="Username or Email Address"
														name="login"
														rules={[{ required: true, message: "Username or email address is required." }]}
													>
														<Input
															id="login"
															value={login}
															onChange={event => setLogin(event.target.value)}
															autoComplete="username"
														/>
													</Form.Item>
												</Col>
											)}

											{mode === "guest" ? (
												<>
													{/* Subject */}
													<Col span={24}>
														<Form.Item
															label="Subject"
															name={"subject"}
															rules={[{ required: true, message: "Subject is required." }]}
														>
															<Input
																id="subject"
																value={subject}
																onChange={event => setSubject(event.target.value)}
																maxLength={255}
															/>
														</Form.Item>
													</Col>

													{/* Description */}
													<Col span={24}>
														<Form.Item
															label="Description"
															name={"description"}
															rules={[{ required: true, message: "Description is required." }]}
														>
															<Spin
																spinning={busy}
																styles={{
																	indicator: {
																		color: token.colorPrimary,
																	},
																}}
															>
																<RichTextEditor value={description} onChange={setDescription} />
															</Spin>
														</Form.Item>
													</Col>

													{/* File picker */}
													{config.fileUploadEnabled ? (
														<Col span={24}>
															<Form.Item label={null}>
																<Dragger {...uploadProps}>
																	<Flex gap={4} justify={"center"} align="center">
																		<PaperClipOutlined />
																		Click or drag file to upload
																	</Flex>
																</Dragger>
															</Form.Item>
														</Col>
													) : null}
												</>
											) : (
												// Password
												<Col span={24}>
													<Form.Item
														label="Password"
														name="password"
														rules={[{ required: true, message: "Password is required." }]}
													>
														<Input.Password
															id="password"
															value={password}
															onChange={event => setPassword(event.target.value)}
															autoComplete={mode === "login" ? "current-password" : "new-password"}
															minLength={mode === "register" ? 8 : undefined}
														/>
													</Form.Item>
												</Col>
											)}

											{mode === "register" ? (
												<>
													{/* Confirm Password */}
													<Col span={24}>
														<Form.Item
															label="Confirm Password"
															name={"confirmPassword"}
															rules={[{ required: true, message: "Confirm password is required." }]}
														>
															<Input.Password
																id="confirmPassword"
																value={confirmPassword}
																onChange={event => setConfirmPassword(event.target.value)}
																autoComplete="new-password"
																minLength={8}
															/>
														</Form.Item>
													</Col>

													{/* Custom fields */}
													{registrationFields.map(field => {
														const value = customFields[field.id] ?? "";
														const update = (next: string) =>
															setCustomFields(current => ({ ...current, [field.id]: next }));
														if (field.type === "textarea") {
															return (
																<Col span={24}>
																	<Form.Item
																		key={field.id}
																		name={`sbay-${field.id}`}
																		label={field.name}
																		rules={[{ required: field.is_required, message: `${field.name} is required.` }]}
																	>
																		<TextArea
																			id={`sbay-${field.id}`}
																			rows={4}
																			placeholder={field.placeholder ?? ""}
																			value={value}
																			onChange={event => update(event.target.value)}
																		/>
																	</Form.Item>
																</Col>
															);
														}
														if (field.type === "select") {
															return (
																<Col span={24}>
																	<Form.Item
																		key={field.id}
																		name={`sbay-${field.id}`}
																		label={field.name}
																		rules={[{ required: field.is_required, message: `${field.name} is required.` }]}
																	>
																		<Select
																			id={`sbay-${field.id}`}
																			placeholder={`Select ${field.name}`}
																			value={value || undefined}
																			onChange={(next: string) => update(next)}
																			options={field.options.map(option => ({ label: option, value: option }))}
																		/>
																	</Form.Item>
																</Col>
															);
														}
														if (field.type === "checkbox") {
															return (
																<Col span={24}>
																	<Form.Item
																		key={field.id}
																		name={`sbay-${field.id}`}
																		valuePropName="checked"
																		label={null}
																		rules={[{ required: field.is_required, message: `${field.name} is required.` }]}
																	>
																		<Checkbox
																			id={`sbay-${field.id}`}
																			checked={value === "1"}
																			onChange={checked => update(checked ? "1" : "0")}
																		>
																			{field.name}
																		</Checkbox>
																	</Form.Item>
																</Col>
															);
														}
														return (
															<Col span={24}>
																<Form.Item
																	key={field.id}
																	name={`sbay-${field.id}`}
																	label={field.name}
																	rules={[{ required: field.is_required, message: `${field.name} is required.` }]}
																>
																	<Input
																		id={`sbay-${field.id}`}
																		type={field.type}
																		placeholder={field.placeholder ?? ""}
																		value={value}
																		onChange={event => update(event.target.value)}
																	/>
																</Form.Item>
															</Col>
														);
													})}
												</>
											) : null}

											{/* Form error alert */}
											{error ? (
												<Col span={24}>
													<Alert title={error} type="error" />
												</Col>
											) : null}

											<Col span={24}>
												{mode === "login" ? (
													<Row gutter={16} align={"middle"} justify={"space-between"}>
														{/* Checkbox */}
														<Col>
															<Form.Item name="remember" valuePropName="checked" label={null}>
																<Checkbox>Remember me.</Checkbox>
															</Form.Item>
														</Col>

														{/* Login button */}
														<Col>
															<Form.Item label={null}>
																<Button type="primary" htmlType="submit" disabled={busy}>
																	{busy ? "Logging in…" : "Login"}
																</Button>
															</Form.Item>
														</Col>
													</Row>
												) : (
													<Space style={{ display: "flex", justifyContent: "end" }}>
														<Form.Item label={null}>
															<Button type="primary" htmlType="submit" block disabled={busy}>
																{busy
																	? mode === "register"
																		? "Creating account…"
																		: "Submitting ticket…"
																	: mode === "register"
																		? "Register"
																		: "Create Ticket"}
															</Button>
														</Form.Item>
													</Space>
												)}
											</Col>
										</Row>
									</Form>

									{/* Lost password */}
									{mode === "login" ? (
										<>
											<Divider style={{ margin: "0px 0px 24px" }} />
											<Flex justify="center" gap={"small"}>
												<Text>Lost your password?</Text>

												<Link
													className="sbay-anchor"
													onClick={() => navigate("/support/reset-password/")}
													style={{ color: token.colorPrimary }}
												>
													Reset Password
												</Link>
											</Flex>
										</>
									) : null}
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
