<?php 
  require '../../../wp-load.php';
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title>Iniciar sesión y obtener licencia</title>
<link rel="stylesheet" type="text/css" href="public/css/login-style.css" />
</head>

<body>
  <div class="login-wrap">
    <div class="login-head">
      <div align="center"><img src="public/images/logo.png" alt=""></div>
    </div>
    <div class="login-cont">
      <div id="btl-login-error" class="btl-error">
        <div class="black">
          <?php
            $LicError = $_GET['error'];
            $MesError = $_GET['message'];
          
            if($LicError == "ERROR:EXCEED_BIND"){
              echo "ERROR:EXCEED_BIND, en caso de duda, póngase en contacto con el administrador.";
            }else if ($LicError == "ERROR:ADMIN_NO_MONEY") {
              echo "ERROR:ADMIN_NO_MONEY, póngase en contacto con el administrador.";
            }else if ($MesError == "ERROR:RIGHTS EXPIRED") {
              echo "ERROR:RIGHTS EXPIRED, póngase en contacto con el administrador.";
            }else {
              echo $LicError;
              echo "<br>";
              echo $MesError;
            }
          ?>
        </div>
      </div>
      <div class="login-foot">
				<div class="foot-tit">Otras opciones</div>
				<div class="foot-acts">
					<a class="link-reg" href="<?php echo site_url(); ?>" target="_blank">¿Ayuda?</a>
					<a class="link-get-pwd" href="<?php echo site_url(); ?>" target="_blank">Comprar cursos</a>
				</div>
			</div>
    </div>
  </div>
</body>
</html>