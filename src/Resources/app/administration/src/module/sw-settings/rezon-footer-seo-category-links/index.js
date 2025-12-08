import './page/sw-settings-rezon-footer-seo-category-links';

const { Module } = Shopware;

Module.register('sw-settings-rezon-footer-seo-category-links', {
    type: 'core',
    name: 'settings-rezon-footer-seo-category-links',
    title: 'rezon-footer-seo-category-links.general.mainMenuItemGeneral',
    description: 'rezon-footer-seo-category-links.general.descriptionTextModule',
    color: '#9AA8B5',
    icon: 'regular-cog',
    entity: 'rezon_footer_seo_category_links',

    routes: {
        index: {
            component: 'sw-settings-rezon-footer-seo-category-links',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index',
                privilege: 'system.system_config',
            },
        },
    },
});

