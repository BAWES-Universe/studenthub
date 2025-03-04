# Login 

`railway login` 

# Link to Railway to use Railway CLI 

`railway link`

# Connect with Sql 

`railway connect mysql` 

`railway connect mysql-wallet`

# import 

`SET foreign_key_checks = 0;`

`source ./railway/staging/studenthub.sql`

`source ./railway/staging/wallet.sql`

`SET foreign_key_checks = 1;`

#Env setup 

## dev 

`RAILWAY_DOCKERFILE_PATH=./Dockerfile-nginx-dev-railway`

## prod 

`RAILWAY_DOCKERFILE_PATH=./Dockerfile-nginx-railway`
