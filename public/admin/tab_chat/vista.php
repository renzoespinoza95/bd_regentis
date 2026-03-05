<style>
  /* Grayscale theme */
  :root{
    --bg:#f5f5f5;
    --panel:#ffffff;
    --border:#d8d8d8;
    --text:#1f1f1f;
    --muted:#6b6b6b;

    --header:#2f2f2f;
    --header-text:#f1f1f1;
    --footer:#2f2f2f;

    --chat-body:#eeeeee;

    --bubble-left:#e9e9e9;   /* Barsi */
    --bubble-right:#d3d3d3;  /* Usuario */
    --bubble-border:#ffffff;

    --contact1:#f7f7f7;
    --contact2:#f0f0f0;
    --hover:#e6e6e6;
    --active:#dcdcdc;
  }

  [v-cloak]{display:none}

  /* Layout contenedores */
  .sidebar {
    background:var(--panel);
    border:1px solid var(--border);
    height:520px;
    overflow-y:auto;
  }
  .chat-box {
    border:1px solid var(--border);
    background:var(--panel);
  }
  .chat-header {
    padding:10px 12px;
    border-bottom:1px solid var(--border);
    background:var(--header);
    color:var(--header-text);
  }
  .chat-body {
    height:420px;
    overflow-y:auto;
    padding:12px;
    background:var(--chat-body);
  }
  .chat-footer {
    padding:8px;
    border-top:1px solid var(--border);
    background:var(--footer);
    color:var(--header-text);
  }

  /* Burbujas */
  .bubble{
    display:inline-block;
    padding:8px 12px;
    border-radius:14px;
    margin:6px 0;
    max-width:80%;
    word-wrap:break-word;
    white-space:pre-wrap;
  }
  .bubble.barsi{
    background:var(--bubble-left);
    border:1px solid var(--bubble-border);
    color:var(--text);
    margin-right:auto;
  }
  .bubble.user{
    background:var(--bubble-right);
    border:1px solid var(--bubble-border);
    color:var(--text);
    margin-left:auto;
  }

  .msg-row{display:flex; gap:8px}
  .msg-row.left{justify-content:flex-start}
  .msg-row.right{justify-content:flex-end}
  .msg-meta{font-size:11px; color:var(--muted); margin-top:2px}
  .json-pre{margin:0; background:transparent; border:none; font-size:12px; line-height:1.3; color:var(--text)}

  /* Contactos (sidebar) */
  .contact{padding:10px 12px; border-bottom:1px solid var(--border); position:relative; background:var(--panel)}
  .contact.active, .contact:hover{background:var(--hover)}
  .contact .name{font-weight:bold; color:var(--text)}
  .muted{color:var(--muted)}
  .contact .avatar{
    width:32px; height:32px; border-radius:50%; object-fit:cover;
    border:1px solid var(--border); float:left; margin-right:8px;
  }
  .contact .info{overflow:hidden}
  .contact .sub{font-size:11px; color:var(--muted)}

  /* Tonos por tipo (ambos en grises) */
  .contact.tipo-1{ background:var(--contact1) }
  .contact.tipo-2{ background:var(--contact2) }
  .contact.tipo-1.active, .contact.tipo-1:hover{ background:var(--active) }
  .contact.tipo-2.active, .contact.tipo-2:hover{ background:var(--active) }

  /* Overwrite del primer bloque con inline style (Destino) */
  .sidebar .contact:first-child{ background:var(--contact1) !important; }

  /* Inputs y botones (grises) */
  .input-block-level{ border:1px solid var(--border); background:#fafafa; color:var(--text) }
  .input-block-level::placeholder{ color:#9a9a9a }
  .btn-primary{
    background:#555; color:#fff; border:1px solid #444;
    text-shadow:none; box-shadow:none;
  }
  .btn-primary:hover{ background:#4a4a4a; border-color:#3f3f3f }

  /* Badges / labels a gris */
  .badge{ background:#bdbdbd; color:#222; text-shadow:none }
  .label.label-success{ background:#bdbdbd; color:#222; text-shadow:none; border:0 }

  /* Fullscreen para Bootstrap 2.3.2 */
.modal.modal-full {
  position: fixed;      /* ya lo es en BS2, reforzamos */
  top: 2%;
  left: 2% !important;  /* evitamos el centrar por margin-left negativo */
  margin-left: 0 !important;
  width: 96%;
  max-width: 96%;
  height: 96%;
  z-index: 1050;
}

.modal.modal-full .modal-body {
  max-height: none;                 /* quitar límite de 400px en BS2 */
  height: calc(100vh - 180px);      /* header + footer aprox. */
  overflow: auto;
}

/* Ajustes responsivos */
@media (max-width: 980px) {
  .modal.modal-full {
    top: 1%;
    left: 1% !important;
    width: 98%;
    height: 98%;
  }
  .modal.modal-full .modal-body {
    height: calc(100vh - 160px);
  }
}
</style>

<div id="appChat" class="row-fluid" v-cloak>
  <!-- Lado izquierdo: contactos -->
  <div class="span3 sidebar">
    <div class="contact" style="background:#527497">
      <div class="name">Destino: <span class="label label-success">Barsi</span></div>
      <div class="muted">usu_id = 1</div>
    </div>

    <div
      v-for="u in sidebarUsers"
      :key="u.id"
      :class="['contact', tipoClass(u.tipoxusu_id), {active: activeUser && activeUser.id===u.id}]"
      @click="setActive(u)"
    >
      <img class="avatar" :src="avatarSrc(u)" @error="onImgError">
      <div class="info">
        <div class="name">
          <i class="icon-user"></i> {{ u.name }}
          <span class="badge pull-right" v-if="threads[u.id] && threads[u.id].length">
            {{ threads[u.id].length }}
          </span>
        </div>
        <div class="sub">
          <span class="muted">usu_id = {{ u.id }}</span>
          <span v-if="u.provincia" class="muted"> · {{ u.provincia }}</span>
        </div>
      </div>
      <div style="clear:both"></div>
    </div>


  </div>

  <!-- Lado derecho: chat -->
  <div class="span9 chat-box">
    <div class="chat-header">
      <strong v-if="activeUser">De: Barsi → {{ activeUser.name }}</strong>
      <span v-else class="muted">Elige un usuario en la izquierda</span>

      <!-- Dropdown Acciones -->
      <!-- Dropdown Acciones -->
      <div class="btn-group pull-right" v-if="activeUser">
        <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
          Acciones <span class="caret"></span>
        </a>
        <ul class="dropdown-menu">
          <li><a href="#" @click.prevent="menuEliminar">Eliminar mensajes</a></li>
        </ul>
      </div>

    </div>


    <div class="chat-body" ref="chatBody">
      <div v-if="!activeUser" class="muted">Selecciona Cherry o Steven para iniciar.</div>

      <template v-if="activeUser">
          <div class="msg-row"
             v-for="m in currentThread"
             :key="m.id"
             :class="m.role==='barsi' ? 'left' : 'right'">
              <div class="bubble" :class="m.role">
                <div v-if="!m.isJSON">{{ m.text }}</div>
                <pre v-else class="json-pre">{{ m.text }}</pre>
                <div class="msg-meta">{{ m.from_name }} · {{ m.ts }}</div>
              </div>
          </div>
      </template>
    </div>

    <div class="chat-footer">
      <div class="row-fluid">
        <div class="span10">
          <input type="text" class="input-block-level"
                 placeholder="Escribe el mensaje (Enter para enviar)…"
                 v-model="draft"
                 :disabled="!activeUser || activeUser.id===DEST.id || sending"
                 @keyup.enter="send"/>
        </div>
        <div class="span2">
          <button class="btn btn-primary btn-block"
                  :disabled="!activeUser || activeUser.id===DEST.id || !draft.trim() || sending"
                  @click="send">
            <i class="icon-envelope icon-white"></i> Enviar
          </button>
        </div>
      </div>
      <div class="muted" v-if="activeUser">Enviando como <b>Barsi</b> (usu_id={{ DEST.id }}) → <b>{{ activeUser.name }}</b> (usu_id={{ activeUser.id }})
      </div>

    </div>
  </div>



</div>

<script>
/* global Vue, axios, apphost */
new Vue({
  el: '#appChat',
  data: {
    // apphost debe apuntar a tu servidor PHP (Flight) — ej: 'http://127.0.0.1'
    apphost: (typeof apphost !== 'undefined' ? apphost : ''),
    defaultAvatar: (typeof apphost !== 'undefined' && apphost ? apphost : '') + '/assets/avatar.jpg',
    DEST: { id: 1, name: 'Barsi' },
    users: [
      { id: 1, name: 'Barsi' },
      { id: 6, name: 'Cherry' },
      { id: 2, name: 'Steven' }
    ],
    activeUser: null,
    users: [],  
    threads: {},        // { '6': [msgs...], '2': [msgs...] }
    chatIds: {},        // { '6': chat_id, '2': chat_id }
    draft: '',
    sending: false,
    seq: 0,
    // 🔎 tablas de los modales
    pastimeRows: [],
    pastimeColumns: [],
    qdrantRows: [],
    qdrantColumns: [],
    searchQuery: '',
    searching: false,
    searchResults: [],
    searchColumns: []
  },
  computed: {
    sidebarUsers () { return this.users; },
    currentThread () {
      if (!this.activeUser) return [];
      const key = String(this.activeUser.id);
      return this.threads[key] || [];
    }
  },
  methods: {
    tipoClass (tipo) {
      const t = Number(tipo || 0);
      return t === 1 ? 'tipo-1' : (t === 2 ? 'tipo-2' : '');
    },
    /* ---------- Helpers UI ---------- */
    nowHHMM () {
      const d = new Date();
      const pad = n => (n<10?'0':'') + n;
      return pad(d.getHours()) + ':' + pad(d.getMinutes());
    },
    fmtHHMM (ts) {
      // ts: 'YYYY-MM-DD HH:MM:SS' o ISO
      const d = new Date((ts || '').replace(' ', 'T'));
      const pad = n => (n<10?'0':'') + n;
      return isNaN(d.getTime()) ? '' : (pad(d.getHours()) + ':' + pad(d.getMinutes()));
    },
    parseMaybeJsonForText (s) {
      // Si hay mensajes antiguos con JSON, intenta mostrar solo next_question
      try {
        const o = JSON.parse(s);
        if (o && typeof o === 'object' && o.next_question) return o.next_question;
      } catch(e) {}
      return s;
    },

    avatarSrc (u) {
      return (u && u.avatar) ? u.avatar : this.defaultAvatar;
    },

    // NUEVO: fallback si la imagen falla
    onImgError (ev) {
      if (ev && ev.target) ev.target.src = this.defaultAvatar;
    },

    // AJUSTE: incluir provincia y avatar al mapear
    async fetchUsers () {
      try {
        const r = await axios.get(`${this.apphost}/usuario/listar`, { timeout: 15000 });
        const arr = Array.isArray(r.data) ? r.data : [];
        const mapped = arr
          .filter(x => Number(x.usu_id) !== Number(this.DEST.id))
          .map(x => ({
            id: Number(x.usu_id),
            name: (x.sobrenombre && x.sobrenombre.trim())
                    ? x.sobrenombre.trim()
                    : (x.cod_usu || `Usuario ${x.usu_id}`),
            tipoxusu_id: Number(x.tipoxusu_id || 0),
            avatar: x.avatar_mini || null,
            provincia: x.provincia || ''
          }));
        this.users = mapped;
      } catch (e) {
        console.error('Error al listar usuarios:', e);
        this.users = [
          { id: 6, name: 'Cherry', tipoxusu_id: 1, avatar: null, provincia: '' },
          { id: 2, name: 'Steven', tipoxusu_id: 2, avatar: null, provincia: '' }
        ];
      }
    },
    pushMsg (obj) {
      const key = String(this.activeUser.id);
      obj.id = (++this.seq) + '_' + key;
      if (!this.threads[key]) this.$set(this.threads, key, []);
      this.threads[key].push(obj);
      this.$nextTick(this.scrollToBottom);
    },
    roleFromRem (remId) {
      return Number(remId) === Number(this.DEST.id) ? 'barsi' : 'user';
    },
    scrollToBottom () {
      const el = this.$refs.chatBody;
      if (el) el.scrollTop = el.scrollHeight;
    },

    /* ---------- Cargar historial al seleccionar usuario ---------- */
    async setActive (u) {
      this.activeUser = u;
      const key = String(u.id);
      if (!this.threads[key]) this.$set(this.threads, key, []);

      // No cargamos historial si seleccionan a Barsi
      if (u.id === this.DEST.id) return;

      // BlockUI mientras carga
      let usedElementBlock = false;
      try {
        if (window.$) {
          const $box = $('.chat-box');
          if ($box.length && typeof $box.block === 'function') {
            usedElementBlock = true;
            $box.block({
              message: '<h4 style="margin:0;padding:0">Cargando chat…</h4>',
              css: { border: 'none', padding: '12px', backgroundColor: '#000', color: '#fff', opacity: .7, borderRadius: '8px' }
            });
          } else if (typeof $.blockUI === 'function') {
            $.blockUI({
              message: '<h4 style="margin:0;padding:0">Cargando chat…</h4>',
              css: { border: 'none', padding: '12px', backgroundColor: '#000', color: '#fff', opacity: .7, borderRadius: '8px' }
            });
          }
        }
      } catch (e) {}

      try {
        // 1) Asegurar/obtener chat_id con Barsi
        let cid = this.chatIds[key];
        if (!cid) {
          const rOpen = await axios.get(`${this.apphost}/chat/open_barsi/${u.id}`, { timeout: 15000 });
          cid = rOpen.data && (rOpen.data.chat_id || rOpen.data.cid || rOpen.data.chatId);
          if (!cid) throw new Error('No se obtuvo chat_id');
          this.$set(this.chatIds, key, cid);
        }

        // ✅ 2) Listar mensajes pasando el UID DEL LECTOR (Barsi)
        const viewerId = this.DEST.id; // Barsi está leyendo en este panel
        const rList = await axios.get(
          `${this.apphost}/msg/listar/${cid}/${viewerId}`,   // ← antes: .../${u.id}
          { timeout: 20000 }
        );
        const rows = Array.isArray(rList.data) ? rList.data : [];


        // 3) Mapear a nuestro formato (cronológico)
        const msgs = rows.slice().reverse().map(row => {
          const role  = this.roleFromRem(row.rem_id); // 'barsi' | 'user'
          const isB   = (role === 'barsi');
          const txt   = this.parseMaybeJsonForText(String(row.contenido_rem || ''));
          return {
            id: row.msg_id,
            from_id: Number(row.rem_id),
            from_name: isB ? this.DEST.name : u.name,
            to_id: Number(row.dest_id),
            to_name: isB ? u.name : this.DEST.name,
            text: txt,
            role,              // <— AQUÍ
            isJSON: false,
            ts: this.fmtHHMM(row.fecha_creacion)
          };
        });



        this.$set(this.threads, key, msgs);
        this.$nextTick(this.scrollToBottom);

      } catch (err) {
        const msg = (err && err.message) ? err.message : 'Error al cargar chat';
        this.$set(this.threads, key, []);
        this.pushMsg({
          from_id: this.DEST.id,
          from_name: this.DEST.name,
          to_id: u.id,
          to_name: u.name,
          text: `💥 ${msg}`,
          role: 'bot',
          isJSON: false,
          ts: this.nowHHMM()
        });
      } finally {
        try {
          if (window.$) {
            if (usedElementBlock && $('.chat-box').length && typeof $('.chat-box').unblock === 'function') {
              $('.chat-box').unblock();
            } else if (typeof $.unblockUI === 'function') {
              $.unblockUI();
            }
          }
        } catch (e) {}
      }
    },

// ====== NUEVO: Acciones del menú ======
    menuEliminar () {
      if (!this.activeUser || this.activeUser.id === this.DEST.id) return;
      const key = String(this.activeUser.id);
      const cid = this.chatIds[key];
      if (!cid) return;

      const doDelete = async () => {
        // BlockUI
        let usedElementBlock = false;
        try {
          if (window.$) {
            const $box = $('.chat-box');
            if ($box.length && typeof $box.block === 'function') {
              usedElementBlock = true;
              $box.block({
                message: '<h4 style="margin:0;padding:0">Eliminando…</h4>',
                css: { border: 'none', padding: '12px', backgroundColor: '#000', color: '#fff', opacity: .7, borderRadius: '8px' }
              });
            } else if (typeof $.blockUI === 'function') {
              $.blockUI({
                message: '<h4 style="margin:0;padding:0">Eliminando…</h4>',
                css: { border: 'none', padding: '12px', backgroundColor: '#000', color: '#fff', opacity: .7, borderRadius: '8px' }
              });
            }
          }
        } catch (e) {}

        try {
          const r = await axios.post(`${this.apphost}/msg/eliminar_chat`, { chat_id: cid }, { timeout: 20000 });
          // Limpiar UI
          this.$set(this.threads, key, []);
          // Feedback
          this.pushMsg({
            from_id: this.DEST.id,
            from_name: this.DEST.name,
            to_id: this.activeUser.id,
            to_name: this.activeUser.name,
            text: `Se eliminaron ${r.data && r.data.deleted ? r.data.deleted : 0} mensajes.`,
            role: 'barsi',
            isJSON: false,
            ts: this.nowHHMM()
          });
        } catch (err) {
          this.pushMsg({
            from_id: this.DEST.id,
            from_name: this.DEST.name,
            to_id: this.activeUser.id,
            to_name: this.activeUser.name,
            text: `💥 Error al eliminar mensajes.`,
            role: 'barsi',
            isJSON: false,
            ts: this.nowHHMM()
          });
        } finally {
          try {
            if (window.$) {
              if (usedElementBlock && $('.chat-box').length && typeof $('.chat-box').unblock === 'function') {
                $('.chat-box').unblock();
              } else if (typeof $.unblockUI === 'function') {
                $.unblockUI();
              }
            }
          } catch (e) {}
        }
      };

      // Confirmación con Apprise / Bootbox / native
      if (window.apprise) {
        apprise('¿Eliminar TODOS los mensajes de este chat?', { confirm: true }, (r) => {
          if (r) doDelete();
        });
      } else if (window.bootbox) {
        bootbox.confirm('¿Eliminar TODOS los mensajes de este chat?', (ok) => ok && doDelete());
      } else {
        if (confirm('¿Eliminar TODOS los mensajes de este chat?')) doDelete();
      }
    },

    async menuPastime () {
      if (!this.activeUser || this.activeUser.id === this.DEST.id) return;
      const uid = this.activeUser.id;

      // BlockUI
      let usedElementBlock = false;
      try {
        if (window.$) {
          if ($('.chat-box').length && typeof $('.chat-box').block === 'function') {
            usedElementBlock = true;
            $('.chat-box').block({
              message: '<h4 style="margin:0;padding:0">Cargando pastime_offer…</h4>',
              css: { border: 'none', padding: '12px', backgroundColor: '#000', color: '#fff', opacity: .7, borderRadius: '8px' }
            });
          } else if (typeof $.blockUI === 'function') {
            $.blockUI({
              message: '<h4 style="margin:0;padding:0">Cargando pastime_offer…</h4>',
              css: { border: 'none', padding: '12px', backgroundColor: '#000', color: '#fff', opacity: .7, borderRadius: '8px' }
            });
          }
        }
      } catch (e) {}

      try {
        const r = await axios.get(`${this.apphost}/pastime/listar/${uid}`, { timeout: 25000 });
        const rows = Array.isArray(r.data) ? r.data : (r.data.rows || []);
        this.pastimeRows = rows;
        this.pastimeColumns = rows.length ? Object.keys(rows[0]) : [];
        $('#modalPastime').modal('show');
      } catch (e) {
        alert('Error cargando pastime_offer');
      } finally {
        try {
          if (window.$) {
            if (usedElementBlock && $('.chat-box').length && typeof $('.chat-box').unblock === 'function') {
              $('.chat-box').unblock();
            } else if (typeof $.unblockUI === 'function') {
              $.unblockUI();
            }
          }
        } catch (e) {}
      }
    },
    /* ---------- Enviar (sigue yendo directo a Flask) ---------- */
    async send () {
  if (!this.activeUser || !this.draft.trim() || this.sending) return;
  if (this.activeUser.id === this.DEST.id) return; // no enviar a Barsi→Barsi

  const text = this.draft.trim();

  // Pintamos inmediato la burbuja como Barsi (izquierda)
  this.pushMsg({
    from_id: this.DEST.id,
    from_name: this.DEST.name,
    to_id: this.activeUser.id,
    to_name: this.activeUser.name,
    text,
    role: 'barsi',       // 👈 ahora envia Barsi → usuario
    isJSON: false,
    ts: this.nowHHMM()
  });

  this.draft = '';
  this.sending = true;

  // BlockUI opcional
  let usedElementBlock = false;
  try {
    if (window.$) {
      const $box = $('.chat-box');
      if ($box.length && typeof $box.block === 'function') {
        usedElementBlock = true;
        $box.block({
          message: '<h4 style="margin:0;padding:0">Enviando…</h4>',
          css: { border: 'none', padding: '12px', backgroundColor: '#000', color: '#fff', opacity: .7, borderRadius: '8px' }
        });
      } else if (typeof $.blockUI === 'function') {
        $.blockUI({
          message: '<h4 style="margin:0;padding:0">Enviando…</h4>',
          css: { border: 'none', padding: '12px', backgroundColor: '#000', color: '#fff', opacity: .7, borderRadius: '8px' }
        });
      }
    }
  } catch (e) {}

  try {
    // ⚡️ NUEVO: usar /chat/crear
    const body = {
      // puedes dejarlos así; el backend ya los ordena internamente
      usu1_id: this.DEST.id,
      usu2_id: this.activeUser.id,

      // ✅ Barsi es el remitente, el usuario es el destino
      rem_id:  this.DEST.id,        // antes: this.activeUser.id  ❌
      dest_id: this.activeUser.id,  // antes: this.DEST.id        ❌

      contenido_rem: text
    };



    // Nota: el backend ordena u1/u2 internamente; no te preocupes por el orden
    const r = await axios.post(`${this.apphost}/chat/crear`, body, { timeout: 20000 });

    // Si quieres, puedes leer r.data.message.msg_id / chat_id aquí
    // console.log('Enviado:', r.data);

  } catch (err) {
    const msg = (err && err.message) ? err.message : 'Error al enviar';
    this.pushMsg({
      from_id: this.DEST.id,
      from_name: this.DEST.name,
      to_id: this.activeUser.id,
      to_name: this.activeUser.name,
      text: `💥 ${msg}`,
      role: 'barsi',
      isJSON: false,
      ts: this.nowHHMM()
    });
  } finally {
    this.sending = false;
    try {
      if (window.$) {
        if (usedElementBlock && $('.chat-box').length && typeof $('.chat-box').unblock === 'function') {
          $('.chat-box').unblock();
        } else if (typeof $.unblockUI === 'function') {
          $.unblockUI();
        }
      }
    } catch (e) {}
  }
}


  
//+++ FIN METHODS +++
  },
  async mounted () {
    // 1) Cargar usuarios desde tu API
    await this.fetchUsers();

    // 2) Seleccionar por defecto el primero de la lista (si existe)
    if (this.users.length) {
      await this.setActive(this.users[0]);
    }
  }
});
</script>
