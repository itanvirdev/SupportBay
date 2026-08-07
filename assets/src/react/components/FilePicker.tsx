interface FilePickerProps {
  files: File[];
  onChange: (files: File[]) => void;
  disabled?: boolean;
}

const accept = '.jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.zip';

export function FilePicker({ files, onChange, disabled }: FilePickerProps) {
  return (
    <div className="sbay-file-picker">
      <label>
        <span>Attachments <small>Optional · up to 5 files, 10 MB each</small></span>
        <input
          type="file"
          accept={accept}
          multiple
          disabled={disabled}
          onChange={(event) => onChange(Array.from(event.target.files ?? []).slice(0, 5))}
        />
      </label>
      {files.length > 0 ? (
        <ul>
          {files.map((file) => (
            <li key={`${file.name}-${file.lastModified}`}>
              <span>{file.name}</span>
              <button
                type="button"
                aria-label={`Remove ${file.name}`}
                onClick={() => onChange(files.filter((candidate) => candidate !== file))}
              >
                Remove
              </button>
            </li>
          ))}
        </ul>
      ) : null}
    </div>
  );
}
