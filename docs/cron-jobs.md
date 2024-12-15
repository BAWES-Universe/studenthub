# Cron Jobs Configuration

## Git and Composer Updates
```bash
# Pull from git every minute
* * * * * cd ~/www && git pull >> ~/logs/git.log

# Install composer dependencies every minute
* * * * * cd ~/www && /usr/local/bin/composer install 2>&1 | cat >> ~/logs/$
```

## Environment Initialization
```bash
# Dev server
* * * * * cd ~/www && ./init --env=Dev-Server --overwrite=All > ~/logs/init$

# Production
* * * * * cd ~/www && ./init --env=Production --overwrite=All > ~/logs/init$

# Database migrations
* * * * * cd ~/www && ./yii migrate --interactive=0 >> ~/logs/migrate.log
```

## Daily Tasks

### 1:30 PM Daily
```bash
30 13 * * * php ~/www/yii cron/daily > /dev/null 2>&1
```
Tasks:
- Birthday alerts
- Age validation alerts
- Civil ID expiry checks
- Company payment notifications

### 8:00 AM Daily
```bash
0 8 * * * php ~/www/yii cron/summary > /dev/null 2>&1
0 8 * * * php ~/www/yii cron/payable-candidate-notification > /dev/null 2>&1
```

### 10:30 AM (Sunday-Thursday)
```bash
30 10 * * 0-4 php ~/www/yii cron/check-daily-attendance > /dev/null 2>&1
```
- Sends morning report to staff

### Every Minute
```bash
* * * * * php ~/www/yii cron/every-minute > /dev/null 2>&1
```
``` 