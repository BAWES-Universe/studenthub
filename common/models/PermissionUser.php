<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "permission_user".
 *
 * @property string $permission_user_uuid
 * @property int $admin_id
 * @property int $staff_id
 * @property string $permission_sub_section_uuid
 * @property string $created_at
 *
 * @property Admin $admin
 * @property PermissionSubSection $permissionSubSectionUu
 * @property Staff $staff
 */
class PermissionUser extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'permission_user';
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'permission_user_uuid',
                ],
                'value' => function() {
                    if (!$this->permission_user_uuid)
                        $this->permission_user_uuid = 'per_user' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->permission_user_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => new Expression('NOW()'),
            ],
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['permission_sub_section_uuid'], 'required'],
            [['admin_id', 'staff_id'], 'integer'],
            [['created_at'], 'safe'],
            [['permission_user_uuid', 'permission_sub_section_uuid'], 'string', 'max' => 60],
            [['permission_user_uuid'], 'unique'],
            [['admin_id'], 'exist', 'skipOnError' => true, 'targetClass' => Admin::class, 'targetAttribute' => ['admin_id' => 'admin_id']],
            [['permission_sub_section_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => PermissionSubSection::class, 'targetAttribute' => ['permission_sub_section_uuid' => 'permission_sub_section_uuid']],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['staff_id' => 'staff_id']],
            [['companies'], 'each', 'skipOnError' => true,'rule' => ['exist', 'targetClass' => Company::class, 'targetAttribute' => 'company_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'permission_user_uuid' => 'Permission User Uuid',
            'admin_id' => 'Admin ID',
            'staff_id' => 'Staff ID',
            'permission_sub_section_uuid' => 'Permission Sub Section Uuid',
            'created_at' => 'Created At',
            'companies' => 'Companies',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdmin()
    {
        return $this->hasOne(Admin::className(), ['admin_id' => 'admin_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPermissionSubSectionUu()
    {
        return $this->hasOne(PermissionSubSection::className(), ['permission_sub_section_uuid' => 'permission_sub_section_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'staff_id']);
    }
}
