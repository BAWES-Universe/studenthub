<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "ticket_attachment".
 *
 * @property string $ticket_attachment_uuid
 * @property string|null $ticket_uuid
 * @property string|null $attachment_uuid
 *
 * @property Ticket $ticketUu
 * @property Attachment $attachmentUu
 */
class TicketAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ticket_attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ticket_attachment_uuid', 'ticket_uuid', 'attachment_uuid'], 'string', 'max' => 60],
            [['ticket_attachment_uuid'], 'unique'],
            [['ticket_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Ticket::className(), 'targetAttribute' => ['ticket_uuid' => 'ticket_uuid']],
            [['attachment_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_uuid' => 'attachment_uuid']],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'ticket_attachment_uuid',
                ],
                'value' => function () {
                    if (!$this->ticket_attachment_uuid)
                        $this->ticket_attachment_uuid = 'ticket_attachment_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar ();

                    return $this->ticket_attachment_uuid;
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
            'ticket_attachment_uuid' => Yii::t('app', 'Ticket Attachment Uuid'),
            'ticket_uuid' => Yii::t('app', 'Ticket Uuid'),
            'attachment_uuid' => Yii::t('app', 'Attachment Uuid'),
        ];
    }

    /**
     * Gets query for [[TicketUu]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTicket($modelClass = "\common\models\Ticket")
    {
        return $this->hasOne($modelClass::className(), ['ticket_uuid' => 'ticket_uuid']);
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
}
