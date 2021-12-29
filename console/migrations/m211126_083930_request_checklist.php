<?php

use yii\db\Migration;

/**
 * Class m211126_083930_request_checklist
 */
class m211126_083930_request_checklist extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%request_checklist}}', [
            'request_checklist_uuid' => $this->char(60)->notNull(),
            'status_name' => $this->string(100)->notNull (),
            'status_name_ar' => $this->string(100),
            'is_require' => $this->boolean (),
            'sort_order' => $this->integer (11),
            'created_at'=> $this->dateTime(),
            'updated_at'=> $this->dateTime()
        ],$tableOptions);

        $this->addPrimaryKey('PK', 'request_checklist', 'request_checklist_uuid');

        $this->addColumn ('note', 'request_checklist_uuid',
            $this->char(60)->after('request_uuid')->null());

        // creates index for column `request_checklist_uuid`
        $this->createIndex(
            'idx-note-request_checklist_uuid',
            'note',
            'request_checklist_uuid'
        );

        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();

        // add foreign key for table `note`
        $this->addForeignKey(
            'fk-note-request_checklist_uuid',
            'note',
            'request_checklist_uuid',
            'request_checklist',
            'request_checklist_uuid',
            'SET NULL'
        );

        Yii::$app->db->createCommand()->batchInsert('{{%request_checklist}}', [
            'request_checklist_uuid', 'status_name', 'status_name_ar', 'is_require', 'created_at', 'updated_at'
        ], [
            [
                'request_checklist_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar (),
                'Shortlisted all the candidates',
                'القائمة المختصرة لجميع المرشحين',
                true,
                date('Y-m-d'),
                date('Y-m-d')
            ],
            [
                'request_checklist_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar (),
                'Invited all the candidates for client request',
                "دعوة جميع المرشحين لطلب العميل",
                true,
                date('Y-m-d'),
                date('Y-m-d')
            ],
            [
                'request_checklist_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar (),
                'Shared candidate CV’s to clients',
                "شارك السيرة الذاتية للمرشح للعملاء",
                true,
                date('Y-m-d'),
                date('Y-m-d')
            ],
            [
                'request_checklist_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar (),
                'Received candidate feedback from clients',
                "تلقي ملاحظات المرشح من العملاء",
                true,
                date('Y-m-d'),
                date('Y-m-d')
            ],
            [
                'request_checklist_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar (),
                'Interview schedule received',
                "تم استلام جدول المقابلة",
                true,
                date('Y-m-d'),
                date('Y-m-d')
            ],
            [
                'request_checklist_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar (),
                'Shared interview schedule with candidates',
                "جدول المقابلة المشترك مع المرشحين",
                true,
                date('Y-m-d'),
                date('Y-m-d')
            ],
            [
                'request_checklist_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar (),
                'Collected Interview results & Date of Joining from clients',
                "تم جمع نتائج المقابلة وتاريخ الانضمام من العملاء",
                true,
                date('Y-m-d'),
                date('Y-m-d')
            ],
            [
                'request_checklist_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar (),
                'Informed joining info with the candidates',
                "معلومات الانضمام المستنيرة مع المرشحين",
                true,
                date('Y-m-d'),
                date('Y-m-d')
            ],
            [
                'request_checklist_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar (),
                'Assigned all the candidates to store',
                "خصص جميع المرشحين للتخزين",
                true,
                date('Y-m-d'),
                date('Y-m-d')
            ],
            [
                'request_checklist_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar (),
                'Issued ID Card for all candidates',
                "إصدار بطاقة الهوية لجميع المرشحين",
                true,
                date('Y-m-d'),
                date('Y-m-d')
            ],
            [
                'request_checklist_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar (),
                'Health and Medical Test (For Kuwaities Waiters, Hostess, Barista, Restaurants)',
                "الفحص الطبي والصحي (للنادل في الكويت ، مضيفة ، باريستا ، مطاعم)",
                false,
                date('Y-m-d'),
                date('Y-m-d')
            ]
        ])->execute ();

        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();

        $this->dropTable ('{{%request_checklist}}');

        $this->dropForeignKey (
            'fk-note-request_checklist_uuid',
            'note'
        );

        $this->dropIndex (
            'idx-note-request_checklist_uuid',
            'note'
        );

        $this->dropColumn ('note', 'request_checklist_uuid');

        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m211126_083930_request_checklist cannot be reverted.\n";

        return false;
    }
    */
}
