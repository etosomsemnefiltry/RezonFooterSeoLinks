<?php declare(strict_types=1);

namespace RezonFooterSeoCategoryLinks\Twig;

use RezonFooterSeoCategoryLinks\Service\FooterCategoryService;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FooterCategoryLinksExtension extends AbstractExtension
{
    private FooterCategoryService $footerCategoryService;

    public function __construct(FooterCategoryService $footerCategoryService)
    {
        $this->footerCategoryService = $footerCategoryService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getFooterCategoryBlocks', [$this, 'getFooterCategoryBlocks']),
        ];
    }

    public function getFooterCategoryBlocks(?SalesChannelContext $salesChannelContext = null): array
    {
        if (!$salesChannelContext) {
            return [];
        }

        $context = $salesChannelContext->getContext();
        $salesChannelId = $salesChannelContext->getSalesChannelId();

        $blocks = $this->footerCategoryService->getFooterCategoryBlocks($context, $salesChannelId);

        // Преобразуем CategoryCollection в массив для Twig
        $blocksForTwig = [];
        foreach ($blocks as $block) {
            $categoriesArray = [];
            if ($block['categories'] instanceof \Shopware\Core\Content\Category\CategoryCollection) {
                $categoriesArray = $block['categories']->getElements();
            } elseif (is_array($block['categories'])) {
                $categoriesArray = $block['categories'];
            }
            
            $blocksForTwig[] = [
                'title' => $block['title'] ?? '',
                'categories' => $categoriesArray,
                'keyword' => $block['keyword'] ?? '',
            ];
        }

        return $blocksForTwig;
    }
}

