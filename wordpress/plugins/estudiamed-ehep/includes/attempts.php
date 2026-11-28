<?php
defined( 'ABSPATH' ) || exit;

function estudiamed_ehep_table() {
	global $wpdb;
	return $wpdb->prefix . 'estudia_ehep_attempts';
}

function estudiamed_ehep_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table = estudiamed_ehep_table();
	$collation = $wpdb->get_charset_collate();
	dbDelta( "CREATE TABLE $table (
		id bigint unsigned NOT NULL AUTO_INCREMENT,
		user_id bigint unsigned NOT NULL,
		mode varchar(12) NOT NULL,
		deadline bigint unsigned NOT NULL DEFAULT 0,
		finished bigint unsigned NOT NULL DEFAULT 0,
		revision int unsigned NOT NULL DEFAULT 0,
		items longtext NOT NULL,
		answers longtext NOT NULL,
		summary longtext NOT NULL,
		PRIMARY KEY  (id),
		KEY user_finished (user_id,finished,id)
	) $collation;" );
}

function estudiamed_ehep_routes() {
	$auth = static function () { return is_user_logged_in(); };
	$namespace = 'estudiamed/v1';
	foreach ( array(
		array( '/ehep', 'GET', 'estudiamed_ehep_dashboard_data' ),
		array( '/ehep/attempts', 'POST', 'estudiamed_ehep_start' ),
		array( '/ehep/attempts/(?P<id>[0-9]+)', 'GET', 'estudiamed_ehep_show' ),
		array( '/ehep/attempts/(?P<id>[0-9]+)/answer', 'POST', 'estudiamed_ehep_answer' ),
		array( '/ehep/attempts/(?P<id>[0-9]+)/favorite', 'POST', 'estudiamed_ehep_favorite' ),
		array( '/ehep/attempts/(?P<id>[0-9]+)/tutor', 'POST', 'estudiamed_ehep_tutor' ),
		array( '/ehep/attempts/(?P<id>[0-9]+)/finish', 'POST', 'estudiamed_ehep_finish' ),
	) as $route ) {
		register_rest_route( $namespace, $route[0], array( 'methods' => $route[1], 'callback' => $route[2], 'permission_callback' => $auth ) );
	}
}
add_action( 'rest_api_init', 'estudiamed_ehep_routes' );

function estudiamed_ehep_error( $code, $message, $status ) {
	return new WP_Error( $code, $message, array( 'status' => $status ) );
}

function estudiamed_ehep_dashboard_data( $request ) {
	global $wpdb;
	$table = estudiamed_ehep_table();
	$user = get_current_user_id();
	$page = max( 1, min( 100000, (int) $request['page'] ) );
	$subjects = array();
	$topics = array();
	$favorites = estudiamed_ehep_favorites();
	$favorite_topics = array();
	foreach ( estudiamed_ehep_bank() as $item ) {
		$area = $item['subject'];
		$topic = estudiamed_ehep_topic( $item );
		$subjects[ $area ] = ( $subjects[ $area ] ?? 0 ) + 1;
		$topics[ $area ][ $topic ] = ( $topics[ $area ][ $topic ] ?? 0 ) + 1;
		if ( isset( $favorites[ $item['id'] ] ) ) {
			$favorite_topics[ $area ][ $topic ] = ( $favorite_topics[ $area ][ $topic ] ?? 0 ) + 1;
		}
	}
	ksort( $subjects );
	$active = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE user_id = %d AND finished = 0 ORDER BY id DESC LIMIT 1", $user ) );
	$stats = array( 'attempts' => 0, 'total' => 0, 'correct' => 0, 'subjects' => array() );
	$history = array();
	$topic_results = array();
	$offset = ( $page - 1 ) * 10;
	for ( $cursor = PHP_INT_MAX; ; ) {
		$recent = $wpdb->get_results( $wpdb->prepare( "SELECT id,mode,finished,summary FROM $table WHERE user_id = %d AND finished > 0 AND id < %d ORDER BY id DESC LIMIT 200", $user, $cursor ), ARRAY_A );
		foreach ( $recent as $row ) {
			$cursor = (int) $row['id'];
			$summary = json_decode( $row['summary'], true );
			if ( $stats['attempts'] >= $offset && count( $history ) < 10 ) {
				$history[] = array( 'id' => $cursor, 'mode' => $row['mode'], 'finished' => (int) $row['finished'], 'score' => $summary['correct'], 'total' => $summary['total'] );
			}
			++$stats['attempts'];
			$stats['total'] += $summary['total'];
			$stats['correct'] += $summary['correct'];
			foreach ( $summary['subjects'] as $name => $subject ) {
				$stats['subjects'][ $name ]['total'] = ( $stats['subjects'][ $name ]['total'] ?? 0 ) + $subject['total'];
				$stats['subjects'][ $name ]['correct'] = ( $stats['subjects'][ $name ]['correct'] ?? 0 ) + $subject['correct'];
			}
			foreach ( $summary['topics'] ?? array() as $name => $record ) {
				$topic_results[ $name ] = ( $topic_results[ $name ] ?? 0 ) + $record;
			}
		}
		if ( count( $recent ) < 200 ) {
			break;
		}
	}
	$learning = array();
	$topic_learning = array();
	if ( function_exists( 'estudia_academy_recommendation' ) ) {
		$weak = array_filter( $stats['subjects'], static function ( $subject ) {
			return $subject['total'] >= 3 && $subject['correct'] < $subject['total'];
		} );
		uasort( $weak, static function ( $left, $right ) {
			return ( $left['correct'] / $left['total'] ) <=> ( $right['correct'] / $right['total'] );
		} );
		foreach ( array_slice( array_keys( $weak ), 0, 3 ) as $name ) {
			$resource = estudia_academy_recommendation( $name );
			if ( $resource ) {
				$learning[ $name ] = $resource;
			}
		}
		arsort( $topic_results );
		foreach ( array_slice( array_keys( array_filter( $topic_results ) ), 0, 3 ) as $name ) {
			$resource = estudia_academy_recommendation( $name );
			if ( $resource ) {
				$topic_learning[ $name ] = $resource;
			}
		}
	}
	return array( 'subjects' => (object) $subjects, 'topics' => (object) $topics, 'favoriteTopics' => (object) $favorite_topics, 'stats' => $stats, 'learning' => (object) $learning, 'topicLearning' => (object) $topic_learning, 'history' => $history, 'page' => $page, 'active' => (int) $active );
}

