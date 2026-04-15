# SGR Nariño — Vistas JSON para Visualizaciones D3plus

> **Fuente:** `https://gobiernoabierto.narino.gov.co/wp-api/sgr.php` Actualizada diariamente
> **Biblioteca:** [D3plus v3](https://d3plus.org/) — 20 tipos de gráfico
> https://d3plus.org/?path=/docs/core-charts-areaplot--d3plus
> https://d3plus.org/?path=/docs/core-charts-barchart--d3plus
> https://d3plus.org/?path=/docs/core-charts-bumpchart--d3plus
> https://d3plus.org/?path=/docs/core-charts-donut--d3plus
> https://d3plus.org/?path=/docs/core-charts-geomap--d3plus 
> https://d3plus.org/?path=/docs/core-charts-lineplot--d3plus
> https://d3plus.org/?path=/docs/core-charts-matrix--d3plus
> https://d3plus.org/?path=/docs/core-charts-network--d3plus
> https://d3plus.org/?path=/docs/core-charts-pack--d3plus
> https://d3plus.org/?path=/docs/core-charts-pie--d3plus
> https://d3plus.org/?path=/docs/core-charts-plot--d3plus
> https://d3plus.org/?path=/docs/core-charts-priestley--d3plus
> https://d3plus.org/?path=/docs/core-charts-radar--d3plus
> https://d3plus.org/?path=/docs/core-charts-radialmatrix--d3plus
> https://d3plus.org/?path=/docs/core-charts-rings--d3plus
> https://d3plus.org/?path=/docs/core-charts-sankey--d3plus
> https://d3plus.org/?path=/docs/core-charts-stackedarea--d3plus
> https://d3plus.org/?path=/docs/core-charts-tree--d3plus
> https://d3plus.org/?path=/docs/core-charts-treemap--d3plus




---

## Estructura original del API

```
{
  ok: boolean,
  totalProyectos: 531,
  totalContratos: 71,
  proyectos: [
    {
      numeroProyecto: string,         // BPIN o código Infra-XXX
      nombreProyecto: string,         // Nombre largo del proyecto
      valorProyecto: string,          // Valor en COP (viene como string)
      metasProyecto: string[],        // Metas/indicadores del proyecto
      entidadEjecutoraProyecto: string, // "Municipio" | "Departamento" | "Otro"
      dependenciaProyecto: string,    // "IDSN" | "Infraestructura" | "Regalías" | "PDA"
      odssProyecto: string[],         // ODS relacionados (vacíos en mayoría)
      contratosProyecto: [
        {
          numeroContrato: string,
          objetoContrato: string,
          valorContrato: string,
          descripcionEjecContractual: string,
          procentajeAvanceFisico: string,  // "27.53" — porcentaje
          metasEjecContractual: string[],
          odssEjecContractual: string[],
          esOpsEjecContractual: string,
          municipiosEjecContractual: [
            {
              nombre: string,
              poblacion_beneficiada: number
            }
          ],
          imagenesEjecContractual: string[]
        }
      ]
    }
  ]
}
```

### Dimensiones categóricas disponibles

| Campo | Valores únicos | Distribución |
|---|---|---|
| `dependenciaProyecto` | IDSN, Infraestructura, Regalías, PDA | 337 / 70 / 38 / 86 |
| `entidadEjecutoraProyecto` | Municipio, Departamento, Otro | 415 / 72 / 44 |
| `vigencia` *(derivada de `numeroProyecto`)* | 2023, 2024, 2025, 2026, IDSN*, Infra* | 1/84/42/24/312/68 |
| `avance` *(de contratos)* | 0–100% | 71 contratos, avg 27.53% |

> `*` Los proyectos IDSN e Infra usan códigos secuenciales propios, sin año en el BPIN.

---

## Índice de Vistas

| Vista | Nombre | Gráficos compatibles |
|---|---|---|
| V-01 | valor_por_dependencia | `BarChart` · `Pie` · `Donut` |
| V-02 | proyectos_dependencia_entidad | `BarChart` (grouped/stacked) · `StackedArea` |
| V-03 | jerarquia_dependencia_entidad | `Treemap` · `Pack` · `Tree` |
| V-04 | vigencia_valor | `LinePlot` · `BarChart` |
| V-05 | vigencia_dependencia_stack | `StackedArea` · `BumpChart` |
| V-06 | distribucion_entidad_ejecutora | `Pie` · `Donut` |
| V-07 | avance_fisico_contratos | `BoxWhisker` · `Plot` |
| V-08 | scatter_valor_avance | `Plot` (scatter) |
| V-09 | radar_metricas_dependencia | `Radar` · `RadialMatrix` |
| V-10 | red_entidad_dependencia | `Network` · `Sankey` · `Rings` |
| V-11 | sankey_flujo_completo | `Sankey` |
| V-12 | municipios_contratos | `Matrix` · `Geomap` |
| V-13 | geomap_poblacion | `Geomap` |
| V-14 | matrix_municipio_dependencia | `Matrix` · `RadialMatrix` |
| V-15 | bump_ranking_dependencias | `BumpChart` |
| V-16 | priestley_timeline | `Priestley` |
| V-17 | rings_proyecto_municipio | `Rings` · `Network` |
| V-18 | treemap_entidad_municipio | `Treemap` · `Pack` |
| V-19 | box_avance_por_entidad | `BoxWhisker` |
| V-20 | tree_full_hierarchy | `Tree` |

---

## V-01 · `valor_por_dependencia`

**Descripción:** Valor total comprometido y número de proyectos por dependencia institucional (IDSN, PDA, Infraestructura, Regalías).

**Gráficos compatibles:** `BarChart` · `Pie` · `Donut`

```json
[
  {
    "id": "IDSN",
    "label": "Instituto Departamental de Salud",
    "total_proyectos": 337,
    "valor_total": 1428383469973,
    "valor_promedio": 4239121277,
    "color": "#1a5276"
  },
  {
    "id": "PDA",
    "label": "Plan Departamental de Agua",
    "total_proyectos": 86,
    "valor_total": 234045476898,
    "valor_promedio": 2721808103,
    "color": "#2980b9"
  },
  {
    "id": "Infraestructura",
    "label": "Secretaría de Infraestructura",
    "total_proyectos": 70,
    "valor_total": 67923724603,
    "valor_promedio": 970338923,
    "color": "#E8A020"
  },
  {
    "id": "Regalías",
    "label": "Regalías SGR",
    "total_proyectos": 38,
    "valor_total": 4791599566190,
    "valor_promedio": 126094725426,
    "color": "#003087"
  }
]
```

**Configuración D3plus:**
```javascript
// BarChart
new d3plus.BarChart()
  .select("#v01-bar")
  .data(V01)
  .groupBy("id")
  .x("id")
  .y("valor_total")
  .render();

// Donut
new d3plus.Donut()
  .select("#v01-donut")
  .data(V01)
  .groupBy("id")
  .value("valor_total")
  .render();
```

---

## V-02 · `proyectos_dependencia_entidad`

**Descripción:** Cruce bidimensional entre dependencia y entidad ejecutora. Permite comparar composición del portafolio por tipo de ejecutor dentro de cada dependencia.

**Gráficos compatibles:** `BarChart` (grouped/stacked) · `StackedArea`

```json
[
  { "dependencia": "IDSN",           "entidad": "Municipio",    "total_proyectos": 301, "valor_total": 666205324197 },
  { "dependencia": "IDSN",           "entidad": "Departamento", "total_proyectos": 36,  "valor_total": 762178145776 },
  { "dependencia": "Infraestructura","entidad": "Municipio",    "total_proyectos": 30,  "valor_total": 56765386194 },
  { "dependencia": "Infraestructura","entidad": "Otro",         "total_proyectos": 24,  "valor_total": 5375408388  },
  { "dependencia": "Infraestructura","entidad": "Departamento", "total_proyectos": 15,  "valor_total": 5782930021  },
  { "dependencia": "Regalías",       "entidad": "Otro",         "total_proyectos": 18,  "valor_total": 4569511060068 },
  { "dependencia": "Regalías",       "entidad": "Municipio",    "total_proyectos": 17,  "valor_total": 193792635669 },
  { "dependencia": "Regalías",       "entidad": "Departamento", "total_proyectos": 3,   "valor_total": 28295870453  },
  { "dependencia": "PDA",            "entidad": "Municipio",    "total_proyectos": 68,  "valor_total": 42254993723  },
  { "dependencia": "PDA",            "entidad": "Departamento", "total_proyectos": 18,  "valor_total": 191790483175 }
]
```

**Configuración D3plus:**
```javascript
// BarChart stacked
new d3plus.BarChart()
  .select("#v02-bar")
  .data(V02)
  .groupBy(["dependencia", "entidad"])
  .x("dependencia")
  .y("total_proyectos")
  .stacked(true)
  .render();
```

---

## V-03 · `jerarquia_dependencia_entidad`

**Descripción:** Estructura jerárquica de tres niveles para visualizar el peso relativo en valor. Nivel 1: Dependencia → Nivel 2: Entidad ejecutora → Nivel 3: Proyecto (representado como nodo hoja con valor).

**Gráficos compatibles:** `Treemap` · `Pack` · `Tree`

> **Nota:** Para escalar a todos los 531 proyectos, el API PHP debe retornar este arreglo completo con campos `id`, `parent` y `value`. La muestra siguiente usa los nodos intermedios ya agregados.

```json
[
  { "id": "SGR Nariño",             "parent": "",                             "value": 0,               "label": "SGR Nariño" },

  { "id": "IDSN",                   "parent": "SGR Nariño",                   "value": 0,               "label": "IDSN" },
  { "id": "PDA",                    "parent": "SGR Nariño",                   "value": 0,               "label": "Plan Departamental de Agua" },
  { "id": "Infraestructura",        "parent": "SGR Nariño",                   "value": 0,               "label": "Infraestructura" },
  { "id": "Regalías",               "parent": "SGR Nariño",                   "value": 0,               "label": "Regalías" },

  { "id": "IDSN_Municipio",         "parent": "IDSN",                         "value": 666205324197,    "label": "Municipio" },
  { "id": "IDSN_Departamento",      "parent": "IDSN",                         "value": 762178145776,    "label": "Departamento" },
  { "id": "PDA_Municipio",          "parent": "PDA",                          "value": 42254993723,     "label": "Municipio" },
  { "id": "PDA_Departamento",       "parent": "PDA",                          "value": 191790483175,    "label": "Departamento" },
  { "id": "Infra_Municipio",        "parent": "Infraestructura",              "value": 56765386194,     "label": "Municipio" },
  { "id": "Infra_Departamento",     "parent": "Infraestructura",              "value": 5782930021,      "label": "Departamento" },
  { "id": "Infra_Otro",             "parent": "Infraestructura",              "value": 5375408388,      "label": "Otro" },
  { "id": "Regalias_Otro",          "parent": "Regalías",                     "value": 4569511060068,   "label": "Otro" },
  { "id": "Regalias_Municipio",     "parent": "Regalías",                     "value": 193792635669,    "label": "Municipio" },
  { "id": "Regalias_Departamento",  "parent": "Regalías",                     "value": 28295870453,     "label": "Departamento" }
]
```

**Configuración D3plus:**
```javascript
// Treemap
new d3plus.Treemap()
  .select("#v03-treemap")
  .data(V03)
  .groupBy(["parent", "id"])
  .value("value")
  .render();

// Pack
new d3plus.Pack()
  .select("#v03-pack")
  .data(V03)
  .groupBy(["parent", "id"])
  .value("value")
  .render();
```

---

## V-04 · `vigencia_valor`

**Descripción:** Evolución del valor total y cantidad de proyectos por vigencia (año derivado del prefijo BPIN). Útil para mostrar el crecimiento de la inversión SGR en el tiempo.

**Gráficos compatibles:** `LinePlot` · `BarChart`

> **Derivación de vigencia:** `numeroProyecto.substring(0,4)` cuando es numérico BPIN. Proyectos IDSN e Infra se agrupan como categorías propias (sin año BPIN estándar).

```json
[
  { "vigencia": "2023", "total_proyectos": 1,   "valor_total": 104605284,       "vigencia_orden": 1 },
  { "vigencia": "2024", "total_proyectos": 84,  "valor_total": 778016162870,    "vigencia_orden": 2 },
  { "vigencia": "2025", "total_proyectos": 42,  "valor_total": 4484147155201,   "vigencia_orden": 3 },
  { "vigencia": "2026", "total_proyectos": 24,  "valor_total": 97035601359,     "vigencia_orden": 4 },
  { "vigencia": "IDSN*","total_proyectos": 312, "valor_total": 1102325755046,   "vigencia_orden": 5 },
  { "vigencia": "Infra*","total_proyectos": 68, "valor_total": 60322957903,     "vigencia_orden": 6 }
]
```

**Configuración D3plus:**
```javascript
// LinePlot
new d3plus.LinePlot()
  .select("#v04-line")
  .data(V04.filter(d => ["2023","2024","2025","2026"].includes(d.vigencia)))
  .groupBy("vigencia")
  .x("vigencia_orden")
  .y("valor_total")
  .render();
```

---

## V-05 · `vigencia_dependencia_stack`

**Descripción:** Composición del valor por dependencia a lo largo de las vigencias con BPIN año-explícito. Ideal para ver qué dependencia domina la inversión en cada año.

**Gráficos compatibles:** `StackedArea` · `BumpChart`

```json
[
  { "vigencia": "2023", "vigencia_num": 2023, "dependencia": "IDSN",           "valor_total": 104605284,     "total_proyectos": 1 },
  { "vigencia": "2024", "vigencia_num": 2024, "dependencia": "IDSN",           "valor_total": 580324112370,  "total_proyectos": 64 },
  { "vigencia": "2024", "vigencia_num": 2024, "dependencia": "PDA",            "valor_total": 105842234190,  "total_proyectos": 12 },
  { "vigencia": "2024", "vigencia_num": 2024, "dependencia": "Infraestructura","valor_total": 21342817060,   "total_proyectos": 5 },
  { "vigencia": "2024", "vigencia_num": 2024, "dependencia": "Regalías",       "valor_total": 70506999250,   "total_proyectos": 3 },
  { "vigencia": "2025", "vigencia_num": 2025, "dependencia": "Regalías",       "valor_total": 4336870060120, "total_proyectos": 28 },
  { "vigencia": "2025", "vigencia_num": 2025, "dependencia": "PDA",            "valor_total": 98762411890,   "total_proyectos": 8 },
  { "vigencia": "2025", "vigencia_num": 2025, "dependencia": "IDSN",           "valor_total": 42110623060,   "total_proyectos": 5 },
  { "vigencia": "2025", "vigencia_num": 2025, "dependencia": "Infraestructura","valor_total": 6404060131,    "total_proyectos": 1 },
  { "vigencia": "2026", "vigencia_num": 2026, "dependencia": "Regalías",       "valor_total": 55830046020,   "total_proyectos": 7 },
  { "vigencia": "2026", "vigencia_num": 2026, "dependencia": "PDA",            "valor_total": 29440831149,   "total_proyectos": 14 },
  { "vigencia": "2026", "vigencia_num": 2026, "dependencia": "Infraestructura","valor_total": 8248044190,    "total_proyectos": 3 },
  { "vigencia": "2026", "vigencia_num": 2026, "dependencia": "IDSN",           "valor_total": 3516680000,    "total_proyectos": 0 }
]
```

**Configuración D3plus:**
```javascript
// StackedArea
new d3plus.StackedArea()
  .select("#v05-stack")
  .data(V05)
  .groupBy("dependencia")
  .x("vigencia_num")
  .y("valor_total")
  .render();

// BumpChart (ranking)
new d3plus.BumpChart()
  .select("#v05-bump")
  .data(V05)
  .groupBy("dependencia")
  .x("vigencia_num")
  .y("total_proyectos")
  .render();
```

---

## V-06 · `distribucion_entidad_ejecutora`

**Descripción:** Participación porcentual de cada tipo de entidad ejecutora en el total de proyectos y en el valor total invertido.

**Gráficos compatibles:** `Pie` · `Donut`

```json
[
  {
    "id": "Municipio",
    "label": "Municipio",
    "total_proyectos": 415,
    "valor_total": 950473399084,
    "pct_proyectos": 78.2,
    "pct_valor": 14.6,
    "color": "#1a5276"
  },
  {
    "id": "Departamento",
    "label": "Departamento de Nariño",
    "total_proyectos": 72,
    "valor_total": 988047429425,
    "pct_proyectos": 13.6,
    "pct_valor": 15.2,
    "color": "#E8A020"
  },
  {
    "id": "Otro",
    "label": "Otro / Entidad Especial",
    "total_proyectos": 44,
    "valor_total": 4574886468456,
    "pct_proyectos": 8.3,
    "pct_valor": 70.2,
    "color": "#003087"
  }
]
```

**Configuración D3plus:**
```javascript
// Pie — cantidad de proyectos
new d3plus.Pie()
  .select("#v06-pie")
  .data(V06)
  .groupBy("id")
  .value("total_proyectos")
  .render();

// Donut — valor financiero
new d3plus.Donut()
  .select("#v06-donut")
  .data(V06)
  .groupBy("id")
  .value("valor_total")
  .render();
```

---

## V-07 · `avance_fisico_contratos`

**Descripción:** Registro individual de los 71 contratos con su porcentaje de avance físico, valor del contrato y municipio beneficiario. Permite análisis de distribución y detección de outliers.

**Gráficos compatibles:** `BoxWhisker` · `Plot`

```json
[
  { "id_contrato": "LP-001-2025",         "dependencia": "Regalías",       "entidad": "Otro",        "avance": 16.4,  "valor_contrato": 7742805115,  "municipio": "Taminango",              "poblacion": 48138 },
  { "id_contrato": "CMA-001-2025",        "dependencia": "IDSN",           "entidad": "Departamento","avance": 20.0,  "valor_contrato": 458302320,   "municipio": "Departamento de Nariño", "poblacion": 279484 },
  { "id_contrato": "GN4086-2024",         "dependencia": "Infraestructura","entidad": "Municipio",   "avance": 26.4,  "valor_contrato": 1398119999,  "municipio": "Túquerres",              "poblacion": 45000 },
  { "id_contrato": "CMA-004-2025",        "dependencia": "Regalías",       "entidad": "Otro",        "avance": 0.0,   "valor_contrato": 8105646721,  "municipio": "Cumbal",                 "poblacion": 39794 },
  { "id_contrato": "CMA-003-2025",        "dependencia": "IDSN",           "entidad": "Departamento","avance": 5.99,  "valor_contrato": 396291896,   "municipio": "Departamento de Nariño", "poblacion": 258089 },
  { "id_contrato": "UM-2025040048",       "dependencia": "PDA",            "entidad": "Departamento","avance": 93.01, "valor_contrato": 7193766489,  "municipio": "El Rosario",             "poblacion": 18162 },
  { "id_contrato": "CM-001-DE-2025",      "dependencia": "IDSN",           "entidad": "Departamento","avance": 5.49,  "valor_contrato": 76077557,    "municipio": "La Unión",               "poblacion": 39794 },
  { "id_contrato": "LP001-2025",          "dependencia": "Regalías",       "entidad": "Otro",        "avance": 14.12, "valor_contrato": 2461332466,  "municipio": "Policarpa",              "poblacion": 16811 },
  { "id_contrato": "CM-002-2025",         "dependencia": "IDSN",           "entidad": "Departamento","avance": 8.42,  "valor_contrato": 222910324,   "municipio": "Departamento de Nariño", "poblacion": 258089 },
  { "id_contrato": "017",                 "dependencia": "Infraestructura","entidad": "Municipio",   "avance": 47.15, "valor_contrato": 11548100716, "municipio": "Tumaco",                 "poblacion": 258089 },
  { "id_contrato": "016",                 "dependencia": "PDA",            "entidad": "Municipio",   "avance": 14.35, "valor_contrato": 826790675,   "municipio": "Ancuya",                 "poblacion": 16336 },
  { "id_contrato": "LP-003-DE-2025",      "dependencia": "Regalías",       "entidad": "Otro",        "avance": 54.98, "valor_contrato": 6929843314,  "municipio": "Olaya Herrera",          "poblacion": 21683 },
  { "id_contrato": "CM-006-2025",         "dependencia": "IDSN",           "entidad": "Departamento","avance": 38.31, "valor_contrato": 481178880,   "municipio": "Córdoba",                "poblacion": 16338 },
  { "id_contrato": "FUV.SGR.011.2025",    "dependencia": "Regalías",       "entidad": "Otro",        "avance": 2.83,  "valor_contrato": 14987364429, "municipio": "San Pedro de Cartago",   "poblacion": 16811 },
  { "id_contrato": "CMA-001-DE-2025",     "dependencia": "PDA",            "entidad": "Departamento","avance": 100.0, "valor_contrato": 131097933,   "municipio": "Belén",                  "poblacion": 13426 }
]
```

**Nota de implementación:** Para cargar los 71 registros completos, el endpoint PHP debe aplanar los contratos con su contexto de proyecto:

```php
// En sgr.php — aplanar contratos
foreach ($proyectos as $proyecto) {
    foreach ($proyecto['contratosProyecto'] as $contrato) {
        $flat_contratos[] = [
            'id_contrato'   => $contrato['numeroContrato'],
            'dependencia'   => $proyecto['dependenciaProyecto'],
            'entidad'       => $proyecto['entidadEjecutoraProyecto'],
            'avance'        => floatval($contrato['procentajeAvanceFisico']),
            'valor_contrato'=> floatval($contrato['valorContrato']),
            'municipio'     => $contrato['municipiosEjecContractual'][0]['nombre'] ?? 'N/A',
            'poblacion'     => intval($contrato['municipiosEjecContractual'][0]['poblacion_beneficiada'] ?? 0),
        ];
    }
}
```

---

## V-08 · `scatter_valor_avance`

**Descripción:** Relación entre el valor del contrato (eje X) y el porcentaje de avance físico (eje Y) agrupado por dependencia. Detecta contratos de alto valor con bajo avance (zona de riesgo).

**Gráficos compatibles:** `Plot` (scatter)

```json
[
  { "id": "FUV.SGR.011.2025",    "dependencia": "Regalías",       "valor_contrato": 14987364429, "avance": 2.83,  "municipio": "San Pedro de Cartago",   "riesgo": "alto" },
  { "id": "017",                 "dependencia": "Infraestructura","valor_contrato": 11548100716, "avance": 47.15, "municipio": "Tumaco",                 "riesgo": "medio" },
  { "id": "CMA-004-2025",        "dependencia": "Regalías",       "valor_contrato": 8105646721,  "avance": 0.0,   "municipio": "Cumbal",                 "riesgo": "alto" },
  { "id": "LP-001-2025",         "dependencia": "Regalías",       "valor_contrato": 7742805115,  "avance": 16.4,  "municipio": "Taminango",              "riesgo": "medio" },
  { "id": "LP-003-DE-2025",      "dependencia": "Regalías",       "valor_contrato": 6929843314,  "avance": 54.98, "municipio": "Olaya Herrera",          "riesgo": "bajo" },
  { "id": "UM-2025040048",       "dependencia": "PDA",            "valor_contrato": 7193766489,  "avance": 93.01, "municipio": "El Rosario",             "riesgo": "bajo" },
  { "id": "GN4086-2024",         "dependencia": "Infraestructura","valor_contrato": 1398119999,  "avance": 26.4,  "municipio": "Túquerres",              "riesgo": "medio" },
  { "id": "LP001-2025",          "dependencia": "Regalías",       "valor_contrato": 2461332466,  "avance": 14.12, "municipio": "Policarpa",              "riesgo": "medio" },
  { "id": "CMA-001-2025",        "dependencia": "IDSN",           "valor_contrato": 458302320,   "avance": 20.0,  "municipio": "Dep. Nariño",            "riesgo": "medio" },
  { "id": "CMA-003-2025",        "dependencia": "IDSN",           "valor_contrato": 396291896,   "avance": 5.99,  "municipio": "Dep. Nariño",            "riesgo": "alto" },
  { "id": "CM-006-2025",         "dependencia": "IDSN",           "valor_contrato": 481178880,   "avance": 38.31, "municipio": "Córdoba",                "riesgo": "medio" },
  { "id": "CMA-001-DE-2025",     "dependencia": "PDA",            "valor_contrato": 131097933,   "avance": 100.0, "municipio": "Belén",                  "riesgo": "bajo" }
]
```

**Configuración D3plus:**
```javascript
new d3plus.Plot()
  .select("#v08-scatter")
  .data(V08)
  .groupBy("dependencia")
  .x("valor_contrato")
  .y("avance")
  .size("valor_contrato")
  .render();
```

---

## V-09 · `radar_metricas_dependencia`

**Descripción:** Comparación multidimensional de las dependencias usando 5 métricas normalizadas (0–100). Expone fortalezas y debilidades de cada dependencia en ejecución SGR.

**Gráficos compatibles:** `Radar` · `RadialMatrix`

> Las métricas se normalizan al máximo del conjunto para que todos los ejes sean comparables.

```json
[
  { "dependencia": "IDSN",           "metrica": "Cobertura Municipal",  "valor": 89,  "valor_raw": "301 municipios" },
  { "dependencia": "IDSN",           "metrica": "Volumen Proyectos",    "valor": 100, "valor_raw": "337 proyectos"  },
  { "dependencia": "IDSN",           "metrica": "Valor Promedio",       "valor": 34,  "valor_raw": "$4.2B COP"      },
  { "dependencia": "IDSN",           "metrica": "Avance Físico Avg",    "valor": 28,  "valor_raw": "27.5%"          },
  { "dependencia": "IDSN",           "metrica": "Contratos Activos",    "valor": 60,  "valor_raw": "43 contratos"   },

  { "dependencia": "PDA",            "metrica": "Cobertura Municipal",  "valor": 55,  "valor_raw": "68 municipios"  },
  { "dependencia": "PDA",            "metrica": "Volumen Proyectos",    "valor": 26,  "valor_raw": "86 proyectos"   },
  { "dependencia": "PDA",            "metrica": "Valor Promedio",       "valor": 22,  "valor_raw": "$2.7B COP"      },
  { "dependencia": "PDA",            "metrica": "Avance Físico Avg",    "valor": 55,  "valor_raw": "53.7%"          },
  { "dependencia": "PDA",            "metrica": "Contratos Activos",    "valor": 24,  "valor_raw": "17 contratos"   },

  { "dependencia": "Infraestructura","metrica": "Cobertura Municipal",  "valor": 40,  "valor_raw": "30 municipios"  },
  { "dependencia": "Infraestructura","metrica": "Volumen Proyectos",    "valor": 21,  "valor_raw": "70 proyectos"   },
  { "dependencia": "Infraestructura","metrica": "Valor Promedio",       "valor": 8,   "valor_raw": "$970M COP"      },
  { "dependencia": "Infraestructura","metrica": "Avance Físico Avg",    "valor": 36,  "valor_raw": "36.8%"          },
  { "dependencia": "Infraestructura","metrica": "Contratos Activos",    "valor": 14,  "valor_raw": "10 contratos"   },

  { "dependencia": "Regalías",       "metrica": "Cobertura Municipal",  "valor": 18,  "valor_raw": "17 municipios"  },
  { "dependencia": "Regalías",       "metrica": "Volumen Proyectos",    "valor": 11,  "valor_raw": "38 proyectos"   },
  { "dependencia": "Regalías",       "metrica": "Valor Promedio",       "valor": 100, "valor_raw": "$126B COP"      },
  { "dependencia": "Regalías",       "metrica": "Avance Físico Avg",    "valor": 22,  "valor_raw": "21.3%"          },
  { "dependencia": "Regalías",       "metrica": "Contratos Activos",    "valor": 2,   "valor_raw": "1 contrato"     }
]
```

**Configuración D3plus:**
```javascript
new d3plus.Radar()
  .select("#v09-radar")
  .data(V09)
  .groupBy("dependencia")
  .metric("metrica")
  .value("valor")
  .render();
```

---

## V-10 · `red_entidad_dependencia`

**Descripción:** Nodos (entidades + dependencias) y enlaces con peso = número de proyectos. Muestra qué entidades ejecutoras se conectan con qué dependencias.

**Gráficos compatibles:** `Network` · `Rings`

```json
{
  "nodes": [
    { "id": "Municipio",    "grupo": "Entidad Ejecutora", "total_proyectos": 415 },
    { "id": "Departamento", "grupo": "Entidad Ejecutora", "total_proyectos": 72  },
    { "id": "Otro",         "grupo": "Entidad Ejecutora", "total_proyectos": 44  },
    { "id": "IDSN",         "grupo": "Dependencia",       "total_proyectos": 337 },
    { "id": "PDA",          "grupo": "Dependencia",       "total_proyectos": 86  },
    { "id": "Infraestructura","grupo": "Dependencia",     "total_proyectos": 70  },
    { "id": "Regalías",     "grupo": "Dependencia",       "total_proyectos": 38  }
  ],
  "edges": [
    { "source": "Municipio",    "target": "IDSN",          "weight": 301, "valor": 666205324197 },
    { "source": "Departamento", "target": "IDSN",          "weight": 36,  "valor": 762178145776 },
    { "source": "Municipio",    "target": "Infraestructura","weight": 30, "valor": 56765386194  },
    { "source": "Otro",         "target": "Infraestructura","weight": 24, "valor": 5375408388   },
    { "source": "Departamento", "target": "Infraestructura","weight": 15, "valor": 5782930021   },
    { "source": "Otro",         "target": "Regalías",      "weight": 18,  "valor": 4569511060068},
    { "source": "Municipio",    "target": "Regalías",      "weight": 17,  "valor": 193792635669 },
    { "source": "Departamento", "target": "Regalías",      "weight": 3,   "valor": 28295870453  },
    { "source": "Municipio",    "target": "PDA",           "weight": 68,  "valor": 42254993723  },
    { "source": "Departamento", "target": "PDA",           "weight": 18,  "valor": 191790483175 }
  ]
}
```

**Configuración D3plus:**
```javascript
new d3plus.Network()
  .select("#v10-network")
  .nodes(V10.nodes)
  .edges(V10.edges)
  .groupBy("grupo")
  .nodeSize("total_proyectos")
  .edgeSize("weight")
  .render();
```

---

## V-11 · `sankey_flujo_completo`

**Descripción:** Flujo de valor e inversión desde la fuente (Vigencia) → Dependencia → Entidad Ejecutora. Cada flujo tiene un peso = valor_total en COP.

**Gráficos compatibles:** `Sankey`

> Sankey en D3plus requiere arreglo plano de `{ source, target, value }`.

```json
[
  { "source": "Vigencia 2024",  "target": "IDSN",           "value": 580324112370  },
  { "source": "Vigencia 2024",  "target": "PDA",            "value": 105842234190  },
  { "source": "Vigencia 2024",  "target": "Infraestructura","value": 21342817060   },
  { "source": "Vigencia 2024",  "target": "Regalías",       "value": 70506999250   },
  { "source": "Vigencia 2025",  "target": "Regalías",       "value": 4336870060120 },
  { "source": "Vigencia 2025",  "target": "PDA",            "value": 98762411890   },
  { "source": "Vigencia 2025",  "target": "IDSN",           "value": 42110623060   },
  { "source": "Vigencia 2026",  "target": "Regalías",       "value": 55830046020   },
  { "source": "Vigencia 2026",  "target": "PDA",            "value": 29440831149   },
  { "source": "IDSN",           "target": "Municipio",      "value": 666205324197  },
  { "source": "IDSN",           "target": "Departamento",   "value": 762178145776  },
  { "source": "PDA",            "target": "Municipio",      "value": 42254993723   },
  { "source": "PDA",            "target": "Departamento",   "value": 191790483175  },
  { "source": "Infraestructura","target": "Municipio",      "value": 56765386194   },
  { "source": "Infraestructura","target": "Departamento",   "value": 5782930021    },
  { "source": "Infraestructura","target": "Otro",           "value": 5375408388    },
  { "source": "Regalías",       "target": "Otro",           "value": 4569511060068 },
  { "source": "Regalías",       "target": "Municipio",      "value": 193792635669  },
  { "source": "Regalías",       "target": "Departamento",   "value": 28295870453   }
]
```

**Configuración D3plus:**
```javascript
new d3plus.Sankey()
  .select("#v11-sankey")
  .data(V11)
  .source("source")
  .target("target")
  .value("value")
  .render();
```

---

## V-12 · `municipios_contratos`

**Descripción:** Municipios que tienen al menos un contrato activo con su valor total contratado, porcentaje de avance y población beneficiada. Base para Matrix y Geomap.

**Gráficos compatibles:** `Matrix` · `Geomap`

```json
[
  { "municipio": "Taminango",              "divipola": "52699", "lat": 1.5726,  "lon": -77.2941, "valor_contratos": 7742805115,  "avance_promedio": 16.4,  "poblacion": 48138,  "dependencia": "Regalías",        "num_contratos": 1 },
  { "municipio": "Túquerres",              "divipola": "52786", "lat": 1.0857,  "lon": -77.6164, "valor_contratos": 1398119999,  "avance_promedio": 26.4,  "poblacion": 45000,  "dependencia": "Infraestructura", "num_contratos": 1 },
  { "municipio": "Cumbal",                 "divipola": "52240", "lat": 0.9150,  "lon": -77.8000, "valor_contratos": 8105646721,  "avance_promedio": 0.0,   "poblacion": 39794,  "dependencia": "Regalías",        "num_contratos": 1 },
  { "municipio": "El Rosario",             "divipola": "52287", "lat": 1.5566,  "lon": -77.1804, "valor_contratos": 7193766489,  "avance_promedio": 93.01, "poblacion": 18162,  "dependencia": "PDA",             "num_contratos": 1 },
  { "municipio": "Policarpa",              "divipola": "52540", "lat": 1.6985,  "lon": -77.2955, "valor_contratos": 2461332466,  "avance_promedio": 14.12, "poblacion": 16811,  "dependencia": "Regalías",        "num_contratos": 1 },
  { "municipio": "Tumaco",                 "divipola": "52835", "lat": 1.7989,  "lon": -78.7628, "valor_contratos": 11548100716, "avance_promedio": 47.15, "poblacion": 258089, "dependencia": "Infraestructura", "num_contratos": 1 },
  { "municipio": "La Unión",               "divipola": "52418", "lat": 1.5963,  "lon": -77.1351, "valor_contratos": 76077557,    "avance_promedio": 5.49,  "poblacion": 39794,  "dependencia": "IDSN",            "num_contratos": 1 },
  { "municipio": "Ancuya",                 "divipola": "52036", "lat": 1.3969,  "lon": -77.4823, "valor_contratos": 826790675,   "avance_promedio": 14.35, "poblacion": 16336,  "dependencia": "PDA",             "num_contratos": 1 },
  { "municipio": "Olaya Herrera",          "divipola": "52490", "lat": 2.2948,  "lon": -78.0698, "valor_contratos": 6929843314,  "avance_promedio": 54.98, "poblacion": 21683,  "dependencia": "Regalías",        "num_contratos": 1 },
  { "municipio": "Córdoba",                "divipola": "52215", "lat": 1.3994,  "lon": -77.5067, "valor_contratos": 481178880,   "avance_promedio": 38.31, "poblacion": 16338,  "dependencia": "IDSN",            "num_contratos": 1 },
  { "municipio": "San Pedro de Cartago",   "divipola": "52612", "lat": 1.5390,  "lon": -77.0658, "valor_contratos": 14987364429, "avance_promedio": 2.83,  "poblacion": 16811,  "dependencia": "Regalías",        "num_contratos": 1 },
  { "municipio": "Belén",                  "divipola": "52083", "lat": 1.5367,  "lon": -77.2095, "valor_contratos": 131097933,   "avance_promedio": 100.0, "poblacion": 13426,  "dependencia": "PDA",             "num_contratos": 1 },
  { "municipio": "Departamento de Nariño", "divipola": "52000", "lat": 1.2136,  "lon": -77.2811, "valor_contratos": 1634860970,  "avance_promedio": 17.41, "poblacion": 279484, "dependencia": "IDSN",            "num_contratos": 5 }
]
```

---

## V-13 · `geomap_poblacion`

**Descripción:** Vista optimizada para Geomap: identificador DIVIPOLA, nombre del municipio, y métricas de inversión y cobertura poblacional.

**Gráficos compatibles:** `Geomap`
> Crea el archivo .topogson con la informacion de (https://github.com/GobernaciondeNarino/tic-suite/tree/claude/add-charting-plugin-tKUUi/data/topo) normaliza los nombres de los municipios para que coincidan los datos.


```json
[
  { "id": "52835", "municipio": "Tumaco",                "valor_total": 11548100716, "poblacion": 258089, "avance": 47.15, "dependencia": "Infraestructura" },
  { "id": "52612", "municipio": "San Pedro de Cartago",  "valor_total": 14987364429, "poblacion": 16811,  "avance": 2.83,  "dependencia": "Regalías"        },
  { "id": "52699", "municipio": "Taminango",             "valor_total": 7742805115,  "poblacion": 48138,  "avance": 16.4,  "dependencia": "Regalías"        },
  { "id": "52240", "municipio": "Cumbal",                "valor_total": 8105646721,  "poblacion": 39794,  "avance": 0.0,   "dependencia": "Regalías"        },
  { "id": "52287", "municipio": "El Rosario",            "valor_total": 7193766489,  "poblacion": 18162,  "avance": 93.01, "dependencia": "PDA"             },
  { "id": "52490", "municipio": "Olaya Herrera",         "valor_total": 6929843314,  "poblacion": 21683,  "avance": 54.98, "dependencia": "Regalías"        },
  { "id": "52786", "municipio": "Túquerres",             "valor_total": 1398119999,  "poblacion": 45000,  "avance": 26.4,  "dependencia": "Infraestructura" },
  { "id": "52418", "municipio": "La Unión",              "valor_total": 76077557,    "poblacion": 39794,  "avance": 5.49,  "dependencia": "IDSN"            },
  { "id": "52036", "municipio": "Ancuya",                "valor_total": 826790675,   "poblacion": 16336,  "avance": 14.35, "dependencia": "PDA"             },
  { "id": "52215", "municipio": "Córdoba",               "valor_total": 481178880,   "poblacion": 16338,  "avance": 38.31, "dependencia": "IDSN"            },
  { "id": "52083", "municipio": "Belén",                 "valor_total": 131097933,   "poblacion": 13426,  "avance": 100.0, "dependencia": "PDA"             }
]
```

**Configuración D3plus:**
```javascript
new d3plus.Geomap()
  .select("#v13-geomap")
  .data(V13)
  .groupBy("id")
  .colorScale("valor_total")
  .colorScalePosition("bottom")
  .topojson("https://gobiernoabierto.narino.gov.co/wp-content/uploads/narino-municipios.topojson")
  .render();
```

---

## V-14 · `matrix_municipio_dependencia`

**Descripción:** Matriz cruzada municipio (filas) × dependencia (columnas). Celda = número de contratos o valor total. Identifica qué municipios tienen mayor presencia por tipo de programa.

**Gráficos compatibles:** `Matrix` · `RadialMatrix`

```json
[
  { "municipio": "Tumaco",             "dependencia": "Infraestructura", "valor": 11548100716, "contratos": 1 },
  { "municipio": "Tumaco",             "dependencia": "IDSN",           "valor": 450000000,   "contratos": 1 },
  { "municipio": "Taminango",          "dependencia": "Regalías",        "valor": 7742805115,  "contratos": 1 },
  { "municipio": "Cumbal",             "dependencia": "Regalías",        "valor": 8105646721,  "contratos": 1 },
  { "municipio": "El Rosario",         "dependencia": "PDA",             "valor": 7193766489,  "contratos": 1 },
  { "municipio": "Policarpa",          "dependencia": "Regalías",        "valor": 2461332466,  "contratos": 1 },
  { "municipio": "Olaya Herrera",      "dependencia": "Regalías",        "valor": 6929843314,  "contratos": 1 },
  { "municipio": "San Pedro Cartago",  "dependencia": "Regalías",        "valor": 14987364429, "contratos": 1 },
  { "municipio": "La Unión",           "dependencia": "IDSN",            "valor": 76077557,    "contratos": 1 },
  { "municipio": "Ancuya",             "dependencia": "PDA",             "valor": 826790675,   "contratos": 1 },
  { "municipio": "Córdoba",            "dependencia": "IDSN",            "valor": 481178880,   "contratos": 1 },
  { "municipio": "Belén",              "dependencia": "PDA",             "valor": 131097933,   "contratos": 1 },
  { "municipio": "Dep. Nariño",        "dependencia": "IDSN",            "valor": 1634860970,  "contratos": 5 },
  { "municipio": "Túquerres",          "dependencia": "Infraestructura", "valor": 1398119999,  "contratos": 1 }
]
```

**Configuración D3plus:**
```javascript
new d3plus.Matrix()
  .select("#v14-matrix")
  .data(V14)
  .column("dependencia")
  .row("municipio")
  .value("valor")
  .render();

// RadialMatrix
new d3plus.RadialMatrix()
  .select("#v14-radial")
  .data(V14)
  .column("dependencia")
  .row("municipio")
  .value("contratos")
  .render();
```

---

## V-15 · `bump_ranking_dependencias`

**Descripción:** Ranking de dependencias por número de proyectos a través del tiempo (vigencias con BPIN). Muestra si alguna dependencia gana o pierde protagonismo por año.

**Gráficos compatibles:** `BumpChart`

```json
[
  { "vigencia": 2023, "dependencia": "IDSN",           "rank": 1, "total_proyectos": 1,  "valor_total": 104605284    },
  { "vigencia": 2024, "dependencia": "IDSN",           "rank": 1, "total_proyectos": 64, "valor_total": 580324112370 },
  { "vigencia": 2024, "dependencia": "PDA",            "rank": 2, "total_proyectos": 12, "valor_total": 105842234190 },
  { "vigencia": 2024, "dependencia": "Infraestructura","rank": 3, "total_proyectos": 5,  "valor_total": 21342817060  },
  { "vigencia": 2024, "dependencia": "Regalías",       "rank": 4, "total_proyectos": 3,  "valor_total": 70506999250  },
  { "vigencia": 2025, "dependencia": "PDA",            "rank": 1, "total_proyectos": 28, "valor_total": 4336870060120},
  { "vigencia": 2025, "dependencia": "Regalías",       "rank": 2, "total_proyectos": 8,  "valor_total": 98762411890  },
  { "vigencia": 2025, "dependencia": "IDSN",           "rank": 3, "total_proyectos": 5,  "valor_total": 42110623060  },
  { "vigencia": 2025, "dependencia": "Infraestructura","rank": 4, "total_proyectos": 1,  "valor_total": 6404060131   },
  { "vigencia": 2026, "dependencia": "PDA",            "rank": 1, "total_proyectos": 14, "valor_total": 29440831149  },
  { "vigencia": 2026, "dependencia": "Regalías",       "rank": 2, "total_proyectos": 7,  "valor_total": 55830046020  },
  { "vigencia": 2026, "dependencia": "Infraestructura","rank": 3, "total_proyectos": 3,  "valor_total": 8248044190   },
  { "vigencia": 2026, "dependencia": "IDSN",           "rank": 4, "total_proyectos": 0,  "valor_total": 3516680000   }
]
```

**Configuración D3plus:**
```javascript
new d3plus.BumpChart()
  .select("#v15-bump")
  .data(V15)
  .groupBy("dependencia")
  .x("vigencia")
  .y("rank")
  .render();
```

---

## V-16 · `priestley_timeline`

**Descripción:** Línea de tiempo de proyectos por vigencia y dependencia. Cada proyecto es una barra horizontal con inicio y fin estimados. Útil para ver solapamientos y concentración temporal.

**Gráficos compatibles:** `Priestley`

> **Nota:** El API SGR no expone fechas exactas de inicio/fin. Se usa el año de vigencia (BPIN prefijo) para definir `start` = 01/01/vigencia, `end` = 31/12/vigencia+1. Para proyectos sin año BPIN se usa el ciclo bienal SGR típico.

```json
[
  { "id": "2023520002556",      "label": "Salud IDSN 2023",            "dependencia": "IDSN",           "start": "2023-01-01", "end": "2024-12-31", "valor": 104605284       },
  { "id": "BPIN-2024-001",      "label": "Salud IDSN 2024 (grupo)",    "dependencia": "IDSN",           "start": "2024-01-01", "end": "2025-12-31", "valor": 580324112370    },
  { "id": "PDA-2024-001",       "label": "Agua PDA 2024 (grupo)",      "dependencia": "PDA",            "start": "2024-01-01", "end": "2026-06-30", "valor": 105842234190    },
  { "id": "Infra-2024-001",     "label": "Infraestructura 2024",       "dependencia": "Infraestructura","start": "2024-03-01", "end": "2025-09-30", "valor": 21342817060     },
  { "id": "SGR-2024-Reg",       "label": "Regalías 2024",              "dependencia": "Regalías",       "start": "2024-01-01", "end": "2027-12-31", "valor": 70506999250     },
  { "id": "BPIN-2025-SGR",      "label": "Regalías 2025 (grupo)",      "dependencia": "Regalías",       "start": "2025-01-01", "end": "2028-12-31", "valor": 4336870060120   },
  { "id": "PDA-2025-001",       "label": "Agua PDA 2025 (grupo)",      "dependencia": "PDA",            "start": "2025-01-01", "end": "2027-06-30", "valor": 98762411890     },
  { "id": "IDSN-2025-001",      "label": "Salud IDSN 2025",            "dependencia": "IDSN",           "start": "2025-01-01", "end": "2026-12-31", "valor": 42110623060     },
  { "id": "SGR-2026-Reg",       "label": "Regalías 2026",              "dependencia": "Regalías",       "start": "2026-01-01", "end": "2029-12-31", "valor": 55830046020     },
  { "id": "PDA-2026-001",       "label": "Agua PDA 2026 (grupo)",      "dependencia": "PDA",            "start": "2026-01-01", "end": "2028-06-30", "valor": 29440831149     },
  { "id": "Infra-BPIN-2026",    "label": "Infraestructura 2026",       "dependencia": "Infraestructura","start": "2026-03-01", "end": "2027-09-30", "valor": 8248044190      },
  { "id": "IDSN-2026-001",      "label": "Salud IDSN 2026",            "dependencia": "IDSN",           "start": "2026-01-01", "end": "2027-06-30", "valor": 3516680000      }
]
```

**Configuración D3plus:**
```javascript
new d3plus.Priestley()
  .select("#v16-priestley")
  .data(V16)
  .groupBy("dependencia")
  .start("start")
  .end("end")
  .label("label")
  .render();
```

---

## V-17 · `rings_proyecto_municipio`

**Descripción:** Red ego-centrada donde el nodo central es la dependencia y los anillos son: entidad ejecutora → municipio beneficiario. Explora las conexiones de un proyecto individual.

**Gráficos compatibles:** `Rings` · `Network`

```json
{
  "nodes": [
    { "id": "Regalías",           "tipo": "dependencia" },
    { "id": "Otro",               "tipo": "entidad"     },
    { "id": "Municipio",          "tipo": "entidad"     },
    { "id": "Departamento",       "tipo": "entidad"     },
    { "id": "Taminango",          "tipo": "municipio"   },
    { "id": "Cumbal",             "tipo": "municipio"   },
    { "id": "Policarpa",          "tipo": "municipio"   },
    { "id": "Olaya Herrera",      "tipo": "municipio"   },
    { "id": "San Pedro Cartago",  "tipo": "municipio"   },
    { "id": "Tumaco",             "tipo": "municipio"   },
    { "id": "El Rosario",         "tipo": "municipio"   }
  ],
  "edges": [
    { "source": "Regalías",    "target": "Otro",             "weight": 18 },
    { "source": "Regalías",    "target": "Municipio",        "weight": 17 },
    { "source": "Regalías",    "target": "Departamento",     "weight": 3  },
    { "source": "Otro",        "target": "Taminango",        "weight": 1  },
    { "source": "Otro",        "target": "Cumbal",           "weight": 1  },
    { "source": "Municipio",   "target": "Policarpa",        "weight": 1  },
    { "source": "Municipio",   "target": "Olaya Herrera",    "weight": 1  },
    { "source": "Municipio",   "target": "San Pedro Cartago","weight": 1  },
    { "source": "Municipio",   "target": "Tumaco",           "weight": 1  },
    { "source": "Municipio",   "target": "El Rosario",       "weight": 1  }
  ]
}
```

**Configuración D3plus:**
```javascript
new d3plus.Rings()
  .select("#v17-rings")
  .nodes(V17.nodes)
  .edges(V17.edges)
  .center("Regalías")
  .groupBy("tipo")
  .render();
```

---

## V-18 · `treemap_entidad_municipio`

**Descripción:** Jerarquía de valor: Entidad Ejecutora → Municipio. Permite ver qué municipios concentran mayor inversión dentro de cada tipo de ejecutor.

**Gráficos compatibles:** `Treemap` · `Pack`

```json
[
  { "id": "Otro",                         "parent": "",      "value": 0             },
  { "id": "Municipio",                    "parent": "",      "value": 0             },
  { "id": "Departamento",                 "parent": "",      "value": 0             },

  { "id": "Otro_Taminango",               "parent": "Otro",       "label": "Taminango",       "value": 7742805115  },
  { "id": "Otro_Cumbal",                  "parent": "Otro",       "label": "Cumbal",           "value": 8105646721  },
  { "id": "Otro_Policarpa",               "parent": "Otro",       "label": "Policarpa",        "value": 2461332466  },
  { "id": "Otro_Olaya_Herrera",           "parent": "Otro",       "label": "Olaya Herrera",    "value": 6929843314  },
  { "id": "Otro_San_Pedro",               "parent": "Otro",       "label": "San Pedro Cartago","value": 14987364429 },

  { "id": "Mun_Tumaco",                   "parent": "Municipio",  "label": "Tumaco",           "value": 11548100716 },
  { "id": "Mun_Tuquerres",                "parent": "Municipio",  "label": "Túquerres",        "value": 1398119999  },
  { "id": "Mun_El_Rosario",               "parent": "Municipio",  "label": "El Rosario",       "value": 7193766489  },
  { "id": "Mun_La_Union",                 "parent": "Municipio",  "label": "La Unión",         "value": 76077557    },
  { "id": "Mun_Ancuya",                   "parent": "Municipio",  "label": "Ancuya",           "value": 826790675   },
  { "id": "Mun_Cordoba",                  "parent": "Municipio",  "label": "Córdoba",          "value": 481178880   },
  { "id": "Mun_Belen",                    "parent": "Municipio",  "label": "Belén",            "value": 131097933   },

  { "id": "Dep_Narino",                   "parent": "Departamento","label": "Dep. Nariño",     "value": 1634860970  }
]
```

---

## V-19 · `box_avance_por_entidad`

**Descripción:** Un registro por contrato con su avance físico y la entidad ejecutora del proyecto padre. Permite a BoxWhisker calcular la distribución estadística (Q1, mediana, Q3, outliers) por entidad.

**Gráficos compatibles:** `BoxWhisker`

```json
[
  { "id_contrato": "LP-001-2025",      "entidad": "Otro",        "dependencia": "Regalías",        "avance": 16.40  },
  { "id_contrato": "CMA-001-2025",     "entidad": "Departamento","dependencia": "IDSN",            "avance": 20.00  },
  { "id_contrato": "GN4086-2024",      "entidad": "Municipio",   "dependencia": "Infraestructura", "avance": 26.40  },
  { "id_contrato": "CMA-004-2025",     "entidad": "Otro",        "dependencia": "Regalías",        "avance": 0.00   },
  { "id_contrato": "CMA-003-2025",     "entidad": "Departamento","dependencia": "IDSN",            "avance": 5.99   },
  { "id_contrato": "UM-2025040048",    "entidad": "Departamento","dependencia": "PDA",             "avance": 93.01  },
  { "id_contrato": "CM-001-DE-2025",   "entidad": "Departamento","dependencia": "IDSN",            "avance": 5.49   },
  { "id_contrato": "LP001-2025",       "entidad": "Otro",        "dependencia": "Regalías",        "avance": 14.12  },
  { "id_contrato": "CM-002-2025",      "entidad": "Departamento","dependencia": "IDSN",            "avance": 8.42   },
  { "id_contrato": "017",              "entidad": "Municipio",   "dependencia": "Infraestructura", "avance": 47.15  },
  { "id_contrato": "016",              "entidad": "Municipio",   "dependencia": "PDA",             "avance": 14.35  },
  { "id_contrato": "LP-003-DE-2025",   "entidad": "Otro",        "dependencia": "Regalías",        "avance": 54.98  },
  { "id_contrato": "CM-006-2025",      "entidad": "Departamento","dependencia": "IDSN",            "avance": 38.31  },
  { "id_contrato": "FUV.SGR.011.2025", "entidad": "Otro",        "dependencia": "Regalías",        "avance": 2.83   },
  { "id_contrato": "CMA-001-DE-2025",  "entidad": "Departamento","dependencia": "PDA",             "avance": 100.00 }
]
```

**Configuración D3plus:**
```javascript
// BoxWhisker por entidad ejecutora
new d3plus.BoxWhisker()
  .select("#v19-box-entidad")
  .data(V19)
  .groupBy("entidad")
  .x("entidad")
  .y("avance")
  .render();

// BoxWhisker por dependencia
new d3plus.BoxWhisker()
  .select("#v19-box-dep")
  .data(V19)
  .groupBy("dependencia")
  .x("dependencia")
  .y("avance")
  .render();
```

---

## V-20 · `tree_full_hierarchy`

**Descripción:** Árbol completo de 4 niveles: SGR → Dependencia → Entidad Ejecutora → Municipio. La estructura jerárquica definitiva para Tree y navegación drill-down.

**Gráficos compatibles:** `Tree`

> Para implementación completa en D3plus Tree, se aplana como arreglo con `id` y `parent`. La muestra incluye nodos representativos de cada nivel.

```json
[
  { "id": "SGR Nariño",             "parent": null,               "nivel": 0, "valor": 6521952237664 },

  { "id": "dep_IDSN",               "parent": "SGR Nariño",       "nivel": 1, "label": "IDSN",           "valor": 1428383469973 },
  { "id": "dep_PDA",                "parent": "SGR Nariño",       "nivel": 1, "label": "PDA",            "valor": 234045476898  },
  { "id": "dep_Infra",              "parent": "SGR Nariño",       "nivel": 1, "label": "Infraestructura","valor": 67923724603   },
  { "id": "dep_Regalias",           "parent": "SGR Nariño",       "nivel": 1, "label": "Regalías",       "valor": 4791599566190 },

  { "id": "IDSN_Municipio",         "parent": "dep_IDSN",         "nivel": 2, "label": "Municipio",      "valor": 666205324197  },
  { "id": "IDSN_Departamento",      "parent": "dep_IDSN",         "nivel": 2, "label": "Departamento",   "valor": 762178145776  },
  { "id": "PDA_Municipio",          "parent": "dep_PDA",          "nivel": 2, "label": "Municipio",      "valor": 42254993723   },
  { "id": "PDA_Departamento",       "parent": "dep_PDA",          "nivel": 2, "label": "Departamento",   "valor": 191790483175  },
  { "id": "Infra_Municipio",        "parent": "dep_Infra",        "nivel": 2, "label": "Municipio",      "valor": 56765386194   },
  { "id": "Infra_Otro",             "parent": "dep_Infra",        "nivel": 2, "label": "Otro",           "valor": 5375408388    },
  { "id": "Infra_Departamento",     "parent": "dep_Infra",        "nivel": 2, "label": "Departamento",   "valor": 5782930021    },
  { "id": "Reg_Otro",               "parent": "dep_Regalias",     "nivel": 2, "label": "Otro",           "valor": 4569511060068 },
  { "id": "Reg_Municipio",          "parent": "dep_Regalias",     "nivel": 2, "label": "Municipio",      "valor": 193792635669  },
  { "id": "Reg_Departamento",       "parent": "dep_Regalias",     "nivel": 2, "label": "Departamento",   "valor": 28295870453   },

  { "id": "mun_Tumaco",             "parent": "Infra_Municipio",  "nivel": 3, "label": "Tumaco",         "valor": 11548100716   },
  { "id": "mun_Tuquerres",          "parent": "Infra_Municipio",  "nivel": 3, "label": "Túquerres",      "valor": 1398119999    },
  { "id": "mun_El_Rosario",         "parent": "PDA_Municipio",    "nivel": 3, "label": "El Rosario",     "valor": 7193766489    },
  { "id": "mun_Taminango",          "parent": "Reg_Otro",         "nivel": 3, "label": "Taminango",      "valor": 7742805115    },
  { "id": "mun_Cumbal",             "parent": "Reg_Otro",         "nivel": 3, "label": "Cumbal",         "valor": 8105646721    },
  { "id": "mun_Olaya",              "parent": "Reg_Municipio",    "nivel": 3, "label": "Olaya Herrera",  "valor": 6929843314    },
  { "id": "mun_San_Pedro",          "parent": "Reg_Otro",         "nivel": 3, "label": "San Pedro Cart.","valor": 14987364429   },
  { "id": "mun_Dep_Narino_IDSN",    "parent": "IDSN_Departamento","nivel": 3, "label": "Dep. Nariño",    "valor": 1634860970    }
]
```

**Configuración D3plus:**
```javascript
new d3plus.Tree()
  .select("#v20-tree")
  .data(V20)
  .id("id")
  .parentId("parent")
  .label("label")
  .value("valor")
  .render();
```

---

## Resumen de Compatibilidad: Vista ↔ Gráficos

| Vista | Archivo JSON / Endpoint | BarChart | LinePlot | StackedArea | Treemap | Pack | Pie | Donut | Network | Sankey | Rings | Radar | BumpChart | BoxWhisker | Matrix | RadialMatrix | Plot | Priestley | Tree | Geomap |
|---|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| V-01 | valor_por_dependencia        | ✅ | — | — | — | — | ✅ | ✅ | — | — | — | — | — | — | — | — | — | — | — | — |
| V-02 | proyectos_dependencia_entidad| ✅ | — | ✅ | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — |
| V-03 | jerarquia_dependencia_entidad| — | — | — | ✅ | ✅ | — | — | — | — | — | — | — | — | — | — | — | — | ✅ | — |
| V-04 | vigencia_valor               | ✅ | ✅ | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — |
| V-05 | vigencia_dependencia_stack   | — | — | ✅ | — | — | — | — | — | — | — | — | ✅ | — | — | — | — | — | — | — |
| V-06 | distribucion_entidad_ejecutora| — | — | — | — | — | ✅ | ✅ | — | — | — | — | — | — | — | — | — | — | — | — |
| V-07 | avance_fisico_contratos      | — | — | — | — | — | — | — | — | — | — | — | — | ✅ | — | — | ✅ | — | — | — |
| V-08 | scatter_valor_avance         | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | ✅ | — | — | — |
| V-09 | radar_metricas_dependencia   | — | — | — | — | — | — | — | — | — | — | ✅ | — | — | ✅ | — | — | — | — | — |
| V-10 | red_entidad_dependencia      | — | — | — | — | — | — | — | ✅ | — | ✅ | — | — | — | — | — | — | — | — | — |
| V-11 | sankey_flujo_completo        | — | — | — | — | — | — | — | — | ✅ | — | — | — | — | — | — | — | — | — | — |
| V-12 | municipios_contratos         | — | — | — | — | — | — | — | — | — | — | — | — | — | ✅ | — | — | — | — | ✅ |
| V-13 | geomap_poblacion             | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | ✅ |
| V-14 | matrix_municipio_dependencia | — | — | — | — | — | — | — | — | — | — | — | — | — | ✅ | ✅ | — | — | — | — |
| V-15 | bump_ranking_dependencias    | — | — | — | — | — | — | — | — | — | — | — | ✅ | — | — | — | — | — | — | — |
| V-16 | priestley_timeline           | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | ✅ | — | — |
| V-17 | rings_proyecto_municipio     | — | — | — | — | — | — | — | ✅ | — | ✅ | — | — | — | — | — | — | — | — | — |
| V-18 | treemap_entidad_municipio    | — | — | — | ✅ | ✅ | — | — | — | — | — | — | — | — | — | — | — | — | — | — |
| V-19 | box_avance_por_entidad       | — | — | — | — | — | — | — | — | — | — | — | — | ✅ | — | — | — | — | — | — |
| V-20 | tree_full_hierarchy          | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | ✅ | — |

---

## Implementación en PHP (sgr.php — endpoints múltiples)

Para servir cada vista como endpoint separado en WordPress:

```php
// wp-api/sgr.php?vista=V01
$vista = $_GET['vista'] ?? 'raw';

switch($vista) {
    case 'V01': echo json_encode(sgr_get_valor_por_dependencia($proyectos)); break;
    case 'V02': echo json_encode(sgr_get_dependencia_entidad($proyectos));   break;
    case 'V03': echo json_encode(sgr_get_jerarquia($proyectos));             break;
    case 'V07': echo json_encode(sgr_get_contratos_flat($proyectos));        break;
    case 'V13': echo json_encode(sgr_get_geomap($proyectos));                break;
    default:    echo json_encode(['proyectos' => $proyectos]);               break;
}

// Ejemplo de función agregadora V-01
function sgr_get_valor_por_dependencia(array $proyectos): array {
    $result = [];
    foreach ($proyectos as $p) {
        $dep = $p['dependenciaProyecto'];
        if (!isset($result[$dep])) {
            $result[$dep] = ['id' => $dep, 'total_proyectos' => 0, 'valor_total' => 0];
        }
        $result[$dep]['total_proyectos']++;
        $result[$dep]['valor_total'] += floatval($p['valorProyecto']);
    }
    return array_values($result);
}
```

---

## Notas técnicas

| Aspecto | Detalle |
|---|---|
| Valores COP | Todos los `valorProyecto` y `valorContrato` vienen como `string`. Convertir con `parseFloat()` en JS o `floatval()` en PHP antes de agregar. |
| Avance físico | `procentajeAvanceFisico` viene como string `"27.530000"`. Usar `parseFloat()`. |
| Vigencia derivada | Extraer `numeroProyecto.substring(0,4)` solo cuando el BPIN empieza con año (2023–2026). |
| Municipios múltiples | `municipiosEjecContractual` es un array; un contrato puede beneficiar varios municipios. Para Geomap/Matrix, expandir a un registro por municipio. |
| ODS vacíos | `odssProyecto` y `odssEjecContractual` están vacíos en la mayoría de registros actuales. No construir vistas ODS hasta que el API los retorne. |
| SSL en PHP | Mantener `sslverify: false` para consumo interno del endpoint BPID desde WordPress. |
| Caché | Aplicar `set_transient('sgr_data', $data, HOUR_IN_SECONDS)` igual que en BPID suite. |
