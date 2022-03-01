<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3"
          crossorigin="anonymous"
    >
    <title>Converter</title>
</head>
<body>

<div class="card my-2 mx-auto" style="max-width: 500px;">
    <div class="card-body">
        <form method="post"
              action="convert.php"
              enctype="multipart/form-data"
              class="d-grid gap-2"
        >
            <label for="images"
                   class="form-label"
            >
                Images
            </label>

            <input type="file"
                   name="images[]"
                   id="images"
                   multiple
                   accept="image/*"
                   class="form-control"
            >

            <label for="video"
                   class="form-label"
            >
                Video
            </label>

            <input type="file"
                   name="videos[]"
                   id="video"
                   multiple
                   accept="video/*"
                   class="form-control"
            >

            <label for="watermark"
                   class="form-label"
            >
                Watermark
            </label>
            <input type="file"
                   name="watermark"
                   id="watermark"
                   accept="image/*"
                   required
                   class="form-control"
            >
            <div class="d-grid d-block">
                <input type="submit"
                       value="Відправити"
                       class="btn d-block btn-success"
                >
            </div>
        </form>
    </div>
</div>
</body>
</html>