#!/bin/sh
set -eu

candidate_model="common/models/Candidate.php"
account_controller="candidate/modules/v1/controllers/AccountController.php"
s3_manager="common/components/S3ResourceManager.php"
common_config="common/config/main.php"
railway_config="environments/prod-railway/common/config/main-local.php"

grep -q "'candidate_civil_photo_front' => 'photos'" "$candidate_model"
grep -q "'candidate_civil_photo_back' => 'photos'" "$candidate_model"
grep -q '"photos/" . $this->oldAttributes' "$candidate_model"
grep -q 'Civil ID copy verification failed' "$candidate_model"
grep -q 'fileExists($targetPath)' "$candidate_model"

grep -q 'headObject' "$s3_manager"
grep -q 'DeleteMarker' "$s3_manager"

grep -q 'candidate_civil_need_verification = true' "$account_controller"
grep -q 'Invalid civil ID or expiry date' "$account_controller"
grep -q 'actionRemoveCivilPhotoBack' "$account_controller"
grep -q 'actionRemoveCivilPhotoFront' "$account_controller"
grep -q 'Unable to remove civil photo back.' "$account_controller"
grep -q 'Unable to remove civil photo front.' "$account_controller"
grep -q "if (!\$model->updateCivilId('back'))" "$account_controller"
grep -q "if (!\$model->updateCivilId('front'))" "$account_controller"

grep -q 'AWS_TEMP_BUCKET_KEY' "$common_config"
grep -q 'AWS_TEMP_BUCKET_SECRET' "$common_config"
grep -q "'key' => getenv('AWS_TEMP_BUCKET_KEY') ?: null" "$common_config"
grep -q "'secret' => getenv('AWS_TEMP_BUCKET_SECRET') ?: null" "$common_config"
grep -q 'AWS_PERMANENT_S3_ACCESS_KEY_ID' "$railway_config"
grep -q 'AWS_PERMANENT_S3_SECRET_ACCESS_KEY' "$railway_config"
grep -q "'key' => getenv('AWS_PERMANENT_S3_ACCESS_KEY_ID') ?: null" "$railway_config"
grep -q "'secret' => getenv('AWS_PERMANENT_S3_SECRET_ACCESS_KEY') ?: null" "$railway_config"

if grep -R "AKIAWMITDJRKVN5ODY2X\\|AKIAWMITDJRKWZZEWCUM" \
    "$common_config" "$railway_config"; then
  echo "hardcoded S3 key suffix remains in patched configs" >&2
  exit 1
fi

echo "PASS S3 Civil ID hardening checks"
