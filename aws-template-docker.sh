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

if [ -z "${GITHUB_DEPLOY_KEY_PATH:-}" ]; then
  echo "Set GITHUB_DEPLOY_KEY_PATH to a readable deploy key file before cloning private repositories." >&2
  exit 1
fi

mkdir -p ~/.ssh
install -m 600 "$GITHUB_DEPLOY_KEY_PATH" ~/.ssh/github
if [ -n "${GITHUB_DEPLOY_PUBLIC_KEY_PATH:-}" ]; then
  install -m 644 "$GITHUB_DEPLOY_PUBLIC_KEY_PATH" ~/.ssh/github.pub
fi
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
#AWS_ECR_REGION="${AWS_ECR_REGION:-eu-west-2}"
#AWS_ECR_REGISTRY="${AWS_ECR_ACCOUNT_ID}.dkr.ecr.${AWS_ECR_REGION}.amazonaws.com"
#AWS_ECR_IMAGE="${AWS_ECR_IMAGE:-studenthub/backend-prod}"
#AWS_ECR_TAG="${AWS_ECR_TAG:-latest}"
#aws ecr get-login-password --region "$AWS_ECR_REGION" | sudo docker login --username AWS --password-stdin "$AWS_ECR_REGISTRY"

# Pull the Docker image from ECR
#sudo docker pull "$AWS_ECR_REGISTRY/$AWS_ECR_IMAGE:$AWS_ECR_TAG"

# Run the Docker container
#sudo docker run -d -p 80:80 --name studenthub-backend-prod "$AWS_ECR_REGISTRY/$AWS_ECR_IMAGE:$AWS_ECR_TAG"
