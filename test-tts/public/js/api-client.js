/**
 * ApiClient - Unified Fetch Wrapper for ChordCue
 * Integrates Laravel Sanctum session auth, CSRF headers, and uniform error handling.
 */
class ApiError extends Error {
    constructor(message, status, data = null) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.data = data;
    }
}

class ValidationError extends ApiError {
    constructor(errors, message = 'Validasi gagal.') {
        super(message, 422, { errors });
        this.name = 'ValidationError';
        this.errors = errors;
    }
}

class ConflictError extends ApiError {
    constructor(data, message = 'Beat ini sudah dipakai chord lain.') {
        super(message, 409, data);
        this.name = 'ConflictError';
        this.conflictData = data;
    }
}

class ForbiddenError extends ApiError {
    constructor(message = 'Anda tidak memiliki izin untuk melakukan tindakan ini.') {
        super(message, 403);
        this.name = 'ForbiddenError';
    }
}

const ApiClient = {
    csrfToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : '';
    },

    async request(url, options = {}) {
        const headers = options.headers ? { ...options.headers } : {};

        // Default JSON headers
        if (!headers['Accept']) {
            headers['Accept'] = 'application/json';
        }
        if (!headers['X-Requested-With']) {
            headers['X-Requested-With'] = 'XMLHttpRequest';
        }

        const token = this.csrfToken();
        if (token) {
            headers['X-CSRF-TOKEN'] = token;
        }

        let body = options.body;
        // Automatically JSON serialize if body is a plain object
        if (body && !(body instanceof FormData) && !(body instanceof URLSearchParams) && typeof body === 'object') {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(body);
        }

        const fetchOptions = {
            ...options,
            headers,
            credentials: 'same-origin',
            body,
        };

        const response = await fetch(url, fetchOptions);

        // Uniform Error Handling
        if (response.status === 401) {
            window.location.href = '/login';
            throw new ApiError('Sesi berakhir, silakan login kembali.', 401);
        }

        let responseData = null;
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            try {
                responseData = await response.json();
            } catch (e) {
                responseData = null;
            }
        } else {
            responseData = await response.text();
        }

        if (response.status === 403) {
            const msg = (responseData && responseData.message) ? responseData.message : 'Anda tidak memiliki izin.';
            throw new ForbiddenError(msg);
        }

        if (response.status === 409) {
            // Returned to caller for conflict confirmation modal ("timpa?")
            throw new ConflictError(responseData, responseData?.message || 'Beat sudah terisi.');
        }

        if (response.status === 422) {
            const errors = responseData?.errors || {};
            throw new ValidationError(errors, responseData?.message || 'Validasi gagal.');
        }

        if (!response.ok) {
            const msg = responseData?.message || `Terjadi kesalahan (Kode: ${response.status})`;
            throw new ApiError(msg, response.status, responseData);
        }

        return responseData;
    },

    get(url, params = null) {
        let finalUrl = url;
        if (params) {
            const query = new URLSearchParams();
            Object.entries(params).forEach(([key, val]) => {
                if (val !== null && val !== undefined && val !== '') {
                    query.append(key, val);
                }
            });
            const queryString = query.toString();
            if (queryString) {
                finalUrl += (url.includes('?') ? '&' : '?') + queryString;
            }
        }
        return this.request(finalUrl, { method: 'GET' });
    },

    post(url, data = {}) {
        return this.request(url, {
            method: 'POST',
            body: data,
        });
    },

    patch(url, data = {}) {
        return this.request(url, {
            method: 'PATCH',
            body: data,
        });
    },

    put(url, data = {}) {
        return this.request(url, {
            method: 'PUT',
            body: data,
        });
    },

    delete(url, data = null) {
        const options = { method: 'DELETE' };
        if (data) {
            options.body = data;
        }
        return this.request(url, options);
    },

    /**
     * Render validation error messages under respective inputs
     */
    displayValidationErrors(formElement, errors) {
        // Clear previous error messages
        formElement.querySelectorAll('.text-red-500.validation-error-msg').forEach(el => el.remove());
        formElement.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));

        if (!errors || typeof errors !== 'object') return;

        Object.entries(errors).forEach(([field, messages]) => {
            const input = formElement.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('border-red-500');
                const errSpan = document.createElement('p');
                errSpan.className = 'text-xs text-red-500 mt-1 validation-error-msg';
                errSpan.textContent = Array.isArray(messages) ? messages[0] : messages;
                input.parentNode.appendChild(errSpan);
            }
        });
    },
};

window.ApiClient = ApiClient;
window.ApiError = ApiError;
window.ValidationError = ValidationError;
window.ConflictError = ConflictError;
window.ForbiddenError = ForbiddenError;
