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

echo "github private key" > ~/.ssh/github
chmod go-rw ~/.ssh/github
echo "github public key" > ~/.ssh/github.pub
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
