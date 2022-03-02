<?php

set_time_limit(0);

require __DIR__ . '/vendor/autoload.php';

function uploadImages($id): array
{
    $files = [];
    $uploads_dir = "uploads/$id";

    if (!file_exists($uploads_dir)) {
        mkdir($uploads_dir, 0777, true);
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

    move_uploaded_file($_FILES['watermark_file']["tmp_name"], "$folder/" . $_FILES['watermark_file']["name"]);

    return $folder . "/" . $_FILES['watermark_file']["name"];
}

function addWatermarkToImage($id, $filename, $stamp_name): bool
{
    $destination = "result/$id";

    if (!file_exists($destination)) {
        mkdir($destination, 0777, true);
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

    $mime = mime_content_type("$stamp_name");

    if ($mime === 'image/png') {
        $stamp = imagecreatefrompng(
            $stamp_name
        );
    } elseif ($mime === 'image/jpeg') {
        $stamp = imagecreatefromjpeg(
            $stamp_name
        );
    } else {
        echo "$filename - Bad file format";
        return false;
    }

    $margin_right = 10;
    $margin_bottom = 10;
    $sx = imagesx($stamp);
    $sy = imagesy($stamp);

    $stamp_w = imagesx($im) * 0.35;
    $stamp_h = $stamp_w / $sx * $sy;

    imagecopyresized(
        $im,
        $stamp,
        imagesx($im) - $stamp_w - $margin_right,
        imagesy($im) - $stamp_h - $margin_bottom,
        0,
        0,
        $stamp_w,
        $stamp_h,
        $sx,
        $sy
    );

    if ($mime === 'image/png') {
        imagepng(
            $im,
            "$destination/$filename"
        );
    } elseif ($mime === 'image/jpeg') {
        imagejpeg(
            $im,
            "$destination/$filename"
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
        mkdir($uploads_dir, 0777, true);
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
        mkdir($destination, 0777, true);
    }

    $ffprobe = FFMpeg\FFProbe::create();

    $width = $ffprobe->streams("uploads/$id/$filename")
        ->videos()
        ->first()->get("width");

    $temp_name = "$destination/" . uniqid() . ".png";

    $size = getimagesize($stamp_name);

    resize_watermark(
        $stamp_name,
        $temp_name,
        $width * 0.35,
        $width * 0.35 / $size[0] * $size[1]
    );

    $ffmpeg = FFMpeg\FFMpeg::create();

    $video = $ffmpeg->open("uploads/$id/$filename");



    $video->filters()
        ->watermark($temp_name, array(
            'position' => 'relative',
            'bottom' => 10,
            'right' => 10
        ));

    $video->save(new FFMpeg\Format\Video\X264(), "$destination/$filename");

    unlink($temp_name);
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

function resize_watermark($watermark, $destination, $width, $height): bool
{
    $mime = mime_content_type($watermark);

    if ($mime === 'image/png') {
        $im = imagecreatefrompng(
            $watermark
        );
    } elseif ($mime === 'image/jpeg') {
        $im = imagecreatefromjpeg(
            $watermark
        );
    } else {
        return false;
    }

    $size = getimagesize($watermark);

    $dst = imagecreatetruecolor($width, $height);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
    imagefilledrectangle($dst, 0, 0, $width, $height, $transparent);
    imagecopyresampled($dst, $im, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
    imagedestroy($im);
    imagepng($dst, $destination); // adjust format as needed
    imagedestroy($dst);
    return true;
}

$id = uniqid();

if (isset($_FILES['images'])) {
    try {
        $files = uploadImages($id);

        if ($_POST['watermark'] === "other") {
            $watermark = uploadStamp($id);
        } else {
            $watermark = $_POST['watermark'];
        }

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

        if ($_POST['watermark'] === "other") {
            $watermark = uploadStamp($id);
        } else {
            $watermark = $_POST['watermark'];
        }

        foreach ($files as $file) {
            addWatermarkToVideo($id, $file, $watermark);
        }

    } catch (Throwable $throwable) {
        echo $throwable->getMessage();
        echo "<pre>";
        echo $throwable->getTraceAsString();
        echo "</pre>";

    }
}

if (isset($_FILES['images']) || isset($_FILES['video'])) {
    $destination = "archives/$id";
    if (!file_exists($destination)) {
        mkdir($destination, 0777, true);
    }
    try {

        zipDirectory("result/$id", "$destination/result.zip");

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
    <link href="assets/bootstrap/bootstrap.min.css"
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
               class="btn d-block btn-success"
            >
                Завантажити результат
            </a>
        </div>

    </div>
</div>
</body>
</html>


