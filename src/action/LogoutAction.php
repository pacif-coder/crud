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
        $returnUrl = Yii::$app->user->getReturnUrl();

        Yii::$app->session->destroy();
        Yii::$app->user->logout();

        return $this->controller->redirect($returnUrl);
    }
}