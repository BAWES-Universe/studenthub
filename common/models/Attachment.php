<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "attachment".
 *
 * @property string $attachment_uuid
 * @property string|null $file_path
 *
 * @property TicketAttachment[] $ticketAttachments
 * @property TicketCommentAttachment[] $ticketCommentAttachments
 */
class Attachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['attachment_uuid'], 'string', 'max' => 60],
            [['file_path'], 'string', 'max' => 250],
        ];
    }

    /**
     *
     * @return type
     */
    public function behaviors()
    {
        return [
            [
                'class' => AttributeBehavior::className (),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'attachment_uuid',
                ],
                'value' => function () {
                    if (!$this->attachment_uuid)
                        $this->attachment_uuid = 'attachment_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar ();

                    return $this->attachment_uuid;
                }
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'attachment_uuid' => Yii::t('app', 'Attachment Uuid'),
            'file_path' => Yii::t('app', 'File Path'),
        ];
    }

    /**
     * Gets query for [[TicketAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTicketAttachments($modelClass = "\common\models\TicketAttachment")
    {
        return $this->hasMany($modelClass::className(), ['attachment_uuid' => 'attachment_uuid']);
    }

    /**
     * Gets query for [[TicketCommentAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTicketCommentAttachments($modelClass = "\common\models\TicketCommentAttachment")
    {
        return $this->hasMany($modelClass::className(), ['attachment_uuid' => 'attachment_uuid']);
    }
}
