<?php
$args = json_decode($_GET['args']);

if( $args->expire_token < time() || ( $args->subdomain  == '' || $args->token == '') ) :

  if( $args->expire_token < time() ) {
    $errorMsg = "Your security token has expired Please re-authenticate your Canto account.";
  }

  if ( $args->subdomain  == '' || $args->token == '' ) {
    $errorMsg = "You haven't connected your Canto account yet.";
  }

  echo '<form><h3 class="media-title"><span style="font-size:14px;font-family:Helvetica,Arial">';
  echo '<strong>Oops!</strong>'. $errorMsg;
  echo '<a href="javascript:;" onclick="window.top.location.href=\''. $args->FBC_SITE . '/wp-admin/options-general.php?page=canto_settings\'">Plugin Settings</a>';
  echo '</span></h3></form>';

else :
?>
<!doctype html>
<html class="no-js" lang="">
<head>
  <meta charset="utf-8">
  <title></title>
  <meta name="description" content="">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="" href="<?php echo $args->FBC_URL; ?>public/assets/app.styles.css">
</head>

<body>
  <img src="<?php echo $args->FBC_URL; ?>/assets/loader_white.gif" id="loader">
  <section id="root"></section>

  <script>
  /* <![CDATA[ */
    var args = <?php echo $_GET['args']; ?>;
    var wpBlockClientId = <?php echo $_GET['wpClientId']; ?>;
  /* ]]> */
  </script>
  <script src="<?php echo $args->FBC_URL; ?>public/assets/app.vendor.bundle.js"></script>
  <script src="<?php echo $args->FBC_URL; ?>public/assets/app.bundle.js"></script>
</body>
</html>

<?php
endif;
?>
