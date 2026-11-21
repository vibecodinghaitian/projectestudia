<?php
defined( 'ABSPATH' ) || exit;

$items = array(
	array( 'epi-01', 'Epidemiología', '¿En qué denominador se basa la tasa de incidencia de una enfermedad durante el seguimiento?',
		array( 'Tiempo-persona en riesgo', 'Personas con la enfermedad al inicio', 'Número de falsos positivos', 'Total de defunciones' ), 0,
		array( 'La tasa divide los casos nuevos por el tiempo total observado en riesgo.', 'Los casos que ya existen se usan para medir prevalencia, no para sumar tiempo en riesgo del primer evento.', 'Los falsos positivos se usan al evaluar pruebas diagnósticas.', 'El total de defunciones no expresa tiempo de seguimiento en riesgo.' ) ),
	array( 'epi-02', 'Epidemiología', '¿Qué indicador expresa la proporción de personas con una enfermedad que obtienen un resultado positivo en una prueba?',
		array( 'Especificidad', 'Sensibilidad', 'Valor predictivo positivo', 'Prevalencia' ), 1,
		array( 'La especificidad describe los resultados negativos entre personas sin la enfermedad.', 'La sensibilidad divide los verdaderos positivos por el total de personas con la enfermedad.', 'El valor predictivo positivo parte de los resultados positivos, no de todas las personas enfermas.', 'La prevalencia mide la frecuencia de la enfermedad en una población.' ) ),
	array( 'epi-03', 'Epidemiología', '¿Qué diseño comienza con grupos que presentan o no un desenlace y compara sus exposiciones previas?',
		array( 'Ensayo aleatorizado', 'Estudio ecológico', 'Estudio de casos y controles', 'Cohorte prospectiva' ), 2,
		array( 'Un ensayo aleatorizado asigna intervenciones antes de observar resultados.', 'Un estudio ecológico analiza datos agregados de grupos.', 'Los casos presentan el desenlace y los controles permiten comparar la exposición previa.', 'Una cohorte prospectiva parte de grupos definidos antes de que ocurra el desenlace.' ) ),
	array( 'bio-01', 'Ciencias básicas', '¿Qué células del páncreas endocrino producen insulina?',
		array( 'Células alfa', 'Células delta', 'Células acinares', 'Células beta' ), 3,
		array( 'Las células alfa producen glucagón.', 'Las células delta secretan somatostatina.', 'Las células acinares secretan enzimas digestivas al sistema de conductos.', 'Las células beta de los islotes secretan insulina.' ) ),
	array( 'bio-02', 'Ciencias básicas', '¿Qué segmento de la nefrona reabsorbe la mayor parte del sodio filtrado en condiciones normales?',
		array( 'Túbulo contorneado proximal', 'Túbulo contorneado distal', 'Conducto colector cortical', 'Rama descendente delgada del asa de Henle' ), 0,
		array( 'El túbulo proximal recupera la mayor fracción de sodio filtrado.', 'El túbulo distal contribuye al ajuste de una fracción menor.', 'El colector regula el balance final bajo control hormonal.', 'La rama descendente delgada se caracteriza por su permeabilidad al agua.' ) ),
	array( 'bio-03', 'Ciencias básicas', '¿Qué estructura inicia normalmente la despolarización en el corazón sano?',
		array( 'Nodo auriculoventricular', 'Nodo sinoauricular', 'Haz de His', 'Fibras de Purkinje' ), 1,
		array( 'El nodo auriculoventricular demora y transmite impulsos procedentes de la aurícula.', 'El nodo sinoauricular actúa como marcapasos fisiológico.', 'El haz de His transmite el impulso hacia el sistema de conducción ventricular.', 'Las fibras de Purkinje distribuyen el impulso por los ventrículos.' ) ),
);
return array_map( static function ( $item ) {
	return array_combine( array( 'id', 'subject', 'stem', 'options', 'key', 'reasons', 'source' ), array_merge( $item, array( 'EstudiaMed · práctica educativa original' ) ) );
}, $items );
