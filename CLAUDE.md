# Nextcloud Basecamp Integration

## Project Overview

A Nextcloud integration app that provides rich link previews for Basecamp card URLs in Text and Talk documents. When a user pastes a Basecamp card URL and selects "Show link preview", it renders a compact inline widget showing the Basecamp logo, card title, status, column, project, assignees, due date, and comment count.

## Development Environment

Use the dev container image from `ghcr.io/juliusknorr/nextcloud-dev-php83:latest`. The project root is mounted into the container as the app directory.

### Start dev environment

```bash
docker run --rm --name nc-dev -p 8090:80 \
  -e SERVER_BRANCH=stable31 \
  -v $(pwd):/var/www/html/apps-extra/integration_basecamp \
  ghcr.io/juliusknorr/nextcloud-dev-php83:latest
```

Default login: **admin / admin**

### Helper script

Use `./nc-dev.sh` as a wrapper for docker exec commands:

```bash
./nc-dev.sh occ app:enable integration_basecamp
./nc-dev.sh occ app:disable integration_basecamp
./nc-dev.sh log              # last 20 log lines
./nc-dev.sh log-basecamp     # basecamp-specific log lines
./nc-dev.sh log-errors       # errors only
./nc-dev.sh php -r "..."     # run PHP code
./nc-dev.sh curl <url>       # curl as admin
```

### Install Text app in dev container

The Text app is not shipped with the dev container and must be installed for testing link previews:

```bash
./nc-dev.sh bash -c "cd /var/www/html/apps && git clone --depth 1 --branch stable31 https://github.com/nextcloud/text.git text && cd text && composer install --no-dev"
./nc-dev.sh occ app:enable text
```

### After changing info.xml

Re-enable the app to pick up manifest changes:

```bash
./nc-dev.sh occ app:disable integration_basecamp && ./nc-dev.sh occ app:enable integration_basecamp
```

## Architecture

The app uses Nextcloud's **Reference Provider** pattern (same as the GitHub and Deck integrations):

1. `BasecampCardReferenceProvider` matches Basecamp card URLs via regex
2. `BasecampAPIService` fetches card data from Basecamp API (Bearer token auth with auto-refresh)
3. A Vue 3 widget (`BasecampCardReferenceWidget.vue`) renders the compact inline preview
4. `BasecampReferenceListener` injects the reference JS when `RenderReferenceEvent` fires

### How link previews work in the Text editor

The Text editor does NOT automatically show link previews for pasted URLs. When a standalone link is detected in a paragraph:
1. A ⋮ menu appears to the left of the link
2. The user can toggle between "Text only" and "Show link preview"
3. When "Show link preview" is selected, the Text editor creates a Preview node that uses `NcReferenceList` to render our widget

This toggle behavior is controlled by the Text editor (`apps/text/src/nodes/Preview.js`), not by our app.

### OAuth 2 Flow

- **Admin** configures Client ID + Client Secret (stored encrypted via `ICrypto`)
- **Users** connect via "Connect to Basecamp" in Personal Settings, which initiates the OAuth redirect flow
- Access tokens expire after 14 days; the app automatically refreshes them using the refresh token
- Fallback: if no per-user token exists, the app falls back to an app-level admin token (if set)
- Token exchange and refresh endpoints are at `launchpad.37signals.com/authorization/token`

### URL pattern matched

```
https://3.basecamp.com/{account_id}/buckets/{project_id}/card_tables/cards/{card_id}
```

Fragments like `#__recording_XXXXX` are ignored (still matches).

## Key Files

- `appinfo/info.xml` — App manifest (namespace: `IntegrationBasecamp`)
- `appinfo/routes.php` — API routes (config, OAuth redirect, disconnect)
- `lib/AppInfo/Application.php` — Bootstrap, registers reference provider + event listener
- `lib/Reference/BasecampCardReferenceProvider.php` — URL matching + API resolution → rich object
- `lib/Service/BasecampAPIService.php` — Basecamp API client, OAuth token management, auto-refresh
- `lib/Settings/Admin.php` — Admin settings (Client ID/Secret, link preview toggle)
- `lib/Settings/Personal.php` — Personal settings (Connect/Disconnect Basecamp)
- `lib/Settings/AdminSection.php` — Settings section registration
- `lib/Controller/ConfigController.php` — Config endpoints + OAuth callback handler
- `lib/Listener/BasecampReferenceListener.php` — Injects reference JS on RenderReferenceEvent
- `src/reference.js` — Registers Vue widget for rich object type `integration_basecamp_card`
- `src/views/BasecampCardReferenceWidget.vue` — Compact card preview widget
- `src/components/AdminSettings.vue` — Admin settings form (Client ID/Secret)
- `src/components/PersonalSettings.vue` — Personal settings (Connect/Disconnect)

## Build Commands

```bash
composer install         # PHP autoloader
npm install              # Frontend dependencies
npm run build            # Production build
npm run dev              # Development build
npm run watch            # Development build with watch
```

After changing Vue/JS files, run `npm run build`. PHP changes are reflected immediately in the container.

## Basecamp API

- Base URL: `https://3.basecampapi.com/{account_id}/`
- Auth: `Authorization: Bearer {token}`
- Card endpoint: `buckets/{project_id}/card_tables/cards/{card_id}.json`
- User-Agent header is required by Basecamp API policy
- OAuth tokens expire after 14 days; refresh tokens last ~10 years
- User info endpoint: `https://launchpad.37signals.com/authorization.json`

### OAuth Endpoints

```
Authorization:  https://launchpad.37signals.com/authorization/new?type=web_server&client_id=...&redirect_uri=...
Token exchange: POST https://launchpad.37signals.com/authorization/token?type=web_server&client_id=...&client_secret=...&code=...&redirect_uri=...
Token refresh:  POST https://launchpad.37signals.com/authorization/token?type=refresh&refresh_token=...&client_id=...&client_secret=...
```

## Important Implementation Notes

- The rich object type `integration_basecamp_card` must match exactly between PHP (`BasecampCardReferenceProvider::RICH_OBJECT_TYPE`) and JS (`registerWidget('integration_basecamp_card', ...)`)
- Admin settings JS must be loaded explicitly via `Util::addScript()` in the `Admin::getForm()` method — Nextcloud does not auto-load it
- The `#[PasswordConfirmationRequired]` attribute on `setSensitiveAdminConfig` ensures Client ID/Secret changes require password re-entry
- The reference cache prefix uses `$this->userId` so that disconnecting/reconnecting invalidates a user's cached references
- The Basecamp API requires a descriptive `User-Agent` header; requests without one will be rejected

## Reference Projects

- `nextcloud/integration_github` — The primary reference for the Reference Provider pattern, OAuth flow, and admin/personal settings structure
- Nextcloud Deck (`apps-writable/deck/`) — Reference for compact inline widget styling; note that Deck uses Vue 2 while this project uses Vue 3
