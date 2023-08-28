<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lativi</title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.1.2/socket.io.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link href="https://vjs.zencdn.net/7.20.3/video-js.css" rel="stylesheet"/>


</head>
<body class="container p-4">

    <label for="id">POST</label>
    <input type="text" class="form-control" id="id">
<button class="btn btn-primary text-uppercase" onclick="download();">
    Download
</button>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    function download() {
        $.ajax({
        type: "POST",
        url: "/api/downloads/post/"+ document.getElementById('id').value,
        data: {},
        headers: {'Authorization':  'Bearer ' + localStorage.getItem('token')},
        dataType: "json",
        success: function (res) {

        },
        error: function (res) {
        }
      });
    }
</script>
</body>
</html>
