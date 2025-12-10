<?php declare(strict_types=1);

namespace RezonFooterSeoCategoryLinks\Subscriber;

use RezonFooterSeoCategoryLinks\Service\FooterCategoryService;
use Shopware\Storefront\Page\Footer\FooterPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FooterSubscriber implements EventSubscriberInterface
{
    private FooterCategoryService $footerCategoryService;

    public function __construct(FooterCategoryService $footerCategoryService)
    {
        $this->footerCategoryService = $footerCategoryService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FooterPageLoadedEvent::class => 'onFooterPageLoaded',
            PageLoadedEvent::class => 'onPageLoaded',
        ];
    }

    public function onFooterPageLoaded(FooterPageLoadedEvent $event): void
    {
        $this->addCategoryBlocks($event);
    }

    public function onPageLoaded(PageLoadedEvent $event): void
    {
        $this->addCategoryBlocks($event);
    }

    private function addCategoryBlocks(PageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $context = $event->getContext();
        $salesChannelContext = $event->getSalesChannelContext();

        $blocks = $this->footerCategoryService->getFooterCategoryBlocks(
            $context,
            $salesChannelContext->getSalesChannelId()
        );

        $blocksForTwig = [];
        if (!empty($blocks)) {
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
                ];
            }
        }
        
        $page->addExtension('rezonFooterCategoryBlocks', $blocksForTwig);
    }
}

