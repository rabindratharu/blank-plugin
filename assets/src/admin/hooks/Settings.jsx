import { useState, useCallback } from "react";
import apiFetch from "@wordpress/api-fetch";
import { __ } from "@wordpress/i18n";

// WordPress provides snake_case keys here. Using them intentionally.
// eslint-disable-next-line camelcase
const { nonce, defaultSettings } = window.BlankPluginSettings || {};

/**
 * Create NONCE middleware for apiFetch
 */
apiFetch.use(apiFetch.createNonceMiddleware(nonce));

const Settings = () => {
	const [settings, setSettings] = useState({});
	const [isLoading, setIsLoading] = useState(true);
	const [isSaving, setIsSaving] = useState(false);
	const [error, setError] = useState(null);
	const [success, setSuccess] = useState(null);

	// Fetch settings from API
	const fetchSettings = useCallback(async () => {
		setIsLoading(true);
		setError(null);

		try {
			const response = await apiFetch({
				path: "/blank-plugin/v1/settings",
				method: "GET",
			});
			setSettings(response);
			setSuccess(null);
		} catch (err) {
			let errorMessage = __(
				"Failed to load settings. Please try again.",
				"blank-plugin",
			);
			if (err.message) {
				errorMessage = err.message;
			} else if (err.data && err.data.message) {
				errorMessage = err.data.message;
			}

			setError(errorMessage);
		} finally {
			setIsLoading(false);
		}
	}, []);

	// Save settings
	const updateSettings = useCallback(async (newSettings) => {
		setIsSaving(true);
		setError(null);

		try {
			const response = await apiFetch({
				path: "/blank-plugin/v1/settings",
				method: "POST",
				data: newSettings,
			});

			console.log("Settings saved successfully:", response);
			setSettings((prev) => ({ ...prev, ...newSettings }));
			setSuccess(__("Settings saved successfully!", "blank-plugin"));
			return response;
		} catch (err) {
			let errorMessage = __("Failed to save settings.", "blank-plugin");
			if (err.message) {
				errorMessage = err.message;
			} else if (err.data && err.data.message) {
				errorMessage = err.data.message;
			}

			setError(errorMessage);
			throw err;
		} finally {
			setIsSaving(false);
		}
	}, []);

	// Update single setting
	const updateSetting = useCallback(
		async (key, value) => {
			return updateSettings({ [key]: value });
		},
		[updateSettings],
	);

	// Reset to default settings
	const resetToDefaults = useCallback(async () => {
		return updateSettings(defaultSettings);
	}, [updateSettings]);

	// Clear notifications
	const clearNotifications = useCallback(() => {
		setError(null);
		setSuccess(null);
	}, []);

	return {
		settings,
		isLoading,
		isSaving,
		error,
		success,
		fetchSettings,
		updateSetting,
		updateSettings,
		resetToDefaults,
		clearNotifications,
	};
};

export default Settings;
