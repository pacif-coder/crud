<?php
namespace Crud\action;

use Yii;

use Crud\models\LoginForm;

/**
 * Login action
 *
 * @return Response|string
 */
class LoginAction extends \yii\base\Action
{
    public $loginForm = ['class' => LoginForm::class];

    public $loginTemplate = 'login.latte';

    /**
     * @return string The login .
     */
    public function run()
    {
        $app = Yii::$app;
        if (!$app->user->isGuest) {
            return $this->controller->goHome();
        }

        $loginForm = Yii::createObject($this->loginForm);

        $post = $app->request->post();
        if ($loginForm->load($post) && $loginForm->login()) {
            return $this->controller->goBack();
        }

        $loginForm->password = '';
        return $this->controller->render($this->loginTemplate, [
            'loginForm' => $loginForm,
        ]);
    }
}