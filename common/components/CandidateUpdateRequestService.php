<?php

namespace common\components;

use common\models\Candidate;
use common\models\CandidateNotification;
use common\models\CandidateUpdateRequest;
use common\models\CandidateUpdateRequestUpload;
use Yii;
use yii\db\Exception as DbException;

/**
 * Staff requests for a profile photo or Civil ID.
 *
 * In-app Activity rows only. This class does not send email or push.
 */
class CandidateUpdateRequestService
{
    /**
     * @param Candidate $candidate
     * @param int $staffId
     * @param string $type
     * @return array{request: CandidateUpdateRequest, already_pending: bool}
     */
    public static function create(Candidate $candidate, $staffId, $type)
    {
        self::assertType($type);
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            $existing = self::pending($candidate->candidate_id, $type);
            if ($existing) {
                $transaction->commit();

                return [
                    'request' => $existing,
                    'already_pending' => true,
                ];
            }

            $request = new CandidateUpdateRequest();
            $request->candidate_id = (int) $candidate->candidate_id;
            $request->request_type = $type;
            $request->status = CandidateUpdateRequest::STATUS_PENDING;
            $request->staff_id = (int) $staffId;
            $request->requested_at = date('Y-m-d H:i:s');
            $request->open_slot = self::openSlot($candidate->candidate_id, $type);
            $request->baseline_photo_sha256 = self::hashKey($candidate->candidate_personal_photo);
            $request->baseline_front_sha256 = self::hashKey($candidate->candidate_civil_photo_front);
            $request->baseline_back_sha256 = self::hashKey($candidate->candidate_civil_photo_back);

            if (!$request->save()) {
                throw new DbException('Could not save update request.');
            }

            $notification = new CandidateNotification();
            $notification->candidate_id = (int) $candidate->candidate_id;
            $notification->staff_id = (int) $staffId;
            $notification->type = $type === CandidateUpdateRequest::TYPE_PROFILE_PHOTO
                ? CandidateNotification::TYPE_PROFILE_PHOTO_REQUEST
                : CandidateNotification::TYPE_CIVIL_ID_REQUEST;
            $notification->update_request_uuid = $request->cur_uuid;
            $notification->is_new = true;

            if (!$notification->save()) {
                throw new DbException('Could not save update-request notification.');
            }

            $transaction->commit();

            return [
                'request' => $request,
                'already_pending' => false,
            ];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $existing = self::pending($candidate->candidate_id, $type);
            if ($existing) {
                return [
                    'request' => $existing,
                    'already_pending' => true,
                ];
            }

            throw $e;
        }
    }

    /**
     * One in-app Activity message for this candidate. The same send key returns the saved row.
     *
     * @param Candidate $candidate
     * @param int $staffId
     * @param string $message
     * @param string $sendKey
     * @return array{operation: string, message?: string, already_sent?: bool, notification?: CandidateNotification}
     */
    public static function sendCustomNotification(Candidate $candidate, $staffId, $message, $sendKey)
    {
        $message = trim((string) $message);
        if ($message === '') {
            return [
                'operation' => 'error',
                'message' => 'Enter a message.',
            ];
        }
        if (mb_strlen($message) > 1000) {
            return [
                'operation' => 'error',
                'message' => 'Message must be 1,000 characters or less.',
            ];
        }
        if (!is_string($sendKey) || !preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/i', $sendKey)) {
            return [
                'operation' => 'error',
                'message' => 'Could not send the notification. Please try again.',
            ];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $existing = self::customNotificationForKey($sendKey);
            if ($existing) {
                $transaction->commit();

                return self::customNotificationResult($existing, $candidate);
            }

            $notification = new CandidateNotification();
            $notification->cn_uuid = $sendKey;
            $notification->candidate_id = (int) $candidate->candidate_id;
            $notification->staff_id = (int) $staffId;
            $notification->type = CandidateNotification::TYPE_STAFF_MESSAGE;
            $notification->message = $message;
            $notification->is_new = true;
            if (!$notification->save()) {
                $transaction->rollBack();

                return [
                    'operation' => 'error',
                    'message' => 'Could not send the notification. Please try again.',
                ];
            }
            $transaction->commit();

            return [
                'operation' => 'success',
                'already_sent' => false,
                'notification' => $notification,
            ];
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            $existing = self::customNotificationForKey($sendKey);
            if ($existing) {
                return self::customNotificationResult($existing, $candidate);
            }
            Yii::error('Custom candidate notification failed.', __METHOD__);

            return [
                'operation' => 'error',
                'message' => 'Could not send the notification. Please try again.',
            ];
        }
    }

    /**
     * @param string $sendKey
     * @return CandidateNotification|null
     */
    private static function customNotificationForKey($sendKey)
    {
        return CandidateNotification::findOne(['cn_uuid' => $sendKey]);
    }

    /**
     * @param CandidateNotification $existing
     * @param Candidate $candidate
     * @return array
     */
    private static function customNotificationResult(CandidateNotification $existing, Candidate $candidate)
    {
        if ((int) $existing->candidate_id !== (int) $candidate->candidate_id
            || (int) $existing->type !== CandidateNotification::TYPE_STAFF_MESSAGE
        ) {
            return [
                'operation' => 'error',
                'message' => 'Could not send the notification. Please try again.',
            ];
        }

        return [
            'operation' => 'success',
            'already_sent' => true,
            'notification' => $existing,
        ];
    }

    /**
     * @param int $candidateId
     * @return CandidateUpdateRequest[]
     */
    public static function latestForStaff($candidateId)
    {
        $rows = [];
        foreach ([CandidateUpdateRequest::TYPE_PROFILE_PHOTO, CandidateUpdateRequest::TYPE_CIVIL_ID] as $type) {
            $row = CandidateUpdateRequest::find()
                ->where(['candidate_id' => (int) $candidateId, 'request_type' => $type])
                ->orderBy(['requested_at' => SORT_DESC, 'cur_uuid' => SORT_DESC])
                ->one();
            if ($row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param int $candidateId
     * @return array<int, array{type: string, requested_at: string}>
     */
    public static function pendingForCandidate($candidateId)
    {
        $rows = CandidateUpdateRequest::find()
            ->where([
                'candidate_id' => (int) $candidateId,
                'status' => CandidateUpdateRequest::STATUS_PENDING,
            ])
            ->all();

        $payload = [];
        foreach ($rows as $row) {
            $payload[] = [
                'type' => $row->request_type,
                'requested_at' => $row->requested_at,
            ];
        }

        return $payload;
    }

    /**
     * Record a temp key that may complete a pending request.
     * Returns true when there is nothing to track or the row is stored.
     * Returns false when a pending request exists and the row could not be stored.
     *
     * @param int $candidateId
     * @param string $purpose
     * @param string $objectKey
     * @return bool
     */
    public static function recordEligibleUpload($candidateId, $purpose, $objectKey)
    {
        $type = self::typeForPurpose($purpose);
        if ($type === null || $objectKey === null || $objectKey === '') {
            return true;
        }

        $request = self::pending($candidateId, $type);
        if (!$request) {
            return true;
        }

        $hash = self::hashKey($objectKey);
        $exists = CandidateUpdateRequestUpload::find()
            ->where(['cur_uuid' => $request->cur_uuid, 'key_sha256' => $hash])
            ->exists();
        if ($exists) {
            return true;
        }

        $upload = new CandidateUpdateRequestUpload();
        $upload->cur_uuid = $request->cur_uuid;
        $upload->key_sha256 = $hash;

        try {
            return $upload->save(false) === true;
        } catch (\Throwable $e) {
            Yii::error('Eligible upload tracking failed.', __METHOD__);

            return false;
        }
    }

    /**
     * @param Candidate $candidate
     * @param string $before
     * @param string $after
     * @param string $submittedTempKey
     * @return void
     */
    public static function completeProfilePhoto(Candidate $candidate, $before, $after, $submittedTempKey)
    {
        if (self::normalizeKey($before) === self::normalizeKey($after)) {
            return;
        }

        $request = self::pending($candidate->candidate_id, CandidateUpdateRequest::TYPE_PROFILE_PHOTO);
        if (!$request) {
            return;
        }

        if (!self::uploadHashExists($request, self::hashKey($submittedTempKey))) {
            return;
        }

        self::markSubmitted($request);
    }

    /**
     * Stage one Civil ID side while a request is pending.
     * Returns null when the normal save path should run.
     *
     * @param Candidate $candidate
     * @param string $side front|back
     * @param string $tempKey
     * @return array|null
     */
    public static function stageCivilSide(Candidate $candidate, $side, $tempKey)
    {
        $request = self::pending($candidate->candidate_id, CandidateUpdateRequest::TYPE_CIVIL_ID);
        if (!$request) {
            return null;
        }

        $hash = self::hashKey($tempKey);
        if (!self::uploadHashExists($request, $hash)) {
            return self::errorResponse(Yii::t('candidate', 'This file was uploaded before the request. Please choose the photo again.'));
        }

        $basename = basename(self::normalizeKey($tempKey));
        if ($basename === '' || $basename === '.' || $basename === '..') {
            return self::errorResponse(Yii::t('app', 'file not available to save.'));
        }

        $stagedKey = 'photos/staging/' . $request->cur_uuid . '/' . $side . '/' . $basename;

        try {
            Yii::$app->resourceManager->copy(
                self::normalizeKey($tempKey),
                $stagedKey,
                Yii::$app->temporaryBucketResourceManager->bucket
            );
        } catch (\Throwable $e) {
            Yii::error('Staged civil copy failed.', __METHOD__);

            return self::errorResponse(Yii::t('app', 'file not available to save.'));
        }

        $suggestion = self::suggestionForSide($request, $side, $stagedKey);
        if (!empty($suggestion['expired'])) {
            self::releaseStagingObject($stagedKey);

            return self::errorResponse(Yii::t('app', 'Invalid Civil ID (Expired)'));
        }

        $saved = self::writeStagedRevision($request, $side, $stagedKey, $hash);
        if (!$saved) {
            self::releaseStagingObject($stagedKey);

            return self::staleCivilResponse();
        }

        $response = [
            'operation' => 'success',
            'message' => $side === 'front'
                ? Yii::t('candidate', 'Civil Photo Front Uploaded Successfully')
                : Yii::t('candidate', 'Civil Photo Back Uploaded Successfully'),
            // Canonical values. An older Student page copies these into profile state.
            'candidate_civil_photo_front' => $candidate->candidate_civil_photo_front,
            'candidate_civil_photo_back' => $candidate->candidate_civil_photo_back,
            'candidate_civil_id' => $candidate->candidate_civil_id,
            'candidate_civil_expiry_date' => $candidate->candidate_civil_expiry_date,
            'civilExpired' => $candidate->candidate_civil_expiry_date
                && (strtotime($candidate->candidate_civil_expiry_date) < strtotime(date('Y-m-d'))),
            'update_request_id' => $saved->cur_uuid,
            'staging_revision' => (int) $saved->staging_revision,
        ];
        if ($saved->staged_front_key) {
            $response['staged_photo_front'] = self::relativePhotoKey($saved->staged_front_key);
        }
        if ($saved->staged_back_key) {
            $response['staged_photo_back'] = self::relativePhotoKey($saved->staged_back_key);
        }
        if (!empty($suggestion['civil_id'])) {
            $response['suggested_civil_id'] = $suggestion['civil_id'];
        }
        if (!empty($suggestion['expiry'])) {
            $response['suggested_civil_expiry_date'] = $suggestion['expiry'];
        }

        return $response;
    }

    /**
     * Clear one staged side.
     * Null means no request is pending, so the caller may remove the saved document.
     * An error means the pending row could not be updated and the saved document must stay.
     *
     * @param Candidate $candidate
     * @param string $side
     * @return array|null
     */
    public static function clearStagedSide(Candidate $candidate, $side)
    {
        $request = self::pending($candidate->candidate_id, CandidateUpdateRequest::TYPE_CIVIL_ID);
        if (!$request) {
            return null;
        }

        $saved = self::writeStagedRevision($request, $side, null, null);
        if (!$saved) {
            return self::staleCivilResponse();
        }

        return [
            'operation' => 'success',
            'message' => $side === 'front'
                ? Yii::t('candidate', 'Civil photo front removed.')
                : Yii::t('candidate', 'Civil photo back removed.'),
            'candidate_civil_photo_front' => $candidate->candidate_civil_photo_front,
            'candidate_civil_photo_back' => $candidate->candidate_civil_photo_back,
            'candidate_civil_id' => $candidate->candidate_civil_id,
            'candidate_civil_expiry_date' => $candidate->candidate_civil_expiry_date,
            'update_request_id' => $saved->cur_uuid,
            'staging_revision' => (int) $saved->staging_revision,
        ];
    }

    /**
     * Lock the request row and advance its staging revision once.
     * Returns null when the row is no longer pending, so a delayed call cannot
     * change a submitted request. The returned model matches the saved row.
     *
     * @param CandidateUpdateRequest $request
     * @param string $side
     * @param string|null $key
     * @param string|null $hash
     * @return CandidateUpdateRequest|null
     */
    private static function writeStagedRevision(CandidateUpdateRequest $request, $side, $key, $hash)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $locked = CandidateUpdateRequest::findBySql(
                'SELECT * FROM {{%candidate_update_request}} WHERE cur_uuid = :id FOR UPDATE',
                [':id' => $request->cur_uuid]
            )->one();
            if (!$locked || $locked->status !== CandidateUpdateRequest::STATUS_PENDING) {
                $transaction->rollBack();

                return null;
            }

            $columns = [
                'staging_revision' => new \yii\db\Expression('[[staging_revision]] + 1'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $previousKey = $side === 'front' ? (string) $locked->staged_front_key : (string) $locked->staged_back_key;
            if ($side === 'front') {
                $columns['staged_front_key'] = $key;
                $columns['staged_front_source_sha256'] = $hash;
            } else {
                $columns['staged_back_key'] = $key;
                $columns['staged_back_source_sha256'] = $hash;
            }

            $updated = Yii::$app->db->createCommand()->update(
                '{{%candidate_update_request}}',
                $columns,
                [
                    'cur_uuid' => $locked->cur_uuid,
                    'status' => CandidateUpdateRequest::STATUS_PENDING,
                    'staging_revision' => (int) $locked->staging_revision,
                ]
            )->execute();
            if ((int) $updated !== 1) {
                $transaction->rollBack();

                return null;
            }

            $locked->refresh();
            $transaction->commit();
            if ($previousKey !== '' && $previousKey !== (string) $key) {
                self::releaseStagingObject($previousKey);
            }

            return $locked;
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            Yii::error('Civil staging revision update failed.', __METHOD__);

            return null;
        }
    }

    /**
     * Commit one staged Civil ID package.
     * Returns null only when no request is pending and the client did not name one,
     * so the existing no-request save can run.
     * A pending request without a matching id and staging revision is rejected.
     *
     * @param Candidate $candidate
     * @param string $civilId
     * @param string $expiryDate Y-m-d
     * @param string|null $requestId
     * @param int|string|null $stagingRevision
     * @return array|null
     */
    public static function commitCivil(Candidate $candidate, $civilId, $expiryDate, $requestId = null, $stagingRevision = null)
    {
        $pending = self::pending($candidate->candidate_id, CandidateUpdateRequest::TYPE_CIVIL_ID);
        $hasToken = is_string($requestId) && $requestId !== '' && $stagingRevision !== null && $stagingRevision !== '';
        if (!$pending && !$hasToken) {
            return null;
        }
        if (!$hasToken || !$pending || !self::revisionMatches($pending, $requestId, $stagingRevision)) {
            return self::staleCivilResponse();
        }

        if ($pending->staged_front_key === null || $pending->staged_front_key === ''
            || $pending->staged_back_key === null || $pending->staged_back_key === ''
        ) {
            return self::errorResponse(Yii::t('candidate', 'Civil ID is required.'));
        }

        if (!self::uploadHashExists($pending, (string) $pending->staged_front_source_sha256)
            || !self::uploadHashExists($pending, (string) $pending->staged_back_source_sha256)
        ) {
            return self::errorResponse(Yii::t('candidate', 'This file was uploaded before the request. Please choose the photo again.'));
        }

        $frontBasename = basename($pending->staged_front_key);
        $backBasename = basename($pending->staged_back_key);
        if (self::hashKey($frontBasename) === (string) $pending->baseline_front_sha256
            || self::hashKey($backBasename) === (string) $pending->baseline_back_sha256
        ) {
            return self::errorResponse(Yii::t('candidate', 'This file was uploaded before the request. Please choose the photo again.'));
        }

        if (strtotime($expiryDate) < strtotime(date('Y-m-d'))) {
            return self::errorResponse(Yii::t('candidate', 'Candidate have expired civil id.'));
        }

        $duplicate = Candidate::find()
            ->andWhere(['!=', 'candidate_id', $candidate->candidate_id])
            ->andWhere(['candidate_civil_id' => $civilId, 'deleted' => 0])
            ->exists();
        if ($duplicate) {
            return self::errorResponse(Yii::t('app', 'Civil ID number already in use with other account!'));
        }

        $frontKey = $pending->staged_front_key;
        $backKey = $pending->staged_back_key;
        $frontPermanent = Candidate::normalizeCivilIdPermanentS3Key($frontBasename);
        $backPermanent = Candidate::normalizeCivilIdPermanentS3Key($backBasename);
        $bucket = Yii::$app->resourceManager->bucket;

        // Leave a copied object in place if the database commit fails. The permanent
        // name is the uploaded basename, so deleting it can remove a file that a
        // concurrent successful submission already saved under the same name.
        try {
            Yii::$app->resourceManager->copy($frontKey, $frontPermanent, $bucket);
            Yii::$app->resourceManager->copy($backKey, $backPermanent, $bucket);
        } catch (\Throwable $e) {
            Yii::error('Permanent civil copy failed.', __METHOD__);

            return self::errorResponse(Yii::t('app', 'file not available to save.'));
        }

        $oldFront = $candidate->candidate_civil_photo_front;
        $oldBack = $candidate->candidate_civil_photo_back;
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $locked = CandidateUpdateRequest::findBySql(
                'SELECT * FROM {{%candidate_update_request}} WHERE cur_uuid = :id FOR UPDATE',
                [':id' => $pending->cur_uuid]
            )->one();
            if (!$locked || !self::revisionMatches($locked, $requestId, $stagingRevision)
                || $locked->staged_front_key !== $frontKey
                || $locked->staged_back_key !== $backKey
            ) {
                $transaction->rollBack();

                return self::staleCivilResponse();
            }

            $candidate->scenario = 'commitRequestedCivilId';
            $candidate->candidate_civil_photo_front = $frontBasename;
            $candidate->candidate_civil_photo_back = $backBasename;
            $candidate->candidate_civil_id = $civilId;
            $candidate->candidate_civil_expiry_date = $expiryDate;
            $candidate->candidate_civil_need_verification = 1;
            if ($candidate->save() !== true) {
                $transaction->rollBack();
                $errors = $candidate->getFirstErrors();

                return self::errorResponse($errors
                    ? (string) reset($errors)
                    : Yii::t('candidate', 'Could not update civil id and expiry date.'));
            }
            if (!self::markSubmitted($locked)) {
                $transaction->rollBack();

                return self::errorResponse(Yii::t('candidate', 'Could not update civil id and expiry date.'));
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            Yii::error('Civil update-request commit failed.', __METHOD__);

            return self::errorResponse(Yii::t('candidate', 'Could not update civil id and expiry date.'));
        }

        self::deleteReplacedObject($oldFront, $frontPermanent);
        self::deleteReplacedObject($oldBack, $backPermanent);
        self::releaseStagingObject($frontKey);
        self::releaseStagingObject($backKey);

        return [
            'operation' => 'success',
            'candidate_civil_id' => $candidate->candidate_civil_id,
            'candidate_civil_expiry_date' => $candidate->candidate_civil_expiry_date,
            'candidate_civil_photo_front' => $candidate->candidate_civil_photo_front,
            'candidate_civil_photo_back' => $candidate->candidate_civil_photo_back,
            'is_incomplete_profile' => $candidate->is_incomplete_profile,
            'candidate_pending_profile' => $candidate->candidate_pending_profile,
            'message' => Yii::t('candidate', 'Civil ID And Expiry Date Updated Successfully'),
        ];
    }

    /**
     * @param string $type
     * @return void
     */
    public static function assertType($type)
    {
        if ($type !== CandidateUpdateRequest::TYPE_PROFILE_PHOTO && $type !== CandidateUpdateRequest::TYPE_CIVIL_ID) {
            throw new \InvalidArgumentException('Invalid request type.');
        }
    }

    /**
     * @param mixed $key
     * @return string
     */
    public static function hashKey($key)
    {
        return hash('sha256', self::normalizeKey($key));
    }

    /**
     * @param mixed $key
     * @return string
     */
    public static function normalizeKey($key)
    {
        return ltrim(trim((string) $key), '/');
    }

    /**
     * @param int $candidateId
     * @param string $type
     * @return string
     */
    public static function openSlot($candidateId, $type)
    {
        return ((int) $candidateId) . ':' . $type;
    }

    /**
     * @param int $candidateId
     * @param string $type
     * @return CandidateUpdateRequest|null
     */
    public static function pending($candidateId, $type)
    {
        return CandidateUpdateRequest::find()
            ->where([
                'candidate_id' => (int) $candidateId,
                'request_type' => $type,
                'status' => CandidateUpdateRequest::STATUS_PENDING,
            ])
            ->one();
    }

    /**
     * @param string $purpose
     * @return string|null
     */
    private static function typeForPurpose($purpose)
    {
        if ($purpose === 'profile_photo') {
            return CandidateUpdateRequest::TYPE_PROFILE_PHOTO;
        }
        if ($purpose === 'civil_id') {
            return CandidateUpdateRequest::TYPE_CIVIL_ID;
        }

        return null;
    }

    /**
     * @param CandidateUpdateRequest $request
     * @param string $hash
     * @return bool
     */
    private static function uploadHashExists(CandidateUpdateRequest $request, $hash)
    {
        if ($hash === '') {
            return false;
        }

        return CandidateUpdateRequestUpload::find()
            ->where(['cur_uuid' => $request->cur_uuid, 'key_sha256' => $hash])
            ->exists();
    }

    /**
     * @param CandidateUpdateRequest $request
     * @return bool
     */
    private static function markSubmitted(CandidateUpdateRequest $request)
    {
        $request->status = CandidateUpdateRequest::STATUS_SUBMITTED;
        $request->submitted_at = date('Y-m-d H:i:s');
        $request->open_slot = null;

        return $request->save(false) === true;
    }

    /**
     * @param CandidateUpdateRequest $request
     * @param string $requestId
     * @param int|string $stagingRevision
     * @return bool
     */
    private static function revisionMatches(CandidateUpdateRequest $request, $requestId, $stagingRevision)
    {
        return $request->status === CandidateUpdateRequest::STATUS_PENDING
            && $request->cur_uuid === (string) $requestId
            && (int) $request->staging_revision === (int) $stagingRevision;
    }

    /**
     * @return array
     */
    private static function staleCivilResponse()
    {
        return self::errorResponse(Yii::t('candidate', 'This Civil ID update is out of date. Refresh the page and submit it again.'));
    }

    /**
     * @param string $message
     * @return array
     */
    private static function errorResponse($message)
    {
        return [
            'operation' => 'error',
            'message' => $message,
        ];
    }

    /**
     * Match Candidate::validateCivilID: a date found on the front is the expiry.
     * A date on the back is used only when the front did not provide one, and a
     * past date on the back does not replace a valid front expiry.
     *
     * @param CandidateUpdateRequest $request
     * @param string $side
     * @param string $stagedKey
     * @return array{expired?: bool, civil_id?: string, expiry?: string}
     */
    private static function suggestionForSide(CandidateUpdateRequest $request, $side, $stagedKey)
    {
        if ($side === 'back' && $request->staged_front_key) {
            $front = self::readStagedSuggestion($request->staged_front_key);
            if (!empty($front['expiry']) && empty($front['expired'])) {
                $back = self::readStagedSuggestion($stagedKey);
                unset($back['expired'], $back['expiry']);

                return $back;
            }
        }

        return self::readStagedSuggestion($stagedKey);
    }

    /**
     * @param string $stagedKey
     * @return array{expired?: bool, civil_id?: string, expiry?: string}
     */
    private static function readStagedSuggestion($stagedKey)
    {
        if (!Yii::$app->has('idExpiryDateExtractor')) {
            return [];
        }

        try {
            $response = Yii::$app->idExpiryDateExtractor->extractExpiryDate($stagedKey);
        } catch (\Throwable $e) {
            return [];
        }

        if (!is_array($response) || ($response['operation'] ?? '') !== 'success') {
            return [];
        }

        $suggestion = [];
        $matches = isset($response['matches']) && is_array($response['matches']) ? $response['matches'] : [];
        if (count($matches) > 0) {
            $date = (string) array_pop($matches);
            $dateTime = strtotime(str_replace('/', '-', $date));
            if ($date !== '' && $dateTime < time()) {
                return ['expired' => true];
            }
            if ($dateTime > 0) {
                $suggestion['expiry'] = date('Y-m-d', $dateTime);
            }
        }

        if (!empty($response['ids'][0])) {
            $suggestion['civil_id'] = (string) $response['ids'][0];
        }

        return $suggestion;
    }

    /**
     * @param string $storedKey
     * @return string
     */
    private static function relativePhotoKey($storedKey)
    {
        $normalized = self::normalizeKey($storedKey);
        if (strpos($normalized, 'photos/') === 0) {
            return substr($normalized, strlen('photos/'));
        }

        return $normalized;
    }

    /**
     * Delete one staging object when no pending request still stores that key.
     * A retry can upload the same filename, which reuses the key, so a referenced
     * object stays. This never deletes a permanent photo. A storage failure is ignored.
     *
     * @param string|null $key
     * @return void
     */
    private static function releaseStagingObject($key)
    {
        $key = self::normalizeKey($key);
        if ($key === '' || strpos($key, 'photos/staging/') !== 0) {
            return;
        }
        if (self::pendingRequestUsesStagingKey($key)) {
            return;
        }

        try {
            Yii::$app->resourceManager->delete($key);
        } catch (\Throwable $e) {
            Yii::warning('Could not delete a staged civil object.', __METHOD__);
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    private static function pendingRequestUsesStagingKey($key)
    {
        return CandidateUpdateRequest::find()
            ->where(['status' => CandidateUpdateRequest::STATUS_PENDING])
            ->andWhere(['or',
                ['staged_front_key' => $key],
                ['staged_back_key' => $key],
            ])
            ->exists();
    }

    /**
     * @param string|null $oldStored
     * @param string $newPermanentKey
     * @return void
     */
    private static function deleteReplacedObject($oldStored, $newPermanentKey)
    {
        $oldKey = Candidate::normalizeCivilIdPermanentS3Key($oldStored);
        if ($oldKey === '' || $oldKey === $newPermanentKey) {
            return;
        }

        try {
            Yii::$app->resourceManager->delete($oldKey);
        } catch (\Throwable $e) {
            Yii::warning('Could not delete the previous civil object.', __METHOD__);
        }
    }
}
