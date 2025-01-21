#!/bin/bash
# Update the package repository and install required packages
sudo apt-get update -y
sudo apt-get upgrade -y
sudo apt-get install -y docker.io unzip curl gh docker-compose

# Start and enable Docker
sudo systemctl start docker
sudo systemctl enable docker

# Add the 'ubuntu' user to the Docker group
sudo usermod -aG docker ubuntu

# gir repo setup 1st way 
apt install -y openssh-clients
ps -auxc | grep ssh-agent
eval $(ssh-agent)
sudo mkdir -p /var/www/html
sudo chmod 2775 /var/www
cd /var/www/html

echo "-----BEGIN RSA PRIVATE KEY-----
      MIIEogIBAAKCAQEAxSNwKJiZ+WTV3/HCemYJ63YCl6VIrNb7sMkp2NDN5kH7K8Fx
      mbzZmRQ+Tbs29nOAPwLbtmCDWBXUp7jRTckNQ4gJJbaQBCKYRvj78q+7Vokz5GOz
      P5+o+80iIe1We62DLGpnUiBZv/LsOBs5Ud7F37srifT8tQBK/Xh8Z0lEHtyUjK82
      ZdmXd+0OdDpLN4PoTmIzQvQ7lNYqvup1QDjO5Uwbny1wjg6kAHzysL0EccpK8aJJ
      9Jwmfz3OWKjoH4a+ne4FObZAzDeTZdOuY8zJsDWVZLJ6mjvLtpXfxehZ97AvAzLi
      d+Phb21wK4FLa9IRQCEtdwwPfe+wxFEn/EvqxwIDAQABAoIBAACzbI2oZTu+wQfn
      yyI2RKjCpaW2X7jFluV9AZoUu/aqm2L/cBD02+0wZjxOgxaDOJyAvRk75JumkDf8
      bzoQkeyAik/JA2AQY2w1LGgjec4H9NhGBngecDJc+1cVie4sor/ArRdcqBUHnxFf
      /2csHJX8C16VMWTPWHToPcD8QLK/YmpXZEpmY6Gkrza3agAZFS2OAFwnAIdQg6Jj
      Ywu+Q8fMdhQ94URji0232DaA9yjyHeFQC80qz79i2/tLTPV1jO2kAjIfBgZqhFmw
      gf5cuII+Dglzm6Rf6dXMKC6gu/Qfgw6pRvq/dndDDUtOoGt4aN/vpbAGe7SocFDq
      by0G9VkCgYEA7I0vnm61igqAMbilCvzNZOswEpE36+BYqpA/zidM78ah3NrHVZez
      I6kZO0O8sDTZk7767rfJAyOflwHDaZ7+D7gxRIwYk3GBEzZCXcndlwDCgksmLiwo
      znVENTbB4/6rc6enKEZ9hKq7fVkklpfhEyuASJrQjzznlLArr1+ATQ0CgYEA1Vi0
      vHOlqoH0KTPAaWr3CLrlT8UdWT7hii2LaW0uUVDwsnVWKgyJ/DUqqMKBP3XE9VpJ
      1yhf3ev7M9/s6D4NM6Gvv2/Y2NHggigIUvEL6ANuX3QIWaIzuFqZGt1AMOkgAGLe
      hoadECk1Lts9JMo9hfDlbXXdTqgm5vN1k5LCaiMCgYBiaUZzUjmiaUX16Yx4Kr5E
      ivff8ZxjPF2G8CYrXPxMEMYMPNSLDmaPom0F0+NvJz2RkvWQVNOCw9JEH46tpWCr
      J4UPsH0U8jaxyHQJ0s6mYMGLNTp4IRUE95Jd1R8K/EtV/Vy0i8byZ5lCx9BJ+nu/
      3uwqyIWV4c+ycyxpdIDLDQKBgDfIqflcg8TBlgXv8i+eOFmgBLWG5NWhC3gF0J4Q
      XjwE3erLo2v7O1kmzbCyqvjc4lME+Km5dNeiDFzUAC35i5okfL+hjyGiPwHJVePa
      AzL3MrTisgw9zSg3CDFuCIrBagyvzV9/czH2lGFcEfC8sLmE83hbiTI35XQd1HBx
      v0PhAoGAdbkL4eJqBeK3PFE4cc8IznQPjbmSo0ru9COzJMYCsGfYD+6sswfdDqLq
      oYoWj/UmmEKRAXJF0Xa/rTa7mVmKQLL/uhDj1+1RIYVxv0ONFKc6ceN95HoM1l9W
      ANyf8oribXlnYsWAIDxT5M/AKs76/+IUxvL8+SQoLwQfDNKsKVM=
      -----END RSA PRIVATE KEY-----" > ~/.ssh/github
chmod go-rw ~/.ssh/github
echo "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDFI3AomJn5ZNXf8cJ6ZgnrdgKXpUis1vuwySnY0M3mQfsrwXGZvNmZFD5Nuzb2c4A/Atu2YINYFdSnuNFNyQ1DiAkltpAEIphG+Pvyr7tWiTPkY7M/n6j7zSIh7VZ7rYMsamdSIFm/8uw4GzlR3sXfuyuJ9Py1AEr9eHxnSUQe3JSMrzZl2Zd37Q50Oks3g+hOYjNC9DuU1iq+6nVAOM7lTBufLXCODqQAfPKwvQRxykrxokn0nCZ/Pc5YqOgfhr6d7gU5tkDMN5Nl065jzMmwNZVksnqaO8u2ld/F6Fn3sC8DMuJ34+FvbXArgUtr0hFAIS13DA9977DEUSf8S+rH ubuntu@ip-172-31-1-148" > ~/.ssh/github.pub
sudo chmod a+r ~/.ssh/github
ssh-add ~/.ssh/github
ssh-keyscan github.com >> ~/.ssh/known_hosts
apt install -y git
git clone git@github.com:plugnio/studenthub.git /var/www/html
cd ./studenthub
git remote add git@github.com:plugnio/studenthub.git
git checkout master
git config --global --add safe.directory /var/www/html

# or 2nd way 
chown -R $(whoami):$(whoami) /var/www 
gh auth login
gh repo clone plugnio/studenthub /var/www/html

#find /var/www -type d -exec chmod 2775 {} \;
#find /var/www -type f -exec chmod 0664 {} \;

docker-compose -f docker-compose-prod.yml -p studenthub-prod-server up -d

# Install AWS CLI
#curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
#sudo apt-get install -y unzip # Install unzip if not already available
#unzip awscliv2.zip
#sudo ./aws/install
#rm -rf awscliv2.zip aws

# Authenticate Docker to Amazon ECR
#aws ecr get-login-password --region eu-west-2 | sudo docker login --username AWS --password-stdin 438663597141.dkr.ecr.eu-west-2.amazonaws.com

# Pull the Docker image from ECR
#sudo docker pull 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-prod

# Run the Docker container
#sudo docker run -d -p 80:80 --name studenthub-backend-prod 438663597141.dkr.ecr.eu-west-2.amazonaws.com/studenthub/backend-prod
