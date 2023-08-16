<?php
namespace Crud\models;

use Yii;

use Crud\helpers\Lang;

use app\models\User;

/**
 * LoginForm is the model behind the login form.
 *
 * @property-read User|null $user
 */
class LoginForm extends \yii\base\Model
{
    public $username;

    public $password;

    public $rememberMe = true;

    public $userClass = User::class;

    protected $user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            ['username', 'required', 'message' => Lang::t($this, 'Username cannot be blank')],
            ['password', 'required', 'message' => Lang::t($this, 'Password cannot be blank')],
            ['rememberMe', 'boolean'],
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
        if ($this->hasErrors()) {
            return;
        }

        $user = $this->getUser();
        if (!$user || !$user->validatePassword($this->password)) {
            $error = Lang::t($this, 'Incorrect username or password');
            $this->addError($attribute, $error);
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        if (!$this->validate()) {
            return false;
        }

        return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600 * 24 * 30 : 0);
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if (false !== $this->user) {
            return $this->user;
        }

        return $this->user = $this->userClass::findByUsername($this->username);
    }

    public function attributeLabels()
    {
        $list = [];
        foreach (array_merge($this->attributes(), ['loginSubmit']) as $attribute) {
            $list[$attribute] = Lang::t($this, $attribute);
        }

        return $list;
    }
}