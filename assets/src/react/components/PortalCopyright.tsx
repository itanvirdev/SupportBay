import { Typography } from "antd";
import { Fragment } from "react";
import { getConfig } from "../core/config";
const { Text, Link } = Typography;

export function PortalCopyright() {
	const config = getConfig();
	const currentYear = new Date().getFullYear();
	const copyright = config.footerCopyrightText.split("{year}").join(String(currentYear));
	const segments = copyright.split("{site_name}");

	return (
		<Text className="sbay-auth-copyright">
			{segments.map((segment, index) => (
				<Fragment key={`${segment}-${index}`}>
					{segment}
					{index < segments.length - 1 ? <Link href={config.homeUrl}>{config.siteName}</Link> : null}
				</Fragment>
			))}

			{!config.removePoweredByBranding ? (
				<>
					{" | "}
					Powered by{" "}
					<Link href="https://themeshala.com/supportbay/" target="_blank" rel="noopener noreferrer">
						SupportBay
					</Link>
				</>
			) : null}
		</Text>
	);
}
