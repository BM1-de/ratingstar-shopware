import template from './rs-seal-config-notice.html.twig';

/**
 * A visible note inside the plugin configuration (domain approval, privacy).
 * config.xml names the note; the text comes from the snippets, so it follows
 * the language of the administration.
 */
Shopware.Component.register('rs-seal-config-notice', {
    template,

    inheritAttrs: false,

    props: {
        notice: {
            type: String,
            required: true,
        },
    },

    computed: {
        title() {
            return this.$t(`rs-seal.config.notice.${this.notice}.title`);
        },

        text() {
            return this.$t(`rs-seal.config.notice.${this.notice}.text`);
        },
    },
});
