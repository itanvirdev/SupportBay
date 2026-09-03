import type { ThemeConfig } from "antd";

/**
 * SupportBay Ant Design Theme Configuration
 *
 * Color Palette:
 * - Primary (Green): #216e52 - Brand color, buttons, links
 * - Success (Lime): #dff369 - Accent highlights
 * - Error (Red): #9c342d - Error states
 * - Background: #f2f6f3 - Page background
 * - Text: #16231f - Primary text
 * - Muted: #697770 - Secondary text
 */
export const supportBayTheme: ThemeConfig = {
	token: {
		// Brand colors
		colorPrimary: "#216e52",
		colorSuccess: "#67c23a",
		colorWarning: "#e6a23c",
		colorError: "#9c342d",
		colorInfo: "#1890ff",

		// Typography
		fontFamily:
			"-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,'Noto Sans',sans-serif,'Apple Color Emoji','Segoe UI Emoji','Segoe UI Symbol','Noto Color Emoji'",
		fontSize: 14,
		fontSizeHeading1: 38,
		fontSizeHeading2: 30,
		fontSizeHeading3: 24,
		fontSizeHeading4: 20,
		fontSizeHeading5: 16,

		// Border radius
		borderRadius: 6,
		borderRadiusLG: 8,
		borderRadiusSM: 4,

		// Colors
		colorBgContainer: "#ffffff",
		colorBgLayout: "#f2f6f3",
		colorBgElevated: "#ffffff",
		colorBorder: "#dfe7e2",
		colorBorderSecondary: "#e5e9e7",
		colorText: "#16231f",
		colorTextSecondary: "#697770",
		colorTextTertiary: "#8a9992",
		colorTextQuaternary: "#b3bdb7",

		// Link
		colorLink: "#216e52",
		colorLinkHover: "#195b42",
		colorLinkActive: "#144033",

		// Box Shadow
		boxShadow: "0 2px 8px rgba(22, 35, 31, 0.08)",
		boxShadowSecondary: "0 4px 16px rgba(22, 35, 31, 0.12)",
	},
	components: {
		Button: {
			primaryShadow: "none",
			defaultShadow: "none",
			dangerShadow: "none",
			borderRadius: 6,
			controlHeight: 36,
			controlHeightLG: 44,
			controlHeightSM: 28,
		},
		Input: {
			borderRadius: 6,
			controlHeight: 36,
			controlHeightLG: 44,
			controlHeightSM: 28,
		},
		Card: {
			borderRadiusLG: 8,
		},
		Select: {
			borderRadius: 6,
		},
		Table: {
			borderRadius: 8,
		},
		Modal: {
			borderRadiusLG: 8,
		},
		Menu: {
			itemBorderRadius: 6,
		},
	},
};

export default supportBayTheme;
