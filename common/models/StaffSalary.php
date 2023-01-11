<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "staff_salary".
 *
 * @property string $staff_salary_uuid
 * @property int $staff_id
 * @property string $salary
 * @property string $salary_currency
 * @property string $comment
 * @property string $salary_date
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Staff $staff
 */
class StaffSalary extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'staff_salary';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['staff_id'], 'integer'],
            [['salary'], 'number'],
            [['created_at', 'updated_at', 'salary_date'], 'safe'],
            [['staff_salary_uuid'], 'string', 'max' => 60],
            [['salary_currency'], 'string', 'max' => 3],
            [['comment'], 'string', 'max' => 255],
            [['staff_salary_uuid'], 'unique'],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['staff_id' => 'staff_id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'staff_salary_uuid',
                ],
                'value' => function() {
                    if(!$this->staff_salary_uuid)
                        $this->staff_salary_uuid = 'staff_salary_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->staff_salary_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function extraFields()
    {
        return ['staff'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'staff_salary_uuid' => Yii::t('app', 'Staff Salary Uuid'),
            'staff_id' => Yii::t('app', 'Staff ID'),
            'salary' => Yii::t('app', 'Salary'),
            'salary_currency' => Yii::t('app', 'Salary Currency'),
            'comment' => Yii::t('app', 'Comment'),
            'salary_date' => Yii::t('app', 'Salary Date'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'staff_id']);
    }
}
