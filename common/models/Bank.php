<?php

namespace common\models;

use Yii;
use common\models\Candidate;

/**
 * This is the model class for table "bank".
 *
 * @property integer $bank_id
 * @property string $bank_name
 * @property string $bank_swift_code
 * @property string $bank_address
 */
class Bank extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bank';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bank_name','bank_swift_code','bank_address'], 'required'],
            [['bank_name'], 'string', 'max' => 100],
            [['bank_swift_code'], 'string', 'max' => 12],
            [['bank_address'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'bank_id' => 'Bank ID',
            'bank_name' => 'Bank Name',
            'bank_swift_code' => 'Bank Swift Code',
            'bank_address' => 'Bank Address',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate()
    {
        return $this->hasMany(Candidate::className(), ['bank_id' => 'bank_id']);
    }
}
