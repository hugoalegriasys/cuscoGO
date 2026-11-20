document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('form-aforo');
  if (!form) return;

  const select = document.getElementById('sitio-aforo');
  const estado = document.getElementById('aforo-estado');
  const resultado = document.getElementById('aforo-resultado');
  const nivel = document.getElementById('aforo-nivel');
  const detalle = document.getElementById('aforo-detalle');
  const boton = form.querySelector('button[type="submit"]');

  function setEstado(mensaje, tipo) {
    estado.textContent = mensaje;
    estado.className = 'status-area' + (tipo ? ' ' + tipo : '');
  }

  function limpiarResultado() {
    resultado.hidden = true;
    nivel.textContent = '';
    nivel.className = 'badge';
    detalle.textContent = '';
  }

  function textoNivel(valor) {
    if (valor === 'Bajo') return 'Bajo (poca afluencia)';
    if (valor === 'Moderado') return 'Moderado (afluencia media)';
    if (valor === 'Alto') return 'Alto (mucha afluencia)';
    return valor;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    limpiarResultado();

    const sitioId = select.value;
    if (!sitioId) {
      setEstado('Selecciona un sitio para consultar el aforo.', 'error');
      select.focus();
      return;
    }

    boton.disabled = true;
    setEstado('Consultando aforo en tiempo real...', 'loading');

    try {
      const response = await fetch(`/api/sitios/${encodeURIComponent(sitioId)}/aforo`, {
        headers: { 'Accept': 'application/json' }
      });

      let data = {};
      try {
        data = await response.json();
      } catch (e) {
        data = {};
      }

      if (response.ok) {
        const valor = data.nivel_aforo || '';
        nivel.textContent = textoNivel(valor);
        nivel.classList.add(valor.toLowerCase());
        const nombreSitio = select.options[select.selectedIndex] ? select.options[select.selectedIndex].text : sitioId;
        detalle.textContent = `Sitio consultado: ${nombreSitio}.`;
        resultado.hidden = false;
        setEstado('Consulta realizada correctamente.', '');
        return;
      }

      if (response.status === 422) {
        setEstado(data.mensaje || 'Los datos del aforo no son válidos.', 'error');
        return;
      }

      if (response.status === 503) {
        setEstado(data.mensaje || 'El sensor no está disponible en este momento.', 'error');
        return;
      }

      setEstado('Ocurrió un error al consultar el aforo.', 'error');
    } catch (error) {
      setEstado('No se pudo conectar con el servidor. Intenta nuevamente.', 'error');
    } finally {
      boton.disabled = false;
    }
  });
});