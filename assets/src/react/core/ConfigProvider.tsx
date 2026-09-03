import { ConfigProvider } from "antd";
import { supportBayTheme } from "./theme";

interface SupportBayConfigProviderProps {
  children: React.ReactNode;
}

/**
 * SupportBay Ant Design ConfigProvider
 * Wraps the app with Ant Design theme and global configuration
 */
export function SupportBayConfigProvider({ children }: SupportBayConfigProviderProps) {
  return (
    <ConfigProvider
      theme={supportBayTheme}
      getPopupContainer={(trigger) => trigger?.parentNode}
    >
      {children}
    </ConfigProvider>
  );
}

export default SupportBayConfigProvider;
