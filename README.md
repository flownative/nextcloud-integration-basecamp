# Nextcloud Basecamp Integration

A Nextcloud app that provides rich link previews for [Basecamp](https://basecamp.com) card URLs and lets you create Basecamp cards directly from Text documents.

## Features

- **Link Previews** — Paste a Basecamp card URL into a Nextcloud Text document or Talk message, select "Show link preview" to render a rich inline widget showing card title, status, project, column, assignees, due date, and comment count
- **Smart Picker** — Type "/" in a Text document and select "Create a Basecamp card" to create cards directly from Nextcloud with project, column, assignee, and due date selection
- **German translations** included (Nextcloud auto-detects language)
- Per-user OAuth 2 authentication with automatic token refresh
- Encrypted token storage

## Requirements

- Nextcloud 28 or later
- A Basecamp account (Basecamp 4)

## Installation

### From Release Archive (recommended)

Download the latest release archive from the [Releases](https://github.com/flownative/nextcloud-integration-basecamp/releases) page and extract it into your Nextcloud apps directory:

```bash
cd /path/to/nextcloud/apps
wget https://github.com/flownative/nextcloud-integration-basecamp/releases/latest/download/integration_basecamp.tar.gz
tar xzf integration_basecamp.tar.gz
rm integration_basecamp.tar.gz
chown -R www-data:www-data integration_basecamp
sudo -u www-data php /path/to/nextcloud/occ app:enable integration_basecamp
```

### Nextcloud AIO (All-in-One)

For Nextcloud AIO installations, install directly into the running container:

```bash
sudo docker exec -it --user root nextcloud-aio-nextcloud bash -c "
  cd /var/www/html/custom_apps &&
  rm -rf integration_basecamp &&
  curl -fsSL -o integration_basecamp.tar.gz https://github.com/flownative/nextcloud-integration-basecamp/releases/latest/download/integration_basecamp.tar.gz &&
  tar xzf integration_basecamp.tar.gz &&
  rm integration_basecamp.tar.gz &&
  chown -R www-data:www-data integration_basecamp
"
sudo docker exec --user www-data nextcloud-aio-nextcloud php occ app:enable integration_basecamp
```

The app is stored in the persistent `custom_apps` volume and survives AIO updates.

### From Source

```bash
cd /path/to/nextcloud/apps
git clone https://github.com/flownative/nextcloud-integration-basecamp integration_basecamp
cd integration_basecamp
composer install --no-dev
npm ci
npm run build
sudo -u www-data php /path/to/nextcloud/occ app:enable integration_basecamp
```

### Upgrading

Remove the old app directory, extract the new release, and re-enable the app. Simply extracting over the old files is not enough — old build artifacts (JS chunks with hashed filenames) would remain and `occ upgrade` does not reliably detect app-level version changes.

**Standard installation:**

```bash
cd /path/to/nextcloud/apps
rm -rf integration_basecamp
wget https://github.com/flownative/nextcloud-integration-basecamp/releases/latest/download/integration_basecamp.tar.gz
tar xzf integration_basecamp.tar.gz
rm integration_basecamp.tar.gz
chown -R www-data:www-data integration_basecamp
sudo -u www-data php /path/to/nextcloud/occ app:disable integration_basecamp
sudo -u www-data php /path/to/nextcloud/occ app:enable integration_basecamp
```

**AIO:** Run the same AIO installation command above (it includes `rm -rf`), then:

```bash
sudo docker exec --user www-data nextcloud-aio-nextcloud php occ app:disable integration_basecamp
sudo docker exec --user www-data nextcloud-aio-nextcloud php occ app:enable integration_basecamp
```

## Configuration

### Admin Setup

1. Register a Basecamp application at [launchpad.37signals.com/integrations](https://launchpad.37signals.com/integrations)
2. Set the **Redirect URI** to `https://your-nextcloud.example.com/index.php/apps/integration_basecamp/oauth-redirect`
3. In Nextcloud, go to **Administration Settings** → **Connected Accounts**
4. Enter the **Client ID** and **Client Secret** from your Basecamp application

### User Setup

1. Go to **Personal Settings** → **Connected Accounts**
2. Click **Connect to Basecamp** and authorize access

## Supported URL Format

```
https://3.basecamp.com/{account_id}/buckets/{project_id}/card_tables/cards/{card_id}
```

## Development

### Dev Environment

The easiest way to develop is with the Nextcloud dev container. It gives you a fully working Nextcloud instance with this app mounted in:

```bash
docker run --rm --name nc-dev -p 8090:80 \
  -e SERVER_BRANCH=stable31 \
  -v $(pwd):/var/www/html/apps-extra/integration_basecamp \
  ghcr.io/juliusknorr/nextcloud-dev-php83:latest
```

Open http://localhost:8090 and log in with **admin / admin**.

Enable the app:

```bash
./nc-dev.sh occ app:enable integration_basecamp
```

### Helper Script

`./nc-dev.sh` wraps common docker exec commands:

```bash
./nc-dev.sh occ <command>     # Run occ commands
./nc-dev.sh log               # Last 20 log lines
./nc-dev.sh log-basecamp      # Basecamp-specific log lines
./nc-dev.sh log-errors        # Errors only
./nc-dev.sh curl <url>        # curl as admin (add -H "OCS-APIREQUEST: true" for API calls)
```

### Building

```bash
npm install           # Install frontend dependencies
npm run build         # Production build
npm run watch         # Rebuild on changes

composer install      # PHP autoloader (run once)
```

PHP changes take effect immediately in the container. After changing Vue/JS files, run `npm run build` (or use `npm run watch`).

### Installing the Text App

The Text app is not included in the dev container and is needed to test link previews and the Smart Picker:

```bash
./nc-dev.sh bash -c "cd /var/www/html/apps && git clone --depth 1 --branch stable31 https://github.com/nextcloud/text.git text && cd text && composer install --no-dev"
./nc-dev.sh occ app:enable text
```

### After Changing `info.xml`

Nextcloud caches the app manifest. Re-enable to pick up changes:

```bash
./nc-dev.sh occ app:disable integration_basecamp && ./nc-dev.sh occ app:enable integration_basecamp
```

### Architecture Overview

The app has two main features, both built on Nextcloud's Reference Provider system:

1. **Link Previews** — Pasting a Basecamp card URL and selecting "Show link preview" renders a compact widget with card title, status, column, assignees, etc.
2. **Smart Picker** — Typing "/" in a Text document shows "Create a Basecamp card", which opens a dialog to create a card directly from Nextcloud. The created card's URL is inserted as a link preview.

Authentication uses OAuth 2 with Basecamp's 37signals launchpad. Tokens are stored encrypted per user. The Basecamp API requires a `User-Agent` header on every request.

### Basecamp API Tips

- API docs: https://github.com/basecamp/bc3-api
- Card tables are not listed via a dedicated endpoint — they're found in the project's `dock` array (tool with `name: "kanban_board"`)
- Columns are embedded in the card table response (the `lists` field), not fetched separately
- Card creation (`POST`) does not support `assignee_ids` — assign via a follow-up `PUT`

### Releasing

1. Create a branch, bump the version in both `appinfo/info.xml` and `package.json`
2. Merge the version bump PR into `main`
3. Tag and push:
   ```bash
   git tag v<version>
   git push origin v<version>
   ```
4. The GitHub Actions workflow (`.github/workflows/release.yml`) builds the release archive automatically and attaches it to the GitHub Release

The version in `info.xml` **must** match the tag — Nextcloud uses `info.xml` to detect the installed version. If you tag without bumping, the release archive will contain the old version and `occ upgrade` won't recognize the update.

## License

AGPL-3.0-or-later
