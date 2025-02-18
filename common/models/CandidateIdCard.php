<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "candidate_id_card".
 *
 * @property integer $id
 * @property integer $candidate_id
 * @property string $expiry_date
 * @property string $deleted
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 */
class CandidateIdCard extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'candidate_id_card';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['candidate_id'], 'integer'],
            //['candidate_id', 'unique', 'comboNotUnique' => 'Candidate Id already exist.', 'targetAttribute' => ['candidate_id', 'deleted']],
            ['candidate_id', 'validateUnique'],
            [['candidate_id', 'expiry_date'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['deleted'], 'default', 'value' => 0],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::class, 'targetAttribute' => ['candidate_id' => 'candidate_id']],
        ];
    }

    /**
     * @param $attribute
     * @param $params
     * @param $validator
     * @return void
     */
    public function validateUnique($attribute, $params, $validator) {

        $query = self::find()
            ->andWhere([$attribute => $this->$attribute, 'deleted' => 0]);

        if($this->id) {
            $query->andWhere(['!=', 'id', $this->id]);
        }

        $exists = $query->exists();

        if($exists) {
            $this->addError($attribute, Yii::t('app', 'Candidate Id already exist.'));
        }
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'candidate_id' => Yii::t('app','Candidate ID'),
            'expiry_date' => Yii::t('app','Expiry Date'),
            'created_at' => Yii::t('app','Created At'),
            'updated_at' => Yii::t('app','Updated At'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        $fields['expired'] = function($model) {
            return strtotime($model->expiry_date) < strtotime(date('Y-m-d'));
        };

        unset($fields['deleted']);

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'candidate'
        ];
    }

    /**
     * @param $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if($insert) {
            $this->deleted = 0;
        }

        return parent::beforeSave($insert);
    }

    /**
     * @return true
     * @throws \yii\db\Exception
     */
    public function afterSave($insert, $changedAttribute)
    {
        //trigger algolia update
        $this->candidate->save(false);

        return true;
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\common\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @inheritdoc
     * @return query\CandidateIdCardQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\CandidateIdCardQuery(get_called_class());
    }
}
