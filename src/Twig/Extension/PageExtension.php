<?php

namespace AmzsCMS\PageBundle\Twig\Extension;

use AmzsCMS\PageBundle\Utils\AssetUtil;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_path_page_cms_asset', [AssetUtil::class, 'getPrefixBundle']),
        ];
    }

}
