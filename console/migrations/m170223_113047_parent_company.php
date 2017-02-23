<?php

use yii\db\Migration;

class m170223_113047_parent_company extends Migration
{
    public function up()
    {
        
        $this->addColumn('company', 'parent_company_id', $this->integer(11)->after('company_id'));

        $this->createIndex(
            'idx-company-parent_company_id',
            'company',
            'parent_company_id'
        );

        $this->addForeignKey(
            'fk-company-parent_company_id',
            'company',
            'parent_company_id',
            'company',
            'company_id',
            'CASCADE'
        );
    }

    public function down()
    {
        $this->dropIndex(
            'idx-company-parent_company_id', 
            'company'
        );

        $this->dropForeignKey(
            'fk-company-parent_company_id',
            'company'
        );

        $this->dropColumn('company', 'parent_company_id');
    }
}
