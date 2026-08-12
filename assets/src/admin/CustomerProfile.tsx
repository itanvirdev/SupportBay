export interface CustomerProfileData {
  customer: {
    id:number; display_name:string; email:string; avatar_url:string|null; state:string; source:string;
    company:string|null; phone:string|null; country:string|null; timezone:string|null; language:string|null;
    last_login_at:string|null; created_at:string;
  };
  summary:{tickets:number;open_tickets:number;purchases:number;verified_purchases:number};
  providers:Array<{provider:string;reference:string}>;
  purchases:Array<{id:number;provider:string;reference:string;product_name:string|null;product_id:string|null;license_type:string|null;purchased_at:string|null;support_expires_at:string|null;status:string}>;
  tickets:Array<{id:number;track_id:string;subject:string;status:string;state:string;priority:string;created_at:string;updated_at:string|null}>;
  activity:Array<{id:number;ticket_id:number;ticket_track_id:string|null;label:string;description:string|null;created_at:string}>;
}

interface Props {
  profile:CustomerProfileData;
  back:()=>void;
  openTicket:(id:number)=>void;
  changeState:(state:'registered'|'suspended')=>Promise<void>;
}

function date(value:string|null):string {
  return value ? new Date(value).toLocaleDateString() : '—';
}

export function CustomerProfile({profile,back,openTicket,changeState}:Props) {
  const {customer,summary}=profile;
  const suspended=customer.state==='suspended';

  return <section className="sbay-customer-profile">
    <button className="sbay-back" onClick={back}>← Back</button>
    <header className="sbay-customer-hero">
      <div className="sbay-customer-avatar">{customer.avatar_url?<img src={customer.avatar_url} alt=""/>:customer.display_name.charAt(0)}</div>
      <div><small>Customer 360 Profile</small><h1>{customer.display_name}</h1><a href={`mailto:${customer.email}`}>{customer.email}</a><p>Customer since {date(customer.created_at)}</p></div>
      <div><span className={`sbay-customer-state is-${customer.state}`}>{customer.state}</span><button onClick={()=>void changeState(suspended?'registered':'suspended')}>{suspended?'Reactivate Customer':'Suspend Customer'}</button></div>
    </header>

    <div className="sbay-customer-summary">
      <div><strong>{summary.tickets}</strong><span>Total Tickets</span></div><div><strong>{summary.open_tickets}</strong><span>Open Tickets</span></div><div><strong>{summary.purchases}</strong><span>Purchases</span></div><div><strong>{summary.verified_purchases}</strong><span>Verified</span></div>
    </div>

    <div className="sbay-customer-grid">
      <main>
        <section><h2>Ticket History</h2>{profile.tickets.length?<div className="sbay-customer-list">{profile.tickets.map(ticket=><button onClick={()=>openTicket(ticket.id)} key={ticket.id}><span><strong>{ticket.subject}</strong><small>#{ticket.track_id} · {ticket.priority} priority</small></span><span><i>{ticket.status}</i><time>{date(ticket.updated_at||ticket.created_at)}</time></span></button>)}</div>:<p>No tickets found.</p>}</section>
        <section><h2>Purchase History</h2>{profile.purchases.length?<div className="sbay-purchase-grid">{profile.purchases.map(purchase=><article key={purchase.id}><header><strong>{purchase.product_name||purchase.product_id||'Product'}</strong><i>{purchase.status}</i></header><dl><div><dt>Provider</dt><dd>{purchase.provider}</dd></div><div><dt>Reference</dt><dd>{purchase.reference}</dd></div><div><dt>License</dt><dd>{purchase.license_type||'—'}</dd></div><div><dt>Purchased</dt><dd>{date(purchase.purchased_at)}</dd></div><div><dt>Support Until</dt><dd>{date(purchase.support_expires_at)}</dd></div></dl></article>)}</div>:<p>No purchase verifications found.</p>}</section>
      </main>
      <aside>
        <section><h2>Customer Information</h2><dl>{[['State',customer.state],['Source',customer.source],['Company',customer.company],['Phone',customer.phone],['Country',customer.country],['Timezone',customer.timezone],['Language',customer.language],['Last Login',date(customer.last_login_at)]].map(([label,value])=><div key={label}><dt>{label}</dt><dd>{value||'—'}</dd></div>)}</dl></section>
        <section><h2>Connected Providers</h2>{profile.providers.length?<ul>{profile.providers.map(provider=><li key={`${provider.provider}-${provider.reference}`}><strong>{provider.provider}</strong><span>{provider.reference}</span></li>)}</ul>:<p>No connected provider accounts.</p>}</section>
        <section><h2>Recent Activity</h2>{profile.activity.length?<ol>{profile.activity.map(activity=><li key={activity.id}><strong>{activity.label}</strong><span>{activity.description||`Ticket #${activity.ticket_track_id}`}</span><time>{date(activity.created_at)}</time></li>)}</ol>:<p>No recent ticket activity.</p>}</section>
      </aside>
    </div>
  </section>;
}