function estudiamed_ehep_start( $request ) {
	global $wpdb;
	$mode = $request['mode'];
	$subject = $request['subject'];
	$count = $request['count'];
	$minutes = $request['minutes'];
	$topic = $request['topic'] ?? '';
	$only_favorites = $request['favorites'] ?? false;
	if ( ! in_array( $mode, array( 'practice', 'mock' ), true ) || ! is_string( $subject ) || ! is_string( $topic ) || ! is_bool( $only_favorites ) ||
		! is_int( $count ) || $count < 1 || $count > 50 ||
		( 'mock' === $mode && ( ! is_int( $minutes ) || $minutes < 1 || $minutes > 120 ) ) ) {
		return estudiamed_ehep_error( 'ehep_config', 'Revisa la configuración del ejercicio.', 400 );
	}
	$favorites = $only_favorites ? estudiamed_ehep_favorites() : array();
	$items = array_values( array_filter( estudiamed_ehep_bank(), static function ( $item ) use ( $subject, $topic, $only_favorites, $favorites ) {
		return ( '' === $subject || $item['subject'] === $subject ) &&
			( '' === $topic || estudiamed_ehep_topic( $item ) === $topic ) &&
			( ! $only_favorites || isset( $favorites[ $item['id'] ] ) );
	} ) );
	if ( count( $items ) < $count ) {
		return estudiamed_ehep_error( 'ehep_count', 'No hay suficientes preguntas en esta selección.', 400 );
	}
	shuffle( $items );
	$items = array_slice( $items, 0, $count );
	foreach ( $items as &$item ) {
		if ( isset( $item['fixedOrder'] ) && ! $item['fixedOrder'] ) {
			$order = array_keys( $item['options'] );
			shuffle( $order );
			$item['options'] = array_map( static function ( $index ) use ( $item ) {
				return $item['options'][ $index ];
			}, $order );
			$item['key'] = array_search( $item['key'], $order, true );
		}
		unset( $item['fixedOrder'] );
	}
	unset( $item );
	$ok = $wpdb->insert( estudiamed_ehep_table(), array(
		'user_id' => get_current_user_id(), 'mode' => $mode, 'deadline' => 'mock' === $mode ? time() + $minutes * 60 : 0,
		'items' => wp_json_encode( $items ), 'answers' => '[]', 'summary' => '{}',
	), array( '%d', '%s', '%d', '%s', '%s', '%s' ) );
	if ( ! $ok ) {
		return estudiamed_ehep_error( 'ehep_store', 'No se pudo guardar el intento.', 500 );
	}
	return estudiamed_ehep_show( array( 'id' => $wpdb->insert_id ) );
}

function estudiamed_ehep_load( $id ) {
	global $wpdb;
	$table = estudiamed_ehep_table();
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d AND user_id = %d", $id, get_current_user_id() ), ARRAY_A );
	if ( ! $row ) {
		return estudiamed_ehep_error( 'ehep_missing', 'No encontramos este intento.', 404 );
	}
	foreach ( array( 'items', 'answers', 'summary' ) as $key ) {
		$row[ $key ] = json_decode( $row[ $key ], true );
	}
	return $row;
}

