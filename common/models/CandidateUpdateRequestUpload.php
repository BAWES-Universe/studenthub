<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Hash of a temp object key presigned after a request was opened.
 *
 * @property int $id
 * @property string $cur_uuid
 * @property string $key_sha256
 * @property string $created_at
 */
class CandidateUpdateRequestUpload extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%candidate_update_request_upload}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['cur_uuid', 'key_sha256'], 'required'],
            [['cur_uuid'], 'string', 'max' => 60],
            [['key_sha256'], 'string', 'max' => 64],
        ];
    }
}
