<?php

declare(strict_types=1);

namespace app\assets;

use yii\bootstrap5\BootstrapPluginAsset;
use yii\web\AssetBundle;
use yii\web\YiiAsset;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/site.css',
    ];
    public $js = [
        'js/order-form.js',
    ];
    public $depends = [
        YiiAsset::class,
        BootstrapPluginAsset::class,
    ];
}
