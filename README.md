# StudentHub

The platform enables the admin to create `Corporate` accounts and `Studenthub Staff` accounts that will manage the employees that are part of the program.

## Backend API Endpoints

### Dev Server

* staff.api.dev.studenthub.co
* student.api.dev.studenthub.co
* admin.api.dev.studenthub.co
* employer.api.dev.studenthub.co
* status.api.dev.studenthub.co
* v.dev.studenthub.co

## Types of Users

### Studenthub Staff

Studenthub staff will be offering trainee recruitment and administrative services to the corporate.

* Create and manage Employee accounts
* Assign and unassign employee to a company.

### Corporate

* Will sign a contract with admin for a fixed hourly rate they will pay for their assigned trainees.
* Will be able to list and view details of their currently assigned trainees.
* Every month, they will need to create a `TransferRequest` and fill in the number of hours worked by every assigned employee. System will calculate the total amount of money they need to transfer to `Studenthub Admin` to be sent out to the `Employees`.
* Transfer requests need to be verified and accepted by the corporate before it is sent out to admin.
* Once the transfer is received by admin, the corporate will be notified and be sent a `Receipt`
* System will notify admin if a corporate hasn't created a transfer request by the X day of every month.

### Employee

Employees are recruited to join the training program by Studenthub staff. They have to sign a contract and provide their identity documents and bank info. They will then be assigned to work for companies.

Employees are to also sign a "Tanazol" document forfeiting their rights as a full timer.

### Admin

* Approve a transfer request, send employer a receipt.
* Send an approved transfer request to the payment company via API which will transfer the salaries out to the employees.

### add puppeteer

`curl -sL https://deb.nodesource.com/setup_12.x | sudo -E bash -`

`sudo apt-get install -y nodejs gconf-service libasound2 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 ca-certificates fonts-liberation libappindicator1 libnss3 lsb-release xdg-utils wget libgbm-dev`

`sudo npm install --global --unsafe-perm puppeteer`

`sudo chmod -R o+rx /usr/lib/node_modules/puppeteer/.local-chromium`

### php extensions required

exif, pdo_mysql

### cron set up for admin notifications

# Pull from git every minute
`* * * * * cd ~/www && git pull >> ~/logs/git.log`

# Install composer dependencies every minute
`* * * * * cd ~/www && /usr/local/bin/composer install 2>&1 | cat >> ~/logs/$`

# Initialize server environment files every minute
Dev server ---> `* * * * * cd ~/www && ./init --env=Dev-Server --overwrite=All > ~/logs/init$`
Production ---> `* * * * * cd ~/www && ./init --env=Production --overwrite=All > ~/logs/init$`

# Migrate database changes every minute
`* * * * * cd ~/www && ./yii migrate --interactive=0 >> ~/logs/migrate.log`

# Daily CRON at 1:30 PM Every Day
`30 13 * * * php ~/www/yii cron/daily > /dev/null 2>&1`

* Cron to Check for birthday : Candidate::birthdayAlert();
* Cron to Check for invalid age : Candidate::ageAlert();
* Cron to Check civil ID expiry date : Candidate::civilIdExpire();
* Cron to send Notification to admin regarding company who didn't created transfer after 35 days : Company::adminPendingPaymentNotification();

# Daily CRON at 8:00 AM Every Day
`0 8 * * * php ~/www/yii cron/summary > /dev/null 2>&1`


# Daily CRON at 8:00 AM Every Day
`0 8 * * * php ~/www/yii cron/payable-candidate-notification > /dev/null 2>&1`

# Daily CRON At 10:30 on every day-of-week from Sunday through Thursday
`30 10 * * 0-4 php ~/www/yii cron/check-daily-attendance > /dev/null 2>&1`

* Sends morning report to staff

# CRON every minute
`* * * * * php ~/www/yii cron/every-minute > /dev/null 2>&1`

### make sure to update staff api url

for `urlManagerStaff` component's `baseUrl` property for files at environments/*/common/config/main-local.php

## Set up Docker Dev Environment -1

Run the following command after installing Docker

```bash
docker-compose up
```

This should set you up with the entire app along with MySQL and Redis. Use the following links to check it out:

