/**
 * Helper function to validate if a string is a well-formed URL.
 *
 * @param {string} str - The string to validate as a URL.
 * @return {boolean} True if the string is a valid URL, false otherwise.
 */
const isURL = (str) => {
	try {
		new URL(str);
		return true;
	} catch {
		return false;
	}
};

/**
 * Validates if a given string is a valid URL.
 * This function is essentially the same as isURL.
 *
 * @param {string} url - The URL string to validate.
 * @return {boolean} True if the URL is valid, false otherwise.
 */
export const isValidUrl = (url) => {
	return isURL(url);
};

/**
 * Ensures that a URL string ends with a trailing slash.
 *
 * @param {string} url - The URL string to normalize.
 * @return {string} The normalized URL with a trailing slash, or empty string if input is invalid.
 */
export const withTrailingSlash = (url) => {
	if (!url || typeof url !== 'string') {
		return '';
	}
	
	const trimmedUrl = url.trim();
	
	if (!trimmedUrl) {
		return '';
	}
	
	if (!isURL(trimmedUrl)) {
		if (trimmedUrl.startsWith('/')) {
			return trimmedUrl.endsWith('/') ? trimmedUrl : trimmedUrl + '/';
		}
		return trimmedUrl.endsWith('/') ? trimmedUrl : trimmedUrl + '/';
	}
	
	const urlObj = new URL(trimmedUrl);
	const pathname = urlObj.pathname.endsWith('/') ? urlObj.pathname : urlObj.pathname + '/';
	
	urlObj.pathname = pathname;
	return urlObj.toString();
};

/**
 * WordPress REST API base URL for BlankPlugin plugin requests.
 */
export const API_NAMESPACE = (() => {
	const restUrl = window.BlankPluginSettings?.restUrl || '';
	const restNamespace = window.BlankPluginSettings?.restNamespace || '';
	
	if (restUrl && restNamespace) {
		return withTrailingSlash(restUrl) + restNamespace.replace(/^\//, '').replace(/\/$/, '');
	}
	
	return (window.wpApiSettings?.root || '/wp-json/') + 'blank-plugin/v1';
})();

/**
 * WordPress REST API nonce for authenticated requests.
 */
export const NONCE = window.BlankPluginSettings?.nonce || window.wpApiSettings?.nonce || '';

/**
 * REST namespace used by the BlankPlugin plugin.
 */
export const REST_NAMESPACE = window.BlankPluginSettings?.restNamespace || 'blank-plugin/v1';

/**
 * Current site's base URL.
 */
export const CURRENT_SITE_URL = (() => {
	const siteUrl = window.BlankPluginSettings?.currentSiteUrl || 
				   window.location.origin || 
				   (window.wpApiSettings?.root ? window.wpApiSettings.root.replace('/wp-json/', '') : '') || 
				   '';
	
	return siteUrl ? withTrailingSlash(siteUrl) : '';
})();

/**
 * Safe wrapper for accessing BlankPluginSettings with fallbacks
 */
export const getBlankPluginSettings = () => {
	return {
		restUrl: window.BlankPluginSettings?.restUrl || window.wpApiSettings?.root || '/wp-json/',
		restNamespace: window.BlankPluginSettings?.restNamespace || 'blank-plugin/v1',
		nonce: window.BlankPluginSettings?.nonce || window.wpApiSettings?.nonce || '',
		currentSiteUrl: window.BlankPluginSettings?.currentSiteUrl || window.location.origin || '',
	};
};

/**
 * Creates a full API endpoint URL
 *
 * @param {string} endpoint - The API endpoint (without namespace)
 * @return {string} Full API URL
 */
export const getApiUrl = (endpoint) => {
	const cleanEndpoint = endpoint.replace(/^\//, '');
	const base = API_NAMESPACE.endsWith('/') ? API_NAMESPACE : API_NAMESPACE + '/';
	
	return base + cleanEndpoint;
};

/**
 * Wrapper for API requests
 */
export const apiRequest = async (endpoint, options = {}) => {
	const url = getApiUrl(endpoint);
	
	const headers = new Headers(options.headers || {});
	
	if (NONCE) {
		headers.set('X-WP-Nonce', NONCE);
		headers.set('Content-Type', 'application/json');
	}
	
	const config = {
		...options,
		headers,
		credentials: 'same-origin',
	};
	
	try {
		const response = await fetch(url, config);
		
		if (!response.ok) {
			throw new Error(`HTTP ${response.status}: ${response.statusText}`);
		}
		
		return await response.json();
	} catch (error) {
		console.error('API Request failed:', error);
		throw error;
	}
};

/**
 * Validates and sanitizes a URL string
 *
 * @param {string} url - The URL to validate and sanitize
 * @return {string | null} Sanitized URL or null if invalid
 */
export const sanitizeUrl = (url) => {
	if (!url || typeof url !== 'string') {
		return null;
	}
	
	const trimmed = url.trim();
	
	try {
		const urlObj = new URL(trimmed);
		
		const allowedProtocols = ['http:', 'https:', 'mailto:', 'tel:'];
		if (!allowedProtocols.includes(urlObj.protocol)) {
			return null;
		}
		
		return urlObj.toString();
	} catch {
		if (trimmed.startsWith('/')) {
			return trimmed;
		}
		
		try {
			const withProtocol = 'https://' + trimmed;
			new URL(withProtocol);
			return withProtocol;
		} catch {
			return null;
		}
	}
};

/**
 * Check if the current environment has the required settings
 */
export const hasRequiredSettings = () => {
	return !!(NONCE && API_NAMESPACE && CURRENT_SITE_URL);
};

/**
 * Utility function to check if we're in WordPress admin
 */
export const isWordPressAdmin = () => {
	return typeof window.wp !== 'undefined' && 
		   typeof window.wp.element !== 'undefined' && 
		   typeof window.wp.components !== 'undefined';
};

/**
 * Get the complete REST API URL for BlankPlugin
 */
export const getRestApiUrl = () => {
	const settings = getBlankPluginSettings();
	return withTrailingSlash(settings.restUrl) + settings.restNamespace.replace(/^\//, '').replace(/\/$/, '');
};

// Default export with all utilities
export default {
	isURL,
	isValidUrl,
	withTrailingSlash,
	API_NAMESPACE,
	NONCE,
	REST_NAMESPACE,
	CURRENT_SITE_URL,
	getBlankPluginSettings,
	getApiUrl,
	apiRequest,
	sanitizeUrl,
	hasRequiredSettings,
	isWordPressAdmin,
	getRestApiUrl,
};