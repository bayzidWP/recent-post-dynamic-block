/**
 * Registers a new block provided a unique name and an object defining its behavior.
 */
import { registerBlockType } from '@wordpress/blocks';

import './style.scss';

/**
 * Internal dependencies
 */
import Edit from './edit/Edit';
import metadata from './block.json';

/**
 * Every block starts by registering a new block type definition.
 */
registerBlockType( metadata.name, {
    /**
     * The Edit function.
     */
	edit: Edit,
} );
