<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "setting".
 *
 * @property string $setting_uuid
 * @property string $code module identifier
 * @property string $key
 * @property string|null $value
 * @property int|null $serialized
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class Setting extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'setting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'key'], 'required'],
            [['value'], 'string'],
            [['serialized'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['setting_uuid'], 'string', 'max' => 60],
            [['code', 'key'], 'string', 'max' => 128],
            [['setting_uuid'], 'unique']
        ];
    }

    /**
     *
     * @return type
     */
    public function behaviors()
    {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'setting_uuid',
                ],
                'value' => function () {
                    if (!$this->setting_uuid)
                        $this->setting_uuid = 'setting_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar ();

                    return $this->setting_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'setting_uuid' => Yii::t('app', 'Setting ID'),
            'code' => Yii::t('app', 'Code'),
            'key' => Yii::t('app', 'Key'),
            'value' => Yii::t('app', 'Value'),
            'serialized' => Yii::t('app', 'Serialized'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * set config
     * @param $code
     * @param $key
     * @param $value
     */
    public static function getConfig($code, $key) {

        //if loaded

        if(Yii::$app->config->has($key))
        {
            return Yii::$app->config->get($key);
        }

        //if store specific

        $model = Setting::find()
            ->andWhere([
                'code' => $code,
                'key' => $key
            ])
            ->one();

        if($model) {
            return $model->value;
        }

        //global

        if(isset(Yii::$app->params[$key])) {
            return Yii::$app->params[$key];
        }
    }

    /**
     * set config
     * @param $code
     * @param $key
     * @param $value
     */
    public static function setConfig($code, $key, $value) {

        //if exists update

        /*if(Yii::$app->config->has($key))
        {
            return self::updateAll([
                'value' => $value
            ], [
                'key' => $key,
            ]);
        }*/

        $model = Setting::find()->andWhere([
            "code" => $code,
            "key" => $key
        ])->one();

        if(!$model) {
            $model = new Setting();
            $model->code = $code;
            $model->key = $key;
        }

        $model->value = $value;

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->getErrors()
            ];
        }

        return [
            "operation" => "success",
            "message" => 'Settings updated successfully'
        ];
    }
}
