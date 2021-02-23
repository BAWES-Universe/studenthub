<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;


/**
 * This is the model class for table "invitation".
 *
 * @property string $invitation_uuid
 * @property int $candidate_id
 * @property string $request_uuid
 * @property int $invitation_status 1-Invited , 2-Rejected, 3-Accepted
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
            [['invitation_created_at', 'invitation_updated_at'], 'safe'],
            [['invitation_uuid', 'request_uuid'], 'string', 'max' => 60],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['invitation_created_by_company'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['invitation_created_by_company' => 'company_id']],
            [['invitation_created_by_staff'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['invitation_created_by_staff' => 'staff_id']],
            [['invitation_updated_by_company'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['invitation_updated_by_company' => 'company_id']],
            [['invitation_updated_by_staff'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['invitation_updated_by_staff' => 'staff_id']],
            [['request_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Request::className(), 'targetAttribute' => ['request_uuid' => 'request_uuid']],
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
                    'invitation_status' => self::STATUS_INVITED,
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
            'invitation_status' => Yii::t('app', 'Invitation Status'),
            'invitation_created_by_staff' => Yii::t('app', 'Invitation Created By Staff'),
            'invitation_updated_by_staff' => Yii::t('app', 'Invitation Updated By Staff'),
            'invitation_created_by_company' => Yii::t('app', 'Invitation Created By Company'),
            'invitation_updated_by_company' => Yii::t('app', 'Invitation Updated By Company'),
            'invitation_created_at' => Yii::t('app', 'Invitation Created At'),
            'invitation_updated_at' => Yii::t('app', 'Invitation Updated At'),
        ];
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
     * @inheritdoc
     * @return query\InvitationQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\InvitationQuery(get_called_class());
    }
}
