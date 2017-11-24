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
 * @property integer $deleted
 * 
 * @property Candidate[] $candidate
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
            [['bank_name','bank_transfer_type'], 'string', 'max' => 50],
            [['bank_swift_code'], 'string', 'max' => 12],
            ['bank_transfer_type', 'in', 'range' => self::getBankCodeList()],
            [['bank_address'], 'string']
        ];
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
     * @inheritdoc
     */
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
    public function extraFields()
    {
        return [
            'candidate'
        ];
    }

    /**
     * Get the Bank transfer type
     */
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate()
    {
        return $this->hasMany(Candidate::className(), ['bank_id' => 'bank_id']);
    }

    /**
     * @return bool
     */
    public function softDelete()
    {
        $this->deleted = 1;
        return $this->save(false);
    }

    /**
     * @inheritdoc
     * @return query\BankQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\BankQuery(get_called_class());
    }

    /**
     * @return array
     */
    private static function getBankCodeList() {
        return ['LCL','SWF','TRF'];
    }
}
