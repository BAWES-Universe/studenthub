<?php
namespace company\models;
/**
 * This is the model class for table "Candidate".
 * It extends from \common\models\Candidate but with custom functionality for this application module
 */
class Candidate extends \common\models\Candidate {

    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['candidate_auth_key'],
        $fields['candidate_password_hash'],
        $fields['candidate_password_reset_token'],
        $fields['candidate_created_at'],
        $fields['candidate_updated_at'],
        $fields['candidate_hourly_rate'],
        $fields['bank_id'],
        $fields['candidate_iban'],
        $fields['candidate_uid'],
        $fields['bank_account_name'],
        $fields['approved'],
        $fields['deleted'],
        $fields['candidate_status'],
        $fields['employee_id']
        );
        
        // Clear bank info from array
        $fields['bank'] = function() {return [];};
        
        return $fields;
    }    
    
    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'store',
            'company',
            'university',
            'country',
            'bank'
        ];
    }
}
