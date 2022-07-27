<!DOCTYPE html>
<html>

<head>
  <title>Main</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>

  <!--Form-->
  <!-- <div class="container mt-3">
    <h4 class="m-1"><abbr title="Upload your audio file">Upload your audio file</abbr></h4>
    <form class="bg-light" action="process.php" method="post" enctype="multipart/form-data">
      <div class="m-1">
        <label for="audioFile">Your audio file:</label>
        <input type="file" id="audioFile" class="form-control" name="audioFile" accept="audio/*">
      </div>
      <div class="m-1">
        <input class="btn btn-primary rounded-pill m-1 float-end" type="submit">
      </div>
    </form>
  </div> -->

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
  <input type="file" id="upload" />
  <audio id="audio" controls>
    <source src="" id="src" />
  </audio>

  <script>
    function handleFiles(event) {
      var files = event.target.files;
      $("#src").attr("src", URL.createObjectURL(files[0]));
      document.getElementById("audio").load();
    }

    document.getElementById("upload").addEventListener("change", handleFiles, false);
  </script>

</body>

</html>