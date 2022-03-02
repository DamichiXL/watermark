<?php

set_time_limit(0);

require __DIR__ . '/vendor/autoload.php';

function uploadImages($id): array
{
    $files = [];
    $uploads_dir = "uploads/$id";

    if (!file_exists($uploads_dir)) {
        mkdir($uploads_dir);
    }

    foreach ($_FILES["images"]["error"] as $key => $error) {
        if ($error == UPLOAD_ERR_OK) {
            $tmp_name = $_FILES["images"]["tmp_name"][$key];
            $name = basename($_FILES["images"]["name"][$key]);
            if (move_uploaded_file($tmp_name, "$uploads_dir/$name")) {
                $files[] = $name;
            } else {
                echo "Uploading error";
            }
        }
    }

    return $files;
}

function uploadStamp($id): string
{
    $folder = "uploads/$id";

    move_uploaded_file($_FILES['watermark']["tmp_name"], "$folder/" . $_FILES['watermark']["name"]);

    return $_FILES['watermark']["name"];
}

function addWatermarkToImage($id, $filename, $stamp_name): bool
{
    $destination = "result/$id";
    if (!file_exists($destination)) {
        mkdir($destination);
    }

    $mime = mime_content_type("uploads/$id/$filename");

    if ($mime === 'image/png') {
        $im = imagecreatefrompng(
            "uploads/$id/$filename"
        );
    } elseif ($mime === 'image/jpeg') {
        $im = imagecreatefromjpeg(
            "uploads/$id/$filename"
        );
    } else {
        return false;
    }

    $mime = mime_content_type("uploads/$id/$stamp_name");

    if ($mime === 'image/png') {
        $stamp = imagecreatefrompng(
            "uploads/$id/$stamp_name"
        );
    } elseif ($mime === 'image/jpeg') {
        $stamp = imagecreatefromjpeg(
            "uploads/$id/$stamp_name"
        );
    } else {
        echo "$filename - Bad file format";
        return false;
    }

    $margin_left = 10;
    $margin_bottom = 10;
    $sy = imagesy($stamp);

    imagecopy(
        $im,
        $stamp,
        $margin_left,
        imagesy($im) - $sy - $margin_bottom,
        0,
        0,
        imagesx($stamp),
        imagesy($stamp)
    );

    if ($mime === 'image/png') {
        imagepng(
            $im,
            "result/$id/$filename"
        );
    } elseif ($mime === 'image/jpeg') {
        imagejpeg(
            $im,
            "result/$id/$filename"
        );
    }

    imagedestroy($im);

    return true;
}

function uploadVideos($id): array
{
    $files = [];
    $uploads_dir = "uploads/$id";

    if (!file_exists($uploads_dir)) {
        mkdir($uploads_dir);
    }

    foreach ($_FILES["videos"]["error"] as $key => $error) {
        if ($error == UPLOAD_ERR_OK) {
            $tmp_name = $_FILES["videos"]["tmp_name"][$key];
            $name = basename($_FILES["videos"]["name"][$key]);
            if (move_uploaded_file($tmp_name, "$uploads_dir/$name")) {
                $files[] = $name;
            } else {
                echo "Uploading error";
            }
        }
    }

    return $files;
}

function addWatermarkToVideo($id, $filename, $stamp_name)
{

    $destination = "result/$id";
    if (!file_exists($destination)) {
        mkdir($destination);
    }

    $ffmpeg = FFMpeg\FFMpeg::create();

    $video = $ffmpeg->open("uploads/$id/$filename");

    $video->filters()
        ->watermark("uploads/$id/$stamp_name", array(
            'position' => 'relative',
            'bottom' => 10,
            'left' => 10,
        ));

    $video->save(new FFMpeg\Format\Video\X264(), "$destination/$id/$filename");
}

function zipDirectory($directory, $destination)
{
    $rootPath = realpath($directory);
    $zip = new ZipArchive();
    $zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        // Skip directories (they would be added automatically)
        if (!$file->isDir()) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);

            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }

    // Zip archive will be created only after closing object
    $zip->close();
}

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

function removeDirectories($id)
{
    recursive_rmdir("result/$id");
    recursive_rmdir("uploads/$id");
}

$id = uniqid();

if (isset($_FILES['images'])) {
    try {

        $files = uploadImages($id);
        $watermark = uploadStamp($id);

        foreach ($files as $file) {
            addWatermarkToImage($id, $file, $watermark);
        }


    } catch (Throwable $throwable) {
        echo $throwable->getMessage();
    }
}

if (isset($_FILES['videos'])) {
    try {

        $files = uploadVideos($id);
        $watermark = uploadStamp($id);

        foreach ($files as $file) {
            addWatermarkToVideo($file, $watermark);
        }

    } catch (Throwable $throwable) {
        echo $throwable->getMessage();
    }
}

if (isset($_FILES['images']) || isset($_FILES['video'])) {
    $destination = "archives";
    if (!file_exists($destination)) {
        mkdir($destination);
    }
    try {

        zipDirectory("result/$id", "$destination/$id/result.zip");

        removeDirectories($id);

    } catch (Throwable $throwable) {
        echo $throwable->getMessage();
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
    <title>Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3"
          crossorigin="anonymous"
    >
</head>
<body>

<div class="card my-2 mx-auto" style="max-width: 500px;">
    <div class="card-body">

        <div class="d-grid d-block">
            <a href="archives/<?= $id ?>/result.zip"
               type="submit"
               value="Відправити"
               class="btn d-block btn-success"
            >
                Завантажити результат
            </a>
        </div>

    </div>
</div>
</body>
</html>


