<?php
if (isset($builder) && ($buttons = $builder->extraControlsByPlace('title'))) {
    echo $buttons;
}