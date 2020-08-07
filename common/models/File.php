<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;
use yii\db\Expression;


/**
 * This is the model class for table "file".
 *
 * @property string $file_uuid
 * @property int $company_id
 * @property string $file_title
 * @property string $file_description
 * @property string $file_name
 * @property string $file_type
 * @property int $file_size
 * @property string $file_s3_path
 * @property string $file_created_datetime
 *
 * @property Company $company
 */
class File extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'file';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_id', 'file_size'], 'integer'],
            [['file_title'], 'required'],
            [['file_description'], 'string'],
            [['file_created_datetime'], 'safe'],
            [['file_uuid'], 'string', 'max' => 60],
            [['file_title', 'file_name'], 'string', 'max' => 255],
            [['file_type'], 'string', 'max' => 10],
            [['file_s3_path'], 'string', 'max' => 225],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
            /**
             *  Amazon S3 Temporary Bucket, validate that uploaded files exist if their values have been changed.
             */
            [
                ['file_s3_path'],
                '\common\components\S3FileExistValidator',
                'filePath' => '',
                'message' => Yii::t('candidate',"Please upload a document"),
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'when' => function($model, $attribute) {
                    return $model->{$attribute} !== $model->getOldAttribute($attribute);
                }
            ],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'file_uuid',
                ],
                'value' => function() {
                    if(!$this->file_uuid)
                        $this->file_uuid = 'file_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->file_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'file_created_datetime',
                'updatedAtAttribute' => false,
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
            'file_uuid' => 'File Uuid',
            'company_id' => 'Company ID',
            'file_title' => 'File Title',
            'file_description' => 'File Description',
            'file_name' => 'File Name',
            'file_type' => 'File Type',
            'file_size' => 'File Size',
            'file_s3_path' => 'File S3 Path',
            'file_created_datetime' => 'File Created Datetime',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return bool
     */
    public function updateDocument() {

        $fileName = $this->file_s3_path;

        $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;
        $targetPath = "company-files/" . $fileName;
        $this->file_s3_path = "company-files/" . $fileName;
        // Copy using S3ResourceManager Component
        try {

            return Yii::$app->resourceManager->copy($fileName, $targetPath, $sourceBucket);

        } catch (\Aws\S3\Exception\S3Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_resume', Yii::t('app', 'Resume not available to save.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_resume', Yii::t('app', 'Resume not available to save.'));

            return false;
        }
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {

            if (!$this->updateDocument() ) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * delete resume
     * @return boolean
     */
    public function deleteDocument() {

        try {
            Yii::$app->resourceManager->delete("company-files/" . $this->file_s3_path);
            return true;
        } catch (\Aws\S3\Exception\S3Exception $e) {

            Yii::error($e->getMessage(), 'file');

            $this->addError('candidate_resume', Yii::t('app', 'Document not available to delete.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'file');

            $this->addError('candidate_resume', Yii::t('app', 'Document not available to delete.'));

            return false;
        }
    }
}
