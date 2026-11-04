/* ==========================================================================
   Simulador de mensualidad dentro del chat (versión genérica)
   --------------------------------------------------------------------------
   El asistente de financiamiento no le pide al modelo de lenguaje que calcule una
   mensualidad: la calcula este código con la fórmula de amortización (pago fijo) y
   el modelo solo la explica. Cada monto cae en un segmento con su tasa anual y sus
   plazos permitidos, de modo que la persona solo puede elegir combinaciones válidas.
   El servidor repite el mismo cálculo al guardar el prospecto: lo que manda el
   navegador nunca se toma como verdad.

   Los segmentos y tasas de este ejemplo son FICTICIOS.

   Ejecutar el ejemplo:  node simulador-mensualidad.js
   ========================================================================== */

const SEGMENTOS = [
  { seg: 'A', min: 5000,   max: 50000,  tasaAnual: 30, plazos: [3, 6, 9, 12] },
  { seg: 'B', min: 50001,  max: 150000, tasaAnual: 26, plazos: [6, 12, 18, 24] },
  { seg: 'C', min: 150001, max: 500000, tasaAnual: 22, plazos: [12, 24, 36, 48] },
];

const segmentoDe = (monto) => SEGMENTOS.find((s) => monto >= s.min && monto <= s.max) || null;

/** Pago fijo mensual: P · r / (1 − (1 + r)^−n), con r = tasa anual / 12. */
function mensualidad(monto, tasaAnualPct, meses) {
  const r = tasaAnualPct / 100 / 12;
  return r === 0 ? monto / meses : (monto * r) / (1 - Math.pow(1 + r, -meses));
}

/** Lo que el widget pinta: opciones válidas para ese monto, o el motivo por el que no aplica. */
function simular(monto, meses) {
  const s = segmentoDe(monto);
  if (!s) return { ok: false, motivo: 'El monto debe estar entre $5,000 y $500,000.' };
  if (!s.plazos.includes(meses)) return { ok: false, motivo: `Para ese monto los plazos son: ${s.plazos.join(', ')} meses.`, plazos: s.plazos };
  const pago = Math.round(mensualidad(monto, s.tasaAnual, meses));
  return { ok: true, segmento: s.seg, tasaAnual: s.tasaAnual, meses, pago, total: pago * meses };
}

module.exports = { segmentoDe, mensualidad, simular };

if (require.main === module) {
  const fmt = (n) => '$' + n.toLocaleString('es-MX');
  for (const [monto, meses] of [[40000, 12], [120000, 24], [120000, 36], [800000, 12]]) {
    const r = simular(monto, meses);
    console.log(`${fmt(monto).padEnd(10)} a ${String(meses).padStart(2)} meses → ` +
      (r.ok ? `segmento ${r.segmento}, ${fmt(r.pago)} al mes (total ${fmt(r.total)})` : r.motivo));
  }
}
