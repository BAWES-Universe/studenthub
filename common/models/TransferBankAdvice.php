<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "transfer_bank_advice".
 *
 * @property string $tba_uuid
 * @property int $serial_no
 * @property string $file_path
 * @property int $created_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Admin $createdBy
 */
class TransferBankAdvice extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'transfer_bank_advice';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['tba_uuid'], 'required'],
            [['created_by', "serial_no"], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['tba_uuid'], 'string', 'max' => 60],
            [['file_path'], 'string', 'max' => 255],
            [['tba_uuid'], 'unique'],
            [['is_deleted'], "boolean"],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => Admin::className(), 'targetAttribute' => ['created_by' => 'admin_id']],
        ];
    }

    /**
     * @return array[]
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'tba_uuid',
                ],
                'value' => function() {
                    if(!$this->tba_uuid)
                        $this->tba_uuid = 'tba_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->tba_uuid;
                }
            ],
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => null,
                'value' => function() {
                    if(isset(Yii::$app->user->identity->admin_id))
                        return Yii::$app->user->identity->admin_id;
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

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'tba_uuid' => Yii::t('app', 'Tba Uuid'),
            'file_path' => Yii::t('app', 'File Path'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            "serial_no" => Yii::t('app', 'Serial No'),
            "is_deleted" => Yii::t('app', 'Is Deleted?'),
        ];
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @return bool
     */
    public function afterSave($insert, $changedAttributes)
    {
        if (!parent::afterSave($insert, $changedAttributes)) {
            return false;
        }

        /*if ($insert) {
            $this->saveFile();
        }*/

        return true;
    }

    /**
     * @return array|false|int[]|string[]
     */
    public function extraFields()
    {
        return array_merge(["createdBy"], parent::extraFields());
    }

    /**
     * @return bool
     */
    public function saveFile($fileName, $source) {

     //   try {

            return  Yii::$app->resourceManager->saveContent(
                "transfer-bank-advice/" . $fileName, // name
                $source, // source file
                "text/plain"
            );

       /* } catch (\Aws\S3\Exception\S3Exception $e) {

            Yii::error($e->getMessage());

            $this->addError('image', Yii::t('app', 'File not available to save.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage());

            $this->addError('image', Yii::t('app', 'File not available to save.'));

            return false;
        }*/
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\common\models\Admin")
    {
        return $this->hasOne($modelClass::className(), ['admin_id' => 'created_by']);
    }
}
