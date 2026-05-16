# Railway DB runtime secrets

Railway database credentials must be supplied through runtime environment variables, not committed PHP config or deployment scripts.

Dev-server Railway app config:

- `DB_DSN`
- `DB_USERNAME`
- `DB_PASSWORD`
- `WALLET_DB_DSN`
- `WALLET_DB_USERNAME`
- `WALLET_DB_PASSWORD`
- `REDIS_HOSTNAME`
- `REDIS_USERNAME`
- `REDIS_PASSWORD`
- `REDIS_PORT`
- `REDIS_DATABASE`

Railway deployment scripts:

- `MYSQL_HOST`, defaults to `mysql.railway.internal`
- `MYSQL_PORT`, defaults to `3306`
- `MYSQL_USER`, defaults to `root`
- `MYSQL_DATABASE`, defaults to `railway`
- `MYSQL_PASSWORD`, required

Set `MYSQL_PASSWORD` in Railway variables before running the deployment scripts. The scripts pass it to `mysql` through `MYSQL_PWD` for each command instead of embedding the password in the script or command line.
