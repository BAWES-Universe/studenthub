# Setup Guide

## Development Environment Setup

### Docker Setup

Run the following command after installing Docker:

```bash
docker-compose up
```

Available endpoints:
* Admin API: http://localhost:21080
* Candidate API: http://localhost:22080
* Company API: http://localhost:23080
* Inspector API: http://localhost:24080
* Staff API: http://localhost:25080
* Verification: http://localhost:26080

### Backend Container Access

```bash
docker-compose exec backend bash
```

### Running Tests

Use the provided script in the project root:
```bash
./run-tests.sh
```

## Server Requirements

### PHP Extensions
Required: exif, pdo_mysql

### Third-Party Integration Environment Variables

Configure these values in the runtime environment instead of committing service credentials:

| Variable | Purpose |
| --- | --- |
| `GOOGLE_MAPS_API_KEY` | Server-side Google Maps component key |
| `GOOGLE_MAPS_BROWSER_API_KEY` | Browser-facing Google Maps key exposed through app params |
| `RECAPTCHA_SECRET_KEY` | Server-side reCAPTCHA verification secret |
| `JIRA_URL` | Jira instance URL |
| `JIRA_EMAIL` | Jira API account email |
| `JIRA_API_TOKEN` | Jira API token |
| `ALGOLIA_APP_ID` | Algolia application ID |
| `ALGOLIA_API_KEY` | Algolia API key |
| `IPINFO_ACCESS_TOKEN` | IP geolocation lookup token |
| `SLACK_WEBHOOK_URL` | Slack incoming webhook URL |

### Puppeteer Setup
```bash
curl -sL https://deb.nodesource.com/setup_12.x | sudo -E bash -
sudo apt-get install -y nodejs gconf-service libasound2 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 ca-certificates fonts-liberation libappindicator1 libnss3 lsb-release xdg-utils wget libgbm-dev
sudo npm install --global --unsafe-perm puppeteer
sudo chmod -R o+rx /usr/lib/node_modules/puppeteer/.local-chromium
```

## Console Commands and Maintenance

### Algolia Search Index Updates
```bash
cd console && ../yii algolia/index fulltimer
cd console && ../yii algolia/index candidate
```

### Cron Jobs
```bash
./yii cron/update-candidate-stats
./yii cron/update-company-stats
```
