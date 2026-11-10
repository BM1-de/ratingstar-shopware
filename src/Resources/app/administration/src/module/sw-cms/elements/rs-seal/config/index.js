import template from './sw-cms-el-config-rs-seal.html.twig';
import './sw-cms-el-config-rs-seal.scss';
import {
    ELEMENT_NAME,
    DEFAULT_VARIANT,
    VARIANTS,
    STATIC_VARIANTS,
    POSITIONS,
    VARIANT_OPTIONS,
    OPTIONS,
} from '../../../../../constant/rs-seal.constant';

const { Mixin } = Shopware;

/**
 * Settings of one seal element. Everything except the variant starts on
 * "default (portal)" — an empty value, for which the storefront prints no
 * attribute. Yes/no options are three-way selects for that reason, never
 * switches.
 */
export default {
    template,

    mixins: [Mixin.getByName('cms-element')],

    computed: {
        variant() {
            const variant = this.element.config.variant.value;

            return VARIANTS.includes(variant) ? variant : DEFAULT_VARIANT;
        },

        variantChoices() {
            return VARIANTS.map((value) => ({
                value,
                label: this.$t(`rs-seal.variants.${value}`),
            }));
        },

        renderModeChoices() {
            return ['live', 'static'].map((value) => ({
                value,
                label: this.$t(`rs-seal.element.config.renderMode.values.${value}`),
            }));
        },

        positionChoices() {
            return this.withDefault(
                POSITIONS.map((value) => ({
                    value,
                    label: this.$t(`rs-seal.element.config.position.values.${value}`),
                })),
            );
        },

        noRichSnippetChoices() {
            return [
                { value: '', label: this.$t('rs-seal.element.config.noRichSnippet.values.auto') },
                { value: '1', label: this.$t('rs-seal.element.config.noRichSnippet.values.suppress') },
            ];
        },

        /** A static image exists for the two seals and the profile card only. */
        canBeStatic() {
            return STATIC_VARIANTS.includes(this.variant);
        },

        isStatic() {
            return this.canBeStatic && this.element.config.renderMode.value === 'static';
        },

        showPosition() {
            return this.variant === 'profile-card' && !this.isStatic;
        },

        /** Options of the chosen variant; the static image only knows its width. */
        optionKeys() {
            const keys = VARIANT_OPTIONS[this.variant] ?? [];

            return this.isStatic ? keys.filter((key) => key === 'size') : keys;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig(ELEMENT_NAME);
        },

        optionType(key) {
            return OPTIONS[key].type;
        },

        optionLabel(key) {
            const option = OPTIONS[key];
            const label = this.$t(`rs-seal.options.${key}.label`);

            return option.type === 'int' ? `${label} (${option.min}–${option.max})` : label;
        },

        optionHelpText(key) {
            const path = `rs-seal.options.${key}.helpText`;

            return this.$te(path) ? this.$t(path) : null;
        },

        optionChoices(key) {
            const option = OPTIONS[key];

            if (option.type === 'bool') {
                return this.withDefault([
                    { value: '1', label: this.$t('rs-seal.element.config.yes') },
                    { value: '0', label: this.$t('rs-seal.element.config.no') },
                ]);
            }

            return this.withDefault(
                option.values.map((value) => ({
                    value,
                    label: this.$t(`rs-seal.options.${key}.values.${value}`),
                })),
            );
        },

        /** Puts "default (portal)" — the empty value — in front of the choices. */
        withDefault(choices) {
            return [{ value: '', label: this.$t('rs-seal.element.config.default') }, ...choices];
        },

        /** A cleared select means "default (portal)" as well. */
        onSelect(key, value) {
            this.element.config[key].value = value ?? '';
        },
    },
};
