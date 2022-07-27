<!DOCTYPE html>
<html>

<head>
  <title>Main</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>

  <?php
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    echo 'NO';
    return;
  }

  if (!isset($_FILES['audioFile']) || $_FILES['audioFile']['error'] !== UPLOAD_ERR_OK) {
    echo 'ERROR';
    return;
  }

  $targetPath = getcwd() . DIRECTORY_SEPARATOR . 'uploads\\';

  $originalFileName  = $_FILES['audioFile']['name'];
  $fileNameComponents = explode('.', $originalFileName);
  $fileExtension = strtolower(end($fileNameComponents));
  $tmpFileName = $_FILES['audioFile']['tmp_name'];

  $newFileName = 'temp.' . $fileExtension;

  move_uploaded_file($tmpFileName, $targetPath . $newFileName);

  $x = parse_url($_SERVER['REQUEST_URI']);

  echo '<audio controls>
         <source src="' . '\\uploads\\' . $newFileName . '" type="audio/wav">
      </audio>';
  ?>

</body>

</html>