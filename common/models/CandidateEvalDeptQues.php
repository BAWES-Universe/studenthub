<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "candidate_eval_dept_ques".
 *
 * @property int $dept_id 1-Sales Associate,2-IT,3-Call Centre Agent, 4-Social Media, 5-Outdoor Sales Representative, 
 * @property string $ceq_uuid
 *
 * @property CandidateEvalQues $ceqUu
 */
class CandidateEvalDeptQues extends \yii\db\ActiveRecord
{

    const DEPT_SALE = '1'; // Sales Associate
    CONST DEPT_IT = '2'; // IT
    CONST DEPT_CALL_CENTER = '3'; //Call Centre Agent,
    CONST DEPT_SOCIAL_MEDIA = '4'; //4-Social Media,
    CONST DEPT_OUTDOOR_SALE = '5'; //Outdoor Sales Representative
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candidate_eval_dept_ques';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['dept_id','ceq_uuid'], 'required'],
            [['ceq_uuid'], 'string', 'max' => 60],
            [['dept_id'], 'in','range', 'between' => [self::DEPT_SALE, self::DEPT_CALL_CENTER,self::DEPT_IT, self::DEPT_OUTDOOR_SALE,self::DEPT_SOCIAL_MEDIA ]],
            [['ceq_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => CandidateEvalQues::className(), 'targetAttribute' => ['ceq_uuid' => 'ceq_uuid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'dept_id' => 'Dept ID',
            'ceq_uuid' => 'Ceq Uuid',
        ];
    }


    public function extraFields()
    {
        return [
            'question',
            'department',
            'departmentQuestion'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestion()
    {
        return $this->hasOne(CandidateEvalQues::className(), ['ceq_uuid' => 'ceq_uuid'])
            ->addSelect('question,ceq_uuid');
    }

    public function getDepartmentQuestion()
    {
        return $this->hasMany(self::className(), ['dept_id' => 'dept_id']);
    }

    /**
     * get department
     * 1-Sales Associate,2-IT,3-Call Centre Agent, 4-Social Media, 5-Outdoor Sales Representative
     */
    public function getDepartment() {
        return self::getDepartmentDetail($this->dept_id);
    }

    public static function getDepartmentDetail($id) {
        $department = null;
        switch ($id) {
            case self::DEPT_SALE:
                $department = 'Sales Associate';
                break;
            case self::DEPT_IT:
                $department = 'IT';
                break;
            case self::DEPT_CALL_CENTER:
                $department = 'Call Centre Agent';
                break;
            case self::DEPT_SOCIAL_MEDIA:
                $department = 'Social Media';
                break;
            default:
                $department = 'Outdoor Sale Representative';
                break;
        }

        return $department;
    }
}
