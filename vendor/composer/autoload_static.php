<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitdda4790530d297fcd08d04c2f9652643
{
    public static $prefixLengthsPsr4 = array (
        'V' => 
        array (
            'Valitron\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Valitron\\' => 
        array (
            0 => __DIR__ . '/..' . '/vlucas/valitron/src/Valitron',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitdda4790530d297fcd08d04c2f9652643::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitdda4790530d297fcd08d04c2f9652643::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}