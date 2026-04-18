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
     *
     * Se usa DELETE FROM en vez de TRUNCATE porque TRUNCATE falla en tablas
     * InnoDB que son referenciadas por claves foráneas, incluso con
     * FOREIGN_KEY_CHECKS = 0.
     */
    public function truncate_tables(): void {
        global $wpdb;

        $wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' );

        // Orden inverso al de creación (hijas primero) para respetar la cadena FK.
        $tables = [ 'imagenes', 'municipios', 'metas', 'contratos', 'proyectos' ];
        foreach ( $tables as $t ) {
            $table = $this->table( $t );
            $wpdb->query( "DELETE FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query( "ALTER TABLE {$table} AUTO_INCREMENT = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
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
    /**
     * Limpiar caches de todos los gráficos publicados.
     *
     * Se ejecuta automáticamente tras cada importación de datos y en
     * las migraciones del updater. Elimina los transients que almacenan
     * los datos pre-computados de cada gráfico para que la próxima
     * solicitud frontend/admin los re-genere con datos frescos.
     *
     * También limpia los transients de rate-limiting para evitar
     * que un usuario quede bloqueado justo después de un re-import.
     */
    public function clear_chart_caches(): void {
        global $wpdb;

        // Limpiar datos cacheados de cada gráfico (incluye drafts y
        // trash para cubrir todos los posibles transients residuales).
        $chart_ids = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'sgr_chart'"
        );
        foreach ( $chart_ids as $cid ) {
            delete_transient( 'sgr_chart_data_' . $cid );
        }

        // Limpiar rate-limit transients (patrón sgr_rate_*).
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '\_transient\_sgr\_rate\_%'
                OR option_name LIKE '\_transient\_timeout\_sgr\_rate\_%'"
        );

        $this->logger->info( 'Caches de gráficos limpiados: ' . count( $chart_ids ) . ' gráficos.' );
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
        $orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'nombre_proyecto';
        $order           = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

        $query = "SELECT p.* FROM {$this->table('proyectos')} p {$where_sql} ORDER BY p.{$orderby} {$order}";

        if ( (int) $args['limite'] > 0 ) {
            $query    .= ' LIMIT %d OFFSET %d';
            $prepare[] = absint( $args['limite'] );
            $prepare[] = absint( $args['offset'] );
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
     * Expresión SQL canónica para la entidad ejecutora.
     *
     * El API SGR devuelve variantes como "Departamento de Nariño",
     * "Municipio de Yacuanquer", "Fundación Universidad del Valle",
     * "Contratista SPD-SGR" que conviven con las tres canónicas. Aquí
     * se consolidan a los tres buckets conceptuales: Departamento,
     * Municipio y Otro.
     *
     * @param string $alias Alias SQL del campo (p. ej. 'p.entidad_ejecutora_proyecto').
     */
    private function entidad_expr( string $alias = 'p.entidad_ejecutora_proyecto' ): string {
        return "(CASE
            WHEN {$alias} IS NULL OR {$alias} = '' THEN 'Otro'
            WHEN {$alias} LIKE '%epartamento%' THEN 'Departamento'
            WHEN {$alias} LIKE '%unicipio%' THEN 'Municipio'
            ELSE 'Otro'
        END)";
    }

    /**
     * Expresión SQL que deriva la vigencia de un proyecto a partir del
     * prefijo del BPIN. Sólo considera años reales 19xx/20xx (el regex
     * ^[0-9]{4} anterior matchaba erróneamente los BPINs SGR "5200..."
     * como vigencia "5200"). Los proyectos sin año-prefijo caen a un
     * bucket "{dependencia}*" para mantener la serie interpretable.
     */
    private function vigencia_expr( string $alias = 'p.numero_proyecto', string $dep_alias = 'p.dependencia_proyecto' ): string {
        return "(CASE
            WHEN {$alias} REGEXP '^(20|19)[0-9]{2}' THEN SUBSTRING({$alias}, 1, 4)
            WHEN {$dep_alias} = 'IDSN' THEN 'IDSN*'
            WHEN {$dep_alias} = 'Infraestructura' THEN 'Infra*'
            WHEN {$dep_alias} = 'PDA' THEN 'PDA*'
            WHEN {$dep_alias} = 'Regalías' THEN 'Regalías*'
            ELSE 'Otros*'
        END)";
    }

    /**
     * Vistas predefinidas para gráficos con JOINs entre tablas.
     *
     * @return array<string, array{label: string, sql: string, columns: string[]}>
     */
    public function get_chart_views(): array {
        // Expresiones reutilizables (v2.5.2+).
        $entidad_norm  = $this->entidad_expr();
        $vigencia_norm = $this->vigencia_expr();

        return [
            // =====================================================================
            // TOTALES Y AGREGADOS
            // =====================================================================

            'valor_por_dependencia' => [
                'label'   => 'Inversión por Dependencia (IDSN, Regalías, PDA, Infraestructura)',
                'sql'     => "SELECT dependencia_proyecto AS label,
                                     SUM(valor_proyecto) AS value,
                                     COUNT(*) AS count,
                                     ROUND(AVG(valor_proyecto), 2) AS valor_promedio
                              FROM {$this->table('proyectos')}
                              WHERE dependencia_proyecto != ''
                              GROUP BY dependencia_proyecto",
                'columns' => [ 'label', 'value', 'count', 'valor_promedio' ],
            ],
            'valor_por_entidad' => [
                'label'   => 'Inversión por Entidad Ejecutora (Departamento, Municipio, Otro)',
                'sql'     => "SELECT {$entidad_norm} AS label,
                                     SUM(valor_proyecto) AS value,
                                     COUNT(*) AS count,
                                     ROUND(AVG(valor_proyecto), 2) AS valor_promedio
                              FROM {$this->table('proyectos')} p
                              GROUP BY {$entidad_norm}",
                'columns' => [ 'label', 'value', 'count', 'valor_promedio' ],
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
                'sql'     => "SELECT m.nombre AS label,
                                     SUM(m.poblacion_beneficiada) AS value,
                                     COUNT(DISTINCT c.id) AS count
                              FROM {$this->table('municipios')} m
                              INNER JOIN {$this->table('contratos')} c ON m.contrato_id = c.id
                              GROUP BY m.nombre",
                'columns' => [ 'label', 'value', 'count' ],
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

            // =====================================================================
            // DISTRIBUCIÓN / CATEGORÍAS
            // =====================================================================

            'distribucion_riesgo_contratos' => [
                'label'   => 'V-08b · Distribución de Riesgo de Contratos (Pie/Donut)',
                'sql'     => "SELECT
                                CASE
                                    WHEN c.porcentaje_avance_fisico >= 50 THEN 'Riesgo bajo'
                                    WHEN c.porcentaje_avance_fisico >= 10 THEN 'Riesgo medio'
                                    ELSE 'Riesgo alto'
                                END AS label,
                                COUNT(*) AS value,
                                SUM(c.valor_contrato) AS total_valor
                              FROM {$this->table('contratos')} c
                              WHERE c.valor_contrato > 0
                              GROUP BY label",
                'columns' => [ 'label', 'value', 'total_valor' ],
            ],

            // =====================================================================
            // AVANCE FÍSICO
            // =====================================================================

            'scatter_valor_avance' => [
                'label'   => 'V-08 · Scatter: Valor Contrato vs Avance Físico por Municipio',
                'sql'     => "SELECT
                                COALESCE(m.nombre, CONCAT('Contrato ', c.numero_contrato)) AS label,
                                p.dependencia_proyecto AS series,
                                c.valor_contrato AS x,
                                c.porcentaje_avance_fisico AS y,
                                c.valor_contrato AS value
                              FROM {$this->table('contratos')} c
                              INNER JOIN {$this->table('proyectos')} p ON c.proyecto_id = p.id
                              LEFT JOIN {$this->table('municipios')} m ON m.contrato_id = c.id
                              WHERE c.valor_contrato > 0
                                AND p.dependencia_proyecto != ''",
                'columns' => [ 'label', 'series', 'x', 'y', 'value' ],
            ],

            'avance_por_entidad' => [
                'label'   => 'V-19 · Avance Físico por Entidad Ejecutora (Distribución)',
                'sql'     => "SELECT
                                {$entidad_norm} AS label,
                                {$entidad_norm} AS series,
                                c.porcentaje_avance_fisico AS value,
                                c.numero_contrato AS detalle,
                                c.valor_contrato AS total_valor
                              FROM {$this->table('contratos')} c
                              INNER JOIN {$this->table('proyectos')} p ON c.proyecto_id = p.id
                              WHERE c.porcentaje_avance_fisico IS NOT NULL",
                'columns' => [ 'label', 'series', 'value', 'detalle', 'total_valor' ],
            ],

            // =====================================================================
            // CRUCES CON SERIES (barras apiladas/agrupadas)
            // =====================================================================

            'valor_dependencia_x_entidad' => [
                'label'   => 'Inversión: Dependencia x Entidad Ejecutora (Apiladas)',
                'sql'     => "SELECT p.dependencia_proyecto AS label,
                                     {$entidad_norm} AS series,
                                     SUM(p.valor_proyecto) AS value,
                                     COUNT(*) AS count
                              FROM {$this->table('proyectos')} p
                              WHERE p.dependencia_proyecto != ''
                              GROUP BY p.dependencia_proyecto, {$entidad_norm}",
                'columns' => [ 'label', 'series', 'value', 'count' ],
            ],
            'proyectos_dependencia_x_entidad' => [
                'label'   => 'Proyectos: Dependencia x Entidad Ejecutora (Agrupadas)',
                'sql'     => "SELECT p.dependencia_proyecto AS label,
                                     {$entidad_norm} AS series,
                                     COUNT(*) AS value
                              FROM {$this->table('proyectos')} p
                              WHERE p.dependencia_proyecto != ''
                              GROUP BY p.dependencia_proyecto, {$entidad_norm}",
                'columns' => [ 'label', 'series', 'value' ],
            ],
            'valor_municipio_x_dependencia' => [
                'label'   => 'Inversión: Municipio x Dependencia (Apiladas)',
                'sql'     => "SELECT m.nombre AS label,
                                     p.dependencia_proyecto AS series,
                                     SUM(c.valor_contrato) AS value
                              FROM {$this->table('municipios')} m
                              INNER JOIN {$this->table('contratos')} c ON m.contrato_id = c.id
                              INNER JOIN {$this->table('proyectos')} p ON c.proyecto_id = p.id
                              WHERE p.dependencia_proyecto != ''
                              GROUP BY m.nombre, p.dependencia_proyecto",
                'columns' => [ 'label', 'series', 'value' ],
            ],
            'valor_entidad_x_dependencia' => [
                'label'   => 'Inversión: Entidad x Dependencia (Apiladas)',
                'sql'     => "SELECT {$entidad_norm} AS label,
                                     p.dependencia_proyecto AS series,
                                     SUM(p.valor_proyecto) AS value,
                                     COUNT(*) AS count
                              FROM {$this->table('proyectos')} p
                              WHERE p.dependencia_proyecto != ''
                              GROUP BY {$entidad_norm}, p.dependencia_proyecto",
                'columns' => [ 'label', 'series', 'value', 'count' ],
            ],

            // =====================================================================
            // TEMPORAL / VIGENCIAS (V-04, V-05, V-05b)
            //
            // v2.5.5: el label ahora muestra SÓLO fechas numéricas
            // (2023, 2024, 2025, 2026). Los proyectos sin año-prefijo
            // (IDSN*, Infra*...) se excluyen via WHERE REGEXP para que
            // el eje X sea estrictamente cronológico.
            // =====================================================================

            'vigencia_valor' => [
                'label'   => 'V-04 · Inversión por Vigencia',
                'sql'     => "SELECT
                                SUBSTRING(p.numero_proyecto, 1, 4) AS label,
                                SUM(p.valor_proyecto) AS value,
                                COUNT(*) AS count,
                                ROUND(AVG(p.valor_proyecto), 2) AS valor_promedio
                              FROM {$this->table('proyectos')} p
                              WHERE p.numero_proyecto REGEXP '^(20|19)[0-9]{2}'
                              GROUP BY SUBSTRING(p.numero_proyecto, 1, 4)",
                'columns' => [ 'label', 'value', 'count', 'valor_promedio' ],
            ],

            'vigencia_dependencia_x' => [
                'label'   => 'V-05 · Inversión: Vigencia x Dependencia (Apiladas)',
                'sql'     => "SELECT
                                SUBSTRING(p.numero_proyecto, 1, 4) AS label,
                                p.dependencia_proyecto AS series,
                                SUM(p.valor_proyecto) AS value,
                                COUNT(*) AS count
                              FROM {$this->table('proyectos')} p
                              WHERE p.dependencia_proyecto != ''
                                AND p.numero_proyecto REGEXP '^(20|19)[0-9]{2}'
                              GROUP BY SUBSTRING(p.numero_proyecto, 1, 4), p.dependencia_proyecto",
                'columns' => [ 'label', 'series', 'value', 'count' ],
            ],

            'proyectos_vigencia_x_dependencia' => [
                'label'   => 'V-05b · Proyectos: Vigencia x Dependencia (Agrupadas)',
                'sql'     => "SELECT
                                SUBSTRING(p.numero_proyecto, 1, 4) AS label,
                                p.dependencia_proyecto AS series,
                                COUNT(*) AS value
                              FROM {$this->table('proyectos')} p
                              WHERE p.dependencia_proyecto != ''
                                AND p.numero_proyecto REGEXP '^(20|19)[0-9]{2}'
                              GROUP BY SUBSTRING(p.numero_proyecto, 1, 4), p.dependencia_proyecto",
                'columns' => [ 'label', 'series', 'value' ],
            ],

            // =====================================================================
            // MATRIZ MUNICIPIO × DEPENDENCIA (V-14)
            // =====================================================================

            'matrix_municipio_dependencia' => [
                'label'   => 'V-14 · Matriz: Municipio x Dependencia (contratos)',
                'sql'     => "SELECT
                                m.nombre AS label,
                                p.dependencia_proyecto AS series,
                                COUNT(DISTINCT c.id) AS value,
                                SUM(c.valor_contrato) AS total_valor
                              FROM {$this->table('municipios')} m
                              INNER JOIN {$this->table('contratos')} c ON m.contrato_id = c.id
                              INNER JOIN {$this->table('proyectos')} p ON c.proyecto_id = p.id
                              WHERE p.dependencia_proyecto != ''
                              GROUP BY m.nombre, p.dependencia_proyecto",
                'columns' => [ 'label', 'series', 'value', 'total_valor' ],
            ],

            // =====================================================================
            // GEOGRÁFICO (V-12, V-13)
            // =====================================================================

            'geomap_valor_municipio' => [
                'label'         => 'V-13 · Geomap: Inversión por Municipio',
                'sql'           => "SELECT
                                        m.nombre AS nombre_raw,
                                        c.id AS contrato_id,
                                        c.valor_contrato AS valor_contrato,
                                        COALESCE(m.poblacion_beneficiada, 0) AS poblacion,
                                        p.dependencia_proyecto AS dependencia
                                      FROM {$this->table('municipios')} m
                                      INNER JOIN {$this->table('contratos')} c ON m.contrato_id = c.id
                                      INNER JOIN {$this->table('proyectos')} p ON c.proyecto_id = p.id
                                      WHERE m.nombre != ''",
                'columns'       => [ 'id', 'label', 'value', 'count', 'poblacion' ],
                'post_process'  => 'geomap_aggregate_valor',
            ],

            'geomap_contratos_municipio' => [
                'label'         => 'V-12 · Geomap: Contratos por Municipio',
                'sql'           => "SELECT
                                        m.nombre AS nombre_raw,
                                        c.id AS contrato_id,
                                        COALESCE(m.poblacion_beneficiada, 0) AS poblacion,
                                        c.porcentaje_avance_fisico AS avance,
                                        p.dependencia_proyecto AS dependencia
                                      FROM {$this->table('municipios')} m
                                      INNER JOIN {$this->table('contratos')} c ON m.contrato_id = c.id
                                      INNER JOIN {$this->table('proyectos')} p ON c.proyecto_id = p.id
                                      WHERE m.nombre != ''",
                'columns'       => [ 'id', 'label', 'value', 'count', 'avance' ],
                'post_process'  => 'geomap_aggregate_contratos',
            ],
        ];
    }

    /**
     * Ejecutar una vista de gráfico predefinida con límite opcional.
     *
     * Cuando la vista no contiene un ORDER BY explícito, se intenta ordenar
     * por la columna `value`. Para vistas tipo scatter (con columnas `x`/`y`)
     * se ordena por `x` para preservar la progresión natural.
     *
     * Si la vista declara un `post_process`, se ejecuta sobre los resultados
     * crudos y se ignora el LIMIT por SQL: el tope se aplica tras la
     * agregación en PHP (las vistas geomap pueden tener 64 municipios pero
     * requieren barrer todas las filas para agregar correctamente).
     */
    public function execute_chart_view( string $view_key, int $limit = 20, string $order_dir = 'DESC' ): array {
        global $wpdb;

        $views = $this->get_chart_views();
        if ( ! isset( $views[ $view_key ] ) ) {
            return [];
        }

        $view         = $views[ $view_key ];
        $sql          = $view['sql'];
        $columns      = $view['columns'] ?? [];
        $post_process = $view['post_process'] ?? null;

        $order_dir = strtoupper( $order_dir ) === 'ASC' ? 'ASC' : 'DESC';

        // Las vistas con post_process (geomap) necesitan todas las filas; se
        // aplica un tope alto a nivel SQL y un tope final en PHP.
        if ( $post_process ) {
            $sql .= ' LIMIT 5000';
            $raw  = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $raw  = is_array( $raw ) ? $raw : [];
            return $this->apply_post_process( $post_process, $raw, $limit, $order_dir );
        }

        // Sólo agregar ORDER BY si la vista no lo tiene ya.
        if ( stripos( $sql, 'ORDER BY' ) === false ) {
            if ( in_array( 'x', $columns, true ) && in_array( 'y', $columns, true ) ) {
                // Scatter: orden natural por x ascendente (o descendente si se pidió).
                $sql .= " ORDER BY x {$order_dir}";
            } elseif ( in_array( 'value', $columns, true ) ) {
                $sql .= " ORDER BY value {$order_dir}";
            }
        }

        // Vistas con muchos registros individuales (scatter / distribuciones)
        // usan un tope más alto porque cada fila es un dato atómico.
        $hard_cap = 500;
        if ( in_array( $view_key, [ 'scatter_valor_avance', 'avance_por_entidad', 'matrix_municipio_dependencia' ], true ) ) {
            $hard_cap = 1000;
        }
        $limit = min( max( 1, $limit ), $hard_cap );
        $sql  .= $wpdb->prepare( ' LIMIT %d', $limit );

        $results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return is_array( $results ) ? $results : [];
    }

    /**
     * Despachar un post-procesador por clave.
     */
    private function apply_post_process( string $key, array $rows, int $limit, string $order_dir ): array {
        switch ( $key ) {
            case 'geomap_aggregate_valor':
                return $this->geomap_aggregate( $rows, 'valor', $limit, $order_dir );
            case 'geomap_aggregate_contratos':
                return $this->geomap_aggregate( $rows, 'contratos', $limit, $order_dir );
        }
        return $rows;
    }

    /**
     * Agregar filas crudas de municipios a nivel DIVIPOLA usando el
     * normalizador canónico. Devuelve una fila por municipio con
     * `id` = DIVIPOLA (clave para d3plus Geomap), `label` = nombre
     * canónico, `value` = métrica principal (valor o contratos), y
     * metadatos auxiliares.
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function geomap_aggregate( array $rows, string $metric, int $limit, string $order_dir ): array {
        if ( empty( $rows ) || ! class_exists( 'SGR_Suite_Municipios_Normalizer' ) ) {
            return [];
        }

        $agg = []; // DIVIPOLA => accumulator.

        foreach ( $rows as $row ) {
            $raw = (string) ( $row['nombre_raw'] ?? '' );
            if ( '' === $raw ) {
                continue;
            }

            $matches = SGR_Suite_Municipios_Normalizer::resolve( $raw );
            if ( empty( $matches ) ) {
                continue;
            }

            $valor       = (float) ( $row['valor_contrato'] ?? 0 );
            $poblacion   = (int) ( $row['poblacion'] ?? 0 );
            $avance      = isset( $row['avance'] ) ? (float) $row['avance'] : null;
            $dependencia = (string) ( $row['dependencia'] ?? '' );
            $contrato_id = (int) ( $row['contrato_id'] ?? 0 );

            foreach ( $matches as $muni ) {
                $key = $muni['divipola'];
                if ( ! isset( $agg[ $key ] ) ) {
                    $agg[ $key ] = [
                        'id'           => $key,
                        'label'        => $muni['nombre'],
                        'value'        => 0.0,
                        'valor_total'  => 0.0,
                        'contratos'    => 0,
                        'poblacion'    => 0,
                        'avance_sum'   => 0.0,
                        'avance_n'     => 0,
                        '_contratos'   => [],
                        'dependencias' => [],
                    ];
                }
                // Evitar contar un mismo contrato dos veces para el mismo municipio.
                if ( $contrato_id > 0 && ! isset( $agg[ $key ]['_contratos'][ $contrato_id ] ) ) {
                    $agg[ $key ]['_contratos'][ $contrato_id ] = true;
                    $agg[ $key ]['contratos']++;
                    $agg[ $key ]['valor_total'] += $valor;
                    if ( null !== $avance ) {
                        $agg[ $key ]['avance_sum'] += $avance;
                        $agg[ $key ]['avance_n']++;
                    }
                }
                $agg[ $key ]['poblacion'] += $poblacion;
                if ( '' !== $dependencia ) {
                    $agg[ $key ]['dependencias'][ $dependencia ] = true;
                }
            }
        }

        // Finalizar: elegir la métrica principal, calcular promedios, limpiar
        // claves internas y redondear.
        $out = [];
        foreach ( $agg as $entry ) {
            $avance_avg = $entry['avance_n'] > 0 ? round( $entry['avance_sum'] / $entry['avance_n'], 2 ) : 0;
            $entry['avance_promedio'] = $avance_avg;
            $entry['dependencias']    = array_keys( $entry['dependencias'] );
            $entry['value']           = 'valor' === $metric ? (float) $entry['valor_total'] : (int) $entry['contratos'];
            unset( $entry['avance_sum'], $entry['avance_n'], $entry['_contratos'] );
            $out[] = $entry;
        }

        // v2.5.6: NO pre-rellenar municipios sin datos. El topojson
        // dibuja los 64 polígonos, pero sólo los que tienen data real
        // reciben color del colorScale y tooltip. Los demás se pintan
        // con el fill por defecto (#FFFCF3 — configurado en JS). Esto
        // evita confusión con tooltips "sin datos" y hace que d3plus use
        // su tooltip nativo sin interferencia.

        // Ordenar por value.
        usort(
            $out,
            static function ( $a, $b ) use ( $order_dir ) {
                $va = (float) ( $a['value'] ?? 0 );
                $vb = (float) ( $b['value'] ?? 0 );
                if ( $va === $vb ) {
                    return 0;
                }
                if ( 'ASC' === $order_dir ) {
                    return $va < $vb ? -1 : 1;
                }
                return $va > $vb ? -1 : 1;
            }
        );

        $hard_cap = max( 1, min( $limit, 64 ) );
        return array_slice( $out, 0, $hard_cap );
    }
}
