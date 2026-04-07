<?php
/**
 * SGR Suite - Clase de Base de Datos v2.0.0
 *
 * Gestiona la creación, consulta y mantenimiento de las tablas
 * para proyectos, contratos, municipios, metas e imágenes del SGR.
 * Incluye soporte para consultas con JOIN entre tablas para gráficos.
 *
 * @package SGR_Suite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGR_Suite_Database {

    /** @var SGR_Suite_Logger */
    private SGR_Suite_Logger $logger;

    /** @var string Prefijo de tablas */
    private string $prefix;

    public function __construct( SGR_Suite_Logger $logger ) {
        global $wpdb;
        $this->logger = $logger;
        $this->prefix = $wpdb->prefix . 'sgr_';
    }

    /**
     * Nombres de tablas (whitelist).
     */
    public function table( string $name ): string {
        $allowed = [ 'proyectos', 'contratos', 'municipios', 'metas', 'imagenes' ];
        if ( ! in_array( $name, $allowed, true ) ) {
            throw new \InvalidArgumentException( "Tabla no permitida: {$name}" );
        }
        return $this->prefix . $name;
    }

    /**
     * Crear todas las tablas del plugin.
     * Usa dbDelta para tablas + queries directas para FK (dbDelta no las maneja bien).
     */
    public function create_tables(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();

        // 1. Tabla de Proyectos
        dbDelta( "CREATE TABLE {$this->table('proyectos')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            numero_proyecto VARCHAR(100) NOT NULL,
            nombre_proyecto TEXT NOT NULL,
            valor_proyecto DECIMAL(20,2) DEFAULT 0.00,
            dependencia_proyecto VARCHAR(500) DEFAULT '',
            entidad_ejecutora_proyecto VARCHAR(500) DEFAULT '',
            total_contratos INT UNSIGNED DEFAULT 0,
            fecha_importacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_numero_proyecto (numero_proyecto),
            KEY idx_dependencia (dependencia_proyecto(191)),
            KEY idx_entidad (entidad_ejecutora_proyecto(191)),
            KEY idx_fecha_importacion (fecha_importacion)
        ) {$charset};" );

        // 2. Tabla de Contratos
        dbDelta( "CREATE TABLE {$this->table('contratos')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            proyecto_id BIGINT UNSIGNED NOT NULL,
            numero_contrato VARCHAR(100) DEFAULT '',
            valor_contrato DECIMAL(20,2) DEFAULT 0.00,
            objeto_contrato TEXT DEFAULT NULL,
            es_ops_ejec_contractual VARCHAR(50) DEFAULT '',
            porcentaje_avance_fisico DECIMAL(8,2) DEFAULT 0.00,
            descripcion_ejec_contractual LONGTEXT DEFAULT NULL,
            fecha_importacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_proyecto_id (proyecto_id),
            KEY idx_numero_contrato (numero_contrato)
        ) {$charset};" );

        // 3. Tabla de Municipios
        dbDelta( "CREATE TABLE {$this->table('municipios')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            contrato_id BIGINT UNSIGNED NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            poblacion_beneficiada INT UNSIGNED DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_contrato_id (contrato_id),
            KEY idx_nombre (nombre(191))
        ) {$charset};" );

        // 4. Tabla de Metas
        dbDelta( "CREATE TABLE {$this->table('metas')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            proyecto_id BIGINT UNSIGNED NOT NULL,
            descripcion_meta TEXT NOT NULL,
            PRIMARY KEY (id),
            KEY idx_proyecto_id (proyecto_id)
        ) {$charset};" );

        // 5. Tabla de Imágenes
        dbDelta( "CREATE TABLE {$this->table('imagenes')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            contrato_id BIGINT UNSIGNED NOT NULL,
            url_imagen TEXT NOT NULL,
            PRIMARY KEY (id),
            KEY idx_contrato_id (contrato_id)
        ) {$charset};" );

        // 6. Foreign Keys (dbDelta no las maneja correctamente, usar queries directas)
        $this->ensure_foreign_keys();

        $this->logger->info( 'Tablas del SGR creadas/actualizadas correctamente.' );
    }

    /**
     * Crear FK constraints si no existen.
     */
    private function ensure_foreign_keys(): void {
        global $wpdb;

        $fks = [
            [
                'table'      => $this->table( 'contratos' ),
                'name'       => 'fk_contrato_proyecto',
                'column'     => 'proyecto_id',
                'ref_table'  => $this->table( 'proyectos' ),
                'ref_column' => 'id',
            ],
            [
                'table'      => $this->table( 'municipios' ),
                'name'       => 'fk_municipio_contrato',
                'column'     => 'contrato_id',
                'ref_table'  => $this->table( 'contratos' ),
                'ref_column' => 'id',
            ],
            [
                'table'      => $this->table( 'metas' ),
                'name'       => 'fk_meta_proyecto',
                'column'     => 'proyecto_id',
                'ref_table'  => $this->table( 'proyectos' ),
                'ref_column' => 'id',
            ],
            [
                'table'      => $this->table( 'imagenes' ),
                'name'       => 'fk_imagen_contrato',
                'column'     => 'contrato_id',
                'ref_table'  => $this->table( 'contratos' ),
                'ref_column' => 'id',
            ],
        ];

        foreach ( $fks as $fk ) {
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                     WHERE CONSTRAINT_SCHEMA = DATABASE()
                     AND CONSTRAINT_NAME = %s
                     AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
                    $fk['name']
                )
            );

            if ( ! $exists ) {
                $wpdb->query( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    "ALTER TABLE {$fk['table']}
                     ADD CONSTRAINT {$fk['name']}
                     FOREIGN KEY ({$fk['column']})
                     REFERENCES {$fk['ref_table']}({$fk['ref_column']})
                     ON DELETE CASCADE"
                );
            }
        }
    }

    /**
     * Eliminar todas las tablas del plugin.
     */
    public function drop_tables(): void {
        global $wpdb;

        $wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' );
        $tables = [ 'imagenes', 'municipios', 'metas', 'contratos', 'proyectos' ];
        foreach ( $tables as $t ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$this->table( $t )}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }
        $wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1' );

        $this->logger->info( 'Todas las tablas del SGR fueron eliminadas.' );
    }

    /**
     * Vaciar todas las tablas.
     */
    public function truncate_tables(): void {
        global $wpdb;

        $wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' );
        $tables = [ 'imagenes', 'municipios', 'metas', 'contratos', 'proyectos' ];
        foreach ( $tables as $t ) {
            $wpdb->query( "TRUNCATE TABLE {$this->table( $t )}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }
        $wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1' );

        $this->logger->info( 'Todas las tablas del SGR fueron vaciadas.' );
    }

    /**
     * AJAX: Vaciar datos (también limpia cache de gráficos).
     */
    public function ajax_truncate_data(): void {
        check_ajax_referer( 'sgr_suite_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Sin permisos.' ], 403 );
        }

        $this->truncate_tables();
        $this->clear_chart_caches();

        wp_send_json_success( [ 'message' => 'Datos eliminados correctamente.' ] );
    }

    /**
     * Limpiar todas las caches de gráficos.
     */
    public function clear_chart_caches(): void {
        global $wpdb;
        $chart_ids = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'sgr_chart' AND post_status = 'publish'"
        );
        foreach ( $chart_ids as $cid ) {
            delete_transient( 'sgr_chart_data_' . $cid );
        }
    }

    /**
     * Insertar o actualizar un proyecto completo con sus relaciones.
     */
    public function upsert_proyecto( array $proyecto ): int|false {
        global $wpdb;

        $numero = sanitize_text_field( $proyecto['numeroProyecto'] ?? '' );
        if ( empty( $numero ) ) {
            return false;
        }

        $nombre   = sanitize_text_field( $proyecto['nombreProyecto'] ?? '' );
        $valor    = floatval( $proyecto['valorProyecto'] ?? 0 );
        $dep      = sanitize_text_field( $proyecto['dependenciaProyecto'] ?? '' );
        $entidad  = sanitize_text_field( $proyecto['entidadEjecutoraProyecto'] ?? '' );
        $contratos_count = ! empty( $proyecto['contratosProyecto'] ) ? count( $proyecto['contratosProyecto'] ) : 0;

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$this->table('proyectos')}
                    (numero_proyecto, nombre_proyecto, valor_proyecto, dependencia_proyecto, entidad_ejecutora_proyecto, total_contratos, fecha_importacion)
                VALUES (%s, %s, %f, %s, %s, %d, NOW())
                ON DUPLICATE KEY UPDATE
                    nombre_proyecto = VALUES(nombre_proyecto),
                    valor_proyecto = VALUES(valor_proyecto),
                    dependencia_proyecto = VALUES(dependencia_proyecto),
                    entidad_ejecutora_proyecto = VALUES(entidad_ejecutora_proyecto),
                    total_contratos = VALUES(total_contratos)",
                $numero, $nombre, $valor, $dep, $entidad, $contratos_count
            )
        );

        $proyecto_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->table('proyectos')} WHERE numero_proyecto = %s",
                $numero
            )
        );

        if ( ! $proyecto_id ) {
            return false;
        }

        $proyecto_id = (int) $proyecto_id;

        // Limpiar datos previos: solo borrar contratos (CASCADE elimina municipios e imágenes) y metas
        $wpdb->delete( $this->table( 'metas' ), [ 'proyecto_id' => $proyecto_id ], [ '%d' ] );
        $wpdb->delete( $this->table( 'contratos' ), [ 'proyecto_id' => $proyecto_id ], [ '%d' ] );

        // Insertar metas
        if ( ! empty( $proyecto['metasProyecto'] ) && is_array( $proyecto['metasProyecto'] ) ) {
            foreach ( $proyecto['metasProyecto'] as $meta ) {
                $meta_text = sanitize_text_field( $meta );
                if ( ! empty( $meta_text ) ) {
                    $wpdb->insert(
                        $this->table( 'metas' ),
                        [ 'proyecto_id' => $proyecto_id, 'descripcion_meta' => $meta_text ],
                        [ '%d', '%s' ]
                    );
                }
            }
        }

        // Insertar contratos
        if ( ! empty( $proyecto['contratosProyecto'] ) && is_array( $proyecto['contratosProyecto'] ) ) {
            foreach ( $proyecto['contratosProyecto'] as $idx => $contrato ) {
                $contrato_id = $this->insert_contrato( $proyecto_id, $contrato, $idx );
                if ( $contrato_id ) {
                    $this->insert_municipios_contrato( $contrato_id, $contrato );
                    $this->insert_imagenes_contrato( $contrato_id, $contrato );
                }
            }
        }

        return $proyecto_id;
    }

    private function insert_contrato( int $proyecto_id, array $contrato, int $idx ): int|false {
        global $wpdb;

        $inserted = $wpdb->insert(
            $this->table( 'contratos' ),
            [
                'proyecto_id'                  => $proyecto_id,
                'numero_contrato'              => sanitize_text_field( $contrato['numeroContrato'] ?? (string) ( $idx + 1 ) ),
                'valor_contrato'               => floatval( $contrato['valorContrato'] ?? 0 ),
                'objeto_contrato'              => sanitize_textarea_field( $contrato['objetoContrato'] ?? '' ),
                'es_ops_ejec_contractual'      => sanitize_text_field( $contrato['esOpsEjecContractual'] ?? '' ),
                'porcentaje_avance_fisico'     => floatval( $contrato['procentajeAvanceFisico'] ?? 0 ),
                'descripcion_ejec_contractual' => sanitize_textarea_field( $contrato['descripcionEjecContractual'] ?? '' ),
            ],
            [ '%d', '%s', '%f', '%s', '%s', '%f', '%s' ]
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    private function insert_municipios_contrato( int $contrato_id, array $contrato ): void {
        global $wpdb;

        if ( empty( $contrato['municipiosEjecContractual'] ) || ! is_array( $contrato['municipiosEjecContractual'] ) ) {
            return;
        }

        foreach ( $contrato['municipiosEjecContractual'] as $mun ) {
            $nombre = sanitize_text_field( $mun['nombre'] ?? '' );
            if ( ! empty( $nombre ) ) {
                $wpdb->insert(
                    $this->table( 'municipios' ),
                    [
                        'contrato_id'          => $contrato_id,
                        'nombre'               => $nombre,
                        'poblacion_beneficiada' => absint( $mun['poblacion_beneficiada'] ?? 0 ),
                    ],
                    [ '%d', '%s', '%d' ]
                );
            }
        }
    }

    private function insert_imagenes_contrato( int $contrato_id, array $contrato ): void {
        global $wpdb;

        if ( empty( $contrato['imagenesEjecContractual'] ) || ! is_array( $contrato['imagenesEjecContractual'] ) ) {
            return;
        }

        foreach ( $contrato['imagenesEjecContractual'] as $img ) {
            $url = esc_url_raw( $img );
            if ( ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) ) {
                $wpdb->insert(
                    $this->table( 'imagenes' ),
                    [ 'contrato_id' => $contrato_id, 'url_imagen' => $url ],
                    [ '%d', '%s' ]
                );
            }
        }
    }

    // =========================================================================
    // CONSULTAS DE LECTURA
    // =========================================================================

    /**
     * Obtener proyectos con sus relaciones.
     */
    public function get_proyectos( array $args = [] ): array {
        global $wpdb;

        $defaults = [
            'limite'      => 0,
            'offset'      => 0,
            'buscar'      => '',
            'dependencia' => '',
            'entidad'     => '',
            'orderby'     => 'nombre_proyecto',
            'order'       => 'ASC',
        ];
        $args = wp_parse_args( $args, $defaults );

        $where   = [];
        $prepare = [];

        if ( ! empty( $args['buscar'] ) ) {
            $like      = '%' . $wpdb->esc_like( sanitize_text_field( $args['buscar'] ) ) . '%';
            $where[]   = '(p.nombre_proyecto LIKE %s OR p.numero_proyecto LIKE %s)';
            $prepare[] = $like;
            $prepare[] = $like;
        }

        if ( ! empty( $args['dependencia'] ) ) {
            $where[]   = 'p.dependencia_proyecto = %s';
            $prepare[] = sanitize_text_field( $args['dependencia'] );
        }

        if ( ! empty( $args['entidad'] ) ) {
            $where[]   = 'p.entidad_ejecutora_proyecto = %s';
            $prepare[] = sanitize_text_field( $args['entidad'] );
        }

        $where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

        $allowed_orderby = [ 'nombre_proyecto', 'numero_proyecto', 'valor_proyecto', 'total_contratos', 'fecha_importacion' ];
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'nombre_proyecto';
        $order   = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

        $limit_sql = '';
        if ( $args['limite'] > 0 ) {
            $where[]   = '1=1'; // placeholder for prepare
            $limit_sql = 'LIMIT %d OFFSET %d';
            $prepare[] = absint( $args['limite'] );
            $prepare[] = absint( $args['offset'] );
            // Remove the placeholder
            array_pop( $where );
        }

        // Rebuild where_sql after limit adjustments
        $where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

        $query = "SELECT p.* FROM {$this->table('proyectos')} p {$where_sql} ORDER BY {$orderby} {$order}";
        if ( ! empty( $limit_sql ) ) {
            $query .= " {$limit_sql}";
        }

        if ( ! empty( $prepare ) ) {
            $query = $wpdb->prepare( $query, ...$prepare ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }

        $proyectos = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        if ( empty( $proyectos ) ) {
            return [];
        }

        foreach ( $proyectos as &$proyecto ) {
            $pid = (int) $proyecto['id'];
            $proyecto['metas']     = $this->get_metas_proyecto( $pid );
            $proyecto['contratos'] = $this->get_contratos_proyecto( $pid );
        }

        return $proyectos;
    }

    public function count_proyectos( array $args = [] ): int {
        global $wpdb;

        $where   = [];
        $prepare = [];

        if ( ! empty( $args['buscar'] ) ) {
            $like      = '%' . $wpdb->esc_like( sanitize_text_field( $args['buscar'] ) ) . '%';
            $where[]   = '(p.nombre_proyecto LIKE %s OR p.numero_proyecto LIKE %s)';
            $prepare[] = $like;
            $prepare[] = $like;
        }
        if ( ! empty( $args['dependencia'] ) ) {
            $where[]   = 'p.dependencia_proyecto = %s';
            $prepare[] = sanitize_text_field( $args['dependencia'] );
        }
        if ( ! empty( $args['entidad'] ) ) {
            $where[]   = 'p.entidad_ejecutora_proyecto = %s';
            $prepare[] = sanitize_text_field( $args['entidad'] );
        }

        $where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';
        $query     = "SELECT COUNT(*) FROM {$this->table('proyectos')} p {$where_sql}";

        if ( ! empty( $prepare ) ) {
            $query = $wpdb->prepare( $query, ...$prepare ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }

        return (int) $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    public function get_metas_proyecto( int $proyecto_id ): array {
        global $wpdb;
        return $wpdb->get_col(
            $wpdb->prepare( "SELECT descripcion_meta FROM {$this->table('metas')} WHERE proyecto_id = %d ORDER BY id", $proyecto_id )
        );
    }

    public function get_contratos_proyecto( int $proyecto_id ): array {
        global $wpdb;

        $contratos = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$this->table('contratos')} WHERE proyecto_id = %d ORDER BY id", $proyecto_id ),
            ARRAY_A
        );

        if ( empty( $contratos ) ) {
            return [];
        }

        foreach ( $contratos as &$c ) {
            $cid = (int) $c['id'];
            $c['municipios'] = $this->get_municipios_contrato( $cid );
            $c['imagenes']   = $this->get_imagenes_contrato( $cid );
        }

        return $contratos;
    }

    public function get_municipios_contrato( int $contrato_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare( "SELECT nombre, poblacion_beneficiada FROM {$this->table('municipios')} WHERE contrato_id = %d ORDER BY nombre", $contrato_id ),
            ARRAY_A
        );
    }

    public function get_imagenes_contrato( int $contrato_id ): array {
        global $wpdb;
        return $wpdb->get_col(
            $wpdb->prepare( "SELECT url_imagen FROM {$this->table('imagenes')} WHERE contrato_id = %d ORDER BY id", $contrato_id )
        );
    }

    public function get_stats(): array {
        global $wpdb;

        $total_proyectos  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table('proyectos')}" );
        $total_contratos  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table('contratos')}" );
        $total_valor      = (float) $wpdb->get_var( "SELECT COALESCE(SUM(valor_proyecto), 0) FROM {$this->table('proyectos')}" );
        $total_metas      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table('metas')}" );
        $total_municipios = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT nombre) FROM {$this->table('municipios')}" );

        return [
            'totalProyectos'  => $total_proyectos,
            'totalContratos'  => $total_contratos,
            'totalValor'      => $total_valor,
            'totalMetas'      => $total_metas,
            'totalMunicipios' => $total_municipios,
            'municipios'      => $wpdb->get_col( "SELECT DISTINCT nombre FROM {$this->table('municipios')} ORDER BY nombre" ),
            'dependencias'    => $wpdb->get_col( "SELECT DISTINCT dependencia_proyecto FROM {$this->table('proyectos')} WHERE dependencia_proyecto != '' ORDER BY dependencia_proyecto" ),
            'entidades'       => $wpdb->get_col( "SELECT DISTINCT entidad_ejecutora_proyecto FROM {$this->table('proyectos')} WHERE entidad_ejecutora_proyecto != '' ORDER BY entidad_ejecutora_proyecto" ),
        ];
    }

    public function get_proyecto_by_bpin( string $bpin ): ?array {
        global $wpdb;

        $proyecto = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table('proyectos')} WHERE numero_proyecto = %s", sanitize_text_field( $bpin ) ),
            ARRAY_A
        );

        if ( ! $proyecto ) {
            return null;
        }

        $pid = (int) $proyecto['id'];
        $proyecto['metas']     = $this->get_metas_proyecto( $pid );
        $proyecto['contratos'] = $this->get_contratos_proyecto( $pid );

        return $proyecto;
    }

    public function get_last_import_date(): ?string {
        global $wpdb;
        return $wpdb->get_var( "SELECT MAX(fecha_importacion) FROM {$this->table('proyectos')}" );
    }

    // =========================================================================
    // CONSULTAS PARA GRÁFICOS (con soporte de JOINs)
    // =========================================================================

    /**
     * Vistas predefinidas para gráficos con JOINs entre tablas.
     *
     * @return array<string, array{label: string, sql: string, columns: string[]}>
     */
    public function get_chart_views(): array {
        return [
            'proyectos' => [
                'label'   => 'Proyectos',
                'sql'     => "SELECT * FROM {$this->table('proyectos')}",
                'columns' => [
                    'numero_proyecto', 'nombre_proyecto', 'valor_proyecto',
                    'dependencia_proyecto', 'entidad_ejecutora_proyecto', 'total_contratos',
                ],
            ],
            'contratos' => [
                'label'   => 'Contratos',
                'sql'     => "SELECT * FROM {$this->table('contratos')}",
                'columns' => [
                    'numero_contrato', 'valor_contrato', 'porcentaje_avance_fisico',
                    'es_ops_ejec_contractual', 'proyecto_id',
                ],
            ],
            'valor_por_dependencia' => [
                'label'   => 'Valor por Dependencia',
                'sql'     => "SELECT dependencia_proyecto AS label, SUM(valor_proyecto) AS value, COUNT(*) AS count
                              FROM {$this->table('proyectos')}
                              WHERE dependencia_proyecto != ''
                              GROUP BY dependencia_proyecto",
                'columns' => [ 'label', 'value', 'count' ],
            ],
            'valor_por_entidad' => [
                'label'   => 'Valor por Entidad Ejecutora',
                'sql'     => "SELECT entidad_ejecutora_proyecto AS label, SUM(valor_proyecto) AS value, COUNT(*) AS count
                              FROM {$this->table('proyectos')}
                              WHERE entidad_ejecutora_proyecto != ''
                              GROUP BY entidad_ejecutora_proyecto",
                'columns' => [ 'label', 'value', 'count' ],
            ],
            'valor_por_municipio' => [
                'label'   => 'Inversión por Municipio',
                'sql'     => "SELECT m.nombre AS label, SUM(c.valor_contrato) AS value, COUNT(DISTINCT c.id) AS count
                              FROM {$this->table('municipios')} m
                              INNER JOIN {$this->table('contratos')} c ON m.contrato_id = c.id
                              GROUP BY m.nombre",
                'columns' => [ 'label', 'value', 'count' ],
            ],
            'poblacion_por_municipio' => [
                'label'   => 'Población Beneficiada por Municipio',
                'sql'     => "SELECT m.nombre AS label, SUM(m.poblacion_beneficiada) AS value
                              FROM {$this->table('municipios')} m
                              GROUP BY m.nombre",
                'columns' => [ 'label', 'value' ],
            ],
            'contratos_por_dependencia' => [
                'label'   => 'Contratos por Dependencia',
                'sql'     => "SELECT p.dependencia_proyecto AS label, COUNT(c.id) AS value, SUM(c.valor_contrato) AS total_valor
                              FROM {$this->table('contratos')} c
                              INNER JOIN {$this->table('proyectos')} p ON c.proyecto_id = p.id
                              WHERE p.dependencia_proyecto != ''
                              GROUP BY p.dependencia_proyecto",
                'columns' => [ 'label', 'value', 'total_valor' ],
            ],
            'avance_promedio_por_dependencia' => [
                'label'   => 'Avance Físico Promedio por Dependencia',
                'sql'     => "SELECT p.dependencia_proyecto AS label, AVG(c.porcentaje_avance_fisico) AS value
                              FROM {$this->table('contratos')} c
                              INNER JOIN {$this->table('proyectos')} p ON c.proyecto_id = p.id
                              WHERE p.dependencia_proyecto != ''
                              GROUP BY p.dependencia_proyecto",
                'columns' => [ 'label', 'value' ],
            ],
            'metas_por_dependencia' => [
                'label'   => 'Metas por Dependencia',
                'sql'     => "SELECT p.dependencia_proyecto AS label, COUNT(mt.id) AS value
                              FROM {$this->table('metas')} mt
                              INNER JOIN {$this->table('proyectos')} p ON mt.proyecto_id = p.id
                              WHERE p.dependencia_proyecto != ''
                              GROUP BY p.dependencia_proyecto",
                'columns' => [ 'label', 'value' ],
            ],
            'top_proyectos_valor' => [
                'label'   => 'Top Proyectos por Valor',
                'sql'     => "SELECT CONCAT(numero_proyecto, ' - ', LEFT(nombre_proyecto, 60)) AS label, valor_proyecto AS value
                              FROM {$this->table('proyectos')}
                              WHERE valor_proyecto > 0
                              ORDER BY valor_proyecto DESC",
                'columns' => [ 'label', 'value' ],
            ],
            'municipios_por_proyecto' => [
                'label'   => 'Municipios por Proyecto (Top)',
                'sql'     => "SELECT CONCAT(p.numero_proyecto, ' - ', LEFT(p.nombre_proyecto, 40)) AS label,
                                     COUNT(DISTINCT m.nombre) AS value
                              FROM {$this->table('proyectos')} p
                              INNER JOIN {$this->table('contratos')} c ON c.proyecto_id = p.id
                              INNER JOIN {$this->table('municipios')} m ON m.contrato_id = c.id
                              GROUP BY p.id, p.numero_proyecto, p.nombre_proyecto",
                'columns' => [ 'label', 'value' ],
            ],
        ];
    }

    /**
     * Ejecutar una vista de gráfico predefinida con límite opcional.
     */
    public function execute_chart_view( string $view_key, int $limit = 20, string $order_dir = 'DESC' ): array {
        global $wpdb;

        $views = $this->get_chart_views();
        if ( ! isset( $views[ $view_key ] ) ) {
            return [];
        }

        $view = $views[ $view_key ];
        $sql  = $view['sql'];

        $order_dir = strtoupper( $order_dir ) === 'ASC' ? 'ASC' : 'DESC';

        // Solo agregar ORDER BY si la vista no lo tiene ya
        if ( stripos( $sql, 'ORDER BY' ) === false ) {
            $sql .= " ORDER BY value {$order_dir}";
        }

        $limit = min( max( 1, $limit ), 500 );
        $sql  .= $wpdb->prepare( ' LIMIT %d', $limit );

        $results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return is_array( $results ) ? $results : [];
    }
}
