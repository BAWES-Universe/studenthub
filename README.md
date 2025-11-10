# StudentHub

StudentHub is a platform that enables corporate recruitment and management of trainees through a structured program. The system facilitates trainee placement, time tracking, and payment processing between companies, trainees, and administrators.

## Quick Start

1. Clone the repository
2. Run `docker-compose up` to start the development environment
3. Access the various APIs:
   - Admin: http://localhost:21080
   - Candidate: http://localhost:22080
   - Company: http://localhost:23080
   - Inspector: http://localhost:24080
   - Staff: http://localhost:25080
   - Verification: http://localhost:26080

## Traefic Reverse Proxy Config

Add these entries to your /etc/hosts file (or C:\Windows\System32\drivers\etc\hosts on Windows)

```
127.0.0.1 admin.studenthub.local
127.0.0.1 candidate.studenthub.local
127.0.0.1 company.studenthub.local
127.0.0.1 staff.studenthub.local
127.0.0.1 manager.studenthub.local
127.0.0.1 inspector.studenthub.local
127.0.0.1 verification.studenthub.local
127.0.0.1 phpmyadmin.studenthub.local
127.0.0.1 traefik.studenthub.local
```
   

## Documentation

Detailed documentation is available in the `docs/` directory:

- [Setup Guide](docs/setup.md) - Installation and configuration
- [User Roles](docs/user-roles.md) - User types and permissions
- [API Endpoints](docs/api-endpoints.md) - Available API endpoints
- [Database Documentation](docs/database/README.md) - Database structure and diagrams
- [Cron Jobs](docs/cron-jobs.md) - Scheduled tasks
- [Analytics](docs/analytics.md) - Event tracking and analytics

## Development

- Run tests: `./run-tests.sh`
- Access backend container: `docker-compose exec backend bash`
- Code generator: http://localhost:8888/bawes/studenthub/admin/web/gii

## allow access from docker to local mysql server 

`GRANT ALL PRIVILEGES ON *.* TO 'root'@'192.168.1.5' IDENTIFIED BY 'root' WITH GRANT OPTION;`

## allow access from docker to local mysql server 
`GRANT ALL PRIVILEGES ON wallet.* TO 'studenthubuser'@'127.0.0.1'`

# copy to s3 

`aws s3 cp ./db.sql s3://studenthub-uploads-dev-server/exports/db.sql`

# Docker 

`docker-compose -f docker-compose-dev.yml down`
`docker-compose -f docker-compose-dev.yml -p studenthub-dev-server up -d`
`docker-compose -f docker-compose-dev.yml -p studenthub-dev-server down`

`docker-compose -f docker-compose-local.yml -p studenthub-local-server up -d`

`docker-compose -f docker-compose-prod.yml -p studenthub-prod-server up -d`

## CI/ CD 

### Build image 

`docker-compose -f docker-compose-dev.yml -p studenthub-dev-server build`

### Run container 

`docker-compose -f docker-compose-dev.yml -p studenthub-dev-server up --force-recreate`

## To rebuild this image you must use `docker-compose build` or `docker-compose up --build`.

## execute docker build 

`docker exec -it <container_id> /bin/bash`

docker exec -it b019f98548b1 /bin/bash

docker exec -it studenthub-backend-dev /bin/bash
docker exec -it studenthub-backend-prod /bin/bash

docker logs b019f98548b1

docker exec -it 50ae5a2794bf0a7f2baa087230036f7b5866b6c868d9e8168b59ec19fa0b7ada /bin/bash

## fixes 
- why composer install not working 

## publish to ecr

### login
- aws ecr get-login-password --region eu-west-2 | docker login --username AWS --password-stdin 438663597141.dkr.ecr.eu-west-2.amazonaws.com 

### basic 
- docker build  -t studenthub/backend-dev .

### cross platform build 
- docker buildx build --platform linux/amd64 -t studenthub/backend-dev -f Dockerfile-nginx-dev .
- docker buildx build --platform linux/amd64 -t studenthub/backend-prod -f Dockerfile-nginx-prod .

### tag and push 

