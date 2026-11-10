import template from './rs-seal-connection-check.html.twig';
import './rs-seal-connection-check.scss';

const DOMAIN = 'RatingStarSeal.config';
const FIELDS = ['baseOrigin', 'slug', 'group', 'apiKey'];

/**
 * "Check connection" button of the plugin configuration. Sends the values
 * currently in the form — saved or not — to the plugin's admin API, which
 * looks up profile, group and API key on the RatingStar server. The result
 * only informs; saving stays possible whatever it says.
 */
Shopware.Component.register('rs-seal-connection-check', {
    template,

    inheritAttrs: false,

    inject: ['rsSealApiService'],

    data() {
        return {
            isLoading: false,
            hasChecked: false,
            hasFailed: false,
            results: [],
        };
    },

    computed: {
        /** The surrounding sw-system-config, which holds the form values. */
        systemConfig() {
            let parent = this.$parent;

            while (parent && parent.actualConfigData === undefined) {
                parent = parent.$parent;
            }

            return parent || null;
        },
    },

    methods: {
        /**
         * Form values of the selected sales channel; an empty field falls
         * back to the value of "all sales channels", as it does at runtime.
         */
        formValues() {
            const configData = this.systemConfig?.actualConfigData ?? {};
            const inherited = configData.null ?? {};
            const current = configData[this.systemConfig?.currentSalesChannelId] ?? {};
            const values = {};

            FIELDS.forEach((field) => {
                const key = `${DOMAIN}.${field}`;
                const own = current[key];
                const value = own === null || own === undefined || own === '' ? inherited[key] : own;

                values[field] = typeof value === 'string' ? value.trim() : '';
            });

            return values;
        },

        onCheck() {
            this.isLoading = true;
            this.hasFailed = false;
            this.results = [];

            return this.rsSealApiService
                .checkConnection(this.formValues())
                .then((response) => {
                    this.results = (response?.checks ?? [])
                        .filter((check) => check.status !== 'skipped')
                        .map((check) => this.present(check));
                })
                .catch(() => {
                    this.hasFailed = true;
                })
                .finally(() => {
                    this.hasChecked = true;
                    this.isLoading = false;
                });
        },

        present(check) {
            return {
                key: check.subject,
                variant: check.status === 'ok' ? 'positive' : 'attention',
                text: this.$t(`rs-seal.config.check.${check.subject}.${check.code}`, {
                    name: check.name ?? '',
                    status: check.httpStatus ?? '',
                }),
            };
        },
    },
});
