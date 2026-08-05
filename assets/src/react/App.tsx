import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { DashboardPage } from './modules/dashboard/DashboardPage';
import './styles/portal.scss';

function App() {
  return <DashboardPage />;
}

const root = document.getElementById('supportbay-customer-portal');

if (root) {
  createRoot(root).render(
    <StrictMode>
      <App />
    </StrictMode>,
  );
}
