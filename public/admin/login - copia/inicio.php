<!DOCTYPE html>
<html lang="es" ng-app="loginApp">
<head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Login</title>
      <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css">
      <base href="<?php echo $mBase ?>">
      <link rel="stylesheet" type="text/css" href="css/login.css">
      <?php
      perso::jquery2();
      perso::vuejs2();
      perso::apprise();
      perso::favicon();
      perso::global_env($apphost, $varhost);
      ?>
</head>
<body>

  <form class="login" @submit.prevent="submit" id="appLogin">
    <fieldset>
      <legend class="legend">Ingreso</legend>

      <div class="input" :class="{'focused': usuarioFocused}">
        <input type="text" placeholder="Usuario" required
               v-model="usuario"
               @focus="onFocus('usuario')"
               @blur="onBlur('usuario')">
        <span><i class="fa fa-envelope-o"></i></span>
      </div>

      <div class="input" :class="{'focused': clavelFocused}">
        <input type="password" placeholder="Password" required
               v-model="clavel"
               @focus="onFocus('clavel')"
               @blur="onBlur('clavel')">
        <span><i class="fa fa-lock"></i></span>
      </div>

      <button type="submit" class="submit">
        <i class="fa" :class="{'fa-long-arrow-right': !success, 'fa-check': success}"></i>
      </button>
    </fieldset>
  </form>


<script>
  new Vue({
    el: '#appLogin',
    data: {
      usuario: '',
      clavel: '',
      usuarioFocused: false,
      clavelFocused: false,
      success: false,
      apphost: '<?php echo $apphost ?>' // Cambia esto por la URL base de tu API
    },
    methods: {
      onFocus(field) {
        if (field === 'usuario') this.usuarioFocused = true;
        if (field === 'clavel') this.clavelFocused = true;
      },
      onBlur(field) {
        if (field === 'usuario') this.usuarioFocused = false;
        if (field === 'clavel') this.clavelFocused = false;
      },
      submit() {
        const postData = {
          usuario: this.usuario,
          clavel: this.clavel
        };

        axios.post(`${this.apphost}/loginVault`, postData)
          .then(response => {
            if (response.data && response.data.res && response.data.res.includes('ok')) {
              // Redirigir si la respuesta es "ok"
              window.location.href = `${this.apphost}/admin/dash`;
            } else {
              // Mostrar alerta si la respuesta no es "ok"
              apprise("Por favor, verifica tus credenciales");
            }
          })
          .catch(error => {
            // Mostrar alerta en caso de error en la solicitud
            apprise("Error al comunicar con el servidor. Por favor, intenta más tarde");
            console.error('Error al enviar los datos:', error);
          });
      }
    }
  });
</script>
</body>
</html>
