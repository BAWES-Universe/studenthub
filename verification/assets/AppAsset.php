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
        'css/stack-interface.css',
        'css/socicon.css',
        'css/lightbox.min.css',
        'css/flickity.css',
        'css/theme.css',
        'css/custom.css',
        'https://fonts.googleapis.com/css?family=Open+Sans:200,300,400,400i,500,600,700%7CMerriweather:300,300i%7CMaterial+Icons'
    ];
    public $js = [
        'js/flickity.min.js',
        'js/easypiechart.min.js',
        'js/parallax.js',
        'js/typed.min.js',
        'js/datepicker.js',
        'js/isotope.min.js',
        'js/ytplayer.min.js',
        'js/lightbox.min.js',
        'js/granim.min.js',
        'js/countdown.min.js',
        'js/twitterfetcher.min.js',
        'js/spectragram.min.js',
        'js/smooth-scroll.min.js',
        'js/scripts.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
