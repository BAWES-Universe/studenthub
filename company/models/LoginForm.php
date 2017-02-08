<?php
namespace company\models;

use Yii;
use yii\base\Model;
use common\models\Company;

/**
 * Login form
 */
class LoginForm extends Model
{
    public $email;
    public $password;
    public $rememberMe = true;

    private $_company = false;


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
            $company = $this->getCompany();
            if (!$company || !$company->validatePassword($this->password)) {
                $this->addError($attribute, 'Incorrect email or password.');
            }
        }
    }

    /**
     * Logs in a company using the provided email and password.
     *
     * @return boolean whether the company is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getCompany(), $this->rememberMe ? 3600 * 24 * 30 : 0);
        } else {
            Yii::error("[Company Login Attempt Failed] ".$this->email, __METHOD__);
            return false;
        }
    }

    /**
     * Finds company by [[username]]
     *
     * @return Company|null
     */
    public function getCompany()
    {
        if ($this->_company === false) {
            $this->_company = Company::findByEmail($this->email);
        }

        return $this->_company;
    }
}
