<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "ticket_comment_attachment".
 *
 * @property string|null $ticket_comment_attachment_uuid
 * @property string $ticket_comment_uuid
 * @property string|null $attachment_uuid
 *
 * @property Attachment $attachmentUu
 * @property TicketComment $ticketCommentUu
 */
class TicketCommentAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ticket_comment_attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ticket_comment_attachment_uuid', 'ticket_comment_uuid', 'attachment_uuid'], 'string', 'max' => 60],
            [['ticket_comment_uuid'], 'unique'],
            [['attachment_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_uuid' => 'attachment_uuid']],
            [['ticket_comment_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => TicketComment::className(), 'targetAttribute' => ['ticket_comment_uuid' => 'ticket_comment_uuid']],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'ticket_comment_attachment_uuid',
                ],
                'value' => function () {
                    if (!$this->ticket_comment_attachment_uuid)
                        $this->ticket_comment_attachment_uuid = 'tca_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar ();

                    return $this->ticket_comment_attachment_uuid;
                }
            ],
        ];
    }

    /**
     * @return string[]
     */
    public function extraFields()
    {
        return [
            'attachment'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'ticket_comment_attachment_uuid' => Yii::t('app', 'Ticket Comment Attachment Uuid'),
            'ticket_comment_uuid' => Yii::t('app', 'Ticket Comment Uuid'),
            'attachment_uuid' => Yii::t('app', 'Attachment Uuid'),
        ];
    }

    /**
     * Gets query for [[AttachmentUu]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment($modelClass = "\common\models\Attachment")
    {
        return $this->hasOne($modelClass::className(), ['attachment_uuid' => 'attachment_uuid']);
    }

    /**
     * Gets query for [[TicketCommentUu]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTicketComment($modelClass = "\common\models\TicketComment")
    {
        return $this->hasOne($modelClass::className(), ['ticket_comment_uuid' => 'ticket_comment_uuid']);
    }
}
