/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';
/**
 * Internal dependencies
 */
import Content from './content';

// Wait for DOM to be ready.
document.addEventListener('DOMContentLoaded', () => {
    // Check if the root element exists in the DOM.
    const target = document.getElementById('blank-plugin-dashboard');
    if (target) {
        // Render the component into the root element.
        const root = createRoot(target);
        root.render(<Content />);
    }
});