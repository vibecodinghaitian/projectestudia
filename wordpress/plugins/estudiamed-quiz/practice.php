<?php
defined( 'ABSPATH' ) || exit;

$source = dirname( __DIR__ ) . '/estudiamed-ehep/includes/practice.php';
if ( ! is_readable( $source ) ) {
	return array();
}
$items = require $source;
$topics = array(
	'epi-01' => 'Medidas de frecuencia', 'epi-02' => 'Pruebas diagnósticas', 'epi-03' => 'Diseños de estudio',
	'bio-01' => 'Fisiología endocrina', 'bio-02' => 'Fisiología renal', 'bio-03' => 'Fisiología cardiovascular',
);
$samples = array_map( static function ( $item ) use ( $topics ) {
	return $item + array(
		'lang' => 'es', 'category' => 'CIENCIAS BÁSICAS', 'type' => 'mcq',
		'quiz_subject' => 'Epidemiología' === $item['subject'] ? 'Epidemiología' : 'Fisiología I',
		'topic' => $topics[ $item['id'] ], 'origin' => 'sample',
	);
}, $items );

$samples[] = array(
	'id' => 'sample-case-epi-01', 'lang' => 'es', 'type' => 'case', 'category' => 'CIENCIAS BÁSICAS',
	'subject' => 'Epidemiología', 'topic' => 'Medidas de frecuencia', 'origin' => 'sample',
	'stem' => 'Un centro de salud sigue durante un año a 200 personas sin la enfermedad al inicio. Al final del año se han detectado 20 casos nuevos y nadie se ha perdido durante el seguimiento. ¿Cuál fue el riesgo acumulado durante ese año?',
	'options' => array( '10 %', '20 %', '1 %', '0,1 %' ),
	'key' => 0,
	'explanation' => 'El riesgo acumulado divide los casos nuevos durante el período entre las personas en riesgo al inicio. En este caso, 20 dividido por 200 equivale al 10 %.',
	'reasons' => array( 'Es la proporción 20/200 expresada como porcentaje.', 'Correspondería a 40 casos nuevos entre 200 personas.', 'Correspondería a dos casos nuevos.', 'Confunde la proporción 0,1 con el porcentaje 0,1 %.' ),
	'source' => 'EstudiaMed · práctica educativa original',
);
return $samples;
