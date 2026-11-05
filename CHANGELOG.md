# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [0.1.0] - 2026-10-22

### Added

- A Laminas MVC module that renders the Contenir CMS's previews: it opens a
  post to `/api/resource/preview` before routing, turns it into a visit to the
  page that shows the record, which the application routes and renders as it
  always would (every route listener included), and answers with the
  protocol's headers and a visible "Preview" badge.
- The services contenir-preview's `ConfigProvider` builds from the
  `contenir_preview` configuration, registered with the application's: each
  section's resolver a path, a named route, or a resolver service, which may
  dispatch a handler without routing.
- A `preview()` view helper and controller plugin for laying the preview's
  values over the record a page loads.
- Page caches stand aside for a preview: the module triggers
  `pagecache.disable` and marks the request `Cache-Control: no-cache, no-store`.
- Requires contenir-preview 0.1.0 or later, so a site installs the renderer
  from released packages alone.
