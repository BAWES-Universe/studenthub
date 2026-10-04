#!/bin/bash
apt-get install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt-get update
apt-get install -y php7.4 php7.4-cli php7.4-common
apt-get install -y php7.4-curl php7.4-gd php7.4-json php7.4-mbstring php7.4-intl php7.4-mysql php7.4-xml php7.4-zip
apt install -y apache2 wget
usermod -a -G www-data ubuntu
chown -R ubuntu:www-data /var/www
chmod 2775 /var/www
find /var/www -type d -exec chmod 2775 {} \;
find /var/www -type f -exec chmod 0664 {} \;
#echo "<?php phpinfo(); ?>" > /var/www/html/phpinfo.php
apt install -y openssh-clients
ps -auxc | grep ssh-agent
eval $(ssh-agent)
cd /var/www/html

if [ -z "${GITHUB_DEPLOY_KEY_PATH:-}" ]; then
  echo "Set GITHUB_DEPLOY_KEY_PATH to a readable deploy key file before cloning private repositories." >&2
  exit 1
fi

install -m 600 "$GITHUB_DEPLOY_KEY_PATH" github
if [ -n "${GITHUB_DEPLOY_PUBLIC_KEY_PATH:-}" ]; then
  install -m 644 "$GITHUB_DEPLOY_PUBLIC_KEY_PATH" github.pub
fi
ssh-add github
ssh-keyscan github.com/ >> ~/.ssh/known_hosts
apt install -y git
git clone git@github.com:plugnio/studenthub.git /var/www/html/studenthub
cd ./studenthub
git remote add git@github.com:plugnio/studenthub.git
git checkout master
git config --global --add safe.directory /var/www/html/studenthub
apt install -y composer
chown -R ubuntu:www-data /var/www
chmod 2775 /var/www
find /var/www -type d -exec chmod 2775 {} \;
find /var/www -type f -exec chmod 0664 {} \;
php composer.phar up --no-interaction
#./init --env=Dev-Server --overwrite=All
./init --env=Production --overwrite=All
./yii migrate --interactive=0
a2enmod rewrite
sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
echo "<Directory /var/www/html>
AllowOverride All
</Directory>" >> /etc/apache2/sites-enabled/000-default.conf

chmod a+rw /etc/apache2/sites-available/

echo "
<VirtualHost *:80>
        ServerName v.studenthub.co

        ServerAdmin khalid@bawes.net
        DocumentRoot /var/www/html/studenthub/verification/web

        #LogLevel info ssl:warn

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        #Include conf-available/serve-cgi-bin.conf

        # Rewrite rules for pretty urls in Yii framework
        <Directory \"/var/www/html/studenthub/verification/web\">
          # use mod_rewrite for pretty URL support
          RewriteEngine on
          # If a directory or a file exists, use the request directly
          RewriteCond %{REQUEST_FILENAME} !-f
          RewriteCond %{REQUEST_FILENAME} !-d
          # Otherwise forward the request to index.php
          RewriteRule . index.php
        </Directory>

</VirtualHost>

<VirtualHost *:443>
        ServerName v.studenthub.co

        ServerAdmin khalid@bawes.net
        DocumentRoot /var/www/html/studenthub/verification/web

        #LogLevel info ssl:warn

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        #Include conf-available/serve-cgi-bin.conf

        SSLEngine on
        SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
        SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key

        # Rewrite rules for pretty urls in Yii framework
        <Directory \"/var/www/html/studenthub/verification/web\">
            # use mod_rewrite for pretty URL support
            RewriteEngine on
            # If a directory or a file exists, use the request directly
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            # Otherwise forward the request to index.php
            RewriteRule . index.php
        </Directory>

</VirtualHost>" > /etc/apache2/sites-available/verification.conf


echo "
<VirtualHost *:80>
        ServerName inspector.api.studenthub.co

        ServerAdmin khalid@bawes.net
        DocumentRoot /var/www/html/studenthub/inspector/web

        #LogLevel info ssl:warn

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        #Include conf-available/serve-cgi-bin.conf

        # Rewrite rules for pretty urls in Yii framework
        <Directory \"/var/www/html/studenthub/inspector/web\">
          # use mod_rewrite for pretty URL support
          RewriteEngine on
          # If a directory or a file exists, use the request directly
          RewriteCond %{REQUEST_FILENAME} !-f
          RewriteCond %{REQUEST_FILENAME} !-d
          # Otherwise forward the request to index.php
          RewriteRule . index.php
        </Directory>

