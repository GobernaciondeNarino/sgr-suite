<?php
/**
 * SGR Suite - REST API
 *
 * Endpoints públicos para acceder a datos de proyectos del SGR.
 * Incluye headers de seguridad y sanitización de parámetros.
 *
 * @package SGR_Suite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGR_Suite_Rest_API {

    private const NAMESPACE = 'sgr-suite/v1';

    /** @var SGR_Suite_Database */
    private SGR_Suite_Database $database;

    public function __construct( SGR_Suite_Database $database ) {
        $this->database = $database;
    }

    /**
     * Registrar rutas REST.
     */
    public function register_routes(): void {
        register_rest_route( self::NAMESPACE, '/proyectos', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_proyectos' ],
            'permission_callback' => '__return_true',
            'args'                => $this->get_proyectos_args(),
        ] );

        register_rest_route( self::NAMESPACE, '/proyectos/(?P<bpin>[a-zA-Z0-9_-]+)', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_proyecto' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'bpin' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/stats', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_stats' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( self::NAMESPACE, '/proyectos/csv', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'export_csv' ],
            'permission_callback' => '__return_true',
        ] );
    }

    /**
     * Argumentos para endpoint de proyectos.
     */
    private function get_proyectos_args(): array {
        return [
            'page' => [
                'default'           => 1,
                'type'              => 'integer',
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
            ],
            'per_page' => [
                'default'           => 20,
                'type'              => 'integer',
                'minimum'           => 1,
                'maximum'           => 100,
                'sanitize_callback' => 'absint',
            ],
            'buscar' => [
                'default'           => '',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'dependencia' => [
                'default'           => '',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'entidad' => [
                'default'           => '',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'orderby' => [
                'default'           => 'nombre_proyecto',
                'type'              => 'string',
                'enum'              => [ 'nombre_proyecto', 'numero_proyecto', 'valor_proyecto', 'total_contratos', 'fecha_importacion' ],
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'order' => [
                'default'           => 'ASC',
                'type'              => 'string',
                'enum'              => [ 'ASC', 'DESC' ],
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    /**
     * GET /proyectos
     */
    public function get_proyectos( \WP_REST_Request $request ): \WP_REST_Response {
        $page     = $request->get_param( 'page' );
        $per_page = $request->get_param( 'per_page' );
        $offset   = ( $page - 1 ) * $per_page;

        $args = [
            'limite'      => $per_page,
            'offset'      => $offset,
            'buscar'      => $request->get_param( 'buscar' ),
            'dependencia' => $request->get_param( 'dependencia' ),
            'entidad'     => $request->get_param( 'entidad' ),
            'orderby'     => $request->get_param( 'orderby' ),
            'order'       => $request->get_param( 'order' ),
        ];

        $proyectos = $this->database->get_proyectos( $args );
        $total     = $this->database->count_proyectos( $args );
        $pages     = (int) ceil( $total / $per_page );

        $response = new \WP_REST_Response( [
            'proyectos'  => $proyectos,
            'total'      => $total,
            'pages'      => $pages,
            'page'       => $page,
            'per_page'   => $per_page,
        ] );

        $this->add_security_headers( $response );
        $response->header( 'X-WP-Total', $total );
        $response->header( 'X-WP-TotalPages', $pages );

        return $response;
    }

    /**
     * GET /proyectos/{bpin}
     */
    public function get_proyecto( \WP_REST_Request $request ): \WP_REST_Response {
        $bpin     = $request->get_param( 'bpin' );
        $proyecto = $this->database->get_proyecto_by_bpin( $bpin );

        if ( ! $proyecto ) {
            return new \WP_REST_Response( [
                'code'    => 'not_found',
                'message' => 'Proyecto no encontrado.',
            ], 404 );
        }

        $response = new \WP_REST_Response( $proyecto );
        $this->add_security_headers( $response );

        return $response;
    }

    /**
     * GET /stats
     */
    public function get_stats( \WP_REST_Request $request ): \WP_REST_Response {
        $stats    = $this->database->get_stats();
        $response = new \WP_REST_Response( $stats );
        $this->add_security_headers( $response );

        return $response;
    }

    /**
     * GET /proyectos/csv
     */
    public function export_csv( \WP_REST_Request $request ): \WP_REST_Response {
        $proyectos = $this->database->get_proyectos();

        $csv = "\xEF\xBB\xBF"; // BOM UTF-8 para Excel
        $csv .= "BPIN,Nombre,Valor,Dependencia,Entidad Ejecutora,Contratos,Metas\n";

        foreach ( $proyectos as $p ) {
            $csv .= sprintf(
                '"%s","%s",%s,"%s","%s",%d,%d' . "\n",
                str_replace( '"', '""', $p['numero_proyecto'] ),
                str_replace( '"', '""', $p['nombre_proyecto'] ),
                $p['valor_proyecto'],
                str_replace( '"', '""', $p['dependencia_proyecto'] ),
                str_replace( '"', '""', $p['entidad_ejecutora_proyecto'] ),
                $p['total_contratos'],
                count( $p['metas'] ?? [] )
            );
        }

        $response = new \WP_REST_Response( $csv );
        $response->header( 'Content-Type', 'text/csv; charset=UTF-8' );
        $response->header( 'Content-Disposition', 'attachment; filename="sgr-proyectos-' . gmdate( 'Y-m-d' ) . '.csv"' );
        $this->add_security_headers( $response );

        return $response;
    }

    /**
     * Agregar headers de seguridad.
     */
    private function add_security_headers( \WP_REST_Response $response ): void {
        $response->header( 'X-Content-Type-Options', 'nosniff' );
        $response->header( 'X-Frame-Options', 'DENY' );
        $response->header( 'Cache-Control', 'public, max-age=300' );
        $response->header( 'X-Robots-Tag', 'noindex' );
    }
}
