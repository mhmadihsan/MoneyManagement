# Local frontend assets

These files are distributed with the application so its interface works without internet access. Library versions, download provenance and SHA-256 checksums are recorded in `manifest.json`. Licenses are included in each component directory.

All runtime references use local application URLs. Font files are resolved relative to their CSS files. Source map directives have been removed from vendored CSS/JavaScript; license notices are preserved. `fonts/fonts.css` contains local font declarations for the Latin font subsets.

When updating a library, replace its complete local dependency tree, keep its license, update the manifest checksums, and run `OfflineAssetsTest` plus the browser suite with `OFFLINE_ASSETS=1`.

See `docs/testing/OFFLINE.md` for packaging and verification details. The Vite output in `public/build` must also be included in an offline deployment.
