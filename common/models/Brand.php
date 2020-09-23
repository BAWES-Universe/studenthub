<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;


/**
 * This is the model class for table "brand".
 *
 * @property string $brand_uuid
 * @property integer $company_id
 * @property string $brand_name_en
 * @property string $brand_name_ar
 * @property string $brand_logo
 * @property string $brand_created_datetime
 * @property string $brand_updated_datetime
 * 
 * @property Company[] $company
 */
class Brand extends \yii\db\ActiveRecord
{ 
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'brand';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_id','brand_name_en','brand_name_ar'], 'required'],
            [['brand_created_datetime', 'brand_updated_datetime'], 'safe'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],

            /**
             *  Amazon S3 Temporary Bucket, validate that uploaded files exist if their values have been changed.

            [
                ['brand_logo'],
                '\common\components\S3FileExistValidator',
                'filePath' => '',
                'message' => Yii::t('candidate', "Please upload a logo"),
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'when' => function($model, $attribute) {
                    return $model->{$attribute} !== $model->getOldAttribute($attribute);
                }
            ]*/
        ];
    }

    public function logoCheck(){

    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'brand_uuid',
                ],
                'value' => function() {
                    if (!$this->brand_uuid)
                        $this->brand_uuid = 'brand_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->brand_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'brand_created_datetime',
                'updatedAtAttribute' => 'brand_updated_datetime',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * delete old logo from cloudinary
     * @return boolean
     */
    public function deleteLogoFromCloudinary() {

        try {
            $path = (YII_ENV == 'prod') ? "company-brand/" : "dev/company-brand/" ;
            $response = Yii::$app->cloudinaryManager->delete( $path . $this->brand_logo);
            if ($response && $response['result'] == 'not found') {
                $this->addError('brand_logo', Yii::t('app', 'Image not available to save.'));
                return false;
            }
        } catch (\Cloudinary\Error $e) {

            Yii::error($e->getMessage(), 'common');

            //$this->addError('brand_logo', Yii::t('app', 'Please try again.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'common');

            //$this->addError('brand_logo', Yii::t('app', 'Image not available to save.'));

            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        return array_merge(parent::fields(), [
            'candidate_count' => function($data) {
                return $this->getCandidates()->count();
            }
        ]);
    }

    /**
     * Set logo from S3 temp url
     * @param string $url
     */
    public function setLogo($brand_logo) {

        if(!Yii::$app->temporaryBucketResourceManager->fileExists($brand_logo)) {
            $this->addError('brand_logo', Yii::t('app', 'Image not available to save.'));
            return false;
        }

        $url = Yii::$app->temporaryBucketResourceManager->getUrl($brand_logo);

        $filename = Yii::$app->security->generateRandomString();

        // deleting old pic

        if ($this->brand_logo) {
            $this->deleteLogoFromCloudinary();
        }

        try {
            $path = (YII_ENV == 'prod') ?  "company-brand/" : "dev/company-brand/";
            $result = Yii::$app->cloudinaryManager->upload(
                $url,
                [
                    'public_id' =>  $path . $filename,
                    "eager" => [
                        [
                            "width" => 200, "height" => 200, "crop" => "thumb", "gravity" => "face"
                        ]
                    ]
                ]
            );

            if ($result) {
                $this->brand_logo = basename($result['url']);
                return true;
            }

        } catch (\Cloudinary\Error $e) {

            Yii::error($e->getMessage(), 'common');

            $this->addError('brand_logo', Yii::t('app', 'Please try again.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'common');

            $this->addError('brand_logo', Yii::t('app', 'Image not available to save.'));

            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'brand_uuid' => Yii::t('candidate', 'ID'),
            'company_id' => Yii::t('candidate', 'Company ID'),
            'brand_name_en' => Yii::t('candidate', 'Name - English'),
            'brand_name_ar' => Yii::t('candidate', 'Name - Arabic'),
            'brand_logo' => Yii::t('candidate', 'Logo'),
            'brand_created_datetime' => Yii::t('candidate', 'Created At'),
            'brand_updated_datetime' => Yii::t('candidate', 'Updated At'),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'company',
            'candidates',
            'stores',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStores($modelClass = "\common\models\Store")
    {
        return $this->hasMany($modelClass::className(), ['brand_uuid' => 'brand_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\common\models\Candidate")
    {
        return $this->hasMany($modelClass::className(), ['store_id' => 'store_id'])
            ->via('stores');
    }
}
