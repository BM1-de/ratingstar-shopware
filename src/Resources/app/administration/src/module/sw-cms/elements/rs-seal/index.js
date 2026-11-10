import { ELEMENT_NAME, defaultElementConfig } from '../../../../constant/rs-seal.constant';

Shopware.Component.register('sw-cms-el-preview-rs-seal', () => import('./preview'));
Shopware.Component.register('sw-cms-el-config-rs-seal', () => import('./config'));
Shopware.Component.register('sw-cms-el-rs-seal', () => import('./component'));

/**
 * Shopping experience element "RatingStar seal". The storefront renders it
 * with storefront/element/cms-element-rs-seal.html.twig.
 */
Shopware.Service('cmsService').registerCmsElement({
    name: ELEMENT_NAME,
    label: 'rs-seal.element.label',
    component: 'sw-cms-el-rs-seal',
    configComponent: 'sw-cms-el-config-rs-seal',
    previewComponent: 'sw-cms-el-preview-rs-seal',
    defaultConfig: defaultElementConfig(),
});
