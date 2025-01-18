<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "discount_category".
 *
 * @property int $category_id
 * @property string $name_en
 * @property string $name_ar
 * @property string $image
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Discount[] $discounts
 */
class DiscountCategory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'discount_category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name_en'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['name_en', 'name_ar', 'image'], 'string', 'max' => 255],
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
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'category_id' => Yii::t('app', 'Category ID'),
            'name_en' => Yii::t('app', 'Name En'),
            'name_ar' => Yii::t('app', 'Name Ar'),
            'image' => Yii::t('app', 'Image'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return array[]
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ]
        ];
    }

    /**
     * @return bool
     */
    public function updateImage() {

        $fileName = $this->image;

        $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;

        $filePath = "discount-category/". $fileName;

        // Copy using S3ResourceManager Component

        try {

            return Yii::$app->resourceManager->copy($fileName, $filePath, $sourceBucket);

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

        if ($insert) {
            //upload new

            if ($this->image) {
                $this->updateImage();
            }
        }
        else if(isset($changedAttributes['image']))
        {
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
    public function getDiscounts($modelClass = "\common\models\Discount")
    {
        return $this->hasMany($modelClass::className(), ['category_id' => 'category_id']);
    }
}
