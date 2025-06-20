/*CSS*/
import './index.scss';

const { Component } = wp.element;
const { TextControl, Button, BaseControl } = wp.components;
const { __ } = wp.i18n;

class BlankPluginTextControl extends Component {
    constructor(props) {
        super(props);
        this.state = {
            value: props.value || props.defaultValue || ''
        };
    }

    handleReset = () => {
        this.setState({ value: this.props.defaultValue || '' });
        wp.customize(this.props.setting).set(this.props.defaultValue || '');
    };

    render() {
        const { label, description, defaultValue } = this.props;
        const { value } = this.state;
        const showReset = value !== defaultValue;

        return (
            <BaseControl
                label={label}
                help={description}
                className="blank-plugin-control"
            >
                <div className="blank-plugin-control__wrapper">
                    <TextControl
                        value={value}
                        onChange={(newValue) => {
                            this.setState({ value: newValue });
                            wp.customize(this.props.setting).set(newValue);
                        }}
                    />
                    {showReset && (
                        <Button
                            className="blank-plugin-control__reset"
                            isSmall
                            isSecondary
                            onClick={this.handleReset}
                        >
                            {__('Reset', 'blank-plugin')}
                        </Button>
                    )}
                </div>
            </BaseControl>
        );
    }
}

// Initialize when Customizer is ready
wp.customize.bind('ready', () => {
    document.querySelectorAll('[id^="blank-plugin-text-control-"]').forEach(container => {
        try {
            const props = JSON.parse(container.dataset.props);

            // Initial render
            wp.element.render(
                <BlankPluginTextControl {...props} />,
                container
            );

            // Update on external changes
            wp.customize(props.setting, (setting) => {
                setting.bind((newValue) => {
                    wp.element.render(
                        <BlankPluginTextControl {...props} value={newValue} />,
                        container
                    );
                });
            });
        } catch (e) {
            console.error('Error parsing control props:', e);
        }
    });
});