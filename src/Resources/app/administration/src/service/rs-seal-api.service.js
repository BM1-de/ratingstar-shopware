const { Application } = Shopware;
const ApiService = Shopware.Classes.ApiService;

/**
 * Admin API of the plugin: checks profile slug, group slug and API key
 * against the RatingStar server without saving anything.
 */
class RsSealApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'rating-star-seal') {
        super(httpClient, loginService, apiEndpoint);
    }

    checkConnection(values) {
        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/check-connection`, values, {
                headers: this.getBasicHeaders(),
            })
            .then((response) => ApiService.handleResponse(response));
    }
}

Application.addServiceProvider('rsSealApiService', (container) => {
    const initContainer = Application.getContainer('init');

    return new RsSealApiService(initContainer.httpClient, container.loginService);
});
