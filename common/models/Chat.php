<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "chat".
 *
 * @property string $chat_uuid
 * @property int $candidate_id
 * @property int $company_id
 * @property int $parent_company_id
 * @property int $store_id
 * @property string $contact_uuid
 * @property int $staff_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property int $staffUnreadCount
 * @property int $candidateUnreadCount
 * @property int $contactUnreadCount
 * @property Contact $contact
 * @property Candidate $candidate
 * @property Company $company
 * @property Company $parentCompany
 * @property Staff $staff
 * @property Store $store
 * @property ChatMessage[] $chatMessages
 */
class Chat extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['candidate_id', 'company_id', 'store_id', ], 'required'],//'chat_uuid','staff_id'
            [['candidate_id', 'company_id', 'parent_company_id', 'store_id', 'staff_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['chat_uuid', "contact_uuid"], 'string', 'max' => 60],
            [['chat_uuid'], 'unique'],
            [['contact_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Contact::class, 'targetAttribute' => ['contact_uuid' => 'contact_uuid']],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::class, 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'company_id']],
            [['parent_company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['parent_company_id' => 'company_id']],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['staff_id' => 'staff_id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::class, 'targetAttribute' => ['store_id' => 'store_id']],
        ];
    }

    /**
     * @return array[]
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'chat_uuid',
                ],
                'value' => function() {
                    if (!$this->chat_uuid)
                        $this->chat_uuid = 'chat_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->chat_uuid;
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
            'chat_uuid' => Yii::t('app', 'Chat Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'company_id' => Yii::t('app', 'Company ID'),
            'parent_company_id' => Yii::t('app', 'Parent Company ID'),
            'store_id' => Yii::t('app', 'Store ID'),
            "contact_uuid"=> Yii::t('app', 'Contact ID'),
            'staff_id' => Yii::t('app', 'Staff ID'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string[]
     */
    public function extraFields()
    {
        return [
          "contact",
          "staff",
          "candidate",
          "company",
          "parentCompany",
            "store",
            "recentMessage",
            "staffUnreadCount",
            "candidateUnreadCount",
            "companyUnreadCount"
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\common\models\Contact")
    {
        return $this->hasOne($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
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
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParentCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'parent_company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\common\models\Store")
    {
        return $this->hasOne($modelClass::className(), ['store_id' => 'store_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChatMessages($modelClass = "\common\models\ChatMessage")
    {
        return $this->hasMany($modelClass::className(), ['chat_uuid' => 'chat_uuid']);
    }

    /**
     * @param $modelClass
     * @return array|\yii\db\ActiveRecord|null
     */
    public function getRecentMessage($modelClass = "\common\models\ChatMessage") {
        return $this->getChatMessages($modelClass)->orderBy("message_index DESC")->one();
    }

    /**
     * @return int
     */
    public function getStaffUnreadCount() {
        return (int) $this->getChatMessages()
            ->andWhere(['!=', "status", ChatMessage::STATUS_READ])
            ->andWhere(["!=", "from", ChatMessage::FROM_STAFF])
            ->count();
    }

    /**
     * @return int
     */
    public function getCandidateUnreadCount() {
        return (int) $this->getChatMessages()
            ->andWhere(['!=', "status", ChatMessage::STATUS_READ])
            ->andWhere(["!=", "from", ChatMessage::FROM_CANDIDATE])
            ->count();
    }

    /**
     * @return int
     */
    public function getCompanyUnreadCount() {
        return (int) $this->getChatMessages()
            ->andWhere(['!=', "status", ChatMessage::STATUS_READ])
            ->andWhere(["!=", "from", ChatMessage::FROM_CONTACT])
            ->count();
    }

    /**
     * @return int
     */
    public function getContactUnreadCount() {
        return (int) $this->getChatMessages()
            ->andWhere(['!=', "status", ChatMessage::STATUS_READ])
            ->andWhere(["!=", "from", ChatMessage::FROM_CONTACT])
            ->count();
    }
}
