<?php

use yii\db\Migration;

/**
 * Class m211127_154105_add_story_table
 */
class m211127_154105_add_story_table extends Migration
{

  /**
   * {@inheritdoc}
   */
  public function safeUp()
  {

      $this->addColumn('staff', 'staff_hourly_rate', $this->double(10,3)->notNull()->defaultValue(1.6) );

      $this->addColumn('request', 'request_priority', $this->integer(50)->defaultValue(0));
      $this->addColumn('request', 'request_time_spent', $this->integer()->null());


      $tableOptions = null;
      if ($this->db->driverName === 'mysql') {
          // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
          $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

      }

      $this->createTable ('{{%story}}', [
          "story_uuid" => $this->char(60),
          "request_uuid" => $this->char(60)->notNull(),
          'staff_id' => $this->integer()->null(),
          'story_status' => $this->smallInteger()->notNull()->defaultValue(0),
          'story_time_spent' => $this->integer(),
          'story_created_at' => $this->dateTime (),
          'story_last_updated_at' => $this->dateTime (),
      ], $tableOptions);

      $this->addPrimaryKey('PK', 'story', 'story_uuid');

      $this->createIndex (
          'idx-story-request_uuid',
          'story',
          'request_uuid'
      );

      $this->addForeignKey (
          'fk-story-request_uuid',
          'story',
          'request_uuid',
          'request',
          'request_uuid',
          'RESTRICT',
          'RESTRICT'
      );

      // creates index for column `staff_id`
      $this->createIndex(
          'idx-story-staff_id',
          'story',
          'staff_id'
      );
      // add foreign key for table `staff`
      $this->addForeignKey(
          'fk-story-staff_id',
          'story',
          'staff_id',
          'staff',
          'staff_id',
          'CASCADE'
      );



      $this->createTable ('{{%story_activity}}', [
          "story_activity_uuid" => $this->char(60),
          "story_uuid" => $this->char(60)->notNull(),
          'staff_id' => $this->integer()->null(),
          'activity_time_spent' => $this->integer(),
          'activity_status' => $this->smallInteger()->notNull()->defaultValue(0),
          'activity_created_at' => $this->dateTime (),
          'activity_last_updated_at' => $this->dateTime ()
      ], $tableOptions);

      $this->addPrimaryKey('PK', 'story_activity', 'story_activity_uuid');


      $this->createIndex (
          'idx-story_activity-story_uuid',
          'story_activity',
          'story_uuid'
      );

      $this->addForeignKey (
          'fk-story_activity-story_uuid',
          'story_activity',
          'story_uuid',
          'story',
          'story_uuid',
          'RESTRICT',
          'RESTRICT'
      );

      // creates index for column `staff_id`
      $this->createIndex(
          'idx-story_activity-staff_id',
          'story_activity',
          'staff_id'
      );
      // add foreign key for table `staff`
      $this->addForeignKey(
          'fk-story_activity-staff_id',
          'story_activity',
          'staff_id',
          'staff',
          'staff_id',
          'CASCADE'
      );

  }

  /**
   * {@inheritdoc}
   */
  public function safeDown()
  {


      $this->dropColumn('staff','staff_hourly_rate');


      $this->dropForeignKey (
          'fk-story-staff_id',
          'story'
      );

      $this->dropIndex('idx-story-staff_id', 'story');


      $this->dropForeignKey (
          'fk-story-request_uuid',
          'story'
      );

      $this->dropIndex('idx-story-request_uuid', 'story');


      $this->dropForeignKey (
          'fk-story_activity-story_uuid',
          'story_activity'
      );
      $this->dropIndex('idx-story_activity-story_uuid', 'story_activity');



      $this->dropForeignKey (
          'fk-story_activity-staff_id',
          'story_activity'
      );
      $this->dropIndex('idx-story_activity-staff_id', 'story_activity');


      $this->dropTable ('{{%story_activity}}');
      $this->dropTable ('{{%story}}');
  }


}
