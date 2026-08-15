import { useEffect, useId, useRef } from 'react';

declare global {
  interface Window {
    wp?: { editor?: { initialize: (id: string, settings: object) => void; remove: (id: string) => void } };
    tinymce?: { get: (id: string) => { getContent: () => string; setContent: (value: string) => void } | null };
  }
}

interface Props { value: string; onChange: (value: string) => void; disabled?: boolean }

export function RichTextEditor({ value, onChange, disabled = false }: Props) {
  const id = `sbay-editor-${useId().replace(/:/g, '')}`;
  const changeRef = useRef(onChange);
  changeRef.current = onChange;

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
  }, [id]);

  useEffect(() => {
    const editor = window.tinymce?.get(id);
    if (editor && editor.getContent() !== value) editor.setContent(value);
  }, [id, value]);

  return <textarea id={id} defaultValue={value} disabled={disabled} />;
}
