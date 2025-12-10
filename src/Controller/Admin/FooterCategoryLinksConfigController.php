<?php declare(strict_types=1);

namespace RezonFooterSeoCategoryLinks\Controller\Admin;

use Shopware\Core\Framework\Context;
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

    public function __construct(
        SystemConfigService $systemConfigService
    ) {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @Route("/_action/rezon-footer-seo-category-links/save-config", name="api.action.rezon.footer.seo.category.links.save.config", methods={"POST"})
     */
    public function saveConfig(Request $request, Context $context): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $salesChannelId = $data['salesChannelId'] ?? null;

        if (!isset($data['config']) || !is_array($data['config'])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid config data'], 400);
        }

        try {
            foreach ($data['config'] as $key => $value) {
                // Используем SystemConfigService для сохранения конфигурации
                $this->systemConfigService->set($key, $value, $salesChannelId);
            }

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @Route("/_action/rezon-footer-seo-category-links/get-config", name="api.action.rezon.footer.seo.category.links.get.config", methods={"GET"})
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

