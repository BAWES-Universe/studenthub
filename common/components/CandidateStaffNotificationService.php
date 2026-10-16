<?php

namespace common\components;

use common\models\Candidate;
use common\models\CandidateNotification;
use Yii;

/**
 * Staff-sent Activity notifications.
 *
 * Photo and Civil ID reminders, plus one plain-text message.
 * This class does not change documents and does not send email, SMS, or push.
 */
class CandidateStaffNotificationService
{
    /**
     * @param Candidate $candidate
     * @param int $staffId
     * @param string $type profile_photo or civil_id
     * @param string $sendKey
     * @return array{operation: string, message?: string, already_sent?: bool, notification?: CandidateNotification}
     */
    public static function sendDocumentRequest(Candidate $candidate, $staffId, $type, $sendKey)
    {
        $document = self::documentRequest($type);
        if ($document === null) {
            return [
                'operation' => 'error',
                'message' => 'Invalid request type.',
            ];
        }

        return self::send(
            $candidate,
            $staffId,
            $document['type'],
            $document['message'],
            $sendKey
        );
    }

    /**
     * The same send key returns the saved row instead of inserting another one.
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

        return self::send(
            $candidate,
            $staffId,
            CandidateNotification::TYPE_STAFF_MESSAGE,
            $message,
            $sendKey
        );
    }

    /**
     * @param string $type
     * @return array{type: int, message: string}|null
     */
    private static function documentRequest($type)
    {
        if ($type === 'profile_photo') {
            return [
                'type' => CandidateNotification::TYPE_PROFILE_PHOTO_REQUEST,
                'message' => 'Please upload a new profile photo.',
            ];
        }
        if ($type === 'civil_id') {
            return [
                'type' => CandidateNotification::TYPE_CIVIL_ID_REQUEST,
                'message' => 'Please update your Civil ID.',
            ];
        }

        return null;
    }

    /**
     * @param Candidate $candidate
     * @param int $staffId
     * @param int $type
     * @param string $message
     * @param string $sendKey
     * @return array{operation: string, message?: string, already_sent?: bool, notification?: CandidateNotification}
     */
    private static function send(Candidate $candidate, $staffId, $type, $message, $sendKey)
    {
        $sendKey = self::normalizeSendKey($sendKey);
        if ($sendKey === null) {
            return [
                'operation' => 'error',
                'message' => 'Could not send the notification. Please try again.',
            ];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $existing = CandidateNotification::findOne(['cn_uuid' => $sendKey]);
            if ($existing) {
                $transaction->commit();

                return self::existingResult($existing, $candidate, $type);
            }

            $notification = new CandidateNotification();
            $notification->cn_uuid = $sendKey;
            $notification->candidate_id = (int) $candidate->candidate_id;
            $notification->staff_id = (int) $staffId;
            $notification->type = $type;
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
            $existing = CandidateNotification::findOne(['cn_uuid' => $sendKey]);
            if ($existing) {
                return self::existingResult($existing, $candidate, $type);
            }
            Yii::error('Staff candidate notification failed.', __METHOD__);

            return [
                'operation' => 'error',
                'message' => 'Could not send the notification. Please try again.',
            ];
        }
    }

    /**
     * @param string $sendKey
     * @return string|null
     */
    private static function normalizeSendKey($sendKey)
    {
        if (!is_string($sendKey) || !preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/i', $sendKey)) {
            return null;
        }

        return strtolower($sendKey);
    }

    /**
     * @param CandidateNotification $existing
     * @param Candidate $candidate
     * @param int $type
     * @return array
     */
    private static function existingResult(CandidateNotification $existing, Candidate $candidate, $type)
    {
        if ((int) $existing->candidate_id !== (int) $candidate->candidate_id
            || (int) $existing->type !== (int) $type
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
}
