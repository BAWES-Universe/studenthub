<?php

namespace candidate\models;


class Contact extends \common\models\Contact
{
    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset(
            $fields['contact_password_hash'],
            $fields['contact_receive_email'],
            $fields['contact_receive_notification'],
            $fields['contact_auth_key'],
            $fields['contact_password_reset_token']
        );

        return $fields;
    }
}
