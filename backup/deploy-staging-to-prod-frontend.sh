# Deploy Student App from Staging & Invalidate Cloudfront
echo 'Student app: Copying Frontend Build from S3 Staging to S3 Production'
aws s3 sync s3://studenthub-candidate-staging/ s3://studenthub-candidate-prod/ --region=eu-west-2 && aws cloudfront create-invalidation --distribution-id E1V7IE2AJ6T7A7 --paths '/*'


# Deploy Employer App from Staging & Invalidate Cloudfront
echo 'Employer app: Copying Frontend Build from S3 Staging to S3 Production'
aws s3 sync s3://studenthub-company-staging/ s3://studenthub-company-prod/ --region=eu-west-2 && aws cloudfront create-invalidation --distribution-id E3NL2XSKDK9UYF --paths '/*'


# Deploy Staff App from Staging & Invalidate Cloudfront
echo 'Staff app: Copying Frontend Build from S3 Staging to S3 Production'
aws s3 sync s3://studenthub-staff-staging/ s3://studenthub-staff-prod/ --region=eu-west-2 && aws cloudfront create-invalidation --distribution-id E97L4ND30CEDZ --paths '/*'


# Deploy Admin App from Staging & Invalidate Cloudfront
echo 'Admin app: Copying Frontend Build from S3 Staging to S3 Production'
aws s3 sync s3://studenthub-admin-staging/ s3://studenthub-admin-prod/ --region=eu-west-2 && aws cloudfront create-invalidation --distribution-id E3PPAL159PAQIU --paths '/*'

# Deploy Inspector App from Staging & Invalidate Cloudfront
echo 'Inspector app: Copying Frontend Build from S3 Staging to S3 Production'
aws s3 sync s3://studenthub-inspector-staging/ s3://studenthub-inspector-prod/ --region=eu-west-2 && aws cloudfront create-invalidation --distribution-id E2YL05NYZCFYDX --paths '/*'