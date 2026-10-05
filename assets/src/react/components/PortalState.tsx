import { Spin, theme } from "antd";

interface PortalStateProps {
	title?: string;
	message: string;
	loading?: boolean;
}

export function PortalState({ title, message, loading }: PortalStateProps) {
	const { token } = theme.useToken();

	return (
		<>
			{loading ? (
				<Spin
					styles={{
						indicator: {
							color: token.colorPrimary,
						},
					}}
					fullscreen
				/>
			) : (
				<main className="sbay-state" role="alert">
					{title ? <h1>{title}</h1> : null}
					<p>{message}</p>
					<button type="button" onClick={() => window.location.reload()}>
						Try again
					</button>
				</main>
			)}
		</>
	);
}
