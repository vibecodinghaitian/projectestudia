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
$samples[] = array(
	'id' => 'sample-case-epi-01-pt', 'lang' => 'pt', 'type' => 'case', 'category' => 'Ciências Básicas',
	'subject' => 'Epidemiologia', 'topic' => 'Medidas de frequência', 'origin' => 'sample', 'difficulty' => 'beginner',
	'stem' => 'Uma unidade de saúde acompanha 200 pessoas sem a doença no início. Em um ano aparecem 20 casos novos e ninguém é perdido no seguimento. Qual é o risco acumulado nesse ano?',
	'options' => array( '10 %', '20 %', '1 %', '0,1 %' ),
	'key' => 0,
	'explanation' => 'O risco acumulado divide os 20 casos novos pelas 200 pessoas inicialmente em risco. O resultado é 0,10, ou 10%.',
	'reasons' => array( 'É o resultado de 20 dividido por 200, expresso como porcentagem.', 'Corresponderia a 40 casos.', 'Corresponderia a dois casos.', 'Confunde a proporção 0,1 com 0,1%.' ),
	'source' => 'EstudiaMed · exercício educacional original',
);
$samples[] = array(
	'id' => 'sample-case-cir-01', 'lang' => 'es', 'type' => 'case', 'category' => 'CIENCIAS CLÍNICAS Y QUIRÚRGICAS',
	'subject' => 'Cirugía', 'topic' => 'Obstrucción intestinal mecánica', 'origin' => 'sample', 'difficulty' => 'intermediate',
	'stem' => 'Paciente ficticio con distensión abdominal y antecedente de cirugía. En la tomografía se observa dilatación del intestino delgado. ¿Qué hallazgo orienta más hacia una obstrucción mecánica focal?',
	'options' => array( 'Dilatación uniforme sin transición', 'Cambio de calibre entre asas proximales dilatadas y asas distales colapsadas', 'Antecedente aislado de cirugía abdominal', 'Distensión que el paciente describe al final del día' ),
	'key' => 1,
	'explanation' => 'Un punto de transición con cambio de calibre entre el intestino proximal y distal orienta a una barrera mecánica focal. El antecedente de cirugía apoya la sospecha de adherencias, pero no identifica el sitio de transición.',
	'reasons' => array( 'Sin transición definida, la dilatación uniforme también puede observarse en el íleo.', 'Localiza el cambio de calibre entre el segmento proximal y el distal.', 'Es un factor de riesgo, pero por sí solo no demuestra obstrucción.', 'El horario de la distensión no localiza una barrera mecánica.' ),
	'source' => 'World Society of Emergency Surgery · Guías de Bolonia, actualización 2017',
	'url' => 'https://wjes.biomedcentral.com/articles/10.1186/s13017-018-0185-2',
);
return $samples;
