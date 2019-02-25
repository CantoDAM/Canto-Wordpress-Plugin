<!doctype html>
<html class="no-js" lang="">
<head>
  <meta charset="utf-8">
  <title></title>
  <meta name="description" content="">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="" href="<?php echo $_GET['FBC_URL']; ?>public/assets/app.styles.css">
</head>

<body>
  <img src="<?php echo $_GET['FBC_URL']; ?>/assets/loader_white.gif" id="loader">
  <section id="root"></section>

  <script>
  /* <![CDATA[ */
  var args = <?php echo $_GET['args']; ?>;
  var wpBlockClientId = <?php echo $_GET['wpClientId']; ?>;
  /* ]]> */
  </script>

  <script src="<?php echo $_GET['FBC_URL']; ?>public/assets/app.vendor.bundle.js"></script>
  <script src="<?php echo $_GET['FBC_URL']; ?>public/assets/app.bundle.js"></script>
</body>
</html>
