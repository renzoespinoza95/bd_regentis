let currentNegIdForProp = 0;
let foundUsuarioData = null;
let currentUserId = 0;

const propietariosMethods = {
  abrirModalPropietario(n) {
    currentNegIdForProp = parseInt(n.neg_id, 10);
    foundUsuarioData = null;
    currentUserId = 0;

    // Rellenar cabecera del negocio directamente con jQuery
    $('#modal_neg_nombre').text(n.nombre || '');
    $('#modal_neg_id').text(n.neg_id ? `(#${n.neg_id})` : '');
    $('#modal_neg_celular').text(n.celular_informes ? ` | Celular: ${n.celular_informes}` : '');

    // Limpiar campos de búsqueda
    $('#modal_cod_usu').val('');
    $('#modal_card_usuario').hide();
    $('#btn_asignar_usuario_actual').hide();

    this.listarPropietariosModal();
    $('#modalPropietario').modal('show');
  },

  listarPropietariosModal() {
    if (!currentNegIdForProp) return;
    this.bloquear('Cargando propietarios...');
    axios.post(`${this.apphost}/QQvN/neg/propietarios`, { neg_id: currentNegIdForProp })
      .then(r => {
        const rows = r.data.rows || [];
        currentUserId = parseInt(r.data.current_usu_id || 0, 10);
        
        // Verificar si el usuario actual está en la lista de propietarios
        const userInList = rows.some(p => parseInt(p.usu_id, 10) === currentUserId);
        if (currentUserId > 0 && !userInList) {
          $('#btn_asignar_usuario_actual').show();
        } else {
          $('#btn_asignar_usuario_actual').hide();
        }

        let html = '';
        if (rows.length === 0) {
          html = '<div class="alert">No hay propietarios asignados</div>';
        } else {
          rows.forEach(p => {
            html += `
              <div class="well" style="margin-top:10px;">
                <div style="display:flex; gap:12px;">
                  <img src="${p.img_perfil || 'https://barsi-img.b-cdn.net/recursos/sg3f.png'}" style="width:60px; height:60px; border-radius:14px; object-fit:cover;">
                  <div style="flex:1; color:#fff !important;">
                    <div style="font-weight:bold; font-size:14px; color:#fff !important;">${p.nombres_apellidos || ''}</div>
                    <div style="color:#fff !important;">@${p.sobrenombre || ''}</div>
                    <div style="color:#fff !important;">${p.cod_usu || ''}</div>
                    <div style="color:#fff !important;">${p.celular || ''}</div>
                    <div style="color:#fff !important;">${p.email || ''}</div>
                  </div>
                  <div style="display:flex; flex-direction:column; gap:6px; align-items:flex-end;">
                    <button class="btn btn-danger btn-mini btn-eliminar-prop" data-negxusuid="${p.negxusu_id}" type="button">Eliminar asignación</button>
                    <button class="btn btn-info btn-mini btn-iniciar-sesion-prop" data-usuid="${p.usu_id}" type="button">Iniciar sesión</button>
                  </div>
                </div>
              </div>
            `;
          });
        }
        $('#modal_lista_propietarios').html(html);
      })
      .finally(() => $.unblockUI());
  }
};

