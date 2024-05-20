<?php
namespace Crud\helpers;

use Yii;


/**
 *
 */
class Url extends \yii\helpers\Url
{
    public static function addParams($params, $hash = null)
    {
        $request = Yii::$app->request;

        // get current url
        $get = [];
        $query = parse_url($request->getUrl(), PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $get);
        }

        foreach ($params as $key => $value) {
            $get[$key] = $value;
        }

        $url = '/' . $request->getPathInfo();
        if ($get) {
            $url .= '?' . http_build_query($get);
        }

        if (null !== $hash) {
            $url .= "#{$hash}";
        }

        return $url;
    }
}