/* WordPress */
import { __ } from '@wordpress/i18n';

import { useContext } from '@wordpress/element';

/* Library */
import classNames from 'classnames';

/*Atrc*/
import { AtrcButton, AtrcWrap, AtrcHeaderTemplate1 } from 'atrc';

/* Inbuilt */
import { AtrcReduxContextData } from '../../routes';

/*Local*/
const AdminHeader = () => {
    const data = useContext(AtrcReduxContextData);

    const { lsSettings, lsSaveSettings } = data;

    const primaryNav = [
        {
            to: '/',
            children: __('Getting started', 'blank-plugin'),
            end: true,
        },
        {
            to: '/settings',
            children: __('Settings', 'blank-plugin'),
        },
    ];

    return (
        <AtrcHeaderTemplate1
            isSticky
            logo={{
                src: BlankPluginLocalize.white_label.dashboard.logo,
            }}
            primaryNav={{
                navs: primaryNav,
            }}
            floatingSidebar={() => (
                <AtrcWrap className={classNames()}>
                    <AtrcButton
                        className={classNames()}
                        onClick={() => lsSaveSettings(null)}>
                        {__(
                            'Show all hidden informations, notices and documentations ',
                            'blank-plugin'
                        )}
                    </AtrcButton>
                </AtrcWrap>
            )}
        />
    );
};

export default AdminHeader;
