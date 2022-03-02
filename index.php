<?php

function recursive_rmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                    recursive_rmdir($dir . DIRECTORY_SEPARATOR . $object);
                else
                    unlink($dir . DIRECTORY_SEPARATOR . $object);
            }
        }
        rmdir($dir);
    }
}

$watermarks = [
    "Watermark #1" => "assets/watermarks/watermark_1.png"
];

if (isset($_POST['id'])){
    $dir = "archives/".$_POST['id'];
    if (file_exists($dir)) {
        recursive_rmdir($dir);
    }
}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="assets/bootstrap/bootstrap.min.css"
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

            <label class="form-label">
                Watermark
            </label>

            <?php foreach ($watermarks as $name => $path): $id = preg_replace('/\s+/', "", strtolower($name));?>

                <div class="form-check">
                    <input class="form-check-input"
                           type="radio"
                           name="watermark"
                           id="<?= $id ?>"
                           value="<?= $path ?>"
                           required
                           checked
                    >
                    <label class="form-check-label"
                           for="<?= $id ?>"
                    >
                        <?= $name ?>
                    </label>
                </div>

            <?php endforeach;?>

            <div class="form-check">
                <input class="form-check-input"
                       type="radio"
                       name="watermark"
                       id="other"
                       value="other"
                       required
                >
                <label class="form-check-label"
                       for="other"
                >
                    Other
                </label>
            </div>

            <input type="file"
                   name="watermark_file"
                   id="watermark"
                   accept="image/*"
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