<?php
namespace candidate\models;

use Yii;
use yii\base\Model;
use common\models\Candidate;

/**
 * Login form
 */
class LoginForm extends Model
{
    public $email;
    public $password;
    public $rememberMe = true;

    private $_candidate = false;


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
            $candidate = $this->getCandidate();
            if (!$candidate || !$candidate->validatePassword($this->password)) {
                $this->addError($attribute, 'Incorrect email or password.');
            }
        }
    }

    /**
     * Logs in a candidate using the provided email and password.
     *
     * @return boolean whether the candidate is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getCandidate(), $this->rememberMe ? 3600 * 24 * 30 : 0);
        } else {
            Yii::error("[Candidate Login Attempt Failed] ".$this->email, __METHOD__);
            return false;
        }
    }

    /**
     * Finds candidate by [[username]]
     *
     * @return Candidate|null
     */
    public function getCandidate()
    {
        if ($this->_candidate === false) {
            $this->_candidate = Candidate::findByEmail($this->email);
        }

        return $this->_candidate;
    }
}
