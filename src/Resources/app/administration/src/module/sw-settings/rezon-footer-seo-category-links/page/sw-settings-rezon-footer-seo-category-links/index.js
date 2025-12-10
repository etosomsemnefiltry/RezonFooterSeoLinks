import template from './sw-settings-rezon-footer-seo-category-links.html.twig';
import './sw-settings-rezon-footer-seo-category-links.scss';

const { Component, Mixin } = Shopware;
const { Criteria, EntityCollection } = Shopware.Data;

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

    watch: {
        'categoryCollections.block1': {
            handler() {
                this.updateBlockConfig(1);
            },
            deep: true,
        },
        'categoryCollections.block2': {
            handler() {
                this.updateBlockConfig(2);
            },
            deep: true,
        },
        'categoryCollections.block3': {
            handler() {
                this.updateBlockConfig(3);
            },
            deep: true,
        },
        'categoryCollections.block4': {
            handler() {
                this.updateBlockConfig(4);
            },
            deep: true,
        },
        'categoryCollections.block5': {
            handler() {
                this.updateBlockConfig(5);
            },
            deep: true,
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

            // Используем systemConfigApiService для загрузки (работает без проблем)
            this.systemConfigApiService
                .getValues('RezonFooterSeoCategoryLinks.config', this.salesChannelId)
                .then((config) => {
                    this.loadCategoryCollections(config);
                })
                .catch(() => {
                    this.loadCategoryCollections({});
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        loadCategoryCollections(config) {
            for (let i = 1; i <= 5; i++) {
                this.initCategoryCollection(i, config[`block${i}Categories`] || []);
            }
        },

        initCategoryCollection(blockNumber, categoryIds) {
            const blockKey = `block${blockNumber}`;

            if (!categoryIds || !Array.isArray(categoryIds) || categoryIds.length === 0) {
                this.categoryCollections[blockKey] = new EntityCollection(
                    this.categoryRepository.route,
                    this.categoryRepository.schema.entity,
                    Shopware.Context.api,
                    this.categoryCriteria,
                );
                return;
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
                    this.categoryCollections[blockKey] = result;
                })
                .catch(() => {
                    this.categoryCollections[blockKey] = new EntityCollection(
                        this.categoryRepository.route,
                        this.categoryRepository.schema.entity,
                        Shopware.Context.api,
                        this.categoryCriteria,
                    );
                });
        },

        updateBlockConfig(blockNumber) {
            const collection = this.categoryCollections[`block${blockNumber}`];
            // Метод нужен для watch, но сохранение делаем в onSave
        },

        createEmptyCollection() {
            return new EntityCollection(
                this.categoryRepository.route,
                this.categoryRepository.schema.entity,
                Shopware.Context.api,
                this.categoryCriteria,
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
                    categoryIds = collection.map((category) => category.id).slice(0, 5);
                }

                config[`RezonFooterSeoCategoryLinks.config.block${i}Categories`] = categoryIds;
            }

            // Сохраняем через наш кастомный endpoint, минуя валидацию домена
            const apiPath = Shopware.Context.api.apiPath || '/api';
            const url = `${apiPath}/_action/rezon-footer-seo-category-links/save-config`;
            const payload = {
                config: config,
                salesChannelId: this.salesChannelId,
            };

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'sw-context-token': Shopware.Context.api.authToken.accessToken,
                },
                body: JSON.stringify(payload),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        this.isSaveSuccessful = true;
                        this.createNotificationSuccess({
                            title: this.$tc('rezon-footer-seo-category-links.general.saveSuccessTitle'),
                            message: this.$tc('rezon-footer-seo-category-links.general.saveSuccessMessage'),
                        });
                    } else {
                        throw new Error(data.message || 'Save failed');
                    }
                })
                .catch((error) => {
                    console.error('Save error:', error);
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
                const limitedCollection = new EntityCollection(
                    this.categoryRepository.route,
                    this.categoryRepository.schema.entity,
                    Shopware.Context.api,
                    this.categoryCriteria,
                );
                
                for (let i = 0; i < 5; i++) {
                    if (collection[i]) {
                        limitedCollection.add(collection[i]);
                    }
                }
                
                this.categoryCollections[`block${blockNumber}`] = limitedCollection;
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
            } 
            else if (category.breadcrumb) {
                breadcrumb = category.breadcrumb;
            }
            else if (category.attributes && category.attributes.breadcrumb) {
                breadcrumb = category.attributes.breadcrumb;
            }
            else if (category.attributes && category.attributes.translated && category.attributes.translated.breadcrumb) {
                breadcrumb = category.attributes.translated.breadcrumb;
            }
            else if (category.parent) {
                const parentName = category.parent.translated?.name || category.parent.name || '';
                const categoryName = this.getCategoryDisplayName(category);
                if (parentName && parentName !== categoryName) {
                    if (category.parent.parent) {
                        const grandParentName = category.parent.parent.translated?.name || category.parent.parent.name || '';
                        if (grandParentName && grandParentName !== parentName) {
                            return `${grandParentName} > ${parentName}`;
                        }
                    }
                    return parentName;
                }
            }

            if (!breadcrumb) {
                return '';
            }

            let breadcrumbNames = [];
            
            if (Array.isArray(breadcrumb)) {
                breadcrumbNames = breadcrumb.filter((item) => item && String(item).trim()).map((item) => String(item).trim());
            } else if (typeof breadcrumb === 'object' && breadcrumb !== null) {
                breadcrumbNames = Object.values(breadcrumb).filter((item) => item && String(item).trim()).map((item) => String(item).trim());
            } else if (typeof breadcrumb === 'string') {
                breadcrumbNames = breadcrumb.split(' > ').filter(Boolean).map((item) => item.trim());
            }

            if (breadcrumbNames.length <= 1) {
                return '';
            }

            return breadcrumbNames.slice(0, -1).join(' > ');
        },

        isSelected(categoryId, collection) {
            if (!collection) {
                return false;
            }

            return collection.has(categoryId);
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

        saveFinish() {
            this.isSaveSuccessful = false;
        },
    },
});
