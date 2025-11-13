/**
 * File editor/index.js.
 */
import { registerPlugin } from '@wordpress/plugins';
import Sidebar from './sidebar';

registerPlugin('blank-plugin-sidebar', {
	render: Sidebar,
	icon: 'admin-plugins',
});
