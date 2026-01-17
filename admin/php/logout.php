<?php
// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
;echo '
      <!DOCTYPE html>
      <html lang="en">
      <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <meta http-equiv="X-UA-Compatible" content="ie=edge">
          <title>Document</title>
          <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      </head>
      <body>
          
          <script>
              swal.fire({
                  text : "Logged out successfully !",
                  icon : "info",
                  timer : 3000,
                  showConfirmButton : false,
              }).then(() => {
                  window.location.href = "../index.php";
              });
      
              setTime(() => {
                  window.location.href = "../index.php";
              }, 3000);
              </script>
      </body>
      </html>
      ';
// header("Location: ../index.php");
exit();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title></title>
</head>
<body>
    
</body>
</html>