* [Admin API on localhost:21080](http://localhost:21080)
* [Candidate API on localhost:23080](http://localhost:22080)
* [Company API on localhost:8080](http://localhost:23080)
* [Inspector API on localhost:8080](http://localhost:24080)
* [Staff API on localhost:8080](http://localhost:25080)
* [Verification on localhost:8080](http://localhost:26080)


## Accessing terminal in backend container

```bash
docker-compose exec backend bash

# Now you can run things like
./init
./yii migrate
```

## Running Codeception Tests

Use `docker-compose run --rm` to launch a new backend container which will run the automated tests then destroy the container after it's done.

We have a shortcut script in the project main folder you can use to run complete tests.

```bash
# Shortcut script in project root folder.
# Launch this from your own device(host) not the container
./run-tests.sh

# What this is doing is calling
docker-compose run --rm backend vendor/bin/codecept run --fail-fast --html report-web.html

# You can also run this in the background by passing `-d` flag
# to docker-compose and check the test results in the
# outputted report-web.html
```
 
# List of events we sending to segment

* Request Activity Added (public)
* Transfer Created (public)
* Transfer Updated (public)
* Fulltimer Created (public)
* Fulltimer Updated (public)
* Request Created
* Request Updated
* Suggestion Created (public)
* Suggestion Updated (public)
* Transfer Marked As Payment Received (public)
* Transfer Locked
* Transfer UnLocked
* Candidate Transfer Paid (Can calculate profit from this) (public)
* Candidate Profile Created (public)
* Candidate Profile Updated (public)
* Candidate Invitation Accepted (public)
* Candidate Invitation Rejected (public)
* Candidate Invited (public)
* New Wallet Entry (public)
* Paid By Wallet (public)
* Expense Added (public)
* Company Profile Created (public)

New events: 
* Story Created 
* Story Updated
* Staff Created 
* Staff Updated
* Candidate Profile Completed - - Country/Region, Age, Gender, and Other Demographic Data
* Company Profile Updated - company_approved_to_hire 

implement mixpanel + segment in frontend apps 

campaing like plugn to calculate campaign cost, ROI, Conversion Rate from Campaign Traffic, 
Customer Acquisition Cost etc


Public events are the events that can be fired manually by uploading event excel sheet.

There can be other custom events fired manually, use `Datetime` column in excel to upload past events
    
## Need to do 

- ability to configure mixpanel account/key etc from admin 
- ability to configure segment account/key etc from admin
- ability to enable/ disable segment/mixpanel from admin
- test in production mode for segment/ mixpanel integration

yii fixture "*, -WalletBankFixture, -WalletTransferFixture, -WalletUserFixture"

# verification url in local 

http://localhost/studenthub/verification/web/index.php?r=site/index&candidate_uid=30609122319

http://localhost:8888/studenthub/verification/web/index.php?r=site/index&candidate_uid=yac7ANlKJWOeigWlY7Xf

## Email campaign 

### Filter 
filterAssigned
filterNotAssigned
filterStore
filterCountry
filterUniversity
idExpired
byApprovalStatus
verifiedProfile
withoutBankInfo
candidateMomKuwaitiFieldIsNull

## mails we sending 

### admin 
- admin - Your account password has been reset 
- candidate - Your account password has been reset 
- staff - Your account password has been reset 
- staff - Reset your StudentHub password
- candidate - Transfer failed. Please update your bank info

### candidate 
- Password reset token 

### common 
- candidate - Your password reset was a success
- candidate - Reset your StudentHub password
- candidate - Happy Birthday from StudentHub
- candidate - Please update your civil id
- candidate - Please confirm your email address
- candidate - We'll stop recommending your profile to companies
- candidate - Jobs in restaurants, cafes, and cinemas
- candidate - Civil ID is expiring
- candidate - Bank information is missing
- candidate - warning mail
- company - Request for Attendance and Working Hours for Part-Time Employees
- sales - New company account request
- company - Congratulation! Your account request approved!
- company - New company account request not approved!
- company contact - Please confirm your email address
- admin - Stand-up report
- candidate - email campaign 
- inspector - Your password reset was a success
- inspector - Reset your StudentHub password
- candidate - You’re invited to apply for a job opening
- admin - Company not paid in current month
- contact - candidate suggestions
- contact - Thank you for your payment/invoices 
- candidate - KD <amount> has been transferred to your bank account
- finance - Transferred KWD {$amount} to {$count} people

### console 
- staff - Morning Report 
- operation - We need to process KWD $amount to ".count($payableCandidate)." people
- staff - Daily Attendance notification

### inspector 
- staff - Password reset token 

### staff 
- khalid - staff created/updated client account 
- contact - <Payroll Month> Payroll 
- staff - Password reset token 
- staff - I've updated the request for <request_position_title> for <company_name>

### company 
- email - has invited you to collaborate in their recruitment process on StudentHub
- Contact - Contact Invitation accepted
- contact - Reset your StudentHub password
- contact - Please confirm your email address
- contact - Password reset token
- staff - <company_name> is looking to hire <request_position_title>

# Sync with Algolia 

`cd console && ../yii algolia/index fulltimer`
`cd console && ../yii algolia/index candidate`

todo 

- company account password set 
- activation 
- approve/reject emails 
- team invitation

 migrate to php 7.4
--------------------------
 https://www.digitalocean.com/community/questions/how-to-upgrade-php-7-0-33-to-7-4-7-on-ubuntu-16-04-nginx
 a2enmod proxy_fcgi setenvif
 a2enconf php7.4-fpm

 https://stackoverflow.com/questions/36057615/ubuntu-apache-module-php7-does-not-exist

sudo systemctl stop redis-server
sudo systemctl restart redis-server
ubuntu@ip-172-31-43-171:/var/www/html$ wget localhost:6379