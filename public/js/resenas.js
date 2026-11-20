document.addEventListener('DOMContentLoaded', () => {
  const select = document.getElementById('sitio-resena');
  const lista = document.getElementById('resena-lista');
  const listaEstado = document.getElementById('resena-lista-estado');
  const promedioEl = document.getElementById('resena-promedio');
  const form = document.getElementById('form-resena');
  const formEstado = document.getElementById('resena-form-estado');
  const comentario = document.getElementById('comentario-resena');
  const contador = document.getElementById('comentario-contador');

  if (!select || !lista || !form) return;

  function setEstado(el, mensaje, tipo) {
    el.textContent = mensaje;
    el.className = 'status-area' + (tipo ? ' ' + tipo : '');
  }

  function actualizarContador() {
    const len = comentario.value.length;
    contador.textContent = `${len}/500`;
  }

  function obtenerCalificacionSeleccionada() {
    const checked = form.querySelector('input[name="calificacion"]:checked');
    return checked ? checked.value : '';
  }

  function limpiarLista() {
    while (lista.firstChild) {
      lista.removeChild(lista.firstChild);
    }
  }

  function renderResenas(resenas) {
    limpiarLista();
    if (!resenas || resenas.length === 0) {
      setEstado(listaEstado, 'Aún no hay reseñas para este sitio.', '');
      const li = document.createElement('li');
      li.className = 'resena-empty';
      li.textContent = 'Sé el primero en compartir tu experiencia.';
      li.setAttribute('data-cy', 'resena-item-vacio');
      lista.appendChild(li);
      return;
    }
    setEstado(listaEstado, '', '');
    resenas.forEach((resena) => {
      const li = document.createElement('li');
      li.className = 'resena-item';
      li.setAttribute('data-cy', 'resena-item');

      const meta = document.createElement('p');
      meta.className = 'resena-meta';
      meta.textContent = `Calificación: ${resena.calificacion} / 5`;

      const texto = document.createElement('p');
      texto.className = 'resena-texto';
      texto.textContent = resena.comentario || '';

      li.appendChild(meta);
      li.appendChild(texto);
      lista.appendChild(li);
    });
  }

  async function cargarResenas() {
    const sitioId = select.value;
    promedioEl.textContent = '0';
    limpiarLista();
    if (!sitioId) {
      setEstado(listaEstado, 'Selecciona un sitio para ver sus reseñas.', '');
      return;
    }
    setEstado(listaEstado, 'Cargando reseñas...', 'loading');
    try {
      const response = await fetch(`/api/sitios/${encodeURIComponent(sitioId)}/resenas`, {
        headers: { 'Accept': 'application/json' }
      });
      let data = {};
      try { data = await response.json(); } catch (e) { data = {}; }

      if (response.ok) {
        promedioEl.textContent = data.promedio ?? 0;
        renderResenas(data.resenas || []);
        return;
      }
      if (response.status === 404) {
        setEstado(listaEstado, 'El sitio seleccionado no existe.', 'error');
        return;
      }
      setEstado(listaEstado, 'Ocurrió un error al cargar las reseñas.', 'error');
    } catch (e) {
      setEstado(listaEstado, 'No se pudo conectar con el servidor.', 'error');
    }
  }

  comentario.addEventListener('input', actualizarContador);
  actualizarContador();

  select.addEventListener('change', () => {
    setEstado(formEstado, '', '');
    cargarResenas();
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    setEstado(formEstado, '', '');

    const sitioId = select.value;
    if (!sitioId) {
      setEstado(formEstado, 'Selecciona un sitio para registrar tu reseña.', 'error');
      select.focus();
      return;
    }

    const calificacion = obtenerCalificacionSeleccionada();
    const comentarioValor = comentario.value;
    const boton = form.querySelector('button[type="submit"]');
    boton.disabled = true;
    setEstado(formEstado, 'Registrando reseña...', 'loading');

    try {
      const response = await fetch(`/api/sitios/${encodeURIComponent(sitioId)}/resenas`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ calificacion, comentario: comentarioValor })
      });
      let data = {};
      try { data = await response.json(); } catch (e) { data = {}; }

      if (response.status === 201) {
        setEstado(formEstado, 'Reseña registrada correctamente.', '');
        form.reset();
        actualizarContador();
        await cargarResenas();
        return;
      }
      if (response.status === 422) {
        setEstado(formEstado, data.mensaje || 'Los datos de la reseña no son válidos.', 'error');
        return;
      }
      if (response.status === 404) {
        setEstado(formEstado, 'El sitio seleccionado no existe.', 'error');
        return;
      }
      setEstado(formEstado, 'Ocurrió un error al registrar la reseña.', 'error');
    } catch (e) {
      setEstado(formEstado, 'No se pudo conectar con el servidor.', 'error');
    } finally {
      boton.disabled = false;
    }
  });

  cargarResenas();
});