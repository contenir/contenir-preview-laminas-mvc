# contenir-preview-laminas-mvc

Renders the Contenir CMS's previews in a Laminas MVC application: the
[preview protocol](../contenir-preview/docs/protocol.md) from
[`contenir/contenir-preview`](../contenir-preview), wired into the MVC's events.

The CMS posts a signed token for the record being edited to `/api/resource/preview` on the site.
This module verifies it before the router runs, works out which page shows the record, and lets the
application render that page as it always would, with the token's values in place of the stored
record's. The answer carries the protocol's headers (never cached, indexed, or framed by anyone but
the CMS) and a small "Preview" badge.

## Install

```sh
composer require contenir/contenir-preview-laminas-mvc
```

Add `Contenir\Preview\Laminas\Mvc` to the application's modules. Until the application has a
`contenir_preview` configuration, the module registers its services and does nothing else.

## Configure

```php
// config/autoload/preview.global.php (the secret in preview.local.php, out of version control)
return [
    'contenir_preview' => [
        'issuer'    => 'https://admin.example.com',
        'audience'  => 'https://www.example.com',
        'secrets'   => ['2026-10' => getenv('CONTENIR_PREVIEW_SECRET')],
        'sign_with' => '2026-10',
        'resolvers' => [
            'site.page.home'        => '/{slug}',
            'library.news.articles' => ['route' => 'news/article', 'params' => ['slug' => '{slug}']],
            'site.page.landing'     => App\Preview\LandingResolver::class,
        ],
    ],
];
```

`issuer`, `audience`, `secrets` and `sign_with` are the same on the CMS and the site.

The CMS chooses which sections offer a Preview; the site says, per section, which page shows one of
its records. Each resolver is one of:

| Resolver                                        | The page                                                           |
|-------------------------------------------------|--------------------------------------------------------------------|
| A path, beginning `/`                           | That path, with each `{field}` filled from the record's values.     |
| `['route' => name, 'params' => [...]]`          | The named route, assembled with the parameters filled the same way. |
| The name of a `PreviewResolverInterface` service | Whatever it answers: a path, a route, or a handler (below).        |

A section the CMS previews but the site has no resolver for is answered `404`, "This section has no
preview on this site".

### Handlers: drafts, new records and workflow sites

A path or route reaches only the pages the router knows. A resolver service can instead answer
`PreviewTarget::handler(SomeController::class, ['action' => 'show', ...])`, and the module dispatches
that controller with those parameters without routing. That is how to preview a record not saved
yet, a draft at an address not live, or a site whose routes are built from its published records
(contenir-mvc-workflow).

### Other settings

| Setting               | Default                         |                                                         |
|-----------------------|---------------------------------|---------------------------------------------------------|
| `replay.cache`        | none                            | A PSR-16 cache service; use one when the site runs on several servers. |
| `replay.directory`    | the system's temporary directory | Where accepted tokens are remembered otherwise; it must exist. |
| `clock`               | the system's                    | A PSR-20 clock service.                                 |
| `marker`              | `true`                          | The visible "Preview" badge on HTML pages.              |

## Use the preview's values

Wherever the site loads a previewable section's record, lay the preview over it. In a controller:

```php
$page = $this->preview()->overlay('site.page.home', ['resource_id' => $row['resource_id']], $row);
```

`overlay()` returns the stored record unchanged outside a preview, or for any other record. For a
record not saved yet, `$this->preview()->unsaved('site.page.home')` gives its values (or `null`).

In a template, `$this->preview()` gives the same context, so a page can tell a preview from a visit:

```php
<?php if ($this->preview()->isActive()): ?>…<?php endif ?>
```

## How it runs

1. `EVENT_ROUTE` at priority 100, before the router: a request to `{base}/api/resource/preview` is
   opened. Anything but a valid `POST` is answered there and then with the protocol's status and a
   short page saying why.
2. The resolved page becomes the request: a path or route is matched by the router as a visit to it
   would be (the request moves to that address, as a `GET`, with the token's post cleared); a
   handler is dispatched directly.
3. Page caches are asked to stand aside, because a cache that keys on a `GET` to a path would
   otherwise see the preview as a visit to the page: it could serve the stored page in place of
   the preview, or store the editor's unsaved page for visitors. The application triggers
   `pagecache.disable` (`PreviewListener::CACHE_DISABLE_EVENT`), which a Laminas MVC page cache
   such as `contenir/cache-laminas-mvc` listens for, and the request carries
   `Cache-Control: no-cache, no-store` in place of the browser's own, so a cache that follows HTTP's
   rules neither serves a stored copy nor stores the answer. A refusal is answered before this and
   never reaches a cache.
4. The application dispatches and renders as normal.
5. `EVENT_FINISH`: a preview's response gets the protocol's headers, replacing the page's own, and
   the badge before `</body>`.

## Quality

```sh
composer check          # Mago format, lint and analyze, then the unit and integration tests
composer mutation-test  # Infection
```
