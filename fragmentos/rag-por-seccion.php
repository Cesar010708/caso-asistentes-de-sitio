<?php
/**
 * RAG filtrado por la sección del sitio (versión genérica).
 *
 * Un mismo asistente vive en varias páginas (inicio, servicios, tienda, contacto…) y
 * debe contestar con el conocimiento de ESA página. Cada fragmento indexado lleva en
 * su metadata las secciones a las que pertenece, como cadena: "|home|contacto|"
 * (o "|all|" si vale para todo el sitio). El widget manda su `data-section`.
 *
 * Por qué se filtra en PHP y no en la base vectorial: ChromaDB no admite `$contains`
 * sobre metadata (devuelve vacío sin error), y una igualdad exacta no sirve para
 * documentos asignados a varias secciones. Se piden más candidatos por similitud de
 * los necesarios y se filtran aquí.
 *
 * Ejecutar el ejemplo:  php rag-por-seccion.php
 */

declare(strict_types=1);

const SECCIONES_VALIDAS = ['home', 'servicios', 'tienda', 'contacto', 'financiamiento'];
const TOP_K = 3;

/** Normaliza lo que manda el widget: cualquier valor desconocido cae en 'default'. */
function seccionValida(?string $s): string
{
    return in_array($s, SECCIONES_VALIDAS, true) ? $s : 'default';
}

/**
 * @param array<int, array{texto:string, secciones:string, score:float}> $candidatos  ordenados por similitud
 */
function contextoPorSeccion(array $candidatos, string $seccion, int $k = TOP_K): array
{
    $elegidos = [];
    foreach ($candidatos as $c) {
        $aplica = $seccion === 'default'
            || str_contains($c['secciones'], "|{$seccion}|")
            || str_contains($c['secciones'], '|all|');
        if ($aplica) {
            $elegidos[] = $c['texto'];
            if (count($elegidos) >= $k) break;
        }
    }
    return $elegidos;
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    // Candidatos de una búsqueda por similitud (ya ordenados), con su metadata de sección.
    $candidatos = [
        ['texto' => 'Envíos de equipo en 3 a 5 días hábiles.',            'secciones' => '|tienda|',            'score' => 0.82],
        ['texto' => 'Horario de atención: lunes a viernes de 9 a 18 h.',  'secciones' => '|contacto|home|',     'score' => 0.80],
        ['texto' => 'Arrendamiento de consultorios por especialidad.',     'secciones' => '|servicios|home|',    'score' => 0.77],
        ['texto' => 'Somos una plataforma de salud con sede en México.',   'secciones' => '|all|',               'score' => 0.70],
        ['texto' => 'Crédito para tratamientos médicos de 3 a 48 meses.',  'secciones' => '|financiamiento|',    'score' => 0.66],
    ];
    foreach (['tienda', 'contacto', 'financiamiento', 'pagina-rara'] as $s) {
        $sec = seccionValida($s);
        printf("[%s → %s]\n", $s, $sec);
        foreach (contextoPorSeccion($candidatos, $sec) as $t) {
            echo "   · {$t}\n";
        }
    }
}
