export function TicketPagination({page,totalPages,previous,next}:{page:number;totalPages:number;previous:()=>void;next:()=>void}) {
  return <nav className="sbay-ticket-pagination" aria-label="Ticket pagination"><button disabled={page<=1} onClick={previous}>‹</button><span>{page}</span><button disabled={page>=totalPages} onClick={next}>›</button></nav>;
}
