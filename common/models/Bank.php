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
 * @property string $bank_transfer_type
 */
class Bank extends \yii\db\ActiveRecord
{
    const LCL = 'Local Bank Transfer';
    const SWF = 'International Transfer';
    const TRF = 'Within Bank Transfer';
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
            [['bank_name','bank_transfer_type'], 'string', 'max' => 100],
            [['bank_swift_code'], 'string', 'max' => 12],
            ['bank_transfer_type', 'validateAttributeCode'],
            [['bank_address'], 'string'],

        ];
    }

    public function validateAttributeCode($model, $attribute)
    {
        if (!in_array($this->bank_transfer_type, [self::LCL, self::SWF, self::TRF])) {
            $this->addError($model,'Transfer Type must be in `'.self::LCL.'`, `'.self::SWF.', `'.self::TRF.'`');
        }
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'bank_id' => 'ID',
            'bank_name' => 'Name',
            'bank_swift_code' => 'Swift Code',
            'bank_address' => 'Address',
            'bank_transfer_type' => 'Transfer Type',
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
