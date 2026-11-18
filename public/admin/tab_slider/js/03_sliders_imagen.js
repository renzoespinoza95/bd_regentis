const sliderImagenMethods = {
  objetivo(mode) {
    return mode === 'crear' ? this.nuevo : this.formulario;
  },

  mostrarUrlImagen(mode) {
    const t = this.objetivo(mode);
    const url = t.imgUrl || (mode === 'editar' ? t.img : '') || (t.imgFile ? 'Archivo local seleccionado (aún sin URL en servidor)' : '');
    if (url) {
      apprise(url, { okBtn: 'Cerrar' });
    } else {
      apprise('No hay URL disponible para esta imagen', { okBtn: 'Entendido' });
    }
  },

  limpiarInputArchivo(mode) {
    const ref = mode === 'crear' ? this.$refs.fileCrear : this.$refs.fileEditar;
    if (ref) ref.value = '';
  },

  extraerUrls(texto) {
    const vistos = {};
    return String(texto || '')
      .split(/\s+/)
      .map(u => u.trim())
      .filter(u => /^https?:\/\/\S+$/i.test(u))
      .filter(u => (vistos[u] ? false : (vistos[u] = true)));
  },

  cargarArchivo(file, mode) {
    if (!file) return;
    if (!file.type || file.type.indexOf('image/') !== 0) {
      apprise('El archivo seleccionado no es una imagen', { okBtn: 'Entendido' });
      return;
    }
    const t = this.objetivo(mode);
    const reader = new FileReader();
    reader.onload = ev => {
      t.imgFile = file;
      t.imgUrl = '';
      t.imgPreview = ev.target.result;
      if (mode === 'editar') t.imagenEliminada = false;
    };
    reader.readAsDataURL(file);
  },

  onFileChange(e, mode) {
    const file = e.target.files[0];
    if (!file) return;
    this.cargarArchivo(file, mode);
  },

  establecerUrl(url, mode) {
    const t = this.objetivo(mode);
    t.imgFile = null;
    t.imgUrl = url;
    t.imgPreview = url;
    if (mode === 'editar') t.imagenEliminada = false;
    this.limpiarInputArchivo(mode);
  },

  aceptarUrl(mode) {
    const t = this.objetivo(mode);
    const urls = this.extraerUrls(t.urlInput + '\n' + t.urlsTexto);

    if (!urls.length) {
      return apprise('Ingresa una URL válida que empiece con http:// o https://', { okBtn: 'Entendido' });
    }

    this.establecerUrl(urls[0], mode);
    t.urlInput = '';
    t.urlsTexto = '';

    if (urls.length > 1) {
      apprise('Esta entidad tiene una sola imagen: se tomó la primera URL.', { okBtn: 'Entendido' });
    }
  },

  pegarUrls(mode) {
    setTimeout(() => {
      const t = this.objetivo(mode);
      const urls = this.extraerUrls(t.urlsTexto);
      if (urls.length) this.establecerUrl(urls[0], mode);
    }, 0);
  },

  zonaDragOver(mode) {
    this.objetivo(mode).arrastrando = true;
  },

  zonaDragLeave(mode) {
    this.objetivo(mode).arrastrando = false;
  },

  zonaDrop(e, mode) {
    const t = this.objetivo(mode);
    t.arrastrando = false;
    const dt = e.dataTransfer;
    if (!dt) return;

    // 1) Archivos de imagen arrastrados
    if (dt.files && dt.files.length) {
      const archivos = Array.from(dt.files).filter(f => f.type && f.type.indexOf('image/') === 0);
      if (archivos.length) {
        this.cargarArchivo(archivos[0], mode);
        if (archivos.length > 1) {
          apprise('Esta entidad tiene una sola imagen: se tomó la primera.', { okBtn: 'Entendido' });
        }
        return;
      }
    }

    // 2) Enlaces (URL) arrastrados
    const texto = dt.getData('text/uri-list') || dt.getData('text/plain') || '';
    const urls = this.extraerUrls(texto);

    if (urls.length) {
      t.urlsTexto = urls.join('\n');
      this.establecerUrl(urls[0], mode);
      if (urls.length > 1) {
        apprise('Esta entidad tiene una sola imagen: se tomó la primera URL.', { okBtn: 'Entendido' });
      }
    } else {
      apprise('Suelta una imagen o un enlace URL válido', { okBtn: 'Entendido' });
    }
  },

  quitarImagen(mode) {
    const t = this.objetivo(mode);

    // Hay una imagen pendiente (archivo o URL aún no guardada): solo se descarta
    if (t.imgFile || t.imgUrl) {
      t.imgFile = null;
      t.imgUrl = '';
      this.limpiarInputArchivo(mode);
      t.imgPreview = (mode === 'editar' && !t.imagenEliminada) ? (t.img || '') : '';
      return;
    }

    if (mode === 'crear') {
      t.imgPreview = '';
      return;
    }

    // Editar: imagen ya guardada en el registro
    this.confirmarEliminarImagen();
  },

  usarUrl(mode) {
    const t = this.objetivo(mode);

    let url = t.imgUrl;
    if (!url) {
      const urls = this.extraerUrls(t.urlInput + '\n' + t.urlsTexto);
      url = urls[0];
    }

    if (!url) {
      return apprise('Ingresa o pega una URL válida de imagen', { okBtn: 'Entendido' });
    }

    // Nuevo slider: aún no existe el registro, solo se deja lista la URL
    if (mode === 'crear') {
      this.establecerUrl(url, mode);
      t.urlInput = '';
      t.urlsTexto = '';
      return apprise('URL lista. Se guardará al presionar "Crear".', { okBtn: 'Ok' });
    }

    // Editar slider: actualiza el registro de reg_slider
    if (!t.slider_id) {
      return apprise('ID inválido');
    }

    bloquearUI('Actualizando imagen...');
    fetch(this.apphost + '/slider/actualizar-imagen', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ slider_id: t.slider_id, img: url })
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          t.img = url;
          t.imgUrl = '';
          t.imgFile = null;
          t.imgPreview = url;
          t.imagenEliminada = false;
          t.urlInput = '';
          t.urlsTexto = '';
          this.limpiarInputArchivo('editar');
          return this.obtenerSliders().then(() => {
            desbloquearUI();
            apprise('Imagen actualizada correctamente');
          });
        }
        desbloquearUI();
        apprise('Error: ' + (data.error || 'Desconocido'));
      })
      .catch(err => {
        desbloquearUI();
        apprise('Error de conexión');
        console.error(err);
      });
  },

  confirmarEliminarImagen() {
    const urlActual = this.formulario.img || '';

    if (!urlActual) {
      this.formulario.imgPreview = '';
      this.formulario.imagenEliminada = true;
      return;
    }

    const esBunny = urlActual.indexOf('barsi-img.b-cdn.net') !== -1;
    const mensaje = esBunny
      ? '¿Estás seguro de eliminar la imagen del servidor Bunny?'
      : '¿Quitar la imagen de este slider?';

    apprise(mensaje, { confirm: true }, async r => {
      if (!r) return;

      // Imagen externa: solo se quita del formulario, se guarda con "Guardar"
      if (!esBunny) {
        this.formulario.imgPreview = '';
        this.formulario.img = '';
        this.formulario.imgFile = null;
        this.formulario.imgUrl = '';
        this.formulario.imagenEliminada = true;
        return;
      }

      try {
        bloquearUI('Eliminando imagen de Bunny...');

        const res = await fetch(this.apphost + '/slider/eliminar-imagen', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ url_imagen: urlActual })
        });
        const data = await res.json();

        desbloquearUI();

        if (data.ok) {
          this.formulario.imgPreview = '';
          this.formulario.imgFile = null;
          this.formulario.imgUrl = '';
          this.formulario.img = '';
          this.formulario.imagenEliminada = true;
          apprise('Imagen eliminada de Bunny correctamente');
        } else {
          apprise('Error al eliminar: ' + (data.error || 'Desconocido'));
        }
      } catch (e) {
        desbloquearUI();
        apprise('Error de conexión');
        console.error(e);
      }
    });
  }
};