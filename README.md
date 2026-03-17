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

1. Clone this repository into your Nextcloud `apps` directory:
   ```bash
   cd /path/to/nextcloud/apps
   git clone https://github.com/flownative/nextcloud-integration-basecamp integration_basecamp
   ```

2. Install dependencies and build:
   ```bash
   cd integration_basecamp
   composer install
   npm install
   npm run build
   ```

3. Enable the app:
   ```bash
   php occ app:enable integration_basecamp
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

See [CLAUDE.md](CLAUDE.md) for development environment setup and architecture details.

## License

AGPL-3.0-or-later
