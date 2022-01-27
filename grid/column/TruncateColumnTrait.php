<?php
namespace app\modules\crud\grid\column;

use yii\helpers\Html;

/**
 *
 * @property string|null $truncateClass
 */
trait TruncateColumnTrait
{
    protected function truncateContent($str)
    {
        if (!$this->truncateClass) {
            return $str;
        }

        $attrs = ['class' => $this->truncateClass];
        $attrs['title'] = trim(strip_tags($str));

        return Html::tag('div', $str, $attrs);
    }
}