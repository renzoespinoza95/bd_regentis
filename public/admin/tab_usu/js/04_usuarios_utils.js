const usuariosUtilsMixin = {
  methods: {
    generarCodigo() { this.nuevo.cod_usuario = 'ADM' + this.randomDigits(4); },
    generarDni() { this.nuevo.dni = this.randomDniFake(); },
    generarUid() { this.nuevo.google_uid = this.randomUid(); },
    generarSobrenombreEditar() {
      axios.get(this.apphost + '/usuario/randomSobrenombre')
        .then(r => {
          if (r.data && r.data.status === 'ok') {
            this.formulario.sobrenombre = r.data.sobrenombre;
          }
        });
    },
    randomUid() {
      const letras = 'ABCDEFGHIJKLMNPQRSTUVWXYZ';
      return (letras[Math.floor(Math.random()*letras.length)] + this.randomDigits(6) + letras[Math.floor(Math.random()*letras.length)]).toUpperCase();
    },
    randomDigits(n) {
      let s = ''; for(let i=0;i<n;i++){ s += Math.floor(Math.random()*10); } return s;
    },
    randomDniFake() {
      const letras = 'ABCDEFGHIJKLMNPQRSTUVWXYZ';
      return (letras[Math.floor(Math.random()*letras.length)] + this.randomDigits(8) + letras[Math.floor(Math.random()*letras.length)]).toUpperCase();
    }
  }
};