<?php
namespace Crud\models;

use Yii;
use yii\helpers\Url;
use yii\base\Model;

use Crud\helpers\Lang;
use Crud\behaviors\BackUrlBehavior;

/**
 * LoginForm is the model behind the login form.
 *
 * @property-read User|null $user
 */
class LoginForm extends Model
{
    public $username;

    public $password;

    public $rememberMe = true;

    public $userIdentityClass;

    public $addBackUrl = false;

    protected $user = false;

    public function behaviors(): array
    {
        // event in BackUrlBehavior not triggered on model event
        // use only for method 'getBackUrl'
        $behaviors = parent::behaviors();
        $behaviors['backUrl'] = BackUrlBehavior::class;

        return $behaviors;
    }

    public function init()
    {
        if (!$this->userIdentityClass) {
            $this->userIdentityClass = Yii::$app->user->identityClass;
        }

        parent::init();
    }

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

        $error = Lang::t($this, 'Incorrect username or password');
        $user = $this->getUser();
        if (!$user) {
            $this->addError($attribute, $error);
            return;
        }

        if ($user->validatePassword($this->password)) {
            return;
        }

        if ($user instanceof Model && $user->hasErrors()) {
            $error = implode("\n", $user->getErrorSummary(true));
        }
        $this->addError($attribute, $error);
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

    public function getAction()
    {
        $params = [''];
        if ($this->addBackUrl) {
            $params[BackUrlBehavior::BACK_URL_PARAM] = $this->getBackUrl();
        }

        return Url::to($params);
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

        $сlass = $this->userIdentityClass;
        return $this->user = $сlass::findByUsername($this->username);
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