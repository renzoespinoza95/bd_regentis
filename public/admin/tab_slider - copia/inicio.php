<?php include VARPATH . "/public/admin/header.php"; ?>
<body>
  <!-- fin sidebar -->
  <div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
<?php include VARPATH . "/public/admin/menu.php"; ?>
    </div>
  </div>
  <div class="container">
  <?php 
  perso::jquery_ui();
  include "vista.php"; 
  include VARPATH . "/public/admin/footer.php"; 
  ?>
  </div>
</body>
</html>
