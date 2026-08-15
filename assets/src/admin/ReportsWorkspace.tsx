import { useState } from 'react';
import { NotificationReportWorkspace } from './NotificationReportWorkspace';
import { TicketReportWorkspace } from './TicketReportWorkspace';

export function ReportsWorkspace() {
  const [section, setSection] = useState<'tickets'|'notifications'>('tickets');
  return <section className="sbay-reports-workspace">
    <nav aria-label="Report type"><button type="button" className={section==='tickets'?'is-active':''} onClick={()=>setSection('tickets')}>Ticket Performance</button><button type="button" className={section==='notifications'?'is-active':''} onClick={()=>setSection('notifications')}>Notification Delivery</button></nav>
    {section === 'tickets' ? <TicketReportWorkspace/> : <NotificationReportWorkspace/>}
  </section>;
}
