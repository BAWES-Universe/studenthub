<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "ticket_comment".
 *
 * @property string $ticket_comment_uuid
 * @property string|null $ticket_uuid
 * @property int|null $candidate_id
 * @property int|null $staff_id
 * @property string|null $ticket_comment_detail
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Candidate $candidate
 * @property Staff $staff
 * @property Ticket $ticket
 * @property TicketCommentAttachment $ticketCommentAttachment
 */
class TicketComment extends \yii\db\ActiveRecord
{
    public $attachments = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ticket_comment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['candidate_id', 'staff_id'], 'integer'],
            [['ticket_comment_detail'], 'string'],
            ['ticket_comment_detail', 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['ticket_comment_uuid', 'ticket_uuid'], 'string', 'max' => 60],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::class, 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['staff_id' => 'staff_id']],
            [['ticket_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Ticket::class, 'targetAttribute' => ['ticket_uuid' => 'ticket_uuid']],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'ticket_comment_uuid',
                ],
                'value' => function () {
                    if (!$this->ticket_comment_uuid)
                        $this->ticket_comment_uuid = 'ticket_comment_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar ();

                    return $this->ticket_comment_uuid;
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
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'ticket_comment_uuid' => Yii::t('app', 'Ticket Comment Uuid'),
            'ticket_uuid' => Yii::t('app', 'Ticket Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'staff_id' => Yii::t('app', 'Staff ID'),
            'ticket_comment_detail' => Yii::t('app', 'Ticket Comment Detail'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @return void
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $this->sendTicketCommentedMail();

        $this->_moveAttachments();
    }

    /**
     * notify staff for new ticket
     */
    public function sendTicketCommentedMail()
    {
        if($this->staff_id)  //notify store candidates if comment from staff
        {
            $toEmails = [$this->ticket->candidate->candidate_email]; //store owner from support candidate

            //$fromName = $this->staff->staff_name;

            //$fromEmail = $this->staff->staff_email;
        }
        else if($this->candidate_id)
        {
            //$fromName = $this->candidate->candidate_name;

            //$fromEmail = $this->candidate->candidate_email;

            //if assigned > notify assigned staff

            if ($this->ticket->staff_id)
            {
                $toEmails = [$this->ticket->staff->staff_email];
            }
            else // else all staff
            {
                $staffs = Staff::find()
                    ->filterActive()
                    ->all();

                $toEmails = ArrayHelper::getColumn ($staffs, 'staff_email');
            }
        }

        $ml = new MailLog();
        $ml->to = $toEmails[0];
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'New comment on ticket #' . $this->ticket_uuid;
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        \Yii::$app->mailer->htmlLayout = "layouts/text";

        $mailer = \Yii::$app->mailer->compose ([
                'html' => 'candidate/ticket-commented-html',
                'text' => 'candidate/ticket-commented-text',
            ], [
                'model' => $this
            ])
            ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->name])
            //->setFrom ([Yii::$app->params['supportEmail']])
            //->setReplyTo($this->ticket_uuid . Yii::$app->params['remailerDomain']) //inbound email address
            ->setTo ($toEmails)
            ->setCc (Yii::$app->params['supportEmail'])
            ->setSubject ('New comment on ticket #' . $this->ticket_uuid);

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

                $file_s3_path = 'comment-attachments/' . $output . '.' . $extension;

                Yii::$app->resourceManager->copy($value, $file_s3_path, $sourceBucket);

                $attachment = new Attachment();
                $attachment->file_path = $file_s3_path;
                $attachment->save();

                $ta = new TicketCommentAttachment();
                $ta->ticket_comment_uuid = $this->ticket_comment_uuid;
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
     * Remove reply text and contents that should have been invisible from email text
     * It returns the adjusted mail content
     * @param string $email
     * @return string
     */
    public static function extractMessageFromEmail($emailContents) {
        // Parse the reply via GitHub's library
        $emailContents = \EmailReplyParser\EmailReplyParser::parseReply($emailContents);

        // Fix for regular mail clients
        $emailContents = preg_replace('~On(.*?)wrote:(.*?)$~si', '', $emailContents);

        // Fix for Outlook mail client replies
        $emailContents = preg_replace('~From:(.*?)Sent:(.*?)To:(.*?)Subject:(.*?)$~si', '', $emailContents);

        return $emailContents;
    }

    /**
     * Extracts email from a string
     * Example Input: Khalid Al-Mutawa <dwa-dw@md.dwa.g.website.io>
     * Returns "dwa-dw@md.dwa.g.website.io"
     * @param string $email
     * @return string
     */
    public static function extractEmailFromString($email) {
        $pattern = "/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i";
        preg_match_all($pattern, $email, $matches);

        return $matches[0][0];
    }

    public function extraFields()
    {
        return [
            'candidate',
            'staff',
            'attachments',
            'ticketCommentAttachments'
        ];
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
     * Gets query for [[TicketUu]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTicket($modelClass = "\common\models\Ticket")
    {
        return $this->hasOne($modelClass::className(), ['ticket_uuid' => 'ticket_uuid']);
    }

    /**
     * Gets query for [[TicketCommentAttachment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTicketCommentAttachments($modelClass = "\common\models\TicketCommentAttachment")
    {
        return $this->hasMany($modelClass::className(), ['ticket_comment_uuid' => 'ticket_comment_uuid']);
    }

    /**
     * Gets query for [[TicketCommentAttachment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAttachments($modelClass = "\common\models\Attachment")
    {
        return $this->hasMany($modelClass::className(), ['attachment_uuid' => 'attachment_uuid'])
            ->via('ticketCommentAttachments');
    }
}
