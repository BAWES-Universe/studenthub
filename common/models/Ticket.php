<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "ticket".
 *
 * @property string $ticket_uuid
 * @property int|null $candidate_id
 * @property int|null $staff_id
 * @property string|null $ticket_detail
 * @property int|null $ticket_status
 * @property string|null $ticket_started_at
 * @property string|null $ticket_completed_at
 * @property int|null $response_time
 * @property int|null $resolution_time
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Candidate $candidate
 * @property Staff $staff
 * @property TicketAttachment[] $ticketAttachments
 * @property TicketComment[] $ticketComments
 */
class Ticket extends \yii\db\ActiveRecord
{
    public $attachments = [];

    const STATUS_PENDING = 0;
    const STATUS_COMPLETED = 10;
    const STATUS_IN_PROGRESS = 1;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ticket';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['candidate_id', 'staff_id', 'ticket_status', 'response_time', 'resolution_time'], 'integer'],
            [['ticket_detail'], 'string'],
            [['created_at', 'updated_at', 'ticket_started_at', 'ticket_completed_at'], 'safe'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::class, 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['staff_id' => 'staff_id']],
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
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'ticket_uuid',
                ],
                'value' => function () {
                    if (!$this->ticket_uuid)
                        $this->ticket_uuid = 'ticket_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar ();

                    return $this->ticket_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @return string[]
     */
    public function extraFields()
    {
        return [
            'attachments',
            'candidate',
            'staff',
            'ticketComments',
            'ticketAttachments',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'ticket_uuid' => Yii::t('app', 'Ticket Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'staff_id' => Yii::t('app', 'Staff ID'),
            'ticket_detail' => Yii::t('app', 'Ticket Detail'),
            'ticket_status' => Yii::t('app', 'Ticket Status'),
            'ticket_started_at' => Yii::t('app', 'Started At'),
            'ticket_completed_at' => Yii::t('app', 'Completed At'),
            'response_time' => Yii::t('app', 'Response time'),
            'resolution_time' => Yii::t('app', 'Resolution time'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        return parent::beforeSave($insert);
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @return void
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        //notify staff

        if($insert) {
            $this->sendTicketGeneratedMail();

                Yii::$app->eventManager->track('Ticket Added', [
                    'ticket_id' => $this->ticket_uuid,
                    'candidate_id' => $this->candidate_id,
                    'ticket_description' => $this->ticket_detail,
                    'number_of_attachments' => sizeof($this->attachments)
                ],
                    null,
                    $this->candidate_id
                );
        }

        if(isset($changedAttributes['ticket_status'])) {

            if($this->ticket_status == self::STATUS_COMPLETED)
            {
                $this->ticket_completed_at = new Expression("NOW()");

                $this->resolution_time = $this->ticket_started_at?time() - strtotime($this->ticket_started_at): 0;

                    Yii::$app->eventManager->track('Ticket Resolved', [
                        'ticket_id' => $this->ticket_uuid,
                        'candidate_id' => $this->candidate_id,
                        'ticket_description' => $this->ticket_detail,
                        'number_of_attachments' => $this->getAttachments()->count()
                    ],
                        null,
                        $this->staff_id
                    );
            }
            else if($this->ticket_status == self::STATUS_IN_PROGRESS)
            {
                $this->ticket_started_at = new Expression("NOW()");
                $this->response_time = time() - strtotime($this->created_at);

                    Yii::$app->eventManager->track('Ticket Started', [
                        'ticket_id' => $this->ticket_uuid,
                        'candidate_id' => $this->candidate_id,
                        'ticket_description' => $this->ticket_detail,
                        'number_of_attachments' => $this->getAttachments()->count()
                    ],
                        null,
                        $this->staff_id
                    );
            }

            self::updateAll([
                'ticket_completed_at' => $this->ticket_completed_at,
                'ticket_started_at' => $this->ticket_started_at,
                'resolution_time' => $this->resolution_time,
                'response_time'=> $this->response_time
            ], [
                'ticket_uuid' => $this->ticket_uuid
            ]);
        }

        $this->_moveAttachments();
    }

    /**
     * notify staff for new ticket
     */
    public function sendTicketAssignedMail() {

        $ml = new MailLog();
        $ml->to = $this->staff->staff_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'Ticket assigned for ' . $this->candidate->candidate_name;
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        \Yii::$app->mailer->htmlLayout = "layouts/text";

        $mailer = \Yii::$app->mailer->compose ([
            'html' => 'candidate/ticket-assigned-html',
            'text' => 'candidate/ticket-assigned-text',
        ], [
            'model' => $this
        ])
            ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->name])
            //->setFrom ([Yii::$app->params['supportEmail']])
            ->setTo ($this->staff->staff_email)
            ->setReplyTo(\Yii::$app->params['supportEmail'])
            ->setSubject ('Ticket assigned for ' . $this->candidate->candidate_name);

        if(isset(\Yii::$app->params['elasticMailIpPool']) && \Yii::$app->params['elasticMailIpPool'])
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);

        $mailer->send ();
    }

    /**
     * notify staff for new ticket
     */
    public function sendTicketGeneratedMail() {

        $staffs = Staff::find()
            ->active()
            ->filterNotificationEnabled()
            ->all();

        $staffEmails = ArrayHelper::getColumn ($staffs, 'staff_email');

        $ml = new MailLog();
        $ml->to = Yii::$app->params['supportEmail'];
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'New ticket generated for ' . $this->candidate->candidate_name;
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        \Yii::$app->mailer->htmlLayout = "layouts/text";

        $mailer = \Yii::$app->mailer->compose ([
                'html' => 'candidate/ticket-generated-html',
                'text' => 'candidate/ticket-generated-text',
            ], [
                'model' => $this
            ])
            ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->name])
            //->setFrom ([Yii::$app->params['supportEmail']])
            ->setTo (Yii::$app->params['supportEmail'])
            ->setCc ($staffEmails)
            ->setReplyTo(\Yii::$app->params['supportEmail'])
            ->setSubject ('New ticket generated for ' . $this->candidate->candidate_name);

        if(isset(\Yii::$app->params['elasticMailIpPool']) && \Yii::$app->params['elasticMailIpPool'])
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);

        $mailer->send ();
    }

    /**
     * move attachments from temp s3 bucket
     * @return type
     */
    public function _moveAttachments() {

        $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;

        foreach ($this->attachments as $value) {

            $output = Yii::$app->security->generateRandomString();

            //$source = Yii::$app->temporaryBucketResourceManager->bucket . '/' . $value;

            try {
    
                $extension = pathinfo($value, PATHINFO_EXTENSION);

                $file_s3_path = 'attachments/' . $output . '.' . $extension;

                Yii::$app->resourceManager->copy($value, $file_s3_path, $sourceBucket);

                $attachment = new Attachment();
                $attachment->file_path = $file_s3_path;
                $attachment->save();

                $ta = new TicketAttachment();
                $ta->ticket_uuid = $this->ticket_uuid;
                $ta->attachment_uuid = $attachment->attachment_uuid;
                $ta->save();

            } catch (\Aws\S3\Exception\S3Exception $e) {

                Yii::error($e->getMessage(), 'candidate');

                //$this->addError('attachments', Yii::t('app', 'Please try again.'));

                return false;

            } catch (\Exception $e) {

                Yii::error($e->getMessage(), 'candidate');

                //$this->addError('candidate_video', Yii::t('app', 'Video not available to save.'));

                return false;
            }
        }
    }

    /**
     * Gets query for [[Candidate]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\common\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * Gets query for [[Staff]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStaff($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * Gets query for [[TicketAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTicketAttachments($modelClass = "\common\models\TicketAttachment")
    {
        return $this->hasMany($modelClass::className(), ['ticket_uuid' => 'ticket_uuid']);
    }

    /**
     * Gets query for [[TicketCommentAttachment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAttachments($modelClass = "\common\models\Attachment")
    {
        return $this->hasMany($modelClass::className(), ['attachment_uuid' => 'attachment_uuid'])
            ->via('ticketAttachments');
    }

    /**
     * Gets query for [[TicketComments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTicketComments($modelClass = "\common\models\TicketComment")
    {
        return $this->hasMany($modelClass::className(), ['ticket_uuid' => 'ticket_uuid']);
    }

    /**
     * @return query\TicketQuery
     */
    public static function find()
    {
        return new query\TicketQuery(get_called_class());
    }
}
