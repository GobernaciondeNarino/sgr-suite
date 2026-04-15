<?php
/**
 * SGR Suite - Normalizador de Municipios
 *
 * Resuelve los nombres de municipios que aparecen en el API del SGR
 * contra la tabla canónica DIVIPOLA de Nariño (64 municipios).
 *
 * El archivo de lookup (data/topo/narino_municipios.lookup.json) viene
 * con el topojson y sirve como fuente única de la verdad para los
 * identificadores, nombres canónicos y códigos DIVIPOLA.
 *
 * El API del SGR expone variantes como:
 *   "Alban / San José"          -> ALBÁN          52019
 *   "Los Andes Sotomayor"       -> LOS ANDES      52418
 *   "San Juan de Pasto"         -> PASTO          52001
 *   "Tumaco"                    -> SAN ANDRÉS DE TUMACO 52835
 *   "Santa Cruz Guachaves"      -> SANTACRUZ      52699
 *   "Cuaspud Carlosama, Cumbal" -> varios IDs separados por coma
 *
 * @package SGR_Suite
 * @since   2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGR_Suite_Municipios_Normalizer {

    /** @var array<string,array{id:string,nombre:string,divipola:string}>|null Cache del lookup. */
    private static ?array $lookup_by_key = null;

    /** @var array<string,string>|null  Alias conocidos -> clave canónica. */
    private static ?array $aliases = null;

    /**
     * Cargar el archivo lookup desde disco una única vez.
     *
     * Indexa por una clave normalizada (uppercase, sin acentos, sin signos)
     * del campo `nombre` y del campo `id`.
     */
    private static function load_lookup(): void {
        if ( null !== self::$lookup_by_key ) {
            return;
        }

        self::$lookup_by_key = [];
        self::$aliases       = self::build_alias_map();

        $path = SGR_SUITE_PATH . 'data/topo/narino_municipios.lookup.json';
        if ( ! is_readable( $path ) ) {
            return;
        }

        $raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        if ( empty( $raw ) ) {
            return;
        }

        $decoded = json_decode( $raw, true );
        if ( ! is_array( $decoded ) ) {
            return;
        }

        foreach ( $decoded as $row ) {
            if ( empty( $row['nombre'] ) || empty( $row['divipola'] ) ) {
                continue;
            }
            $entry = [
                'id'       => (string) ( $row['id'] ?? '' ),
                'nombre'   => (string) $row['nombre'],
                'divipola' => (string) $row['divipola'],
            ];
            $keys = [
                self::normalize_key( $row['nombre'] ),
                self::normalize_key( $row['id'] ?? '' ),
            ];
            foreach ( array_unique( array_filter( $keys ) ) as $k ) {
                self::$lookup_by_key[ $k ] = $entry;
            }
        }
    }

    /**
     * Alias conocidos → nombre canónico tal como aparece en el lookup.
     *
     * Las claves son la forma normalizada (uppercase, sin acentos) del
     * alias observado en la data del SGR. Los valores son una clave
     * normalizada que existe en el lookup principal.
     */
    private static function build_alias_map(): array {
        $pairs = [
            // Alias directos (variación de nombre).
            'ALBAN SAN JOSE'            => 'ALBAN',
            'ALBAN / SAN JOSE'          => 'ALBAN',
            'ARBOLEDA/BERRUECOS'        => 'ARBOLEDA',
            'ARBOLEDA BERRUECOS'        => 'ARBOLEDA',
            'COLON/GENOVA'              => 'COLON',
            'COLON GENOVA'              => 'COLON',
            'EL CONTADERO'              => 'CONTADERO',
            'LOS ANDES SOTOMAYOR'       => 'LOS ANDES',
            'MAGUI PAYAN'               => 'MAGUI',
            'MALLAMA PIEDRANCHA'        => 'MALLAMA',
            'SAN JUAN DE PASTO'         => 'PASTO',
            'SANTACRUZ DE GUACHAVEZ'    => 'SANTACRUZ',
            'SANTA CRUZ DE GUACHAVEZ'   => 'SANTACRUZ',
            'SANTA CRUZ GUACHAVES'      => 'SANTACRUZ',
            'SANTA CRUZ DE GUACHAVES'   => 'SANTACRUZ',
            'SANTA BARBARA DE ISCUANDE' => 'SANTA BARBARA',
            'TUMACO'                    => 'SAN ANDRES DE TUMACO',
            'CARLOSAMA'                 => 'CUASPUD CARLOSAMA',
            'CUASPUD'                   => 'CUASPUD CARLOSAMA',
            // Variantes ortográficas comunes.
            'POTOSI'                    => 'POTOSI',
            'IMUES'                     => 'IMUES',
            'LA LLANADA'                => 'LA LLANADA',
        ];

        $normalized = [];
        foreach ( $pairs as $alias => $canonical ) {
            $normalized[ self::normalize_key( $alias ) ] = self::normalize_key( $canonical );
        }
        return $normalized;
    }

    /**
     * Normalizar una cadena para uso como clave de búsqueda.
     *
     * Elimina acentos, convierte a mayúsculas, colapsa espacios y quita
     * caracteres que no sean letras, números o espacios.
     */
    public static function normalize_key( string $value ): string {
        $value = trim( $value );
        if ( '' === $value ) {
            return '';
        }

        // Transliterar a ASCII (sin tildes / eñes).
        if ( function_exists( 'iconv' ) ) {
            $converted = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $value );
            if ( false !== $converted ) {
                $value = $converted;
            }
        } else {
            $map = [
                'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
                'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
                'Ü' => 'U', 'ü' => 'U', 'Ñ' => 'N', 'ñ' => 'N',
            ];
            $value = strtr( $value, $map );
        }

        $value = strtoupper( $value );
        // Conservar letras/números/espacios; el resto a espacio.
        $value = preg_replace( '/[^A-Z0-9 ]+/', ' ', $value );
        $value = preg_replace( '/\s+/', ' ', $value );
        return trim( (string) $value );
    }

    /**
     * Resolver un string crudo (potencialmente con varios municipios separados
     * por comas) a una lista de entradas del lookup.
     *
     * @return array<int,array{id:string,nombre:string,divipola:string}>
     */
    public static function resolve( string $raw ): array {
        self::load_lookup();
        if ( null === self::$lookup_by_key || empty( self::$lookup_by_key ) ) {
            return [];
        }

        $raw = trim( $raw );
        if ( '' === $raw ) {
            return [];
        }

        // Descartar referencias al departamento completo.
        $stripped = self::normalize_key( $raw );
        if ( in_array( $stripped, [ 'DEPARTAMENTO DE NARINO', 'DEPARTAMENTO NARINO', 'NARINO DEPARTAMENTO' ], true ) ) {
            return [];
        }

        // Soportar listas separadas por comas (caso frecuente en el API).
        $pieces = array_map( 'trim', explode( ',', $raw ) );

        $results = [];
        $seen    = [];
        foreach ( $pieces as $piece ) {
            $entry = self::resolve_single( $piece );
            if ( $entry && ! isset( $seen[ $entry['divipola'] ] ) ) {
                $seen[ $entry['divipola'] ] = true;
                $results[] = $entry;
            }
        }
        return $results;
    }

    /**
     * Resolver un único nombre (sin comas). Aplica alias y fallbacks.
     *
     * @return array{id:string,nombre:string,divipola:string}|null
     */
    private static function resolve_single( string $piece ): ?array {
        $key = self::normalize_key( $piece );
        if ( '' === $key ) {
            return null;
        }

        // 1. Alias conocidos (e.g. "SAN JUAN DE PASTO" -> "PASTO").
        if ( isset( self::$aliases[ $key ] ) ) {
            $key = self::$aliases[ $key ];
        }

        // 2. Hit directo en el lookup.
        if ( isset( self::$lookup_by_key[ $key ] ) ) {
            return self::$lookup_by_key[ $key ];
        }

        // 3. Fallback: probar eliminando un sufijo común (segunda palabra
        //    compuesta como "SOTOMAYOR", "PIEDRANCHA", "PAYAN"...).
        $words = explode( ' ', $key );
        while ( count( $words ) > 1 ) {
            array_pop( $words );
            $sub = implode( ' ', $words );
            if ( isset( self::$lookup_by_key[ $sub ] ) ) {
                return self::$lookup_by_key[ $sub ];
            }
            if ( isset( self::$aliases[ $sub ] ) && isset( self::$lookup_by_key[ self::$aliases[ $sub ] ] ) ) {
                return self::$lookup_by_key[ self::$aliases[ $sub ] ];
            }
        }

        return null;
    }

    /**
     * Resolver a un único DIVIPOLA (string) para el primer match, o null.
     */
    public static function resolve_first_divipola( string $raw ): ?string {
        $list = self::resolve( $raw );
        return ! empty( $list ) ? $list[0]['divipola'] : null;
    }

    /**
     * Devolver todos los municipios del lookup.
     *
     * @return array<int,array{id:string,nombre:string,divipola:string}>
     */
    public static function all(): array {
        self::load_lookup();
        if ( null === self::$lookup_by_key ) {
            return [];
        }
        // Deduplicar por divipola porque el lookup se indexa por múltiples claves.
        $unique = [];
        foreach ( self::$lookup_by_key as $entry ) {
            $unique[ $entry['divipola'] ] = $entry;
        }
        ksort( $unique );
        return array_values( $unique );
    }
}
