<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "staff_leave".
 *
 * @property string $staff_leave_uuid
 * @property int $staff_id
 * @property string $from_date
 * @property string $to_date
 * @property string $note
 * @property string $category
 * @property string $file
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Staff $staff
 */
class StaffLeave extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'staff_leave';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['staff_leave_uuid', 'created_at', 'updated_at'], 'required'],
            [['staff_id'], 'integer'],
            [['from_date', 'to_date', 'created_at', 'updated_at','file','category','status'], 'safe'],
            [['note'], 'string'],
            [['staff_leave_uuid'], 'string', 'max' => 60],
            [['staff_leave_uuid'], 'unique'],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['staff_id' => 'staff_id']],
            [
                ['file'],
                '\common\components\S3FileExistValidator',
                'skipOnError' => true,
                'filePath' => '',
                'message' => "Please upload attachment",
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'extensions' => 'pdf,doc,docx',
                'when' => function($model, $attribute) {
                    return (trim($model->file) && $model->{$attribute} !== $model->getOldAttribute($attribute));
                }
            ],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'staff_leave_uuid',
                ],
                'value' => function() {
                    if (!$this->staff_leave_uuid)
                        $this->staff_leave_uuid = 'staff_leave_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->staff_leave_uuid;
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

    public function fields()
    {
        $field = parent::fields();
        $field['categoryLbl'] = function($model) {
            return $model->getCategoryLabel();
        };
        $field['statusLbl'] = function($model) {
            return $model->getStatusLabel();
        };

        return $field;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'staff_leave_uuid' => Yii::t('app', 'Staff Leave Uuid'),
            'staff_id' => Yii::t('app', 'Staff ID'),
            'from_date' => Yii::t('app', 'From Date'),
            'to_date' => Yii::t('app', 'To Date'),
            'note' => Yii::t('app', 'Note'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'staff_id']);
    }

    public function getCategoryLabel() {
        switch ($this->category) {
            case 1:
                $label = 'Vacation';
                break;
            case 2:
                $label = 'Sick Leave';
                break;
            default:
                $label = 'Casual Leave';
                break;
        }
        return $label;
    }
    public function getStatusLabel() {
        switch ($this->status) {
            case 1:
                $label = 'Approved';
                break;
            case 2:
                $label = 'Declined';
                break;
            default:
                $label = 'Pending';
                break;
        }
        return $label;
    }

    public function beforeSave($insert)
    {
        if(!parent::beforeSave ($insert)) {
            return false;
        }

        //on resume uploaded

        if($insert && $this->file) {
            return $this->updateAttachment();
        }

        return true;
    }

    /**
     * save resume to permanent bucket
     * @return boolean
     */
    public function updateAttachment() {

        $fileName = $this->file;

        $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;

        $targetPath = "staff-leave/" . $fileName;

        // Copy using S3ResourceManager Component

        try {

            Yii::$app->resourceManager->copy($fileName, $targetPath, $sourceBucket);

        } catch (\Aws\S3\Exception\S3Exception $e) {

            Yii::error($e->getMessage(), 'staff');

            $this->addError('file', Yii::t('app', 'file not available to save.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'staff');

            $this->addError('file', Yii::t('app', 'file not available to save.'));

            return false;
        }

        return true;
    }
}
