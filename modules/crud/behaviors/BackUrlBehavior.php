<?php
namespace app\modules\crud\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\Controller;

/**
 * This controller behavior is designed to get a back link to transfer the user
 * after performing some action.
 *
 * For example, after receiving and processing data on the object editing
 * form, return the user to the general list of objects from
 * which he came to this form.
 *
 * ```php
 *  class DefaultController extends Controller {
 *
 *      public function behaviors() {
 *          $behaviors = parent::behaviors();
 *          $behaviors['backUrl'] = app\modules\crud\behaviors\BackUrlBehavior::className();
 *          return $behaviors;
 *      }
 *
 *      public function actionEdit($id) {
 *          $model = Model::findOne($id);
 *
 *          if ($model->load(Yii::$app->request->post()) && $model->validate()) {
 *              $model->save();
 *              return $this->goBack();
 *          }
 *      }
 * }
 * ```
 */
class BackUrlBehavior extends Behavior {
    const BACK_URL_PARAM = 'back-url';

    public function getBackUrl() {
        $url = Yii::$app->request->get(self::BACK_URL_PARAM);
        if (null !== $url) {
            return $url;
        }

        $url = $this->getHostInfo() . Yii::$app->request->getUrl();
        $referer = Yii::$app->request->headers->get('referer');
        if (null !== $referer && $url != $referer) {
            return $referer;
        }

        $session = Yii::$app->session;
        if (!$session->isActive) {
            return;
        }

        $stack = $session->get($this->getSessionParam());
        if (null !== $stack) {
            return $stack['prev'];
        }
    }

    public static function addBackUrl($urlTo) {
        $urlTo[self::BACK_URL_PARAM] = Yii::$app->request->getUrl();
        return $urlTo;
    }

    public function events() {
        return [
            Controller::EVENT_AFTER_ACTION => 'saveUrl',
            Controller::EVENT_BEFORE_ACTION => 'setReturnUrl',
        ];
    }

    public function setReturnUrl() {
        $url = $this->getBackUrl();
        if (null !== $url) {
            Yii::$app->getUser()->setReturnUrl($url);
        }
    }

    public function saveUrl() {
        $session = Yii::$app->session;
        if (!$session->isActive) {
            return;
        }

        $url = Yii::$app->request->getUrl();
        $param = $this->getSessionParam();
        $stack = $session->get($param);
        if (null === $stack) {
            $stack = [];
            $stack['current'] = $url;
            $stack['prev'] = Yii::$app->request->headers->get('referer');
            $session->set($param, $stack);
        }

        if ($stack['current'] == $url) {
            return;
        }

        $stack['prev'] = $stack['current'];
        $stack['current'] = $url;
        $session->set($param, $stack);
    }

    /**
     * Workaround yii2 [[\yii\web\Request::getHostInfo()]] - with some server
     * settings, the 'Host' parameter in the request header comes
     * with a redundant port parameter, so for https
     * connections, the value is 'https://server.com:433'
     */
    protected function getHostInfo() {
        $hostInfo = Yii::$app->request->getHostInfo();

        $hostInfoComponents = parse_url($hostInfo);
        if (!isset($hostInfoComponents['port'])) {
            return $hostInfo;
        }

        $secure = Yii::$app->request->getIsSecureConnection();
        $post = $hostInfoComponents['port'];
        if (($post !== 80 && !$secure) || ($post !== 443 && $secure)) {
            return $hostInfo;
        }

        $postInfo = ':' . $post;
        return substr($hostInfo, 0, -strlen($postInfo));
    }

    protected function getSessionParam() {
        return '_' . self::class . '-' . self::BACK_URL_PARAM;
    }
}