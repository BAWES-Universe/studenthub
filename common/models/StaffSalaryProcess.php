<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "staff_salary_process".
 *
 * @property string $staff_salary_process_uuid
 * @property int $staff_id
 * @property string $salary_month
 * @property string $salary_amount
 * @property string $salary_tags
 * @property string $salary_created_datetime
 * @property string $salary_updated_datetime
 *
 * @property Staff $staff
 */
class StaffSalaryProcess extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'staff_salary_process';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['staff_salary_process_uuid', 'salary_created_datetime', 'salary_updated_datetime'], 'required'],
            [['staff_id'], 'integer'],
            [['salary_month', 'salary_created_datetime', 'salary_updated_datetime'], 'safe'],
            [['salary_amount'], 'number'],
            [['staff_salary_process_uuid'], 'string', 'max' => 60],
            [['salary_tags'], 'string', 'max' => 225],
            [['staff_salary_process_uuid'], 'unique'],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['staff_id' => 'staff_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'staff_salary_process_uuid' => 'Staff Salary Process Uuid',
            'staff_id' => 'Staff ID',
            'salary_month' => 'Salary Month',
            'salary_amount' => 'Salary Amount',
            'salary_tags' => 'Salary Tags',
            'salary_created_datetime' => 'Salary Created Datetime',
            'salary_updated_datetime' => 'Salary Updated Datetime',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'staff_id']);
    }
}
