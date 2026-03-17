var el = wp.element.createElement;
const { __ } = wp.i18n; // Import __() from wp.i18n

import { registerPlugin } from '@wordpress/plugins';
import { __experimentalMainDashboardButton as MainDashboardButton } from '@wordpress/edit-post';
import { Dashicon, Button, Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';
const { subscribe, useSelect } = wp.data;
const post_type = wp.data.select( 'core/editor' ).getCurrentPostType();

