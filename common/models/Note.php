<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;


/**
 * This is the model class for table "note".
 *
 * @property string $note_uuid
 * @property integer $company_id
 * @property integer $candidate_id
 * @property string $request_uuid
 * @property string $invitation_uuid
 * @property string $suggestion_uuid
 * @property string $contact_uuid
 * @property string $fulltimer_uuid
 * @property string $note_type
 * @property string $note_text
 * @property integer $created_by
 * @property integer $updated_by
 * @property string $note_created_datetime
 * @property string $note_updated_datetime
 * 
 * @property Company[] $company
 * @property staff[] $Staff
 */
class Note extends \yii\db\ActiveRecord
{
    const TYPE_INTERNAL_NOTE = "Internal Note";
    const TYPE_PHONE_CALL = "Phone Call";
    const TYPE_EMAIL = "Email";
    const TYPE_MEETING = "Meeting";
    const TYPE_INTERVIEW = "Interview";
    const TYPE_TASK = "Task";

    const TYPE_SUGGESTED = "Suggested";
    const TYPE_ACCEPTED = "Accepted";
    const TYPE_REJECTED = "Rejected";

    const TYPE_INVITATION_ACCEPTED = "Invitation Accepted";
    const TYPE_INVITATION_REJECTED = "Invitation Rejected";

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'note';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['note_text'], 'required'],
            ['note_type', 'in', 'range' => [
                self::TYPE_INTERNAL_NOTE,
                self::TYPE_PHONE_CALL,
                self::TYPE_EMAIL,
                self::TYPE_MEETING,
                self::TYPE_INTERVIEW,
                self::TYPE_TASK,
                self::TYPE_SUGGESTED,
                self::TYPE_ACCEPTED,
                self::TYPE_REJECTED,
                self::TYPE_INVITATION_ACCEPTED,
                self::TYPE_INVITATION_REJECTED
            ]],
            ['request_uuid', 'validateRequest'],
            ['contact_uuid', 'validateContact'],
            [['note_created_datetime', 'note_updated_datetime'], 'safe'],//,'created_by','updated_by'
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
            [['request_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Request::className(), 'targetAttribute' => ['request_uuid' => 'request_uuid']],
            [['fulltimer_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Fulltimer::className(), 'targetAttribute' => ['fulltimer_uuid' => 'fulltimer_uuid']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['created_by' => 'staff_id']],
            [['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['updated_by' => 'staff_id']],
            ['invitation_uuid', 'exist', 'skipOnError' => true, 'targetClass' => Invitation::className(), 'targetAttribute' => ['invitation_uuid' => 'invitation_uuid']],
            [['suggestion_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Suggestion::className(), 'targetAttribute' => ['suggestion_uuid' => 'suggestion_uuid']],
        ];
    }

    /**
     * can't update for cancelled/completed request
     * @param type $attribute
     * @param type $params
     * @param type $validator
     */
    public function validateRequest($attribute, $params, $validator)
    {
        if($this->request && in_array($this->request->request_status, [Request::STATUS_CANCELLED, Request::STATUS_DELIVERED])) {
            $this->addError($attribute, Yii::t('app', "Can't update for cancelled/completed request."));
        }
    }

    /**
     * exist to check in same company
     * @param type $attribute
     * @param type $params
     * @param type $validator
     */
    public function validateContact($attribute, $params, $validator)
    {
        if ($this->company_id && $this->contact_uuid) {
            $exist = CompanyContact::find()->andWhere(['company_id'=>$this->company_id,'contact_uuid'=>$this->contact_uuid])->exists();
            if (!$exist) {
                $this->addError($attribute, Yii::t('app', "Invalid contact request"));
            }
        }
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'note_uuid',
                ],
                'value' => function() {
                    if (!$this->note_uuid)
                        $this->note_uuid = 'note_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->note_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'note_created_datetime',
                'updatedAtAttribute' => 'note_updated_datetime',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'defaultValue' => function() {
                    return $this->created_by;//for guest user
                }
            ],
            [
                'class' => BlameableBehavior::className(),
                'updatedByAttribute' => 'updated_by',
                'defaultValue' => function() {
                    return $this->updated_by;//for guest user
                }
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'note_uuid' => Yii::t('candidate', 'ID'),
            'candidate_id' => Yii::t('candidate', 'Candidate ID'),
            'request_uuid' => Yii::t('candidate', 'Request ID'),
            'invitation_uuid' => Yii::t('candidate', 'Invitation ID'),
            'contact_uuid' => Yii::t('candidate', 'Contact ID'),
            'fulltimer_uuid' => Yii::t('candidate', 'FullTimer ID'),
            'note_type' => Yii::t('app', 'Note type'),
            'company_id' => Yii::t('candidate', 'Company ID'),
            'note_text' => Yii::t('candidate', 'Note'),
            'note_created_datetime' => Yii::t('candidate', 'Created At'),
            'note_updated_datetime' => Yii::t('candidate', 'Updated At'),
            'created_by' => Yii::t('candidate', 'Created by'),
            'updated_by' => Yii::t('candidate', 'Updated by'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        $fields['note_text'] = function($model) {
            return html_entity_decode($model->note_text);
        };

        return $fields;
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if(!parent::beforeSave($insert)) {
            return false;
        }

        if($this->request) {
            $message = Yii::t ('app', '[Update on request from {name} @ {email} by {staffName}] {activityDetail}', [
                'name' => $this->request->company->company_name,
                'email' => $this->request->company->company_email,
                'staffName' => $this->createdBy? $this->createdBy->staff_name: 'Guest',
                'activityDetail' => $this->note_text
            ]);

            Yii::info ($message, __METHOD__);
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes) {
        parent::afterSave($insert, $changedAttributes);

        if($insert && $this->request) {

            //update `request_updated_at` field
            $this->request->request_updated_datetime = '';
            $this->request->update(false);
            Company::updateRequest($this->request->company_id);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'candidate',
            'request',
            'invitation',
            'company',
            'createdBy',
            'updatedBy',
            'companyContact',
        ];
    }

    /**
     * Gets query for [[Invitation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvitation($modelName = '\common\models\Invitation')
    {
        return $this->hasOne($modelName::className(), ['invitation_uuid' => 'invitation_uuid']);
    }

    /**
     * Gets query for [[Request]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelName = '\common\models\Candidate')
    {
        return $this->hasOne($modelName::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * Gets query for [[Request]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelName = '\common\models\Request')
    {
        return $this->hasOne($modelName::className(), ['request_uuid' => 'request_uuid']);
    }

    /**
     * Gets query for [[CompanyContact]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContact($modelName = '\common\models\CompanyContact')
    {
        return $this->hasOne($modelName::className(), ['contact_uuid' => 'contact_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'updated_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimer($modelClass = "\common\models\Fulltimer")
    {
        return $this->hasOne($modelClass::className(), ['fulltimer_uuid' => 'fulltimer_uuid']);
    }

    public function getSuggestion($modelClass = "\common\models\Suggestion") {
        return $this->hasOne($modelClass::className(), ['suggestion_uuid' => 'suggestion_uuid']);
    }

    /**
     * @inheritdoc
     * @return query\NoteQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\NoteQuery(get_called_class());
    }
}
