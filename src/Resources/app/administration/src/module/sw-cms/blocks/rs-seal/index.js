import { ELEMENT_NAME } from '../../../../constant/rs-seal.constant';

Shopware.Component.register('sw-cms-preview-rs-seal', () => import('./preview'));
Shopware.Component.register('sw-cms-block-rs-seal', () => import('./component'));

/**
 * Block with one slot holding the RatingStar seal element, so the seal can
 * be added to a shopping experience layout in one step.
 */
Shopware.Service('cmsService').registerCmsBlock({
    name: ELEMENT_NAME,
    label: 'rs-seal.block.label',
    category: 'commerce',
    component: 'sw-cms-block-rs-seal',
    previewComponent: 'sw-cms-preview-rs-seal',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: null,
        marginRight: null,
        sizingMode: 'boxed',
    },
    slots: {
        content: ELEMENT_NAME,
    },
});
