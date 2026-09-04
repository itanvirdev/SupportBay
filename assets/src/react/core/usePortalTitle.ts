import { useEffect } from "react";
import { getConfig } from "./config";

/**
 * Updates the browser tab title based on the current portal route.
 *
 * The base title comes from the admin-configured `pageTitle` setting.
 * Auth and ticket-detail routes append a short context suffix so the tab
 * always shows what the user is looking at.
 */
export function usePortalTitle(route: string): void {
  const config = getConfig();

  useEffect(() => {
    const base = config.pageTitle;
    let suffix = "";

    if (route === "reset-password") suffix = " — Reset Password";
    else if (route === "login") suffix = " — Login";
    else if (route === "register") suffix = " — Register";
    else if (route === "guest-ticket") suffix = " — Create Ticket";
    else if (route === "tickets") suffix = " — Tickets";
    else if (route === "purchases") suffix = " — Purchases";
    else if (route === "profile") suffix = " — Profile";

    document.title = `${base}${suffix}`;
  }, [config.pageTitle, route]);
}