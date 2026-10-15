<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * One Staff request that a candidate upload a new profile photo or Civil ID.
 *
 * @property string $cur_uuid
 * @property int $candidate_id
 * @property string $request_type
 * @property string $status
 * @property int $staff_id
 * @property string $requested_at
 * @property string|null $submitted_at
 * @property string|null $baseline_photo_sha256
 * @property string|null $baseline_front_sha256
 * @property string|null $baseline_back_sha256
 * @property string|null $staged_front_key
 * @property string|null $staged_back_key
 * @property string|null $staged_front_source_sha256
 * @property string|null $staged_back_source_sha256
 * @property int $staging_revision
 * @property string|null $open_slot
 * @property string $created_at
 * @property string $updated_at
 */
class CandidateUpdateRequest extends ActiveRecord
{
    const TYPE_PROFILE_PHOTO = 'profile_photo';
    const TYPE_CIVIL_ID = 'civil_id';
    const STATUS_PENDING = 'pending';
    const STATUS_SUBMITTED = 'submitted';

    public static function tableName()
    {
        return '{{%candidate_update_request}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'cur_uuid',
                ],
                'value' => function () {
                    if (!$this->cur_uuid) {
                        $this->cur_uuid = 'cur_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();
                    }

                    return $this->cur_uuid;
                },
            ],
        ];
    }

    public function rules()
    {
        return [
            [['candidate_id', 'request_type', 'status', 'staff_id', 'requested_at'], 'required'],
            [['candidate_id', 'staff_id'], 'integer'],
            [['requested_at', 'submitted_at', 'created_at', 'updated_at'], 'safe'],
            [['cur_uuid'], 'string', 'max' => 60],
            [['request_type'], 'string', 'max' => 32],
            [['status'], 'string', 'max' => 16],
            [['open_slot'], 'string', 'max' => 64],
            [['baseline_photo_sha256', 'baseline_front_sha256', 'baseline_back_sha256', 'staged_front_source_sha256', 'staged_back_source_sha256'], 'string', 'max' => 64],
            [['staged_front_key', 'staged_back_key'], 'string', 'max' => 255],
            [['request_type'], 'in', 'range' => [self::TYPE_PROFILE_PHOTO, self::TYPE_CIVIL_ID]],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_SUBMITTED]],
        ];
    }

    /**
     * Staff-facing status. Omits staged keys and civil values.
     *
     * @return array
     */
    public function toStaffArray()
    {
        $staff = Staff::findOne($this->staff_id);

        return [
            'id' => $this->cur_uuid,
            'type' => $this->request_type,
            'status' => $this->status,
            'requested_at' => $this->requested_at,
            'submitted_at' => $this->submitted_at,
            'requested_by' => [
                'staff_id' => (int) $this->staff_id,
                'staff_name' => $staff ? $staff->staff_name : null,
            ],
        ];
    }
}