</VirtualHost>

<VirtualHost *:443>
        ServerName inspector.api.studenthub.co

        ServerAdmin khalid@bawes.net
        DocumentRoot /var/www/html/studenthub/inspector/web

        #LogLevel info ssl:warn

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        #Include conf-available/serve-cgi-bin.conf

        SSLEngine on
        SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
        SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key

        # Rewrite rules for pretty urls in Yii framework
        <Directory \"/var/www/html/studenthub/inspector/web\">
            # use mod_rewrite for pretty URL support
            RewriteEngine on
            # If a directory or a file exists, use the request directly
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            # Otherwise forward the request to index.php
            RewriteRule . index.php
        </Directory>

</VirtualHost>" > /etc/apache2/sites-available/inspector.conf


echo "
<VirtualHost *:80>
        ServerName status.api.studenthub.co

        ServerAdmin khalid@bawes.net
        DocumentRoot /var/www/html/studenthub/status/web

        #LogLevel info ssl:warn

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        #Include conf-available/serve-cgi-bin.conf

        # Rewrite rules for pretty urls in Yii framework
        <Directory \"/var/www/html/studenthub/status/web\">
          # use mod_rewrite for pretty URL support
          RewriteEngine on
          # If a directory or a file exists, use the request directly
          RewriteCond %{REQUEST_FILENAME} !-f
          RewriteCond %{REQUEST_FILENAME} !-d
          # Otherwise forward the request to index.php
          RewriteRule . index.php
        </Directory>

</VirtualHost>

<VirtualHost *:443>
        ServerName status.api.studenthub.co

        ServerAdmin khalid@bawes.net
        DocumentRoot /var/www/html/studenthub/status/web

        #LogLevel info ssl:warn

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        #Include conf-available/serve-cgi-bin.conf

        SSLEngine on
        SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
        SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key

        # Rewrite rules for pretty urls in Yii framework
        <Directory \"/var/www/html/studenthub/status/web\">
            # use mod_rewrite for pretty URL support
            RewriteEngine on
            # If a directory or a file exists, use the request directly
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            # Otherwise forward the request to index.php
            RewriteRule . index.php
        </Directory>

</VirtualHost>" > /etc/apache2/sites-available/status.conf

echo "
<VirtualHost *:80>
        ServerName employer.api.studenthub.co

        ServerAdmin khalid@bawes.net
        DocumentRoot /var/www/html/studenthub/company/web

        #LogLevel info ssl:warn

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        #Include conf-available/serve-cgi-bin.conf

        # Rewrite rules for pretty urls in Yii framework
        <Directory \"/var/www/html/studenthub/company/web\">
          # use mod_rewrite for pretty URL support
          RewriteEngine on
          # If a directory or a file exists, use the request directly
          RewriteCond %{REQUEST_FILENAME} !-f
          RewriteCond %{REQUEST_FILENAME} !-d
          # Otherwise forward the request to index.php
          RewriteRule . index.php
        </Directory>

</VirtualHost>

<VirtualHost *:443>
        ServerName employer.api.studenthub.co

        ServerAdmin khalid@bawes.net
        DocumentRoot /var/www/html/studenthub/company/web

        #LogLevel info ssl:warn

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        #Include conf-available/serve-cgi-bin.conf

        SSLEngine on
        SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
        SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key

        # Rewrite rules for pretty urls in Yii framework
        <Directory \"/var/www/html/studenthub/company/web\">
            # use mod_rewrite for pretty URL support
            RewriteEngine on
            # If a directory or a file exists, use the request directly
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            # Otherwise forward the request to index.php
            RewriteRule . index.php
        </Directory>

</VirtualHost>" > /etc/apache2/sites-available/employer.conf

echo "
<VirtualHost *:80>
        ServerName student.api.studenthub.co

        ServerAdmin khalid@bawes.net
        DocumentRoot /var/www/html/studenthub/candidate/web

        #LogLevel info ssl:warn

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        #Include conf-available/serve-cgi-bin.conf

        # Rewrite rules for pretty urls in Yii framework
        <Directory \"/var/www/html/studenthub/candidate/web\">
          # use mod_rewrite for pretty URL support
          RewriteEngine on
          # If a directory or a file exists, use the request directly
          RewriteCond %{REQUEST_FILENAME} !-f
          RewriteCond %{REQUEST_FILENAME} !-d
          # Otherwise forward the request to index.php
          RewriteRule . index.php
        </Directory>

