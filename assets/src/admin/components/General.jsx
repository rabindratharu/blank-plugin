import { TextControl, ToggleControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const General = ({ settings, onSettingsUpdate, isSaving }) => {
    return (
        <div className="blank-plugin-general-settings">
            <h3>{__('General Settings', 'blank-plugin')}</h3>
            
            <TextControl
                label={__('Setting 1', 'blank-plugin')}
                help={__('First text setting description.', 'blank-plugin')}
                value={settings.setting1 || ''}
                onChange={(value) => onSettingsUpdate('setting1', value)}
                disabled={isSaving}
            />
            
            <TextControl
                label={__('Setting 2', 'blank-plugin')}
                help={__('Second text setting description.', 'blank-plugin')}
                value={settings.setting2 || ''}
                onChange={(value) => onSettingsUpdate('setting2', value)}
                disabled={isSaving}
            />
            
            <ToggleControl
                label={__('Enable Setting 3', 'blank-plugin')}
                help={__('Toggle this setting on or off.', 'blank-plugin')}
                checked={settings.setting3 || false}
                onChange={(value) => onSettingsUpdate('setting3', value)}
                disabled={isSaving}
            />
            
            <ToggleControl
                label={__('Enable Quiz Block', 'blank-plugin')}
                help={__('Enable or disable the quiz functionality.', 'blank-plugin')}
                checked={settings.quiz !== false} // Default is true
                onChange={(value) => onSettingsUpdate('quiz', value)}
                disabled={isSaving}
            />
            
            <SelectControl
                label={__('Option Selection', 'blank-plugin')}
                value={settings.setting5 || 'option-1'}
                options={[
                    { label: __('Option 1', 'blank-plugin'), value: 'option-1' },
                    { label: __('Option 2', 'blank-plugin'), value: 'option-2' },
                ]}
                onChange={(value) => onSettingsUpdate('setting5', value)}
                disabled={isSaving}
            />
        </div>
    );
};

export default General;