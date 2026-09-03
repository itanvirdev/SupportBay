export type WorkspaceMode = 'staff' | 'customer';

export interface WorkspaceTicket {
  id:number;track_id:string;subject:string;status:string;state?:string;priority:string;
  assigned_agent_id?:number|null;agent_name?:string|null;customer_name?:string|null;
  customer_avatar_url?:string|null;category_id?:number|null;
  category_name?:string|null;tags?:Array<{id:number;name:string;color:string|null}>;
  reply_count?:number;latest_reply_excerpt?:string;needs_reply?:boolean;has_support_reply?:boolean;last_reply_at?:string|null;
  updated_at:string|null;created_at:string;
}
export interface TicketPage {items:WorkspaceTicket[];page:number;total:number;totalPages:number;showCategories?:boolean}
export interface TicketQueryParams {page:number;perPage:number;search:string;status:string;state:string;priority:string;assignment:string;agentId:string;categoryId:string;tagId:string;customFieldId:string;customFieldValue:string;needReply:boolean;orderby:string;order:string}
export interface TicketWorkspaceOptions {agents:Array<{id:number;name:string}>;categories:Array<{id:number;name:string}>;tags:Array<{id:number;name:string;color:string|null}>;custom_fields:Array<{id:number;name:string;type:string;options:string[];category_ids:number[]}>}
