import { ToggleControl, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const Advanced = ({ settings, onSettingChange, isSaving }) => {
    return (
        <div className="blank-plugin-advanced-settings">
            <h3>{__('Advanced Settings', 'blank-plugin')}</h3>
            
            <Notice status="warning" isDismissible={false}>
                {__('These settings are for advanced users. Changes may affect plugin behavior.', 'blank-plugin')}
            </Notice>

            <ToggleControl
                label={__('Delete All Settings on Deactivation', 'blank-plugin')}
                help={__('When enabled, all plugin settings will be deleted when the plugin is deactivated.', 'blank-plugin')}
                checked={settings.deleteAll || false}
                onChange={(value) => onSettingChange('deleteAll', value)}
                disabled={isSaving}
            />
        </div>
    );
};

export default Advanced;