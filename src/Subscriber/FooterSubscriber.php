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
        // Добавляем блоки категорий на все страницы, где есть футер
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

        if (!empty($blocks)) {
            $page->addExtension('rezonFooterCategoryBlocks', new \ArrayObject($blocks));
        }
    }
}

