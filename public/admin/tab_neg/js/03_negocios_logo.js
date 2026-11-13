const negociosLogoMethods = {
  abrirModalLogo(n) {
    this.logoNeg = {
      neg_id: n.neg_id,
      nombre: n.nombre,
      preview: n.img_logo || '',
      nueva_preview: '',
      file: null
    };
    $('#modalLogoNeg').modal('show');
  },
  onFileChangeLogo(e) {
    const file = e.target.files[0];
    if (!file) return;

    this.logoNeg.file = file;

    const reader = new FileReader();
    reader.onload = (evt) => {
      this.logoNeg.nueva_preview = evt.target.result;
    };
    reader.readAsDataURL(file);
  },
  guardarLogoBunny() {
    if (!this.logoNeg.file) {
      return apprise('Selecciona una imagen primero');
    }

    const formData = new FormData();
    formData.append('file', this.logoNeg.file);

    const flaskHost = typeof url_flask !== 'undefined' ? url_flask : 'http://localhost:5000';

    this.bloquear('Enviando imagen a Flask...');
    axios.post(`${flaskHost}/tk_guardar_img`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    .then(r => {
      if (r.data.ok) {
        const jobId = r.data.job_id;
        this.iniciarPollingJobParaGuardar(jobId, flaskHost);
      } else {
        $.unblockUI();
        apprise(r.data.error || 'No se pudo procesar la imagen');
      }
    })
    .catch(() => {
      $.unblockUI();
      apprise('Error de conexión con el servicio de Flask');
    });
  },
  iniciarPollingJobParaGuardar(jobId, flaskHost) {
    this.bloquear('Procesando imagen en segundo plano...');
    
    const interval = setInterval(() => {
      axios.get(`${flaskHost}/jobs/result?job_id=${jobId}`)
        .then(r => {
          const data = r.data;
          
          if (data.status === 'finished') {
            clearInterval(interval);
            
            if (data.result && data.result.url) {
              const urlFinal = data.result.url;
              this.logoNeg.nueva_preview = urlFinal;
              
              axios.post(`${this.apphost}/neg/logo/actualizar`, {
                neg_id: this.logoNeg.neg_id,
                img_logo: urlFinal
              })
              .then(() => {
                $.unblockUI();
                $('#modalLogoNeg').modal('hide');
                apprise('¡Logo actualizado correctamente!');
                this.listarNeg();
              })
              .catch(() => {
                $.unblockUI();
                apprise('Error al actualizar el logo en la base de datos');
              });

            } else {
              $.unblockUI();
              apprise('El proceso finalizó pero no devolvió la URL de la imagen.');
            }
          } else if (data.status === 'failed') {
            clearInterval(interval);
            $.unblockUI();
            apprise('El worker de Flask falló al procesar la imagen.');
          }
        })
        .catch(() => {
          clearInterval(interval);
          $.unblockUI();
          apprise('Error al consultar el estado de la tarea en Flask');
        });
    }, 2000);
  },
  eliminarLogoBunny() {
    apprise('¿Desea eliminar la imagen actual?', { confirm: true }, ok => {
      if (!ok) return;
      
      this.bloquear('Eliminando imagen...');
      axios.post(`${this.apphost}/QQvN/neg/logo/defecto`, { neg_id: this.logoNeg.neg_id })
        .then(r => {
          if (r.data.status === 'ok') {
            this.logoNeg.preview = r.data.img_logo;
            this.logoNeg.nueva_preview = '';
            this.logoNeg.file = null;
            apprise('Imagen eliminada / restablecida por defecto');
            this.listarNeg();
          }
        })
        .finally(() => $.unblockUI());
    });
  },
  reiniciarNegocioModal() {
    const negId = this.logoNeg.neg_id;
    const negNombre = this.logoNeg.nombre;
    apprise(`¿Estás seguro de reiniciar el negocio <b>${negNombre}</b>?<br><br>Esto aplicará soft delete a todos sus sliders, páginas web (categorías, subcategorías, ítems) y productos POS.`, { confirm: true }, ok => {
      if (!ok) return;
      this.bloquear('Reiniciando registros...');
      axios.post(`${this.apphost}/neg/reiniciar`, { neg_id: negId })
        .then(r => {
          if (r.data.status === 'ok') {
            apprise('Negocio reiniciado correctamente.');
            $('#modalLogoNeg').modal('hide');
            this.listarNeg();
          } else {
            apprise(r.data.msg || 'Error al reiniciar negocio');
          }
        })
        .catch(() => {
          apprise('Error de conexión al reiniciar negocio');
        })
        .finally(() => {
          $.unblockUI();
        });
    });
  }
};