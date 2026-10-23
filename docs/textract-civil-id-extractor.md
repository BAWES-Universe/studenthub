# Textract Civil ID Expiry Extractor

`common\components\IdExpiryDateExtractor` reads Civil ID images from the permanent S3 bucket and sends them to AWS Textract.

## Required runtime configuration

Set these values in the runtime secret store. Do not commit real key values.

- `AWS_TEXTRACT_ACCESS_KEY_ID`
- `AWS_TEXTRACT_SECRET_ACCESS_KEY`

The extractor reuses the configured permanent S3 bucket and region from `resourceManager` unless `bucket` or `region` are explicitly set on the component.

## Safety behavior

- Missing Textract credentials or bucket configuration returns `operation: error` without creating an AWS client.
- Only `photos/` S3 object keys are accepted for Textract reads.
- AWS/provider exceptions are logged by exception type or AWS error code and are not returned to API callers.
- The Civil ID validation cron only updates an expiry date when Textract returns an actual date match.