function estudiamed_ehep_snapshot( $row ) {
	$items = array();
	$favorites = estudiamed_ehep_favorites();
	$omitted = 0;
	foreach ( $row['items'] as $i => $item ) {
		$item['selected'] = $row['answers'][ $i ] ?? null;
		$item['favorite'] = isset( $favorites[ $item['id'] ] );
		$item['topic'] = estudiamed_ehep_topic( $item );
		if ( null === $item['selected'] ) {
			++$omitted;
		}
		if ( isset( $item['image'] ) ) {
			$item['image'] = plugins_url( 'assets/question-images/' . $item['image'], ESTUDIAMED_EHEP_FILE );
		}
		if ( ! $row['finished'] && ( 'mock' === $row['mode'] || null === $item['selected'] ) ) {
			unset( $item['key'], $item['reasons'], $item['explanation'], $item['caces_answer'] );
		} else {
			$item['clinical'] = estudiamed_ehep_clinical_review( $item );
		}
		$items[] = $item;
	}
	if ( $row['finished'] ) {
		$row['summary']['omitted'] = $omitted;
		$row['summary']['incorrect'] = count( $items ) - $row['summary']['correct'] - $omitted;
	}
	return array(
		'id' => (int) $row['id'], 'mode' => $row['mode'], 'deadline' => (int) $row['deadline'],
		'serverTime' => time(), 'finished' => (int) $row['finished'], 'items' => $items, 'summary' => $row['summary'],
	);
}

function estudiamed_ehep_grade( $row ) {
	$summary = array( 'correct' => 0, 'total' => count( $row['items'] ), 'subjects' => array(), 'topics' => array() );
	foreach ( $row['items'] as $i => $item ) {
		$subject = $item['subject'];
		$correct = ( $row['answers'][ $i ] ?? -1 ) === $item['key'] ? 1 : 0;
		$summary['correct'] += $correct;
		if ( ! $correct && isset( $row['answers'][ $i ] ) ) {
			$topic = estudiamed_ehep_topic( $item );
			if ( ! in_array( $topic, array( 'Convocatoria I · 2023', 'Sin tema asignado' ), true ) ) {
				$summary['topics'][ $topic ] = ( $summary['topics'][ $topic ] ?? 0 ) + 1;
			}
		}
		$summary['subjects'][ $subject ]['correct'] = ( $summary['subjects'][ $subject ]['correct'] ?? 0 ) + $correct;
		$summary['subjects'][ $subject ]['total'] = ( $summary['subjects'][ $subject ]['total'] ?? 0 ) + 1;
	}
	return $summary;
}

function estudiamed_ehep_store( $row, $fields, $formats ) {
	global $wpdb;
	$fields['revision'] = (int) $row['revision'] + 1;
	$formats[] = '%d';
	$ok = $wpdb->update( estudiamed_ehep_table(), $fields,
		array( 'id' => $row['id'], 'user_id' => get_current_user_id(), 'revision' => $row['revision'], 'finished' => 0 ),
		$formats, array( '%d', '%d', '%d', '%d' ) );
	if ( false === $ok ) {
		return estudiamed_ehep_error( 'ehep_store', 'No se pudo guardar el intento.', 500 );
	}
	if ( ! $ok ) {
		return estudiamed_ehep_error( 'ehep_conflict', 'El intento cambió. Vuelve a cargarlo.', 409 );
	}
	return true;
}

function estudiamed_ehep_finalize( $row ) {
	if ( $row['finished'] ) {
		return estudiamed_ehep_snapshot( $row );
	}
	$row['summary'] = estudiamed_ehep_grade( $row );
	$row['finished'] = time();
	$stored = estudiamed_ehep_store( $row, array( 'finished' => $row['finished'], 'summary' => wp_json_encode( $row['summary'] ) ), array( '%d', '%s' ) );
	return is_wp_error( $stored ) ? $stored : estudiamed_ehep_snapshot( $row );
}

function estudiamed_ehep_show( $request ) {
	$row = estudiamed_ehep_load( (int) $request['id'] );
	if ( is_wp_error( $row ) ) {
		return $row;
	}
	if ( ! $row['finished'] && $row['deadline'] && time() >= $row['deadline'] ) {
		return estudiamed_ehep_finalize( $row );
	}
	return estudiamed_ehep_snapshot( $row );
}

function estudiamed_ehep_answer( $request ) {
	$row = estudiamed_ehep_load( (int) $request['id'] );
	if ( is_wp_error( $row ) ) {
		return $row;
	}
	if ( $row['finished'] || ( $row['deadline'] && time() >= $row['deadline'] ) ) {
		return estudiamed_ehep_finalize( $row );
	}
	$item = $request['item'];
	$answer = $request['answer'];
	if ( ! is_int( $item ) || ! is_int( $answer ) || ! isset( $row['items'][ $item ]['options'][ $answer ] ) ) {
		return estudiamed_ehep_error( 'ehep_answer', 'Selecciona una alternativa válida.', 400 );
	}
	if ( 'practice' === $row['mode'] && isset( $row['answers'][ $item ] ) ) {
		return estudiamed_ehep_snapshot( $row );
	}
	$row['answers'][ $item ] = $answer;
	$stored = estudiamed_ehep_store( $row, array( 'answers' => wp_json_encode( $row['answers'] ) ), array( '%s' ) );
	return is_wp_error( $stored ) ? $stored : estudiamed_ehep_snapshot( $row );
}

function estudiamed_ehep_finish( $request ) {
	$row = estudiamed_ehep_load( (int) $request['id'] );
	return is_wp_error( $row ) ? $row : estudiamed_ehep_finalize( $row );
}
