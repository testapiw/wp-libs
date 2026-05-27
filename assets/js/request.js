!(function () {
    class XTRequest {
        constructor(config) {
            this.ajaxUrl = config.ajax_url;
            this.baseUrl = config.base_url;
            this.nonce = config.nonce;
            this.restNonce = config.rest_nonce;
        }

        request(endpoint, payload = {}, method = 'POST') {
            payload.action = endpoint;
            payload.nonce = this.nonce;

            let options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.restNonce
                },

            };

            let url = `${this.baseUrl}/${endpoint}`;

            if (method === 'POST' || method === 'PUT' || method === 'DELETE') {
                options.body = JSON.stringify(payload);
            }
            else if (method === 'GET') {
                const params = new URLSearchParams(payload).toString();
                if (params) {
                    url += `?${params}`;
                }
            }

            return fetch(url, options)
                .then(async response => {

                    if (response.status === 401) {
                        if (typeof this.onUnauthorized === 'function') {
                            this.onUnauthorized(response);
                        }
                    }

                    if (!response.ok) {
                        let message = 'Server error';
                        let data = {};
                        try {
                            data = await response.json();
                            if (response.status !== 500 && data.message) {
                                message = data.message;
                            }
                        } catch (e) {
                            const text = await response.text();
                            console.error('Non-JSON 500 response:', text);
                        }
                        // console.error(`Server error (${endpoint}):`, response.status);
                        return {
                            success: false, results: [],
                            message: data?.message || message,
                            status: response.status,
                            errors: data?.errors || {}
                        };
                    }

                    return response.json();
                })
                .catch(error => {
                    console.error(`Request error (${endpoint}):`, error);
                    return { success: false, message: 'Network error' };
                });
        }

        get(endpoint, payload = {}) {
            return this.request(endpoint, payload, 'GET');
        }

        post(endpoint, payload = {}) {
            return this.request(endpoint, payload, 'POST');
        }

        put(endpoint, payload = {}) {
            return this.request(endpoint, payload, 'PUT');
        }

        delete(endpoint, payload = {}) {
            return this.request(endpoint, payload, 'DELETE');
        }
    }

    window.vcom = window.vcom || {};
    window.vcom.XTRequest = XTRequest;

})();