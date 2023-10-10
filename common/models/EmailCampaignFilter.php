<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "email_campaign_filter".
 *
 * @property string $cf_uuid
 * @property string $campaign_uuid
 * @property string $param
 * @property string $value
 *
 * @property EmailCampaign $campaignUu
 */
class EmailCampaignFilter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'email_campaign_filter';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['campaign_uuid'], 'required'],//'cf_uuid',
            [['cf_uuid', 'campaign_uuid'], 'string', 'max' => 60],
            [['param'], 'string', 'max' => 50],
            [['value'], 'string', 'max' => 100],
            [['cf_uuid'], 'unique'],
            [['campaign_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => EmailCampaign::className(), 'targetAttribute' => ['campaign_uuid' => 'campaign_uuid']],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'cf_uuid',
                ],
                'value' => function() {
                    if (!$this->cf_uuid)
                        $this->cf_uuid = 'cf_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->cf_uuid;
                }
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'cf_uuid' => Yii::t('app', 'Cf Uuid'),
            'campaign_uuid' => Yii::t('app', 'Campaign Uuid'),
            'param' => Yii::t('app', 'Param'),
            'value' => Yii::t('app', 'Value'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCampaign($modelClass = "\common\models\EmailCampaign")
    {
        return $this->hasOne($modelClass::className(), ['campaign_uuid' => 'campaign_uuid']);
    }
}
