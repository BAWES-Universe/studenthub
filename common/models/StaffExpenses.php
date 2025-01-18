<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "staff_expenses".
 *
 * @property string $staff_expense_uuid
 * @property string $supplier
 * @property int $category
 * @property string $purchase_date
 * @property string $total_amount
 * @property int $currency
 * @property double $vat
 * @property int $reimbursable
 * @property string $description
 * @property string $file
 * @property int $staff_id
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 * @property int $created_by
 * @property int $updated_by
 *
 * @property Staff $staff
 */
class StaffExpenses extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'staff_expenses';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['supplier','category','purchase_date','total_amount'], 'required'],
            [['category', 'reimbursable', 'staff_id', 'created_by', 'updated_by','status'], 'integer'],
            [['purchase_date', 'total_amount', 'created_at', 'updated_at'], 'safe'],
            [['vat'], 'number'],
            [['currency', 'description'], 'string'],
            [['staff_expense_uuid'], 'string', 'max' => 60],
            [['supplier', 'file'], 'string', 'max' => 225],
            [['staff_expense_uuid'], 'unique'],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['staff_id' => 'staff_id']],
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
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'staff_expense_uuid',
                ],
                'value' => function() {
                    if (!$this->staff_expense_uuid)
                        $this->staff_expense_uuid = 'staff_exp_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->staff_expense_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by'
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'staff_expense_uuid' => 'Staff Expense Uuid',
            'supplier' => 'Supplier',
            'category' => 'Category',
            'purchase_date' => 'Purchase Date',
            'total_amount' => 'Total Amount',
            'currency' => 'Currency',
            'vat' => 'Vat',
            'reimbursable' => 'Reimbursable',
            'description' => 'Description',
            'file' => 'File',
            'staff_id' => 'Staff ID',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'staff_id']);
    }

    public function extraFields()
    {
        return [
            'category',
            'staff'
        ];
    }

    public function getCategory() {
        return $this->getCategoryDetail($this->category);
    }

    public function getCategoryDetail($id) {
        switch ($id) {
            case 1 :
                $lbl = 'Accommodations';
                break;
            case 2 :
                $lbl = 'Company Resources';
                break;
            case 3 :
                $lbl = 'Education and training';
                break;
            case 4 :
                $lbl = 'Meals';
                break;
            case 5 :
                $lbl = 'Miscellaneous';
                break;
            case 6 :
                $lbl = 'Rent and utilities';
                break;
            case 7 :
                $lbl = 'Software license';
                break;
            case 8 :
                $lbl = 'Travel';
                break;
            default :
                $lbl = 'Unknown';
                break;
        }
        return $lbl;
    }

    public function getStatusDetail($id) {
        switch ($id) {
            case 1 :
                $lbl = 'Approved';
                break;
            case 2 :
                $lbl = 'Reimbursed';
                break;
            case 3 :
                $lbl = 'Cancelled';
                break;
            default :
                $lbl = 'Pending';
                break;
        }
        return $lbl;
    }

    public function getStatus() {
        return $this->getStatusDetail($this->status);
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

        $targetPath = "staff-expenses/" . $fileName;

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

    public function beforeDelete()
    {
        $this->deleteFile();
        return parent::beforeDelete(); // TODO: Change the autogenerated stub
    }


    public function deleteFile() {
        try {

            Yii::$app->resourceManager->delete("staff-expenses/" . $this->file);

        } catch (\Aws\S3\Exception\S3Exception $e) {

            Yii::error($e->getMessage(), 'staff');

            $this->addError('file', Yii::t('app', 'File not available to delete.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'staff');

            $this->addError('file', Yii::t('app', 'File not available to delete.'));

            return false;
        }
    }
}
