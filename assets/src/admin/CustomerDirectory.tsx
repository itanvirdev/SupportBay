import { FormEvent, useCallback, useEffect, useState } from 'react';
import { adminGet } from './api';
import { Preloader } from '../shared/components/Preloader';
import { RequestState } from '../shared/components/RequestState';

interface CustomerDirectoryItem {
  id:number; display_name:string; email:string; avatar_url:string|null; state:string; source:string;
  company:string|null; phone:string|null; country:string|null; last_login_at:string|null;
  ticket_count:number; open_ticket_count:number; purchase_count:number; verified_purchase_count:number;
  last_activity_at:string|null; created_at:string;
}

interface CustomerDirectoryPage {
  items:CustomerDirectoryItem[]; page:number; total:number; totalPages:number;
}

interface Props { back:()=>void; openCustomer:(id:number)=>void }

const defaults={page:1,search:'',state:'',source:'',orderby:'last_activity',order:'desc'};

export function CustomerDirectory({back,openCustomer}:Props) {
  const [query,setQuery]=useState(defaults);
  const [draftSearch,setDraftSearch]=useState('');
  const [result,setResult]=useState<CustomerDirectoryPage|null>(null);
  const [error,setError]=useState<string|null>(null);
  const update=(changes:Partial<typeof defaults>)=>setQuery(current=>({...current,...changes,page:changes.page??1}));
  const load=useCallback(async()=>{
    setError(null);
    const params=new URLSearchParams({page:String(query.page),per_page:'20',search:query.search,state:query.state,source:query.source,orderby:query.orderby,order:query.order});
    try {
      const response=await adminGet<CustomerDirectoryItem[]>(`admin/customers/directory?${params}`);
      setResult({items:response.data,page:Number(response.meta.page)||1,total:Number(response.meta.total)||0,totalPages:Number(response.meta.total_pages)||1});
    } catch(reason) {
      setError(reason instanceof Error?reason.message:'Customer directory could not be loaded.');
    }
  },[query]);
  useEffect(()=>{void load();},[load]);
  const search=(event:FormEvent)=>{event.preventDefault();update({search:draftSearch.trim()});};

  return <section className="sbay-customer-directory">
    <button className="sbay-back" onClick={back}>← Back to tickets</button>
    <header><div><small>Customer Management</small><h1>Customer Directory</h1><p>Search customer identities and review their support history.</p></div><strong>{result?.total??'—'} customers</strong></header>
    <div className="sbay-directory-filters">
      <form onSubmit={search}><input aria-label="Search customers" placeholder="Search name, email, company, or phone" value={draftSearch} onChange={event=>setDraftSearch(event.target.value)}/><button>Search</button></form>
      <select aria-label="Customer state" value={query.state} onChange={event=>update({state:event.target.value})}><option value="">All States</option>{['guest','registered','verified','suspended'].map(value=><option key={value}>{value}</option>)}</select>
      <select aria-label="Customer source" value={query.source} onChange={event=>update({source:event.target.value})}><option value="">All Sources</option>{['guest','registration','wordpress','provider','admin'].map(value=><option key={value}>{value}</option>)}</select>
      <select aria-label="Sort customers" value={`${query.orderby}:${query.order}`} onChange={event=>{const [orderby,order]=event.target.value.split(':');update({orderby,order});}}><option value="last_activity:desc">Recent Activity</option><option value="name:asc">Name A–Z</option><option value="tickets:desc">Most Tickets</option><option value="purchases:desc">Most Purchases</option><option value="created_at:desc">Newest Customers</option></select>
      <button disabled={JSON.stringify(query)===JSON.stringify(defaults)&&draftSearch===''} onClick={()=>{setDraftSearch('');setQuery(defaults);}}>Reset</button>
    </div>
    {error&&result?<div className="sbay-admin-error" role="alert">{error}</div>:null}
    <div className="sbay-directory-table">
      <div className="is-header"><span>Customer</span><span>State</span><span>Tickets</span><span>Purchases</span><span>Last Activity</span></div>
      {!result&&error?<RequestState compact title="Customers could not be loaded" message={error} retry={()=>void load()}/>:!result?<Preloader label="Loading customers…" compact/>:result.items.length===0?<RequestState compact title={query.search||query.state||query.source?'No matching customers':'No customers yet'} message={query.search||query.state||query.source?'Adjust or reset the filters to see other customers.':'Customer records will appear after registration, guest submission, or provider connection.'} action={query.search||query.state||query.source?()=>{setDraftSearch('');setQuery(defaults);}:undefined} actionLabel={query.search||query.state||query.source?'Reset filters':undefined}/>:result.items.map(customer=><button onClick={()=>openCustomer(customer.id)} key={customer.id}><span className="sbay-directory-person"><i>{customer.avatar_url?<img src={customer.avatar_url} alt=""/>:customer.display_name.charAt(0)}</i><span><strong>{customer.display_name}</strong><small>{customer.email}{customer.company?` · ${customer.company}`:''}</small></span></span><span><i className={`sbay-customer-state is-${customer.state}`}>{customer.state}</i><small>{customer.source}</small></span><span><strong>{customer.ticket_count}</strong><small>{customer.open_ticket_count} open</small></span><span><strong>{customer.purchase_count}</strong><small>{customer.verified_purchase_count} verified</small></span><span>{new Date(customer.last_activity_at||customer.created_at).toLocaleDateString()}</span></button>)}
      <footer><span>Showing {result?.items.length??0} of {result?.total??0}</span><nav aria-label="Customer pagination"><button disabled={!result||result.page<=1} onClick={()=>update({page:query.page-1})}>‹</button><span>{result?.page??1}</span><button disabled={!result||result.page>=result.totalPages} onClick={()=>update({page:query.page+1})}>›</button></nav></footer>
    </div>
  </section>;
}
