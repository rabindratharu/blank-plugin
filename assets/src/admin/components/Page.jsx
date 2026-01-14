import { useState, useEffect } from 'react';
import { Panel, PanelBody, Notice, Spinner, Snackbar } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import General from './General';
import Advanced from './Advanced';
import Settings from '../hooks/Settings';
import Header from './Header';
import Footer from './Footer';

const Page = () => {
    const {
        settings,
        isLoading,
        isSaving,
        error,
        success,
        fetchSettings,
        updateSettings,
        resetToDefaults,
        clearNotifications
    } = Settings();

    const [activeTab, setActiveTab] = useState('general');
    const [hasUnsavedChanges, setHasUnsavedChanges] = useState(false);
    const [localSettings, setLocalSettings] = useState({});

    // Initialize local settings when fetched
    useEffect(() => {
        if (settings && Object.keys(settings).length > 0) {
            setLocalSettings(settings);
        }
    }, [settings]);

    // Fetch settings on mount
    useEffect(() => {
        fetchSettings();
    }, [fetchSettings]);

    // Handle setting changes
    const handleSettingChange = (key, value) => {
        setLocalSettings(prev => ({
            ...prev,
            [key]: value
        }));
        setHasUnsavedChanges(true);
    };

    // Handle bulk settings update
    const handleSettingsUpdate = (newSettings) => {
        setLocalSettings(prev => ({
            ...prev,
            ...newSettings
        }));
        setHasUnsavedChanges(true);
    };

    // Save all settings
    const handleSaveSettings = async () => {
        try {
            await updateSettings(localSettings);
            setHasUnsavedChanges(false);
        } catch (error) {
            console.error('Failed to save settings:', error);
        }
    };

    // Reset to defaults
    const handleReset = async () => {
        if (window.confirm(__('Are you sure you want to reset all settings to default values?', 'blank-plugin'))) {
            await resetToDefaults();
            setHasUnsavedChanges(false);
            // Refresh local settings after reset
            setTimeout(() => {
                fetchSettings();
            }, 500);
        }
    };

    // Clear notifications after 5 seconds
    useEffect(() => {
        if (error || success) {
            const timer = setTimeout(() => {
                clearNotifications();
            }, 5000);
            return () => clearTimeout(timer);
        }
    }, [error, success, clearNotifications]);

    if (isLoading && Object.keys(localSettings).length === 0) {
        return (
            <div className="blank-plugin-settings-page">
                <Header />
                <div className="blank-plugin-settings-loading">
                    <Spinner />
                    <p>{__('Loading settings...', 'blank-plugin')}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="blank-plugin-settings-page">
            <Header />

            {error && (
                <Notice
                    status="error"
                    isDismissible={true}
                    onRemove={clearNotifications}
                >
                    {error}
                </Notice>
            )}

            {success && (
                <Snackbar
					explicitDismiss={ true }
					onRemove={clearNotifications}
					status="success"
				>
					{success}
				</Snackbar>
            )}

            <div className="blank-plugin-settings-tabs">
                <button
                    className={`blank-plugin-tab ${activeTab === 'general' ? 'active' : ''}`}
                    onClick={() => setActiveTab('general')}
                >
                    {__('General', 'blank-plugin')}
                </button>
                <button
                    className={`blank-plugin-tab ${activeTab === 'advanced' ? 'active' : ''}`}
                    onClick={() => setActiveTab('advanced')}
                >
                    {__('Advanced', 'blank-plugin')}
                </button>
            </div>

            <div className="blank-plugin-settings-content">
                <Panel>
                    <PanelBody>
                        {activeTab === 'general' && (
                            <General
                                settings={localSettings}
                                onSettingsUpdate={handleSettingChange} // Fixed prop name
                                isSaving={isSaving}
                            />
                        )}
                        {activeTab === 'advanced' && (
                            <Advanced
                                settings={localSettings}
                                onSettingChange={handleSettingChange}
                                isSaving={isSaving}
                            />
                        )}
                    </PanelBody>
                </Panel>
            </div>

            <Footer
                hasUnsavedChanges={hasUnsavedChanges}
                isSaving={isSaving}
                onSave={handleSaveSettings}
                onReset={handleReset}
            />
        </div>
    );
};

export default Page;