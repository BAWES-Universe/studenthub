<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "permission_section".
 *
 * @property string $permission_uuid
 * @property string $section_name
 * @property string $created_at
 *
 * @property PermissionSubSection[] $permissionSubSections
 */
class PermissionSection extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'permission_section';
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'permission_uuid',
                ],
                'value' => function() {
                    if (!$this->permission_uuid)
                        $this->permission_uuid = 'per_sec' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->permission_uuid;
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

    public function extraFields()
    {
        return [
            'permissionSubSections'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['section_name'], 'required'],
            [['created_at'], 'safe'],
            [['permission_uuid'], 'string', 'max' => 60],
            [['section_name'], 'string', 'max' => 255],
            [['permission_uuid'], 'unique'],
            [['companies'], 'default', 'value' => []],
            [['companies'], 'each', 'skipOnError' => true,'rule' => ['exist', 'targetClass' => Company::class, 'targetAttribute' => 'company_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'permission_uuid' => 'Permission Uuid',
            'section_name' => 'Section Name',
            'companies' => 'Companies',
            'created_at' => 'Created At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPermissionSubSections()
    {
        return $this->hasMany(PermissionSubSection::className(), ['permission_uuid' => 'permission_uuid']);
    }
}
