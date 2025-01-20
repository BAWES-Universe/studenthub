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

## execute docker build 

`docker exec -it <container_id> /bin/bash`

docker exec -it b019f98548b1 /bin/bash

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

### tag and push 
- docker tag studenthub/backend-dev:latest 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-dev:latest
- docker push 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-dev:latest

## using docker compose 
- docker-compose -f docker-compose-dev.yml build --build-arg platform=linux/amd64 tag=studenthub/backend-dev .
- docker-compose -f docker-compose-dev.yml up --build

## mysql 

`docker-compose exec mysql mysql -u root -p`

`docker-compose exec mysql mysql -u studenthubuser -pstudenthub -h mysql-1`

# Fix 

./common/bin/png-linux-386 "http://localhost:8888/bawes/studenthub/staff/web/v1/candidate-id-cards/1/BjE4JvIxqgIO3SiNyNpTPdIzK6YwWLlm" "/var/www/html/common/runtime/cache";

bash: ./common/bin/png-linux: cannot execute binary file: Exec format error

http://localhost:8888/bawes/studenthub/staff/web/v1/candidate-id-cards/1/BjE4JvIxqgIO3SiNyNpTPdIzK6YwWLlm

https://staff.api.dev.studenthub.co/v1/candidate-id-cards/1/yGVo9g1t4urP9ScpxP1A2yMwUuNN7hl6

$command = "/var/www/html/common/bin/png-linux-386 https://staff.api.dev.studenthub.co/v1/candidate-id-cards/8/yGVo9g1t4urP9ScpxP1A2yMwUuNN7hl6 /tmp/id-cards/IJE71DHapkjGgL2dqy4M > /dev/null 2>&1";

exec($command, $output, $returnVar);

var_dump($output);
var_dump($returnVar);

## License

Proprietary software. All rights reserved.


