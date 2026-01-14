import { __ } from '@wordpress/i18n';

const Header = () => {
    return (
        <div className="blank-plugin-settings-header">
            <h1 className="blank-plugin-settings-title">
                {__('Blank Plugin Settings', 'blank-plugin')}
            </h1>
            <p className="blank-plugin-settings-description">
                {__('Configure your plugin settings here. Changes will be saved automatically when you navigate away from a field.', 'blank-plugin')}
            </p>
        </div>
    );
};

export default Header;