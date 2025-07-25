<?php

use yii\db\Migration;

/**
 * Class m250724_111443_add_company_candidate_permissions
 */
class m250724_111443_add_company_candidate_permissions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Create UUIDs
        $companyUUID = Yii::$app->db->createCommand("SELECT UUID()")->queryScalar();
        $candidateUUID = Yii::$app->db->createCommand("SELECT UUID()")->queryScalar();

        // Insert 'Company' section
        $this->insert('permission_section', [
            'permission_uuid' => $companyUUID,
            'section_name' => 'Company',
            'created_at' => new \yii\db\Expression('NOW()'),
            'is_company_specific_permission' => 1
        ]);

        // Insert Company sub-sections
        $companySubSections = [
            ['Company Stats', 'company-stats'],
            ['Company Contracts', 'company-contracts'],
            ['Company Transfers', 'company-transfers'],
            ['Company Contact Login', 'company-contact-login'],
            ['Company Notes', 'company-notes'],
            ['Company Activity', 'company-activity'],
            ['Company Impersonate', 'company-impersonate'],
        ];

        foreach ($companySubSections as [$name, $slug]) {
            $this->insert('permission_sub_section', [
                'permission_sub_section_uuid' => Yii::$app->db->createCommand("SELECT UUID()")->queryScalar(),
                'sub_section_name' => $name,
                'sub_section_slug' => $slug,
                'permission_uuid' => $companyUUID,
                'created_at' => new \yii\db\Expression('NOW()')
            ]);
        }

        // Insert 'Candidate' section
        $this->insert('permission_section', [
            'permission_uuid' => $candidateUUID,
            'section_name' => 'Candidate',
            'created_at' => new \yii\db\Expression('NOW()'),
            'is_company_specific_permission' => 1
        ]);

        // Insert Candidate sub-section
        $this->insert('permission_sub_section', [
            'permission_sub_section_uuid' => Yii::$app->db->createCommand("SELECT UUID()")->queryScalar(),
            'sub_section_name' => 'Candidate Financials',
            'sub_section_slug' => 'candidate-financials',
            'permission_uuid' => $candidateUUID,
            'created_at' => new \yii\db\Expression('NOW()')
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('permission_sub_section', ['sub_section_slug' => [
            'company-stats', 'company-contracts', 'company-transfers', 'company-contact-login',
            'company-notes', 'company-activity', 'company-impersonate', 'candidate-financials'
        ]]);

        $this->delete('permission_section', ['section_name' => ['Company', 'Candidate']]);
    
    }
}
