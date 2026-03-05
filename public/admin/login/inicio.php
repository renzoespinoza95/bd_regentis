<!DOCTYPE html>
<html lang="es" ng-app="loginApp">
<head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Login</title>
      <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css">
      <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
      <base href="<?php echo $mBase ?>">
      <link rel="stylesheet" type="text/css" href="css/login.css">
      <?php
      perso::jquery2();
      perso::vuejs2();
      perso::vue_select();
      perso::apprise();
      perso::favicon();
      perso::global_env($apphost, $varhost);
      ?>
</head>
<body>

  <form class="login" id="appLogin">

  <!-- LOGIN NORMAL -->
  <fieldset v-if="paso==1">
    <legend class="legend">Ingreso</legend>

    <div class="input">
      <input type="text" placeholder="Usuario" v-model="usuario">
      <span><i class="fa fa-envelope-o"></i></span>
    </div>

    <div class="input">
      <input type="password" placeholder="Password" v-model="clavel">
      <span><i class="fa fa-lock"></i></span>
    </div>

    <button type="button" class="submit" @click="submitLogin">
      <i class="fa fa-long-arrow-right"></i>
    </button>
  </fieldset>

  <!-- SELECCION NEGOCIO -->
  <fieldset v-if="paso==2">
    <legend class="legend">Seleccionar negocio</legend>

    <label class="label-vselect">Mercado</label>
    <v-select
      :options="mercados"
      label="nombre"
      v-model="mercadoSeleccionado"
      @input="cargarNegocios">
    </v-select>

    <br>

    <label class="label-vselect">Negocio</label>
    <v-select
      :options="negocios"
      label="nombre"
      v-model="negocioSeleccionado">
    </v-select>

    <br>

    <button type="button" class="submit" @click="continuar">
      <i class="fa fa-check"></i>
    </button>
  </fieldset>

</form>


<script>
  Vue.component('v-select', VueSelect.VueSelect);
  new Vue({
    el: '#appLogin',
    data: {
      paso:1,
      usuario: '',
      clavel: '',
      usuarioFocused: false,
      clavelFocused: false,
      mercados:[],
      negocios:[],
      mercadoSeleccionado:null,
      negocioSeleccionado:null,
      success: false,
      apphost: '<?php echo $apphost ?>' // Cambia esto por la URL base de tu API
    },
    methods: {
      onFocus(field) {
        if (field === 'usuario') this.usuarioFocused = true;
        if (field === 'clavel') this.clavelFocused = true;
      },
      cargarMercados(){

        axios.get(`${this.apphost}/loginVault/mercados`)
        .then(r=>{
          this.mercados=r.data
        })

      },
      cargarNegocios(){

        axios.get(`${this.apphost}/loginVault/negocios/${this.mercadoSeleccionado.mercado_id}`)
        .then(r=>{
          this.negocios=r.data
        })

      },
      continuar(){

        axios.post(`${this.apphost}/loginVault/negocioSeleccionado`,{
          neg_id:this.negocioSeleccionado.neg_id
        })
        .then(()=>{
          window.location.href=`${this.apphost}/admin/dash`
        })

      },
      onBlur(field) {
        if (field === 'usuario') this.usuarioFocused = false;
        if (field === 'clavel') this.clavelFocused = false;
      },
      submitLogin(){

        axios.post(`${this.apphost}/loginVault`,{
          usuario:this.usuario,
          clavel:this.clavel
        })
        .then(r=>{

          if(r.data.res==='ok'){

            // 👉 si es super admin
            if(r.data.rol_id==1){

              window.location.href=`${this.apphost}/admin/dash`
              return

            }

            // 👉 si necesita elegir negocio
            this.cargarMercados()
            this.paso=2

          }else{

            apprise("Credenciales incorrectas")

          }

        })

      },
    }
  });
</script>
</body>
</html>
