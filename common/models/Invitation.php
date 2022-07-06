<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use Segment\Segment;

/**
 * This is the model class for table "invitation".
 *
 * @property string $invitation_uuid
 * @property int $candidate_id
 * @property string $request_uuid
 * @property string $story_uuid
 * @property int $invitation_status 1-Invited , 2-Rejected, 3-Accepted
 * @property string $invitation_app_seen_at
 * @property string $invitation_email_seen_at
 * @property int $invitation_created_by_staff
 * @property int $invitation_updated_by_staff
 * @property int $invitation_created_by_company
 * @property int $invitation_updated_by_company
 * @property string $invitation_created_at
 * @property string $invitation_updated_at
 *
 * @property Candidate $candidate
 * @property Company $invitationCreatedByCompany
 * @property Staff $invitationCreatedByStaff
 * @property Company $invitationUpdatedByCompany
 * @property Staff $invitationUpdatedByStaff
 * @property Request $requestUu
 */
class Invitation extends \yii\db\ActiveRecord
{
    const STATUS_INVITED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_ACCEPTED = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'invitation';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['candidate_id', 'invitation_status', 'invitation_created_by_staff', 'invitation_updated_by_staff', 'invitation_created_by_company', 'invitation_updated_by_company'], 'integer'],
            [['request_uuid', 'candidate_id'], 'required'],
            [['request_uuid'], 'validateDuplicateRequest'],
            [['invitation_email_seen_at', 'invitation_app_seen_at', 'invitation_created_at', 'invitation_updated_at'], 'safe'],
            [['invitation_uuid', 'request_uuid'], 'string', 'max' => 60],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['invitation_created_by_company'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['invitation_created_by_company' => 'company_id']],
            [['invitation_created_by_staff'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['invitation_created_by_staff' => 'staff_id']],
            [['invitation_updated_by_company'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['invitation_updated_by_company' => 'company_id']],
            [['invitation_updated_by_staff'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['invitation_updated_by_staff' => 'staff_id']],
            [['request_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Request::className(), 'targetAttribute' => ['request_uuid' => 'request_uuid']],
            [['story_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Story::className(), 'targetAttribute' => ['story_uuid' => 'story_uuid']],
        ];
    }

    /**
     * Validate duplicate invitation if one is already exist
     */
    public function validateDuplicateRequest($attribute)
    {
        if(
            $this->candidate_id &&
            $this->request_uuid &&
            $this->invitation_status == self::STATUS_INVITED
        ) {
            $query = self::find()
                ->andWhere([
                    //'invitation_status' => self::STATUS_INVITED,
                    'request_uuid' => $this->request_uuid,
                    'candidate_id' => $this->candidate_id
                ]);

            if ($query->exists()) {
                $this->addError('candidate_id', Yii::t('app', 'Candidate already invited'));
            }
        }
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'invitation_uuid',
                ],
                'value' => function() {
                    if (!$this->invitation_uuid)
                        $this->invitation_uuid = 'invitation_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->invitation_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'invitation_created_at',
                'updatedAtAttribute' => 'invitation_updated_at',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'invitation_created_by_staff',
                'updatedByAttribute' => 'invitation_updated_by_staff',
                'value' => function() {
                    if(isset(Yii::$app->user->identity->staff_id))
                        return Yii::$app->user->identity->staff_id;
                }
            ],
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'invitation_created_by_company',
                'updatedByAttribute' => 'invitation_updated_by_company',
                'value' => function() {
                    if(isset(Yii::$app->user->identity->company_id))
                        return Yii::$app->user->identity->company_id;
                }
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'invitation_uuid' => Yii::t('app', 'Invitation Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'request_uuid' => Yii::t('app', 'Request Uuid'),
            'story_uuid' => Yii::t('app', 'Story Uuid'),
            'invitation_status' => Yii::t('app', 'Invitation Status'),
            'invitation_email_seen_at' => Yii::t('app', 'Invitation Email Seen At'),
            'invitation_app_seen_at' => Yii::t('app', 'Invitation in App Seen At'),
            'invitation_created_by_staff' => Yii::t('app', 'Invitation Created By Staff'),
            'invitation_updated_by_staff' => Yii::t('app', 'Invitation Updated By Staff'),
            'invitation_created_by_company' => Yii::t('app', 'Invitation Created By Company'),
            'invitation_updated_by_company' => Yii::t('app', 'Invitation Updated By Company'),
            'invitation_created_at' => Yii::t('app', 'Invitation Created At'),
            'invitation_updated_at' => Yii::t('app', 'Invitation Updated At'),
        ];
    }

    /**
     * @param bool $insert
     * @return false|void
     */
    public function beforeSave($insert)
    {
        if(!parent::beforeSave($insert)) {
            return false;
        }

        return true;
    }

    /**
     * after object saved
     * @param boolean $insert
     * @param array $changedAttributes
     * @return boolean
     */
    public function afterSave($insert, $changedAttributes) {
        parent::afterSave($insert, $changedAttributes);

        if($insert && $this->candidate_id) {
            $this->sendNotification();
            $this->jobInvitationEmail();
        }

        //update `request_updated_at` field
        $this->request->request_updated_datetime = '';
        $this->request->update(false);
        
        if(YII_ENV == 'prod') {
            if ($insert) {

                if($this->candidate) {
                    $name = $this->candidate->candidate_name ? $this->candidate->candidate_name : $this->candidate->candidate_name_ar;
                } else {
                    $name = null;
                }

                if($this->invitationCreatedByStaff) {
                    $staff = $this->invitationCreatedByStaff->staff_name;
                } else if($this->invitationCreatedByCompany) {
                    $staff = $this->invitationCreatedByCompany->company_name;
                } else {
                    $staff = null;
                }

                Segment::track([
                    'userId' => Yii::$app->user->getId(),
                    'event' => 'Candidate Invited',
                    'properties' => [
                        'candidate' => $name,
                        'staff' => $staff,
                        'candidate_id' => $this->candidate_id,
                        'request_uuid' => $this->request_uuid,
                        'invitation_created_by_staff' => $this->invitation_created_by_staff,
                        'invitation_created_by_company' => $this->invitation_created_by_company,
                        'invitation_created_at' => $this->invitation_created_at
                    ]
                ]);
            }
        }

        return true;
    }

    /**
     * mobile notification on candidate invitation
     */
    public function sendNotification()
    {
        $heading = Yii::t('app', "You’re invited to apply for a job opening");
        $subtitle = "@ " . $this->request->company->company_name;
        $content = $this->request->request_job_description;

        $filters = [
            [
                "field" => "tag",
                "key" => "candidate_id",
                "relation" => "=",
                "value" => $this->candidate_id
            ]
        ];

        $data = [
            'subject' => 'invitation',
            'invitation_uuid' => $this->invitation_uuid
        ];

        MobileNotification::notifyCandidate($heading, $data, $filters, $subtitle, $content);
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'request',
            'story',
            'company',
            'candidate',
            'suggestion',
            'notes',
            'note' // in case user accept invitation then show invitation
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStory($modelClass = "\common\models\Story")
    {
        return $this->hasOne($modelClass::className(), ['story_uuid' => 'story_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestion($modelClass = "\common\models\Suggestion")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id','request_uuid'=>'request_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\common\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationCreatedByCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'invitation_created_by_company']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationCreatedByStaff($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'invitation_created_by_staff']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationUpdatedByCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'invitation_updated_by_company']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationUpdatedByStaff($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'invitation_updated_by_staff']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\common\models\Request")
    {
        return $this->hasOne($modelClass::className(), ['request_uuid' => 'request_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id'])
            ->via('request');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\common\models\Note")
    {
        return $this->hasMany($modelClass::className(), ['note_uuid' => 'note_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNote($modelClass = "\common\models\Note")
    {
        return $this->hasOne($modelClass::className(), ['invitation_uuid' => 'invitation_uuid']);
    }

    /**
     * @inheritdoc
     * @return query\InvitationQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\InvitationQuery(get_called_class());
    }

    /**
     * job invitation email
     */
    public function jobInvitationEmail()
    {
        $url = Yii::$app->params['candidateAppUrl'] . 'invitation-detail/' . $this->invitation_uuid;

        return Yii::$app->mailer->compose("candidate/job-invitation",
            [
                "logo" => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
                "model" => $this,
                "url" => $url
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->candidate->candidate_email)
            ->setSubject("You’re invited to apply for a job opening")
            ->send();
    }
}
