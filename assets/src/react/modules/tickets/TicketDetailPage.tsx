import { useCallback, useEffect, useRef, useState } from 'react';
import { portalApi } from '../../api/portal';
import type { PortalAttachment, PortalTicketDetail } from '../../api/types';
import { formatDate, formatDateTime } from '../../core/date';
import { Preloader } from '../../../shared/components/Preloader';
import { RequestState } from '../../../shared/components/RequestState';
import { FilePicker } from '../../components/FilePicker';
import { getConfig } from '../../core/config';

interface TicketDetailPageProps {
  ticketId: number;
  navigate: (path: string) => void;
}

export function TicketDetailPage({ ticketId, navigate }: TicketDetailPageProps) {
  const config=getConfig();
  const [detail, setDetail] = useState<PortalTicketDetail | null>(null);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [reply, setReply] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [files, setFiles] = useState<File[]>([]);
  const [transitioning, setTransitioning] = useState(false);
  const [transitionError, setTransitionError] = useState<string | null>(null);
  const [downloadingId, setDownloadingId] = useState<number | null>(null);
  const [downloadError, setDownloadError] = useState<string | null>(null);
  const [preview,setPreview]=useState<{url:string;name:string;mime:string}|null>(null);
  const [creationWarning] = useState<string | null>(() => {
    const key = `sbay-ticket-attachment-warning:${ticketId}`;
    const warning = window.sessionStorage.getItem(key);
    window.sessionStorage.removeItem(key);
    return warning;
  });
  const requestId=useRef(0);
  const mutationPending=useRef(false);

  const loadDetail=useCallback(async(background=false)=>{
    const currentRequest=++requestId.current;
    if(!background)setLoadError(null);
    try{
      const response=await portalApi.ticket(ticketId);
      if(currentRequest===requestId.current)setDetail(response);
    }catch(reason){
      if(!background&&currentRequest===requestId.current)setLoadError(reason instanceof Error?reason.message:'The ticket conversation could not be loaded.');
    }
  },[ticketId]);

  useEffect(()=>{void loadDetail(false);},[loadDetail]);
  useEffect(()=>{
    if(!config.ticketListAutoRefreshEnabled)return;
    const interval=window.setInterval(()=>{
      if(document.hidden||mutationPending.current||submitting||transitioning)return;
      void loadDetail(true);
    },Math.max(5,config.ticketListAutoRefreshInterval)*1000);
    return()=>window.clearInterval(interval);
  },[config.ticketListAutoRefreshEnabled,config.ticketListAutoRefreshInterval,loadDetail,submitting,transitioning]);

  if (!detail && loadError) {
    return <RequestState title="Ticket unavailable" message={loadError} retry={()=>void loadDetail(false)} />;
  }

  if (!detail) {
    return <Preloader label="Loading ticket conversation…" />;
  }

  const canReply = ['open', 'pending', 'answered'].includes(detail.ticket.status);

  const submitReply = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSubmitting(true);
    setError(null);
    requestId.current++;
    mutationPending.current=true;

    try {
      const message = await portalApi.reply(ticketId, reply);
      const attachments = await Promise.all(
        files.map((file) => portalApi.uploadAttachment(ticketId, message.id, file)),
      );
      setDetail({
        ...detail,
        messages: [...detail.messages, { ...message, attachments }],
      });
      setReply('');
      setFiles([]);
    } catch (exception) {
      setError(exception instanceof Error ? exception.message : 'Reply could not be added.');
    } finally {
      mutationPending.current=false;
      setSubmitting(false);
    }
  };

  const transitionTicket = async () => {
    const reopening = ['resolved', 'closed'].includes(detail.ticket.status);
    const confirmed = window.confirm(
      reopening
        ? 'Reopen this ticket and continue the conversation?'
        : 'Close this ticket? You will not be able to reply until it is reopened.',
    );

    if (!confirmed) {
      return;
    }

    setTransitioning(true);
    setTransitionError(null);
    requestId.current++;
    mutationPending.current=true;

    try {
      const ticket = reopening
        ? await portalApi.reopenTicket(ticketId)
        : await portalApi.closeTicket(ticketId);
      setDetail({ ...detail, ticket });
    } catch (exception) {
      setTransitionError(
        exception instanceof Error
          ? exception.message
          : 'Ticket status could not be changed.',
      );
    } finally {
      mutationPending.current=false;
      setTransitioning(false);
    }
  };

  const downloadAttachment = async (attachment: PortalAttachment) => {
    setDownloadingId(attachment.id);
    setDownloadError(null);

    try {
      const blob = await portalApi.downloadAttachment(attachment.id);
      const url = URL.createObjectURL(blob);
      const previewableMime = attachment.mime_type.startsWith('image/') || attachment.mime_type === 'application/pdf';
      if(config.attachmentPopupPreviewEnabled&&previewableMime){
        setPreview({url,name:attachment.original_name,mime:attachment.mime_type});
        return;
      }
      const link = document.createElement('a');
      link.href = url;
      link.download = attachment.original_name;
      link.click();
      window.setTimeout(() => URL.revokeObjectURL(url), 0);
    } catch (exception) {
      setDownloadError(
        exception instanceof Error
          ? exception.message
          : 'Attachment could not be downloaded.',
      );
    } finally {
      setDownloadingId(null);
    }
  };

  return (
    <section className="sbay-page">
      <button className="sbay-back" type="button" onClick={() => navigate('/support/tickets/')}>
        ← Back to tickets
      </button>
      <header className="sbay-ticket-header">
        <div>
          <span className="sbay-kicker">Ticket #{detail.ticket.track_id}</span>
          <h1>{detail.ticket.subject}</h1>
          <p>Opened {formatDate(detail.ticket.created_at)}</p>
        </div>
        <div className="sbay-ticket-actions">
          <span className={`sbay-badge sbay-badge--${detail.ticket.status}`}>
            {config.ticketStatusLabels[detail.ticket.status]??detail.ticket.status}
          </span>
          <button
            className={['resolved', 'closed'].includes(detail.ticket.status) ? 'sbay-primary-button' : 'sbay-secondary-button'}
            type="button"
            onClick={transitionTicket}
            disabled={transitioning}
          >
            {transitioning
              ? 'Updating…'
              : ['resolved', 'closed'].includes(detail.ticket.status) ? 'Reopen ticket' : 'Close ticket'}
          </button>
        </div>
      </header>

      {transitionError ? <p className="sbay-form-error" role="alert">{transitionError}</p> : null}
      {creationWarning ? <p className="sbay-form-error" role="alert">{creationWarning}</p> : null}

      <div className="sbay-detail-grid">
        <section className="sbay-thread" aria-label="Ticket conversation">
          {detail.messages.length === 0 ? (
            <p className="sbay-empty">No messages have been added yet.</p>
          ) : detail.messages.map((message) => (
            <article
              className={message.author_type === 'agent' ? 'is-support' : 'is-customer'}
              key={message.id}
            >
              <div className="sbay-message__meta">
                <strong>{message.author_type === 'agent' ? 'Support team' : 'You'}</strong>
                <time>{formatDateTime(message.created_at)}</time>
              </div>
              <div className="sbay-rich-content" dangerouslySetInnerHTML={{ __html: message.content }} />
              {message.attachments.length > 0 ? (
                <ul className="sbay-attachments" aria-label="Attachments">
                  {message.attachments.map((attachment) => (
                    <li key={attachment.id}>
                      <span>
                        <strong>{attachment.original_name}</strong>
                        <small>{Math.max(1, Math.round(attachment.file_size / 1024))} KB</small>
                      </span>
                      <button
                        type="button"
                        disabled={downloadingId === attachment.id}
                        onClick={() => downloadAttachment(attachment)}
                      >
                        {downloadingId === attachment.id ? 'Loading…' : config.attachmentPopupPreviewEnabled&&(attachment.mime_type.startsWith('image/')||attachment.mime_type==='application/pdf')?'View':'Download'}
                      </button>
                    </li>
                  ))}
                </ul>
              ) : null}
            </article>
          ))}
          {downloadError ? <p className="sbay-form-error" role="alert">{downloadError}</p> : null}
          {preview?<div className="sbay-attachment-preview" role="dialog" aria-modal="true" aria-label={preview.name}><header><strong>{preview.name}</strong><button type="button" aria-label="Close preview" onClick={()=>{URL.revokeObjectURL(preview.url);setPreview(null);}}>×</button></header>{preview.mime==='application/pdf'?<iframe src={preview.url} title={preview.name}/>:<img src={preview.url} alt={preview.name}/>}</div>:null}
          {canReply ? (
            <form className="sbay-reply-form" onSubmit={submitReply}>
              <label htmlFor="sbay-ticket-reply">Add a reply</label>
              <textarea
                id="sbay-ticket-reply"
                value={reply}
                onChange={(event) => setReply(event.target.value)}
                rows={5}
                required
              />
              {config.fileUploadEnabled?<FilePicker files={files} onChange={setFiles} disabled={submitting} maxSizeMb={config.fileUploadMaxSizeMb} allowedExtensions={config.fileUploadAllowedExtensions}/>:null}
              {error ? <p className="sbay-form-error" role="alert">{error}</p> : null}
              <button className="sbay-primary-button" type="submit" disabled={submitting}>
                {submitting ? 'Sending…' : 'Send reply'}
              </button>
            </form>
          ) : (
            <p className="sbay-reply-closed">This ticket is closed to new replies.</p>
          )}
        </section>

        <aside className="sbay-ticket-aside">
          <h2>Ticket details</h2>
          <dl>
            <div><dt>Status</dt><dd>{config.ticketStatusLabels[detail.ticket.status]??detail.ticket.status}</dd></div>
            <div><dt>Priority</dt><dd>{detail.ticket.priority}</dd></div>
            <div><dt>Source</dt><dd>{detail.ticket.source}</dd></div>
          </dl>
          {detail.custom_fields.length > 0 ? (
            <div className="sbay-ticket-custom-data">
              <h3>Additional information</h3>
              <dl>{detail.custom_fields.map((field) => (
                <div key={field.id}>
                  <dt>{field.name}</dt>
                  <dd>{field.type === 'checkbox'
                    ? field.value === '1' ? 'Yes' : 'No'
                    : field.type === 'url'
                      ? <a href={field.value} target="_blank" rel="noreferrer">{field.value}</a>
                      : field.value}</dd>
                </div>
              ))}</dl>
            </div>
          ) : null}
          {detail.verification ? (
            <div className="sbay-linked-product">
              <span className="sbay-verified">Verified purchase</span>
              <h3>{detail.verification.product_name ?? 'Verified product'}</h3>
              <p>{detail.verification.license_type ?? 'Purchase verified'}</p>
            </div>
          ) : null}
        </aside>
      </div>
    </section>
  );
}