For dev 
- docker tag studenthub/backend-dev:latest 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-dev:latest
- docker push 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-dev:latest

For prod 
- docker tag studenthub/backend-prod:latest 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-prod:latest
- docker push 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-prod:latest

## using docker compose 
- docker-compose -f docker-compose-dev.yml build --build-arg platform=linux/amd64 tag=studenthub/backend-dev .
- docker-compose -f docker-compose-dev.yml up --build

## mysql 

`docker-compose exec mysql mysql -u root -p`

`docker-compose exec mysql mysql -u studenthubuser -pstudenthub -h mysql-1`

# flush dns cache 

sudo vim /etc/hosts
- add 127.0.0.1 student.api.studenthub.co

sudo killall -HUP mDNSResponder
- to clear cache 

open any frontend app (candidate, staff, admin, employer) and test 
- make sure to use http in local 

# Fix 

./common/bin/png-linux-386 "http://localhost:8888/bawes/studenthub/staff/web/v1/candidate-id-cards/1/BjE4JvIxqgIO3SiNyNpTPdIzK6YwWLlm" "/var/www/html/common/runtime/cache";

bash: ./common/bin/png-linux: cannot execute binary file: Exec format error

http://localhost:8888/bawes/studenthub/staff/web/v1/candidate-id-cards/1/BjE4JvIxqgIO3SiNyNpTPdIzK6YwWLlm

https://staff.api.dev.studenthub.co/v1/candidate-id-cards/1/yGVo9g1t4urP9ScpxP1A2yMwUuNN7hl6

$command = "/var/www/html/common/bin/png-linux-386 https://staff.api.dev.studenthub.co/v1/candidate-id-cards/8/yGVo9g1t4urP9ScpxP1A2yMwUuNN7hl6 /tmp/id-cards/IJE71DHapkjGgL2dqy4M > /dev/null 2>&1";

exec($command, $output, $returnVar);

var_dump($output);
var_dump($returnVar);
 
 # on reboot, don't forget to run this based on the environment you want to run
 
 - docker-compose -f docker-compose-prod.yml -p studenthub-prod-server up -d

 - docker-compose -f docker-compose-dev.yml -p studenthub-dev-server up -d

 - docker-compose -f docker-compose-local.yml -p studenthub-local-server up -d

# git tag 

git tag -a v2.0 -m "Version 2.0: PHP 7.4 to 8.2"
git push origin v2.0
git tag -l

# fix migration applied but ActiveRecord/ Table column not found error getting trigger 
`docker exec -it studenthub-backend-prod /bin/bash`
`./yii cache/flush-schema db`
`./yii cache/flush cache`

## if still not working 

rm -rf /var/www/html/admin/runtime/cache
rm -rf /var/www/html/candidate/runtime/cache
rm -rf /var/www/html/company/runtime/cache
rm -rf /var/www/html/console/runtime/cache
rm -rf /var/www/html/common/runtime/cache
rm -rf /var/www/html/staff/runtime/cache
rm -rf /var/www/html/inspector/runtime/cache
rm -rf /var/www/html/manager/runtime/cache
rm -rf /var/www/html/status/runtime/cache
rm -rf /var/www/html/verification/runtime/cache


# Automatically start ssh-agent and add GitHub SSH key

Add the following lines to the ubuntu user's ~/.bashrc (or ~/.profile):

`if [ -z "$SSH_AUTH_SOCK" ]; then
    eval "$(ssh-agent -s)"
    ssh-add ~/.ssh/github
fi`


#mysql trigger 

SHOW CREATE TRIGGER after_candidate_working_hour_update \G;

DROP TRIGGER IF EXISTS after_candidate_working_hour_update;

DELIMITER $$

CREATE DEFINER=`root`@`%` TRIGGER `after_candidate_working_hour_update`
BEFORE UPDATE ON `candidate_working_hour`
FOR EACH ROW
BEGIN
    IF (NEW.total_time < 0 OR NEW.total_time IS NULL) THEN
        SET NEW.total_time = TIMESTAMPDIFF(SECOND, OLD.start_time, NEW.end_time);
    END IF;
END $$

DELIMITER;



