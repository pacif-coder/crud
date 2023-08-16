<?php
namespace Crud\action;

use Yii;

/**
 * Logout action
 *
 * @return Response|string
 */
class LogoutAction extends \yii\base\Action
{
    /**
     * @return redirect
     */
    public function run()
    {
        Yii::$app->user->logout();
        return $this->controller->goHome();
    }
}