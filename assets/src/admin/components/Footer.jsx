import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const Footer = ({ hasUnsavedChanges, isSaving, onSave, onReset }) => {
    return (
        <div className="blank-plugin-settings-footer">
            <div className="blank-plugin-settings-actions">
                <Button
                    isPrimary
                    onClick={onSave}
                    disabled={!hasUnsavedChanges || isSaving}
                >
                    {isSaving ? (
                        <>
                            <Spinner />
                            {__('Saving...', 'blank-plugin')}
                        </>
                    ) : (
                        __('Save Changes', 'blank-plugin')
                    )}
                </Button>
                
                <Button
                    isSecondary
                    onClick={onReset}
                    disabled={isSaving}
                >
                    {__('Reset to Defaults', 'blank-plugin')}
                </Button>
            </div>
            
            {hasUnsavedChanges && !isSaving && (
                <div className="blank-plugin-settings-unsaved-notice">
                    {__('You have unsaved changes.', 'blank-plugin')}
                </div>
            )}
        </div>
    );
};

export default Footer;