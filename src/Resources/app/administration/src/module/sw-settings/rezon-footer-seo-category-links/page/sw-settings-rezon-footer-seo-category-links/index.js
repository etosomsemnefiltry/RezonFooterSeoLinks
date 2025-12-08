import template from './sw-settings-rezon-footer-seo-category-links.html.twig';
import './sw-settings-rezon-footer-seo-category-links.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('sw-settings-rezon-footer-seo-category-links', {
    template,

    inject: [
        'repositoryFactory',
        'systemConfigApiService',
    ],

    mixins: [
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            salesChannelId: null,
            categoryCollections: {
                block1: null,
                block2: null,
                block3: null,
                block4: null,
                block5: null,
            },
        };
    },

    computed: {
        categoryRepository() {
            return this.repositoryFactory.create('category');
        },

        categoryCriteria() {
            const criteria = new Criteria(1, 500);
            criteria.addAssociation('media');
            criteria.addAssociation('parent');
            criteria.addSorting(Criteria.sort('name', 'ASC', false));

            return criteria;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.loadConfig();
        },

        loadConfig() {
            this.isLoading = true;

            this.systemConfigApiService
                .getValues('RezonFooterSeoCategoryLinks.config', this.salesChannelId)
                .then((config) => {
                    this.loadCategoryCollections(config);
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        loadCategoryCollections(config) {
            for (let i = 1; i <= 5; i++) {
                const categoryIds = config[`block${i}Categories`] || [];

                if (categoryIds.length === 0) {
                    this.categoryCollections[`block${i}`] = this.createEmptyCollection();
                    continue;
                }

                const criteria = new Criteria(1, 100);
                criteria.setIds(categoryIds);
                criteria.addAssociation('media');
                criteria.addAssociation('parent');

                this.categoryRepository
                    .search(criteria, {
                        ...Shopware.Context.api,
                        inheritance: true,
                    })
                    .then((result) => {
                        this.$set(this.categoryCollections, `block${i}`, result);
                    })
                    .catch(() => {
                        this.categoryCollections[`block${i}`] = this.createEmptyCollection();
                    });
            }
        },

        createEmptyCollection() {
            return this.categoryRepository.create(
                Shopware.Context.api,
                this.categoryCriteria
            );
        },

        onSave() {
            this.isLoading = true;
            this.isSaveSuccessful = false;

            const config = {};

            for (let i = 1; i <= 5; i++) {
                const collection = this.categoryCollections[`block${i}`];
                let categoryIds = [];

                if (collection && collection.length > 0) {
                    // Ограничиваем до 5 категорий
                    categoryIds = collection.slice(0, 5).map((category) => category.id);
                }

                config[`RezonFooterSeoCategoryLinks.config.block${i}Categories`] = categoryIds;
            }

            this.systemConfigApiService
                .batchSave(config, this.salesChannelId)
                .then(() => {
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        title: this.$tc('rezon-footer-seo-category-links.general.saveSuccessTitle'),
                        message: this.$tc('rezon-footer-seo-category-links.general.saveSuccessMessage'),
                    });
                })
                .catch(() => {
                    this.createNotificationError({
                        title: this.$tc('rezon-footer-seo-category-links.general.saveErrorTitle'),
                        message: this.$tc('rezon-footer-seo-category-links.general.saveErrorMessage'),
                    });
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        onCategoryChange(blockNumber, collection) {
            // Ограничиваем до 5 категорий
            if (collection && collection.length > 5) {
                const limitedCollection = collection.slice(0, 5);
                this.$set(this.categoryCollections, `block${blockNumber}`, limitedCollection);
                this.createNotificationWarning({
                    title: this.$tc('rezon-footer-seo-category-links.general.limitReachedTitle'),
                    message: this.$tc('rezon-footer-seo-category-links.general.limitReachedMessage'),
                });
            }
        },

        getCategoryDisplayName(category) {
            if (!category) {
                return '';
            }

            if (category.translated && category.translated.name) {
                return category.translated.name;
            }

            return category.name || '';
        },

        getCategoryBreadcrumbLabel(category) {
            if (!category) {
                return '';
            }

            let breadcrumb = null;

            if (category.translated && category.translated.breadcrumb) {
                breadcrumb = category.translated.breadcrumb;
            } else if (category.breadcrumb) {
                breadcrumb = category.breadcrumb;
            }

            if (!breadcrumb) {
                return '';
            }

            let breadcrumbNames = [];

            if (Array.isArray(breadcrumb)) {
                breadcrumbNames = breadcrumb.filter((item) => item && String(item).trim()).map((item) => String(item).trim());
            } else if (typeof breadcrumb === 'string') {
                breadcrumbNames = breadcrumb.split(' > ').filter(Boolean).map((item) => item.trim());
            }

            if (breadcrumbNames.length <= 1) {
                return '';
            }

            return breadcrumbNames.slice(0, -1).join(' > ');
        },

        fetchCategories(searchTerm = '', limit = 25) {
            const criteria = new Criteria(1, limit);
            criteria.addAssociation('media');
            criteria.addAssociation('parent');
            criteria.addSorting(Criteria.sort('name', 'ASC', false));

            if (searchTerm) {
                criteria.setTerm(searchTerm);
            }

            return this.categoryRepository
                .search(criteria, {
                    ...Shopware.Context.api,
                    inheritance: true,
                })
                .then((result) => ({
                    data: result,
                    total: result.total,
                }));
        },

        isSelected(categoryId, collection) {
            if (!collection) {
                return false;
            }

            return collection.has(categoryId);
        },

        saveFinish() {
            this.isSaveSuccessful = false;
        },
    },
});

