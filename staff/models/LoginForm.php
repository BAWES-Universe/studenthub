<?php
namespace staff\models;

use Yii;
use yii\base\Model;

/**
 * Login form
 */
class LoginForm extends Model
{
    public $email;
    public $password;
    public $rememberMe = true;

    private $_staff = false;


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // email and password are both required
            [['email', 'password'], 'required'],
            // email must be an email
            ['email', 'email'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $staff = $this->getStaff();
            if (!$staff || !$staff->validatePassword($this->password)) {
                $this->addError($attribute, 'Incorrect email or password.');
            }
        }
    }

    /**
     * Logs in a staff using the provided email and password.
     *
     * @return boolean whether the staff is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getStaff(), $this->rememberMe ? 3600 * 24 * 30 : 0);
        } else {
            Yii::error("[Staff Login Attempt Failed] ".$this->email, __METHOD__);
            return false;
        }
    }

    /**
     * Finds staff by [[username]]
     *
     * @return Staff|null
     */
    public function getStaff()
    {
        if ($this->_staff === false) {
            $this->_staff = Staff::findByEmail($this->email);
        }

        return $this->_staff;
    }
}
