<?php declare(strict_types=1);

namespace RezonFooterSeoCategoryLinks\Service;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class FooterCategoryService
{
    private SystemConfigService $systemConfigService;
    private EntityRepository $categoryRepository;

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepository $categoryRepository
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Получить все блоки категорий для футера
     *
     * @return array<int, array{title: string, categories: CategoryCollection}>
     */
    public function getFooterCategoryBlocks(Context $context, ?string $salesChannelId = null): array
    {
        $blocks = [];

        for ($i = 1; $i <= 5; $i++) {
            $configKey = 'RezonFooterSeoCategoryLinks.config.block' . $i . 'Categories';
            
            // Сначала пробуем настройки конкретного канала, затем глобальные
            $categoryIds = $this->systemConfigService->get($configKey, $salesChannelId);
            if (empty($categoryIds) || !is_array($categoryIds)) {
                $categoryIds = $this->systemConfigService->get($configKey);
            }
            
            // Возможен вариант, что данные сохранены как JSON-строка
            if (is_string($categoryIds)) {
                $decoded = json_decode($categoryIds, true);
                if (is_array($decoded)) {
                    $categoryIds = $decoded;
                } else {
                    $categoryIds = null;
                }
            }

            if (empty($categoryIds) || !is_array($categoryIds)) {
                continue;
            }

            $categories = $this->loadCategories($categoryIds, $context);

            if ($categories->count() === 0) {
                continue;
            }

            // Формируем заголовок из breadcrumbs первой категории
            $title = $this->generateBlockTitle($categories->first());

            // Загружаем ключевое слово для блока
            $keywordKey = 'RezonFooterSeoCategoryLinks.config.block' . $i . 'Keyword';
            $keyword = $this->systemConfigService->get($keywordKey, $salesChannelId);
            if (empty($keyword)) {
                $keyword = $this->systemConfigService->get($keywordKey);
            }

            $blocks[] = [
                'title' => $title,
                'categories' => $categories,
                'keyword' => $keyword ?: '',
            ];
        }

        return $blocks;
    }

    private function loadCategories(array $categoryIds, Context $context): CategoryCollection
    {
        if (empty($categoryIds)) {
            return new CategoryCollection();
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $categoryIds));
        $criteria->addAssociation('seoUrls');
        $criteria->addAssociation('parent');
        $criteria->setLimit(count($categoryIds));

        $result = $this->categoryRepository->search($criteria, $context);

        // Сохраняем порядок из конфигурации
        $orderedCategories = new CategoryCollection();
        foreach ($categoryIds as $categoryId) {
            $category = $result->get($categoryId);
            if ($category) {
                $orderedCategories->add($category);
            }
        }

        return $orderedCategories;
    }

    private function generateBlockTitle(?CategoryEntity $category): string
    {
        if (!$category) {
            return '';
        }

        $categoryName = $category->getTranslated()['name'] ?? $category->getName() ?? '';
        
        // Получаем breadcrumb
        $breadcrumb = $category->getTranslated()['breadcrumb'] ?? $category->getBreadcrumb() ?? [];
        
        if (is_string($breadcrumb)) {
            $breadcrumb = explode(' > ', $breadcrumb);
        }

        if (is_array($breadcrumb) && count($breadcrumb) > 1) {
            // Берем предпоследний элемент (главная категория) и текущую категорию
            $mainCategory = $breadcrumb[count($breadcrumb) - 2] ?? '';
            if ($mainCategory && $mainCategory !== $categoryName) {
                return $mainCategory . ' - ' . $categoryName;
            }
        }

        // Если breadcrumb нет, проверяем родительскую категорию
        $parent = $category->getParent();
        if ($parent) {
            $parentName = $parent->getTranslated()['name'] ?? $parent->getName() ?? '';
            if ($parentName && $parentName !== $categoryName) {
                return $parentName . ' - ' . $categoryName;
            }
        }

        return $categoryName;
    }
}

