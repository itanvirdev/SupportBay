# Performance and Release Packaging

## Production assets

Build minified React assets with:

```bash
npm run build
```

The administrator Reports and Settings workspaces and customer portal route
components are loaded on demand. Webpack emits content-hashed chunks into
`assets/dist`; every emitted file in that directory is required in a release.

WordPress editor assets are loaded only on ticket-detail and Settings screens.
The media library is loaded only in Settings, where logo selection needs it.

## Production ZIP

Build the distributable plugin with:

```bash
npm run release
```

The release builder:

1. Compiles production React assets.
2. Copies only runtime files according to `.distignore`.
3. Installs Composer production autoloading with `--no-dev` and an authoritative
   classmap.
4. Creates `build/supportbay-{version}.zip` with a single `SupportBay/` root.

Do not distribute `composer archive` output from a development working tree. It
can include local development dependencies and ignored files. The release builder
is the canonical packaging path.

## Package verification

A release must contain:

- `supportbay.php`, `uninstall.php`, `readme.txt`, and `license.txt`;
- all compiled files under `assets/dist`;
- `includes` runtime modules and providers;
- production `vendor/autoload.php` and Composer metadata.

A release must not contain source TypeScript, Node modules, flow tests, developer
tools, project-planning documents, or local operating-system files.
