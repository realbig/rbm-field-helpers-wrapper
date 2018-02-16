<?php
/**
 * Provides helper functions.
 *
 * @since	  1.0.0
 *
 * @package	RBM_Field_Helpers_Wrapper
 * @subpackage RBM_Field_Helpers_Wrapper/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since		1.0.0
 *
 * @return		RBM_Field_Helpers_Wrapper
 */
function RBMFIELDHELPERSWRAPPER() {
	return RBM_Field_Helpers_Wrapper::instance();
}