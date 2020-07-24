<?php

namespace verification\assets;

use yii\web\AssetBundle;

/**
 * Main verification application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/bootstrap.css',
        'css/theme.css',
        'css/custom.css',
        'https://fonts.googleapis.com/css?family=Open+Sans:200,300,400,400i,500,600,700%7CMerriweather:300,300i%7CMaterial+Icons'
    ];
    public $js = [
    ];
    public $depends = [
    ];
}
