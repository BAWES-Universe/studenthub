<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;


/**
 * This is the model class for table "university".
 *
 * @property integer $university_id
 * @property string $university_name_en
 * @property string $university_name_ar
 * @property integer $university_data_source
 * @property string $university_created_by
 * @property string $university_updated_by
 * @property string $university_created_at
 * @property string $university_updated_at
 * @property integer $deleted
 *
 * @property Candidate[] $candidates
 */
class University extends \yii\db\ActiveRecord
{
    //This tells us where this model source data is coming from
    const FROM_ADMIN = 0;
    const FROM_CANDIDATE = 1;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'university';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['university_name_en', 'university_name_ar'], 'string', 'max' => 60],
            //Rule for data source
            ['university_data_source', 'in', 'range' => [self::FROM_ADMIN, self::FROM_CANDIDATE]],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'university_created_by',
                'updatedByAttribute' => 'university_updated_by',
                'value' => function() {

                    if(Yii::$app->user->isGuest) {
                        return false;
                    }

                    if(isset(Yii::$app->user->identity->candidate_id))
                        return Yii::$app->user->identity->candidate_id;
                    
                    if(isset(Yii::$app->user->identity->admin_uuid))
                        return Yii::$app->user->identity->admin_id;            

                    if(isset(Yii::$app->user->identity->staff_uuid))
                        return Yii::$app->user->identity->staff_id;                    
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'university_created_at',
                'updatedAtAttribute' => 'university_updated_at',
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
            'university_id' => Yii::t('app','University ID'),
            'university_name_en' => Yii::t('app','University Name En'),
            'university_name_ar' => Yii::t('app','University Name Ar'),
            'university_data_source' => Yii::t('app','University Name Ar'),
            'university_created_by' => Yii::t('app','Created By'),
            'university_updated_by' => Yii::t('app','Updated By'),
            'university_created_at' => Yii::t('app','Created At'),
            'university_updated_at' => Yii::t('app','Updated At'),
            'university_date_source' => Yii::t('app','Data Source'),
            'deleted' => Yii::t('app','Deleted')
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'candidates'
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();
        unset($fields['deleted']);
        $fields['total_candidates'] = function($model) {
            return (int) sizeof($model->candidates);
        };

        return $fields;
    }    

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates()
    {
        return $this->hasMany(Candidate::className(),['university_id'=>'university_id']);
    }

    /**
     * soft delete university
     * @return bool
     */
    public function softDelete()
    {
        $this->deleted = 1;
        return $this->save(false);
    }

    /**
     * @inheritdoc
     * @return query\UniversityQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\UniversityQuery(get_called_class());
    }
}
