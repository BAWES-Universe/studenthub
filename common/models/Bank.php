<?php

namespace common\models;

use function Couchbase\defaultDecoder;
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
            [['bank_address'], 'string'],

        ];
    }


    public function fields()
    {
        return [
            'bank_id',
            'bank_name',
            'bank_swift_code',
            'bank_address',
            'bank_transfer_type',
            'transfer_type_value' => function($data) {
                return $data->getTypeValue();
            }
        ];

        return $fields;
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

    public function getTypeValue()
    {
        switch ($this->bank_transfer_type) {
            case 'LCL' :
                return self::LCL;
            break;
            case 'SWF' :
                return self::SWF;
                break;
            case 'TRF' :
                return self::TRF;
                break;
            default:
                return '';
                break;
        }
    }
}
