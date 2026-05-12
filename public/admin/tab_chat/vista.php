<style>
/* ===============================
   🎨 TEMA COLORIDO REGENTIS
================================ */
:root{
  --bg:#f0f4ff;
  --panel:#ffffff;
  --border:#e0e6ff;

  --text:#1a1a2e;
  --muted:#6c7a89;

  --header:linear-gradient(135deg,#667eea,#764ba2);
  --header-text:#ffffff;

  --footer:#ffffff;

  --chat-body:linear-gradient(180deg,#eef2ff,#f9fbff);

  /* Burbujas */
  --bubble-left:linear-gradient(135deg,#FFE082,#FFC107);
  --bubble-right:linear-gradient(135deg,#4facfe,#00f2fe);
  --bubble-border:#ffffff;

  /* Sidebar */
  --contact1:#f8f9ff;
  --contact2:#eef2ff;
  --hover:#e0e7ff;
  --active:#c7d2fe;

  /* Extras */
  --accent:#ff7a18;
  --accent2:#32d2aa;
}

[v-cloak]{display:none}

/* ===============================
   LAYOUT
================================ */
.sidebar {
  background:var(--panel);
  border-right:1px solid var(--border);
  height:520px;
  overflow-y:auto;
}

.chat-box {
  border-left:1px solid var(--border);
  background:var(--panel);
}

.chat-header {
  padding:12px;
  background:var(--header);
  color:var(--header-text);
  font-weight:bold;
}

.chat-body {
  height:420px;
  overflow-y:auto;
  padding:15px;
  background:var(--chat-body);
}

.chat-footer {
  padding:10px;
  background:#fff;
  border-top:1px solid var(--border);
}

/* ===============================
   BURBUJAS (WHATSAPP STYLE)
================================ */
.bubble{
  display:inline-block;
  padding:10px 14px;
  border-radius:18px;
  margin:6px 0;
  max-width:75%;
  word-wrap:break-word;
  font-size:14px;
  box-shadow:0 2px 6px rgba(0,0,0,0.08);
}

/* YO */
.bubble.yo{
  background:var(--bubble-right);
  color:#fff;
  margin-left:auto;
  border-bottom-right-radius:6px;
}

/* OTRO */
.bubble.otro{
  background:var(--bubble-left);
  color:var(--text);
  margin-right:auto;
  border-bottom-left-radius:6px;
}

/* FILAS */
.msg-row{
  display:flex;
  align-items:flex-end;
  gap:8px;
}

.msg-row.left{justify-content:flex-start}
.msg-row.right{justify-content:flex-end}

/* META */
.msg-meta{
  font-size:11px;
  margin-top:3px;
  opacity:0.7;
}

/* JSON */
.json-pre{
  margin:0;
  background:transparent;
  border:none;
  font-size:12px;
}

/* ===============================
   CONTACTOS
================================ */
.contact{
  padding:10px 12px;
  border-bottom:1px solid var(--border);
  cursor:pointer;
  transition:all .2s ease;
}

.contact:hover{
  background:var(--hover);
  transform:scale(1.02);
}

.contact.active{
  background:var(--active);
}

.contact .name{
  font-weight:bold;
  color:var(--text);
}

.contact .sub{
  font-size:11px;
  color:var(--muted);
}

.contact .avatar{
  width:34px;
  height:34px;
  border-radius:50%;
  object-fit:cover;
  float:left;
  margin-right:8px;
  border:2px solid #fff;
  box-shadow:0 0 0 2px #ddd;
}

.contact.tipo-1{
  background:linear-gradient(90deg,#fdfbfb,#ebedee);
}

.contact.tipo-2{
  background:linear-gradient(90deg,#f6f9ff,#eef2ff);
}

/* ===============================
   INPUTS
================================ */
.input-block-level{
  border:1px solid var(--border);
  border-radius:10px;
  padding:10px;
  font-size:14px;
}

.input-block-level:focus{
  border-color:#667eea;
  box-shadow:0 0 0 2px rgba(102,126,234,0.2);
}

/* ===============================
   BOTÓN
================================ */
.btn-primary{
  background:linear-gradient(135deg,#667eea,#764ba2);
  border:none;
  color:#fff;
  border-radius:10px;
  padding:10px;
  font-weight:bold;
}

.btn-primary:hover{
  opacity:0.9;
}

/* ===============================
   BADGES
================================ */
.badge{
  background:#ff7a18;
  color:#fff;
}

.label.label-success{
  background:#32d2aa;
  color:#fff;
}

/* ===============================
   MODAL FULL
================================ */
.modal.modal-full {
  position: fixed;
  top: 2%;
  left: 2% !important;
  margin-left: 0 !important;
  width: 96%;
  height: 96%;
}

.modal.modal-full .modal-body {
  height: calc(100vh - 180px);
  overflow: auto;
}

/* ===============================
   RESPONSIVE
================================ */
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

  #appChat {
    margin-top: 80px;
  }
}

/* ===============================
   AVATAR EN CHAT
================================ */
.msg-row img.avatar{
  width:30px;
  height:30px;
  border-radius:50%;
}

/* ===============================
   SCROLL BONITO
================================ */
.chat-body::-webkit-scrollbar{
  width:6px;
}

.chat-body::-webkit-scrollbar-thumb{
  background:#ccc;
  border-radius:10px;
}

/* SIDEBARS */
.sidebar-container {
  position: relative;
  overflow: hidden;
}

.sidebar-wrapper {
  display: flex;
  width: 200%;
  transition: transform 0.3s ease;
}

.sidebar-page {
  width: 50%;
}

.nav-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(0,0,0,0.6);
  color: white;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  text-align: center;
  line-height: 28px;
  cursor: pointer;
  z-index: 10;
}

.left { left: 5px; }
.right { right: 5px; }

.badge-red {
  position: absolute;
  right: 10px;
  top: 18px;

  width: 22px;        /* 🔥 mismo ancho */
  height: 22px;       /* 🔥 mismo alto */

  background: #e74c3c;
  color: white;

  border-radius: 50%; /* 🔥 círculo perfecto */

  display: flex;      /* 🔥 centra el número */
  align-items: center;
  justify-content: center;

  font-size: 11px;
  font-weight: bold;
}

#appChat {
  display: flex;
  height: 80vh;
  margin-top: 30px;
}

.sidebar-container {
  height: 100%;
}

.sidebar-wrapper {
  height: 100%;
}

.sidebar-page {
  height: 100%;
  overflow-y: auto;
  background-color: white;
}

.chat-row {
  display: flex;
  align-items: center;
  gap: 10px;
  position: relative;
}

.chat-text {
  flex: 1;
}

.last-msg {
  font-size: 12px;
  color: #999;
  margin-top: 2px;
}

.contact:hover {
  background: #05DD8C;
}

</style>
<div id="appChat" class="row-fluid" v-cloak>

  <!-- SIDEBAR -->
  <div class="span3 sidebar-container">

  <!-- FLECHAS -->
  <div class="nav-arrow left" @click="prevSidebar">
    ◀
  </div>

  <div class="nav-arrow right" @click="nextSidebar">
    ▶
  </div>

  <!-- WRAPPER -->
  <div class="sidebar-wrapper" :style="sidebarStyle">

    <!-- 🔹 SIDEBAR 1 (usuarios ORIGINAL) -->
    <div class="sidebar-page">

      <div style="background:orange">
        DEBUG: {{ users.length }} usuarios
      </div>

      <div style="background:lightblue">
        DEBUG filtered: {{ filteredUsers.length }}
      </div>

      <div class="contact" style="background:#527497">
        <div class="name">
          Yo: <span class="label label-success">{{ adminName }}</span>
        </div>
        <div class="muted">usu_id = {{ ADMIN_ID }}</div>
      </div>

      <div style="padding:10px">
        <input v-model="searchUser"
          placeholder="Buscar usuario..."
          class="input-block-level">
      </div>

      <div
        v-for="u in filteredUsers"
        :key="u.id"
        :class="['contact', tipoClass(u.tipoxusu_id), {active: activeUser && activeUser.id===u.id}]"
        @click="setActive(u)"
      >
        <img class="avatar" :src="avatarSrc(u)">
        <div class="name">{{ u.name }}</div>
      </div>

    </div>

    <!-- 🔹 SIDEBAR 2 (CHATS 🔥 NUEVO) -->
    <div class="sidebar-page">

      <div class="contact" style="background:#2c3e50; color:white">
        <b>Chats</b>
      </div>

      <div
        v-for="c in chatsSidebar"
        :key="c.chat_id"
        class="contact chat-row"
        @click="abrirChatSidebar(c)"
      >

        <!-- 🖼️ AVATAR -->
        <img
          class="avatar"
          :src="c.usu1_id == ADMIN_ID ? c.img_usu2 : c.img_usu1"
        >

        <!-- 📄 TEXO -->
        <div class="chat-text">

          <div class="name">
            {{ nombreChat(c) }}
          </div>

          <div class="last-msg">
            {{ c.ultimo_mensaje || 'Sin mensajes' }}
          </div>

        </div>

        <!-- 🔴 BADGE -->
        <span v-if="c.no_leidos > 0" class="badge-red">
          {{ c.no_leidos }}
        </span>

      </div>

    </div>

  </div>

</div>

  <!-- CHAT -->
  <div class="span9 chat-box">

    <div class="chat-header">
      <strong v-if="activeUser">
        De: {{ adminName }} → {{ activeUser.name }}
      </strong>

      <div class="btn-group pull-right" v-if="activeUser">
        <a class="btn dropdown-toggle" data-toggle="dropdown">
          Acciones <span class="caret"></span>
        </a>

        <ul class="dropdown-menu">
          <li>
            <a href="#" @click.prevent="eliminarChat">
              🗑 Eliminar mensajes
            </a>
          </li>
        </ul>
      </div>
    </div>

    <div class="chat-body" ref="chatBody">

      <div v-if="!activeUser" class="muted">
        Selecciona un usuario
      </div>

      <div v-for="m in currentThread" :key="m.id"
           :class="['msg-row', m.isMine ? 'right' : 'left']">

        <img v-if="!m.isMine && m.showAvatar" :src="m.avatar" class="avatar">

        <div class="bubble" :class="m.isMine ? 'yo' : 'otro'">
          {{ m.text }}
          <div class="msg-meta">{{ m.ts }}</div>
        </div>

      </div>

    </div>

    <div class="chat-footer">

      <div class="row-fluid">
        <div class="span10">
          <input type="text"
                 class="input-block-level"
                 v-model="draft"
                 @keyup.enter="send"
                 placeholder="Escribe mensaje...">
        </div>

        <div class="span2">
          <button class="btn btn-primary btn-block" @click="send">
            Enviar
          </button>
        </div>
      </div>

      <div class="muted" v-if="activeUser">
        Enviando como <b>{{ adminName }}</b>
      </div>

    </div>

  </div>

</div>

<script>
const ADMIN = <?= json_encode($administrador_actual) ?>;

new Vue({
  el: '#appChat',

  data: {
    apphost: (typeof apphost !== 'undefined' ? apphost : ''),
    ADMIN: ADMIN,
    ADMIN_ID: <?= intval($administrador_actual['usu_id'] ?? 0) ?>,
    searchUser: '',
    sidebarIndex: 0,
    chatsSidebar: [],
    intervalChats: null,
    users: [],
    activeUser: null,

    threads: {},
    chatIds: {},

    draft: ''
  },

  computed: {

    adminName () {
      return this.ADMIN.nombres_apellidos || this.ADMIN.sobrenombre || 'Yo';
    },

    sidebarStyle() {
      return {
        transform: `translateX(-${this.sidebarIndex * 50}%)`
      }
    },

    filteredUsers () {

        if (!this.users || !this.users.length) return [];

        const txt = (this.searchUser || '').toLowerCase().trim();

        let lista = this.users.filter(u => u.id !== this.ADMIN_ID);

        if (!txt) return lista;

        return lista.filter(u => {

          const texto =
            (u.nombres_apellidos || '') + ' ' +
            (u.name || '') + ' ' +
            (u.provincia || '');

          return texto.toLowerCase().includes(txt);

        });

      },

      currentThread () {
        if (!this.activeUser) return [];
        return this.threads[this.activeUser.id] || [];
      }

  },

  methods: {

    tipoClass (t) {
      return Number(t) === 1 ? 'tipo-1' : 'tipo-2';
    },

    avatarSrc (u) {
      return `https://i.pravatar.cc/40?img=${u.id}`;
    },

    onImgError (e) {
      e.target.src = 'https://i.pravatar.cc/40';
    },

    formatHora (fecha) {
      if (!fecha) return '';
      const d = new Date(fecha.replace(' ', 'T'));
      return d.getHours().toString().padStart(2,'0') + ':' +
             d.getMinutes().toString().padStart(2,'0');
    },    

    nextSidebar() {
      this.sidebarIndex = (this.sidebarIndex + 1) % 2
    },

    prevSidebar() {
      this.sidebarIndex = (this.sidebarIndex - 1 + 2) % 2
    },

    nombreChat(c) {
      return c.usu1_id == this.ADMIN_ID
        ? c.usuario2
        : c.usuario1
    },

    abrirChatSidebar(c) {

      const uid = c.usu1_id == this.ADMIN_ID
        ? c.usu2_id
        : c.usu1_id

      const user = this.users.find(u => u.id == uid)

      if (user) this.setActive(user)
    },

    async fetchChatsSidebar() {

      try {

        const r = await axios.post(`${this.apphost}/WDAA/cant_msg`, {
          usu_id: this.ADMIN_ID
        })

        this.chatsSidebar = r.data.data

      } catch (e) {
        console.error('❌ error chats sidebar', e)
      }

    },

    /* =========================
       AGRUPAR MENSAJES
    ========================= */
    procesarMensajes (lista, usuarioDestino) {

      return lista.map((m, i) => {

        const prev = lista[i - 1];
        const isMine = m.rem_id == this.ADMIN_ID;
        const showAvatar = !prev || prev.rem_id !== m.rem_id;

        return {
          id: m.msg_id,
          text: m.contenido_rem,
          isMine: isMine,
          showAvatar: showAvatar,
          from_name: isMine ? this.adminName : usuarioDestino.name,
          avatar: isMine
            ? this.avatarSrc({id: this.ADMIN_ID})
            : this.avatarSrc(usuarioDestino),
          ts: this.formatHora(m.fecha_creacion)
        };

      });

    },

    /* =========================
       REFRESH CHAT (🔥 NUEVO)
    ========================= */
    async refreshChat () {

      if (!this.activeUser) return;

      const chat_id = this.chatIds[this.activeUser.id];
      if (!chat_id) return;

      try {

        const r = await axios.get(
          `${this.apphost}/msg/listar/${chat_id}/${this.ADMIN_ID}`
        );

        // 🔥 mensajes crudos
        const mensajesRaw = Array.isArray(r.data.mensajes)
          ? r.data.mensajes
          : [];

        // 🔥 ordenar
        const ordenados = mensajesRaw.sort(
          (a, b) => new Date(a.fecha_creacion) - new Date(b.fecha_creacion)
        );

        // 🔥 procesar para UI
        const mensajesProcesados = this.procesarMensajes(
          ordenados,
          this.activeUser
        );

        // 🔥 guardar reactivo
        this.$set(this.threads, this.activeUser.id, mensajesProcesados);

        // 🔥 scroll automático
        this.$nextTick(() => {
          const el = this.$refs.chatBody;
          if (el) el.scrollTop = el.scrollHeight;
        });

      } catch (e) {
        console.error('❌ error refreshChat:', e);
      }

    },

    /* =========================
       LISTAR USUARIOS
    ========================= */
    async fetchUsers () {

      console.log('📡 llamando endpoint...');

      try {

        const r = await axios.get(`${this.apphost}/oxi/usuario/listar`);

        console.log('✅ respuesta API:', r.data);

        const mapped = r.data.map(u => ({

          id: Number(u.usu_id),

          name:
            u.nombres_apellidos ||
            u.sobrenombre ||
            u.name ||
            u.cod_usu ||

            'Sin nombre',

          nombres_apellidos:
            u.nombres_apellidos ||
            u.sobrenombre ||
            u.name ||
            u.cod_usu ||

            '',

          tipoxusu_id: u.tipoxusu_id || 0,
          provincia: u.provincia || ''

        }));

        console.log('🧠 usuarios mapeados:', mapped);

        this.users = mapped;

        console.log('📦 users asignado:', this.users);

      } catch (e) {

        console.error('❌ ERROR fetchUsers:', e);

      }

    },

    /* =========================
       ABRIR CHAT
    ========================= */
    async setActive (u) {

      try {

        // 🔥 activar usuario
        this.activeUser = u;

        // 🔥 abrir / crear chat
        const r = await axios.get(
          `${this.apphost}/chat/open_barsi/${u.id}`
        );

        const chat_id = r.data.chat_id;

        // guardar relación usuario → chat
        this.$set(this.chatIds, u.id, chat_id);

        // 🔥 traer mensajes
        const res = await axios.get(
          `${this.apphost}/msg/listar/${chat_id}/${this.ADMIN_ID}`
        );

        // 🔥 FIX IMPORTANTE (tu error estaba aquí)
        const mensajesRaw = Array.isArray(res.data.mensajes)
          ? res.data.mensajes
          : [];

        // 🔥 ordenar correctamente
        const ordenados = mensajesRaw.sort(
          (a, b) => new Date(a.fecha_creacion) - new Date(b.fecha_creacion)
        );

        // 🔥 procesar para UI
        const mensajes = this.procesarMensajes(ordenados, u);

        // 🔥 guardar en threads reactivo
        this.$set(this.threads, u.id, mensajes);

        // 🔥 scroll automático
        this.$nextTick(() => {
          const el = this.$refs.chatBody;
          if (el) el.scrollTop = el.scrollHeight;
        });

      } catch (e) {

        console.error('❌ error en setActive:', e);

      }

    },

    /* =========================
       ENVIAR MENSAJE
    ========================= */
    async send () {

      if (!this.draft || !this.activeUser) return;

      const text = this.draft;

      const now = new Date();
      const ts = now.getHours().toString().padStart(2,'0') + ':' +
                 now.getMinutes().toString().padStart(2,'0');

      this.pushMsg({
        text,
        isMine: true,
        showAvatar: true,
        from_name: this.adminName,
        avatar: this.avatarSrc({id: this.ADMIN_ID}),
        ts: ts
      });

      this.draft = '';

      await axios.post(`${this.apphost}/msg/enviar`, {
        chat_id: this.chatIds[this.activeUser.id],
        dest_id: this.activeUser.id,
        texto: text
      });

      /* =====================================
         🔥 REFRESH AUTOMÁTICO (TU PEDIDO)
      ===================================== */
      setTimeout(() => {
        this.refreshChat();
      }, 800);

    },

    /* =========================
       ELIMINAR CHAT
    ========================= */
async eliminarChat () {

  if (!this.activeUser) return;

  // 🔥 CONFIRMACIÓN CON TU APPRISE
  apprise("¿Seguro que quieres eliminar toda la conversación?", {
    confirm: true,
    animate: true,
    textYes: "Sí, eliminar",
    textNo: "Cancelar"
  }, async (r) => {

    if (!r) return;

    try {

      await axios.post(`${this.apphost}/msg/eliminar_chat`, {
        chat_id: this.chatIds[this.activeUser.id]
      });

      this.$set(this.threads, this.activeUser.id, []);
      this.activeUser = null;

      // 🔥 MENSAJE ÉXITO
      apprise("🗑️ Chat eliminado correctamente", {
        animate: true
      });

    } catch (e) {

      // 🔥 ERROR
      apprise("❌ Error al eliminar el chat", {
        animate: true
      });

    }

  });

},

    /* =========================
       PUSH UI
    ========================= */
    pushMsg (m) {

      if (!this.threads[this.activeUser.id]) {
        this.$set(this.threads, this.activeUser.id, []);
      }

      this.threads[this.activeUser.id].push(m);

      this.$nextTick(() => {
        const el = this.$refs.chatBody;
        if (el) el.scrollTop = el.scrollHeight;
      });

    }

  },

  async mounted () {

    console.log('🔥 mounted iniciado');

    await this.fetchUsers()
    await this.fetchChatsSidebar()

    this.intervalChats = setInterval(() => {
      this.fetchChatsSidebar()
    }, 60000) // 1 minuto

    console.log('🔥 después de fetchUsers:', this.users);

  },
  watch: {
    searchUser (val) {
      console.log('🔍 escribiendo:', val);
    }
  },
    beforeDestroy () {
    clearInterval(this.intervalChats)
  }

});
</script>