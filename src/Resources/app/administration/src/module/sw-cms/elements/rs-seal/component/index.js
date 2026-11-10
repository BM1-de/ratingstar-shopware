import template from './sw-cms-el-rs-seal.html.twig';
import './sw-cms-el-rs-seal.scss';
import { ELEMENT_NAME, DEFAULT_VARIANT, VARIANTS, STATIC_VARIANTS } from '../../../../../constant/rs-seal.constant';

const { Mixin } = Shopware;

/**
 * What the layout editor shows in place of the seal: a placeholder naming
 * the variant and the profile. The real seal is drawn by seal.js in the
 * storefront; the administration loads nothing from RatingStar.
 */
export default {
    template,

    mixins: [Mixin.getByName('cms-element')],

    computed: {
        variant() {
            const variant = this.element.config?.variant?.value;

            return VARIANTS.includes(variant) ? variant : DEFAULT_VARIANT;
        },

        variantLabel() {
            return this.$t(`rs-seal.variants.${this.variant}`);
        },

        isStatic() {
            return this.element.config?.renderMode?.value === 'static' && STATIC_VARIANTS.includes(this.variant);
        },

        subjectLabel() {
            const group = (this.element.config?.group?.value ?? '').trim();
            const slug = (this.element.config?.slug?.value ?? '').trim();

            if (group !== '') {
                return this.$t('rs-seal.element.preview.group', { name: group });
            }

            if (slug !== '') {
                return this.$t('rs-seal.element.preview.slug', { name: slug });
            }

            return this.$t('rs-seal.element.preview.configured');
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig(ELEMENT_NAME);
        },
    },
};
