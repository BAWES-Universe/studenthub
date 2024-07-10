<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "discount".
 *
 * @property string $discount_uuid
 * @property int $category_id
 * @property int $company_id
 * @property int $store_id
 * @property string $description_en
 * @property string $description_ar
 * @property string $how_to_apply_en
 * @property string $how_to_apply_ar
 * @property string $image
 * @property string $valid_until
 * @property string $created_at
 * @property string $updated_at
 *
 * @property DiscountCategory $category
 * @property Company $company
 * @property Store $store
 */
class Discount extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'discount';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['discount_uuid', 'category_id', 'company_id', 'description_en'], 'required'],
            [['category_id', 'company_id', 'store_id'], 'integer'],
            [['description_en', 'description_ar'], 'string'],
            [['valid_until', 'created_at', 'updated_at'], 'safe'],
            [['discount_uuid'], 'string', 'max' => 60],
            [['how_to_apply_en', 'how_to_apply_ar', 'image'], 'string', 'max' => 255],
            [['discount_uuid'], 'unique'],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => DiscountCategory::className(), 'targetAttribute' => ['category_id' => 'category_id']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::className(), 'targetAttribute' => ['store_id' => 'store_id']],
            /**
             *  Amazon S3 Temporary Bucket, validate that uploaded files exist if their values have been changed.
             */
            [
                ['image'],
                '\common\components\S3FileExistValidator',
                'filePath' => '',
                'message' => Yii::t('app',"Please upload a image"),
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'when' => function($model, $attribute) {
                    return $model->{$attribute} !== $model->getOldAttribute($attribute);
                },
            ],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'discount_uuid',
                ],
                'value' => function() {
                    if (!$this->discount_uuid)
                        $this->discount_uuid = 'discount_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->discount_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'discount_uuid' => Yii::t('app', 'Discount Uuid'),
            'category_id' => Yii::t('app', 'Category ID'),
            'company_id' => Yii::t('app', 'Company ID'),
            'store_id' => Yii::t('app', 'Store ID'),
            'description_en' => Yii::t('app', 'Description En'),
            'description_ar' => Yii::t('app', 'Description Ar'),
            'how_to_apply_en' => Yii::t('app', 'How To Apply En'),
            'how_to_apply_ar' => Yii::t('app', 'How To Apply Ar'),
            'image' => Yii::t('app', 'Image'),
            'valid_until' => Yii::t('app', 'Valid Until'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return bool
     */
    public function updateImage() {

        $fileName = $this->image;

        $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;

        $this->image = (YII_ENV == 'prod') ? "discount/". $fileName
            : "dev/discount/". $fileName;

        // Copy using S3ResourceManager Component

        try {

            return Yii::$app->resourceManager->copy($fileName, $this->image, $sourceBucket);

        } catch (\Aws\S3\Exception\S3Exception $e) {

            Yii::error($e->getMessage());

            $this->addError('image', Yii::t('app', 'Image not available to save.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage());

            $this->addError('image', Yii::t('app', 'Image not available to save.'));

            return false;
        }
    }

    /**
     * @return bool
     */
    public function beforeDelete()
    {
        if(!parent::beforeDelete()) {
            return false;
        }

        $this->deleteImage();

        return true;
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @return void
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if(isset($changedAttributes['image'])) {

            //remove old

            $oldImage = $this->getOldAttribute("image");

            if ($oldImage) {
                $this->deleteImage($oldImage);
            }

            //upload new

            if ($this->image) {
                $this->updateImage();
            }
        }
    }

    /**
     * delete resume
     * @return boolean
     */
    public function deleteImage($oldImage = null) {

        try {

            $image = !empty($oldImage) ? $oldImage: $this->image;

            Yii::$app->resourceManager->delete($image);

            return true;
        }
        catch (\Aws\S3\Exception\S3Exception $e)
        {
            Yii::error($e->getMessage(), 'file');

            $this->addError('image', Yii::t('app', 'Document not available to delete.'));

            return false;
        }
        catch (\Exception $e)
        {
            Yii::error($e->getMessage(), 'file');

            $this->addError('image', Yii::t('app', 'Document not available to delete.'));

            return false;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(DiscountCategory::className(), ['category_id' => 'category_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore()
    {
        return $this->hasOne(Store::className(), ['store_id' => 'store_id']);
    }
}
