<?php
namespace Crud\behaviors;

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
 *          $behaviors['backUrl'] = BackUrlBehavior::class;
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
class BackUrlBehavior extends Behavior
{
    public $ignoreScheme = true;

    const BACK_URL_PARAM = 'back-url';

    public function getBackUrl()
    {
        $url = Yii::$app->request->get(self::BACK_URL_PARAM);
        if (null !== $url) {
            return $url;
        }
    }

    public static function addBackUrl($urlTo, $hash = null)
    {
        $request = Yii::$app->request;

        // get current url without BACK_URL_PARAM in request params
        $get = [];
        $query = parse_url($request->getUrl(), PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $get);
        }

        if (isset($get[self::BACK_URL_PARAM])) {
            unset($get[self::BACK_URL_PARAM]);
        }

        $currentUrl = '/' . $request->getPathInfo();
        if ($get) {
            $currentUrl .= '?' . http_build_query($get);
        }

        if (null !== $hash) {
            $currentUrl .= "#{$hash}";
        }

        // is params case
        if (is_array($urlTo)) {
            $urlTo[self::BACK_URL_PARAM] = $currentUrl;
            return $urlTo;
        }

        if (is_string($urlTo)) {
            $parts = parse_url($urlTo);

            $urlTo = [];
            if (isset($parts['query'])) {
                parse_str($parts['query'], $urlTo);
            }

            $urlTo[self::BACK_URL_PARAM] = $currentUrl;

            $url = '';
            if ($parts['path']) {
                $url .= $parts['path'];
            }

            if ($urlTo) {
                $url .= '?' . http_build_query($urlTo);
            }

            return $url;
        }
    }

    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'setReturnUrl',
        ];
    }

    public function setReturnUrl()
    {
        $url = $this->getBackUrl();
        if (null !== $url) {
            Yii::$app->user->setReturnUrl($url);
        }
    }

    /**
     * Workaround yii2 [[\yii\web\Request::getHostInfo()]] - with some server
     * settings, the 'Host' parameter in the request header comes
     * with a redundant port parameter, so for https
     * connections, the value is 'https://server.com:433'
     */
    protected function getHostInfo()
    {
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
}