</VirtualHost>

<VirtualHost *:443>
        ServerName student.api.studenthub.co

        ServerAdmin khalid@bawes.net
        DocumentRoot /var/www/html/studenthub/candidate/web

        #LogLevel info ssl:warn

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        #Include conf-available/serve-cgi-bin.conf

        SSLEngine on
        SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
        SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key

        # Rewrite rules for pretty urls in Yii framework
        <Directory \"/var/www/html/studenthub/candidate/web\">
            # use mod_rewrite for pretty URL support
            RewriteEngine on
            # If a directory or a file exists, use the request directly
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            # Otherwise forward the request to index.php
            RewriteRule . index.php
        </Directory>

</VirtualHost>" > /etc/apache2/sites-available/student.conf

echo "
<VirtualHost *:80>
        ServerName staff.api.studenthub.co

        ServerAdmin khalid@bawes.net
        DocumentRoot /var/www/html/studenthub/staff/web

        #LogLevel info ssl:warn

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        #Include conf-available/serve-cgi-bin.conf

        # Rewrite rules for pretty urls in Yii framework
        <Directory \"/var/www/html/studenthub/staff/web\">
          # use mod_rewrite for pretty URL support
          RewriteEngine on
          # If a directory or a file exists, use the request directly
          RewriteCond %{REQUEST_FILENAME} !-f
          RewriteCond %{REQUEST_FILENAME} !-d
          # Otherwise forward the request to index.php
          RewriteRule . index.php
        </Directory>

</VirtualHost>

<VirtualHost *:443>
        ServerName staff.api.studenthub.co

        ServerAdmin khalid@bawes.net
        DocumentRoot /var/www/html/studenthub/staff/web

        #LogLevel info ssl:warn

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        #Include conf-available/serve-cgi-bin.conf

        SSLEngine on
        SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
        SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key

        # Rewrite rules for pretty urls in Yii framework
        <Directory \"/var/www/html/studenthub/staff/web\">
            # use mod_rewrite for pretty URL support
            RewriteEngine on
            # If a directory or a file exists, use the request directly
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            # Otherwise forward the request to index.php
            RewriteRule . index.php
        </Directory>

</VirtualHost>" > /etc/apache2/sites-available/staff.conf

echo "
<VirtualHost *:80>
        ServerName admin.api.studenthub.co

        ServerAdmin khalid@bawes.net
        DocumentRoot /var/www/html/studenthub/admin/web

        #LogLevel info ssl:warn

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        #Include conf-available/serve-cgi-bin.conf

        # Rewrite rules for pretty urls in Yii framework
        <Directory \"/var/www/html/studenthub/admin/web\">
          # use mod_rewrite for pretty URL support
          RewriteEngine on
          # If a directory or a file exists, use the request directly
          RewriteCond %{REQUEST_FILENAME} !-f
          RewriteCond %{REQUEST_FILENAME} !-d
          # Otherwise forward the request to index.php
          RewriteRule . index.php
        </Directory>

</VirtualHost>

<VirtualHost *:443>
        ServerName admin.api.studenthub.co

        ServerAdmin khalid@bawes.net
        DocumentRoot /var/www/html/studenthub/admin/web

        #LogLevel info ssl:warn

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        #Include conf-available/serve-cgi-bin.conf

        SSLEngine on
        SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
        SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key

        # Rewrite rules for pretty urls in Yii framework
        <Directory \"/var/www/html/studenthub/admin/web\">
            # use mod_rewrite for pretty URL support
            RewriteEngine on
            # If a directory or a file exists, use the request directly
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            # Otherwise forward the request to index.php
            RewriteRule . index.php
        </Directory>

</VirtualHost>" > /etc/apache2/sites-available/admin.conf

a2ensite company.conf
a2ensite inspector.conf
a2ensite staff.conf
a2ensite status.conf
a2ensite student.conf
a2ensite verification.conf
a2ensite admin.conf

a2enmod ssl
a2ensite default-ssl
a2enmod headers

systemctl restart apache2

#install new cron file
crontab /var/www/html/studenthub/cron/cronlist
