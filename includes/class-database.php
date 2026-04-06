<?php
/**
 * SGR Suite - Clase de Base de Datos
 *
 * Gestiona la creación, consulta y mantenimiento de las tablas
 * para proyectos, contratos, municipios, metas e imágenes del SGR.
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
     * Nombres de tablas.
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
     */
    public function create_tables(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();

        // Tabla de Proyectos
        $sql_proyectos = "CREATE TABLE {$this->table('proyectos')} (
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
        ) {$charset};";

        // Tabla de Contratos
        $sql_contratos = "CREATE TABLE {$this->table('contratos')} (
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
            KEY idx_numero_contrato (numero_contrato),
            CONSTRAINT fk_contrato_proyecto FOREIGN KEY (proyecto_id) REFERENCES {$this->table('proyectos')}(id) ON DELETE CASCADE
        ) {$charset};";

        // Tabla de Municipios por contrato
        $sql_municipios = "CREATE TABLE {$this->table('municipios')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            contrato_id BIGINT UNSIGNED NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            poblacion_beneficiada INT UNSIGNED DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_contrato_id (contrato_id),
            KEY idx_nombre (nombre(191)),
            CONSTRAINT fk_municipio_contrato FOREIGN KEY (contrato_id) REFERENCES {$this->table('contratos')}(id) ON DELETE CASCADE
        ) {$charset};";

        // Tabla de Metas por proyecto
        $sql_metas = "CREATE TABLE {$this->table('metas')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            proyecto_id BIGINT UNSIGNED NOT NULL,
            descripcion_meta TEXT NOT NULL,
            PRIMARY KEY (id),
            KEY idx_proyecto_id (proyecto_id),
            CONSTRAINT fk_meta_proyecto FOREIGN KEY (proyecto_id) REFERENCES {$this->table('proyectos')}(id) ON DELETE CASCADE
        ) {$charset};";

        // Tabla de Imágenes por contrato
        $sql_imagenes = "CREATE TABLE {$this->table('imagenes')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            contrato_id BIGINT UNSIGNED NOT NULL,
            url_imagen TEXT NOT NULL,
            PRIMARY KEY (id),
            KEY idx_contrato_id (contrato_id),
            CONSTRAINT fk_imagen_contrato FOREIGN KEY (contrato_id) REFERENCES {$this->table('contratos')}(id) ON DELETE CASCADE
        ) {$charset};";

        dbDelta( $sql_proyectos );
        dbDelta( $sql_contratos );
        dbDelta( $sql_municipios );
        dbDelta( $sql_metas );
        dbDelta( $sql_imagenes );

        $this->logger->info( 'Tablas del SGR creadas/actualizadas correctamente.' );
    }

    /**
     * Eliminar todas las tablas del plugin.
     */
    public function drop_tables(): void {
        global $wpdb;

        // Orden inverso por dependencias de FK
        $tables = [ 'imagenes', 'municipios', 'metas', 'contratos', 'proyectos' ];
        foreach ( $tables as $t ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$this->table( $t )}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }

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
     * AJAX: Vaciar datos.
     */
    public function ajax_truncate_data(): void {
        check_ajax_referer( 'sgr_suite_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Sin permisos.' ], 403 );
        }

        $this->truncate_tables();
        wp_send_json_success( [ 'message' => 'Datos eliminados correctamente.' ] );
    }

    /**
     * Insertar o actualizar un proyecto completo con sus relaciones.
     *
     * @param array $proyecto Datos del proyecto desde la API.
     * @return int|false ID del proyecto insertado o false en error.
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

        // Upsert proyecto
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
                $numero,
                $nombre,
                $valor,
                $dep,
                $entidad,
                $contratos_count
            )
        );

        // Obtener ID
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

        // Limpiar datos relacionados previos
        $this->delete_related_data( $proyecto_id );

        // Insertar metas
        if ( ! empty( $proyecto['metasProyecto'] ) && is_array( $proyecto['metasProyecto'] ) ) {
            foreach ( $proyecto['metasProyecto'] as $meta ) {
                $meta_text = sanitize_text_field( $meta );
                if ( ! empty( $meta_text ) ) {
                    $wpdb->insert(
                        $this->table( 'metas' ),
                        [
                            'proyecto_id'     => $proyecto_id,
                            'descripcion_meta' => $meta_text,
                        ],
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

    /**
     * Eliminar datos relacionados de un proyecto (contratos, metas, municipios, imágenes).
     */
    private function delete_related_data( int $proyecto_id ): void {
        global $wpdb;

        // Las FK con CASCADE manejan las dependencias
        $wpdb->delete( $this->table( 'metas' ), [ 'proyecto_id' => $proyecto_id ], [ '%d' ] );
        $wpdb->delete( $this->table( 'contratos' ), [ 'proyecto_id' => $proyecto_id ], [ '%d' ] );
    }

    /**
     * Insertar un contrato.
     */
    private function insert_contrato( int $proyecto_id, array $contrato, int $idx ): int|false {
        global $wpdb;

        $numero     = sanitize_text_field( $contrato['numeroContrato'] ?? (string) ( $idx + 1 ) );
        $valor      = floatval( $contrato['valorContrato'] ?? 0 );
        $objeto     = sanitize_textarea_field( $contrato['objetoContrato'] ?? '' );
        $es_ops     = sanitize_text_field( $contrato['esOpsEjecContractual'] ?? '' );
        $avance     = floatval( $contrato['procentajeAvanceFisico'] ?? 0 );
        $desc       = sanitize_textarea_field( $contrato['descripcionEjecContractual'] ?? '' );

        $inserted = $wpdb->insert(
            $this->table( 'contratos' ),
            [
                'proyecto_id'                  => $proyecto_id,
                'numero_contrato'              => $numero,
                'valor_contrato'               => $valor,
                'objeto_contrato'              => $objeto,
                'es_ops_ejec_contractual'      => $es_ops,
                'porcentaje_avance_fisico'     => $avance,
                'descripcion_ejec_contractual' => $desc,
            ],
            [ '%d', '%s', '%f', '%s', '%s', '%f', '%s' ]
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    /**
     * Insertar municipios de un contrato.
     */
    private function insert_municipios_contrato( int $contrato_id, array $contrato ): void {
        global $wpdb;

        if ( empty( $contrato['municipiosEjecContractual'] ) || ! is_array( $contrato['municipiosEjecContractual'] ) ) {
            return;
        }

        foreach ( $contrato['municipiosEjecContractual'] as $mun ) {
            $nombre    = sanitize_text_field( $mun['nombre'] ?? '' );
            $poblacion = absint( $mun['poblacion_beneficiada'] ?? 0 );

            if ( ! empty( $nombre ) ) {
                $wpdb->insert(
                    $this->table( 'municipios' ),
                    [
                        'contrato_id'          => $contrato_id,
                        'nombre'               => $nombre,
                        'poblacion_beneficiada' => $poblacion,
                    ],
                    [ '%d', '%s', '%d' ]
                );
            }
        }
    }

    /**
     * Insertar imágenes de un contrato.
     */
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
                    [
                        'contrato_id' => $contrato_id,
                        'url_imagen'  => $url,
                    ],
                    [ '%d', '%s' ]
                );
            }
        }
    }

    /**
     * Obtener todos los proyectos con sus relaciones desde la BD.
     *
     * @param array $args Argumentos de consulta opcionales.
     * @return array
     */
    public function get_proyectos( array $args = [] ): array {
        global $wpdb;

        $defaults = [
            'limite'      => 0,
            'offset'      => 0,
            'buscar'      => '',
            'dependencia' => '',
            'entidad'     => '',
            'municipio'   => '',
            'orderby'     => 'nombre_proyecto',
            'order'       => 'ASC',
        ];
        $args = wp_parse_args( $args, $defaults );

        $where   = [ '1=1' ];
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

        $where_sql = implode( ' AND ', $where );

        // Ordenamiento seguro
        $allowed_orderby = [ 'nombre_proyecto', 'numero_proyecto', 'valor_proyecto', 'total_contratos', 'fecha_importacion' ];
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'nombre_proyecto';
        $order   = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

        $limit_sql = '';
        if ( $args['limite'] > 0 ) {
            $limit_sql = $wpdb->prepare( 'LIMIT %d OFFSET %d', absint( $args['limite'] ), absint( $args['offset'] ) );
        }

        $query = "SELECT p.* FROM {$this->table('proyectos')} p WHERE {$where_sql} ORDER BY {$orderby} {$order} {$limit_sql}";

        if ( ! empty( $prepare ) ) {
            $query = $wpdb->prepare( $query, ...$prepare ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }

        $proyectos = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        if ( empty( $proyectos ) ) {
            return [];
        }

        // Cargar relaciones
        foreach ( $proyectos as &$proyecto ) {
            $pid = (int) $proyecto['id'];
            $proyecto['metas']     = $this->get_metas_proyecto( $pid );
            $proyecto['contratos'] = $this->get_contratos_proyecto( $pid );
        }

        return $proyectos;
    }

    /**
     * Contar total de proyectos (para paginación).
     */
    public function count_proyectos( array $args = [] ): int {
        global $wpdb;

        $where   = [ '1=1' ];
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

        $where_sql = implode( ' AND ', $where );
        $query     = "SELECT COUNT(*) FROM {$this->table('proyectos')} p WHERE {$where_sql}";

        if ( ! empty( $prepare ) ) {
            $query = $wpdb->prepare( $query, ...$prepare ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }

        return (int) $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Obtener metas de un proyecto.
     */
    public function get_metas_proyecto( int $proyecto_id ): array {
        global $wpdb;

        return $wpdb->get_col(
            $wpdb->prepare(
                "SELECT descripcion_meta FROM {$this->table('metas')} WHERE proyecto_id = %d ORDER BY id ASC",
                $proyecto_id
            )
        );
    }

    /**
     * Obtener contratos de un proyecto con sus relaciones.
     */
    public function get_contratos_proyecto( int $proyecto_id ): array {
        global $wpdb;

        $contratos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table('contratos')} WHERE proyecto_id = %d ORDER BY id ASC",
                $proyecto_id
            ),
            ARRAY_A
        );

        if ( empty( $contratos ) ) {
            return [];
        }

        foreach ( $contratos as &$contrato ) {
            $cid = (int) $contrato['id'];
            $contrato['municipios'] = $this->get_municipios_contrato( $cid );
            $contrato['imagenes']   = $this->get_imagenes_contrato( $cid );
        }

        return $contratos;
    }

    /**
     * Obtener municipios de un contrato.
     */
    public function get_municipios_contrato( int $contrato_id ): array {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT nombre, poblacion_beneficiada FROM {$this->table('municipios')} WHERE contrato_id = %d ORDER BY nombre ASC",
                $contrato_id
            ),
            ARRAY_A
        );
    }

    /**
     * Obtener imágenes de un contrato.
     */
    public function get_imagenes_contrato( int $contrato_id ): array {
        global $wpdb;

        return $wpdb->get_col(
            $wpdb->prepare(
                "SELECT url_imagen FROM {$this->table('imagenes')} WHERE contrato_id = %d ORDER BY id ASC",
                $contrato_id
            )
        );
    }

    /**
     * Estadísticas generales.
     */
    public function get_stats(): array {
        global $wpdb;

        $total_proyectos = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table('proyectos')}" );
        $total_contratos = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table('contratos')}" );
        $total_valor     = (float) $wpdb->get_var( "SELECT COALESCE(SUM(valor_proyecto), 0) FROM {$this->table('proyectos')}" );
        $total_metas     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table('metas')}" );
        $total_municipios = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT nombre) FROM {$this->table('municipios')}" );

        $municipios_lista = $wpdb->get_col( "SELECT DISTINCT nombre FROM {$this->table('municipios')} ORDER BY nombre ASC" );
        $dependencias     = $wpdb->get_col( "SELECT DISTINCT dependencia_proyecto FROM {$this->table('proyectos')} WHERE dependencia_proyecto != '' ORDER BY dependencia_proyecto ASC" );
        $entidades        = $wpdb->get_col( "SELECT DISTINCT entidad_ejecutora_proyecto FROM {$this->table('proyectos')} WHERE entidad_ejecutora_proyecto != '' ORDER BY entidad_ejecutora_proyecto ASC" );

        return [
            'totalProyectos'  => $total_proyectos,
            'totalContratos'  => $total_contratos,
            'totalValor'      => $total_valor,
            'totalMetas'      => $total_metas,
            'totalMunicipios' => $total_municipios,
            'municipios'      => $municipios_lista,
            'dependencias'    => $dependencias,
            'entidades'       => $entidades,
        ];
    }

    /**
     * Obtener un proyecto por BPIN.
     */
    public function get_proyecto_by_bpin( string $bpin ): ?array {
        global $wpdb;

        $proyecto = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table('proyectos')} WHERE numero_proyecto = %s",
                sanitize_text_field( $bpin )
            ),
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

    /**
     * Última importación.
     */
    public function get_last_import_date(): ?string {
        global $wpdb;
        return $wpdb->get_var( "SELECT MAX(fecha_importacion) FROM {$this->table('proyectos')}" );
    }
}
