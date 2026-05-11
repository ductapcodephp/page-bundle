<?php

namespace AmzsCMS\PageBundle\Utils;

class AssetUtil
{
    private function __construct()
    {
    }

    public static function getPrefixBundle(): string
    {
        return 'bundles/amzspage/';
    }
}