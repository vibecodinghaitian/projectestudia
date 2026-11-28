<?php
/**
 * Plugin Name: EstudiaMed EHEP Ecuador
 * Description: Banco educativo, simulacros y seguimiento para el módulo EHEP.
 * Version: 1.5.0
 * Requires PHP: 7.4
 */
defined( 'ABSPATH' ) || exit;

define( 'ESTUDIAMED_EHEP_FILE', __FILE__ );
require_once __DIR__ . '/includes/bank.php';
require_once __DIR__ . '/includes/clinical.php';
require_once __DIR__ . '/includes/attempts.php';
require_once __DIR__ . '/includes/dashboard.php';
require_once __DIR__ . '/includes/editorial.php';

register_activation_hook( __FILE__, 'estudiamed_ehep_install' );
