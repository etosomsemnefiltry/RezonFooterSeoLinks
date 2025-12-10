<?php declare(strict_types=1);

namespace RezonFooterSeoCategoryLinks\Controller\Admin;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class FooterCategoryLinksConfigController extends AbstractController
{
    private SystemConfigService $systemConfigService;
    private EntityRepository $systemConfigRepository;

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepository $systemConfigRepository
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->systemConfigRepository = $systemConfigRepository;
    }

    /**
     * @Route("/api/_action/rezon-footer-seo-category-links/save-config", name="api.action.rezon.footer.seo.category.links.save.config", methods={"POST"})
     */
    public function saveConfig(Request $request, Context $context): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $salesChannelId = $data['salesChannelId'] ?? null;

        if (!isset($data['config']) || !is_array($data['config'])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid config data'], 400);
        }

        try {
            $configurations = [];
            
            foreach ($data['config'] as $key => $value) {
                // Ищем существующую конфигурацию
                $criteria = new Criteria();
                $criteria->addFilter(new EqualsFilter('configurationKey', $key));
                if ($salesChannelId) {
                    $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
                } else {
                    $criteria->addFilter(new EqualsFilter('salesChannelId', null));
                }
                
                $existing = $this->systemConfigRepository->search($criteria, $context)->first();
                
                $configurations[] = [
                    'id' => $existing ? $existing->getId() : null,
                    'configurationKey' => $key,
                    'configurationValue' => $value,
                    'salesChannelId' => $salesChannelId,
                ];
            }
            
            // Сохраняем напрямую в БД, минуя валидацию домена
            $this->systemConfigRepository->upsert($configurations, $context);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @Route("/api/_action/rezon-footer-seo-category-links/get-config", name="api.action.rezon.footer.seo.category.links.get.config", methods={"GET"})
     */
    public function getConfig(Request $request): JsonResponse
    {
        $salesChannelId = $request->query->get('salesChannelId');

        $config = [];
        for ($i = 1; $i <= 5; $i++) {
            $value = $this->systemConfigService->get(
                'RezonFooterSeoCategoryLinks.config.block' . $i . 'Categories',
                $salesChannelId
            );
            $config['block' . $i . 'Categories'] = $value ?: [];
        }

        return new JsonResponse(['config' => $config]);
    }
}

