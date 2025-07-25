<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "permission_sub_section".
 *
 * @property string $permission_sub_section_uuid
 * @property string $sub_section_name
 * @property string $sub_section_slug
 * @property string $permission_uuid
 * @property string $created_at
 *
 * @property PermissionSection $permissionUu
 * @property PermissionUser[] $permissionUsers
 */
class PermissionSubSection extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'permission_sub_section';
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'permission_sub_section_uuid',
                ],
                'value' => function() {
                    if (!$this->permission_sub_section_uuid)
                        $this->permission_sub_section_uuid = 'per_sb_sec' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->permission_sub_section_uuid;
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
            [['sub_section_name','permission_uuid','sub_section_slug'], 'required'],
            [['created_at'], 'safe'],
            [['permission_sub_section_uuid', 'permission_uuid'], 'string', 'max' => 60],
            [['sub_section_name'], 'string', 'max' => 255],
            [['permission_sub_section_uuid'], 'unique'],
            [['permission_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => PermissionSection::class, 'targetAttribute' => ['permission_uuid' => 'permission_uuid']],
            [['is_company_specific_permission'], 'boolean']
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'permission_sub_section_uuid' => 'Permission Sub Section Uuid',
            'sub_section_name' => 'Sub Section Name',
            'sub_section_slug' => 'Sub Section slug',
            'permission_uuid' => 'Permission Uuid',
            'created_at' => 'Created At',
            'is_company_specific_permission' => 'Is Company Specific Permission'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPermission()
    {
        return $this->hasOne(PermissionSection::className(), ['permission_uuid' => 'permission_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPermissionUsers()
    {
        return $this->hasMany(PermissionUser::className(), ['permission_sub_section_uuid' => 'permission_sub_section_uuid']);
    }
}
