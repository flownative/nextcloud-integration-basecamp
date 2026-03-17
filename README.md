# Nextcloud Basecamp Integration

A Nextcloud app that provides rich link previews for [Basecamp](https://basecamp.com) card URLs in Text and Talk documents.

## Features

- Paste a Basecamp card URL into a Nextcloud Text document or Talk message
- Select "Show link preview" to render a rich inline widget showing:
  - Basecamp logo and card title with status indicator
  - Project name and Kanban column
  - Assignees, due date, and comment count
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
wget https://github.com/flownative/nextcloud-integration-basecamp/releases/download/v0.1.0/integration_basecamp.tar.gz
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
  curl -fsSL -o integration_basecamp.tar.gz https://github.com/flownative/nextcloud-integration-basecamp/releases/download/v0.1.0/integration_basecamp.tar.gz &&
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

Download and extract the new release over the existing installation, then run:

```bash
sudo -u www-data php /path/to/nextcloud/occ upgrade
```

For AIO, repeat the installation steps above — the new files replace the old ones in the persistent volume.

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

See [CLAUDE.md](CLAUDE.md) for development environment setup and architecture details.

## License

AGPL-3.0-or-later
