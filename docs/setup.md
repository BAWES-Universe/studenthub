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

### Service Integration Secrets

Keep service API tokens in deployment environment variables instead of checked-in
config files or browser bundles:

```bash
WALLET_API_KEY=
YEASTER_MICROSERVICE_API_KEY=
EVENT_MANAGER_ENDPOINT_API_KEY=
```

`WALLET_API_KEY` is used by the legacy wallet integration when enabled.
`YEASTER_MICROSERVICE_API_KEY` authenticates voicemail microservice requests.
`EVENT_MANAGER_ENDPOINT_API_KEY` authenticates EventManager calls to the SQS
bridge endpoint when `sqsEndpoint` is configured.

## Server Requirements

### PHP Extensions
Required: exif, pdo_mysql

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