// Eventos independientes manejados con jQuery para total compatibilidad con el modal
$(document).ready(function() {
  // Buscar usuario por código
  $(document).on('click', '#btn_modal_buscar_usu', function() {
    const cod_usu = $('#modal_cod_usu').val().trim();
    if (!cod_usu) {
      apprise('Ingrese código de usuario');
      return;
    }
    $.blockUI({ message: 'Buscando usuario...' });
    axios.post((typeof apphost !== 'undefined' ? apphost : '') + '/QQvN/buscarUsuarioCodUsu', { cod_usu: cod_usu })
      .then(r => {
        if (r.data.status === 'ok') {
          foundUsuarioData = r.data.usuario;
          
          if (r.data.tiene_negocio) {
            apprise(`Esta cuenta ya administra un negocio (${r.data.negocio_nombre})`);
            $('#modal_card_usuario').hide();
            foundUsuarioData = null;
            return;
          }

          $('#modal_usu_img').attr('src', foundUsuarioData.img_perfil || 'https://barsi-img.b-cdn.net/recursos/sg3f.png');
          $('#modal_usu_nombre').text(foundUsuarioData.nombres_apellidos || '');
          $('#modal_usu_sobrenombre').text('@' + (foundUsuarioData.sobrenombre || ''));
          $('#modal_usu_cod').text(foundUsuarioData.cod_usu || '');
          $('#modal_usu_cel').text(foundUsuarioData.celular || '');
          $('#modal_usu_email').text(foundUsuarioData.email || '');
          $('#modal_card_usuario').show();
        } else {
          apprise(r.data.msg || 'Usuario no encontrado');
          $('#modal_card_usuario').hide();
          foundUsuarioData = null;
        }
      })
      .catch(() => {
        apprise('Usuario no encontrado');
        $('#modal_card_usuario').hide();
        foundUsuarioData = null;
      })
      .finally(() => $.unblockUI());
  });

  // Asignar propietario encontrado
  $(document).on('click', '#btn_modal_asignar_usu', function() {
    if (!foundUsuarioData) {
      apprise('Busca un usuario primero');
      return;
    }
    $.blockUI({ message: 'Asignando propietario...' });
    axios.post((typeof apphost !== 'undefined' ? apphost : '') + '/negxusu/asignar', {
      neg_id: currentNegIdForProp,
      usu_id: foundUsuarioData.usu_id
    })
    .then(r => {
      if (r.data.status === 'ok') {
        apprise('Propietario asignado');
        $('#modal_cod_usu').val('');
        $('#modal_card_usuario').hide();
        foundUsuarioData = null;
        
        // Recargar lista dentro del modal
        if (typeof appNeg !== 'undefined' && appNeg.listarPropietariosModal) {
          appNeg.listarPropietariosModal();
        } else {
          $('#btn_modal_buscar_usu').trigger('click');
        }
      } else {
        apprise(r.data.msg || 'Error al asignar');
      }
    })
    .finally(() => $.unblockUI());
  });

  // Asignar usuario actual consumiendo /QQvN/verificarNegocioAdmin antes del mensaje de confirmación
  $(document).on('click', '#btn_asignar_usuario_actual', function() {
    if (!currentUserId || !currentNegIdForProp) return;

    $.blockUI({ message: 'Verificando cuenta...' });
    axios.post((typeof apphost !== 'undefined' ? apphost : '') + '/QQvN/verificarNegocioAdmin')
      .then(r => {
        $.unblockUI();
        if (r.data.status === 'ok') {
          if (r.data.tiene_negocio) {
            apprise(`Esta cuenta ya administra un negocio (${r.data.negocio_nombre})`);
            return;
          }

          apprise('¿Asignar usuario actual como propietario?', { confirm: true }, ok => {
            if (!ok) return;
            $.blockUI({ message: 'Asignando usuario actual...' });
            axios.post((typeof apphost !== 'undefined' ? apphost : '') + '/negxusu/asignar', {
              neg_id: currentNegIdForProp,
              usu_id: currentUserId
            })
            .then(resp => {
              if (resp.data.status === 'ok') {
                apprise('Usuario asignado correctamente');
                if (typeof appNeg !== 'undefined' && appNeg.listarPropietariosModal) {
                  appNeg.listarPropietariosModal();
                }
              } else {
                apprise(resp.data.msg || 'Error al asignar usuario');
              }
            })
            .catch(() => {
              apprise('Error al conectar con el servidor');
            })
            .finally(() => $.unblockUI());
          });
        } else {
          apprise('Error al verificar negocio de la cuenta');
        }
      })
      .catch(() => {
        $.unblockUI();
        apprise('Error al conectar con el servidor');
      });
  });

  // Eliminar asignación de propietario actual con apprise de confirmación
  $(document).on('click', '.btn-eliminar-prop', function() {
    const btn = $(this);
    const negxusu_id = btn.data('negxusuid');
    apprise('¿Eliminar asignación?', { confirm: true }, ok => {
      if (!ok) return;
      $.blockUI({ message: 'Eliminando asignación...' });
      axios.post((typeof apphost !== 'undefined' ? apphost : '') + '/QQvN/neg/propietario/eliminar', { negxusu_id: negxusu_id })
        .then(() => {
          apprise('Asignación eliminada');
          btn.closest('.well').fadeOut(300, function() { 
            $(this).remove(); 
            // Recargar lista para actualizar estado del botón "Asignar usuario actual"
            if (typeof appNeg !== 'undefined' && appNeg.listarPropietariosModal) {
              appNeg.listarPropietariosModal();
            }
          });
        })
        .finally(() => $.unblockUI());
    });
  });

  // Iniciar sesión como este usuario (propietario)
  $(document).on('click', '.btn-iniciar-sesion-prop', function() {
    const usu_id = $(this).data('usuid');
    apprise('¿Iniciar sesión como este usuario?', { confirm: true }, async (r) => {
      if(!r) return;
      
      const host = (typeof apphost !== 'undefined' ? apphost : '');
      
      if (typeof $.blockUI === 'function') {
        $.blockUI({ message: 'Iniciando sesión...' });
      }
      
      try {
        const res = await axios.post(host + '/YfrW/loginNeg', { usu_id: usu_id });
        
        if (typeof $.unblockUI === 'function') {
          $.unblockUI();
        }
        
        if (res.data.res === 'ok'){
          window.location.href = host + '/admin/dash';
        } else {
          apprise(res.data.msg || 'No se pudo iniciar sesión');
        }
      } catch (error) {
        if (typeof $.unblockUI === 'function') {
          $.unblockUI();
        }
        apprise('Error al conectar con el servidor');
      }
    });
  });
});