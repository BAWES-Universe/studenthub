<?php

namespace common\models;

use Yii;
use common\models\Candidate;

/**
 * This is the model class for table "bank".
 *
 * @property integer $bank_id
 * @property string $bank_name
 * @property string|null $bank_iban_code
 * @property string $bank_swift_code
 * @property integer $bank_code_abk
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
            [['bank_name','bank_swift_code','bank_address', 'bank_iban_code'], 'required'],
            [['bank_name','bank_transfer_type'], 'string', 'max' => 50],
            [['bank_code_abk'], "number", "max"=> 100],
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
            'bank_id' => Yii::t('candidate','ID'),
            'bank_name' => Yii::t('candidate','Name'),
            'bank_iban_code' => Yii::t('candidate','Bank IBAN'),
            'bank_swift_code' => Yii::t('candidate','Swift Code'),
            "bank_code_abk" => Yii::t('candidate','Bank Code [ABK]'),
            'bank_address' => Yii::t('candidate','Address'),
            'bank_transfer_type' => Yii::t('candidate','Transfer Type'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        $fields['transfer_type_value'] = function($model) {
            return $model->getTypeValue();
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
    public function getCandidate($modelClass = "\common\models\Candidate")
    {
        return $this->hasMany($modelClass::className(), ['bank_id' => 'bank_id']);
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
