const escapeHtml=(value:string)=>value.replace(/[&<>'"]/g,character=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[character]??character));

export function renderSavedReply(content:string,context:Record<string,string|number|null|undefined>):string {
  return content.replace(/\{\{([a-z0-9_]+)\}\}/gi,(token,key:string)=>Object.prototype.hasOwnProperty.call(context,key)&&context[key]!==null&&context[key]!==undefined?escapeHtml(String(context[key])):token);
}
