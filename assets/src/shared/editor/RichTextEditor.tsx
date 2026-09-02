import { useEffect, useId, useRef } from 'react';

declare global {
  interface Window {
    wp?: { editor?: { initialize: (id: string, settings: object) => void; remove: (id: string) => void } };
    tinymce?: { get: (id: string) => { getContent: () => string; setContent: (value: string) => void; insertContent: (content: string) => void } | null };
  }
}

interface Props { value: string; onChange: (value: string) => void; disabled?: boolean; placeholderOptions?: string[]; showSavedReplies?: boolean; onSavedRepliesClick?: () => void }
const EMPTY_PLACEHOLDERS: string[] = [];

export function RichTextEditor({ value, onChange, disabled = false, placeholderOptions = EMPTY_PLACEHOLDERS, showSavedReplies = false, onSavedRepliesClick }: Props) {
  const id = `sbay-editor-${useId().replace(/:/g, '')}`;
  const changeRef = useRef(onChange);
  changeRef.current = onChange;
  const insertPlaceholder = (placeholder: string) => {
    const editor = window.tinymce?.get(id);
    const token = `{{${placeholder}}}`;
    if (editor) {
      editor.insertContent(token);
      changeRef.current(editor.getContent());
      return;
    }
    changeRef.current(`${value}${value ? ' ' : ''}${token}`);
  };

  useEffect(() => {
    window.wp?.editor?.initialize(id, {
      mediaButtons: false,
      quicktags: false,
      tinymce: {
        menubar: false,
        statusbar: true,
        height: 280,
        plugins: 'lists link textcolor',
        toolbar1: 'bold italic underline | bullist numlist | alignleft aligncenter alignright | link | forecolor | removeformat',
        valid_elements: 'p[style],br,strong/b,em/i,u,ul,ol,li,div[style],span[style],a[href|target|rel]',
        invalid_elements: 'script,style,table,iframe,video,audio,object,embed,img',
        setup: (editor: { on: (events: string, callback: () => void) => void; getContent: () => string }) => {
          editor.on('change input undo redo', () => changeRef.current(editor.getContent()));
        },
      },
    });
    return () => window.wp?.editor?.remove(id);
  }, [id, placeholderOptions]);

  useEffect(() => {
    const editor = window.tinymce?.get(id);
    if (editor && editor.getContent() !== value) editor.setContent(value);
  }, [id, value]);

  return <div className="sbay-rich-text-editor">
    {placeholderOptions.length ? <select className="sbay-rich-text-editor__placeholders" aria-label="Insert placeholder" defaultValue="" disabled={disabled} onChange={(event) => { if (event.target.value) insertPlaceholder(event.target.value); event.target.value = ''; }}><option value="">Placeholders</option>{placeholderOptions.map((placeholder) => <option value={placeholder} key={placeholder}>{`{{${placeholder}}}`}</option>)}</select> : null}
    {showSavedReplies && onSavedRepliesClick ? <button className={`sbay-rich-text-editor__saved-replies${placeholderOptions.length ? ' has-placeholders' : ''}`} type="button" disabled={disabled} onClick={onSavedRepliesClick}>Saved Replies</button> : null}
    <textarea id={id} defaultValue={value} disabled={disabled} />
  </div>;
}
