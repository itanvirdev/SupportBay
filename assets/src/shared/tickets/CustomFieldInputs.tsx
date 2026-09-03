export interface TicketCustomFieldOption {
  id: number;
  name: string;
  type: 'text'|'textarea'|'email'|'url'|'number'|'select'|'checkbox';
  placeholder: string|null;
  options: string[];
  is_required: boolean;
  category_ids: number[];
}

export function CustomFieldInputs({fields,categoryId,values,change}:{fields:TicketCustomFieldOption[];categoryId:number|null;values:Record<number,string>;change:(id:number,value:string)=>void}) {
  const applicable=fields.filter(field=>field.category_ids.length===0||(categoryId!==null&&field.category_ids.includes(categoryId)));
  return <>{applicable.map(field=><label key={field.id}><span>{field.name}{field.is_required?' *':''}</span>{field.type==='textarea'?<textarea required={field.is_required} value={values[field.id]??''} placeholder={field.placeholder??''} onChange={event=>change(field.id,event.target.value)}/>:field.type==='select'?<select required={field.is_required} value={values[field.id]??''} onChange={event=>change(field.id,event.target.value)}><option value="">{field.placeholder||'Select an option…'}</option>{field.options.map(option=><option key={option} value={option}>{option}</option>)}</select>:field.type==='checkbox'?<input type="checkbox" checked={(values[field.id]??'')==='1'} onChange={event=>change(field.id,event.target.checked?'1':'0')}/>:<input type={field.type} required={field.is_required} value={values[field.id]??''} placeholder={field.placeholder??''} onChange={event=>change(field.id,event.target.value)}/>}</label>)}</>;
}
