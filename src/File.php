<?php

namespace ph\sen;
use Exception;

trait File
{
    protected static $folder = APP_ROOT . '/../resources/';

    public function writeFile($fileName, array $data) {
        if (file_exists(self::$folder . $fileName)) {
            throw new Exception('File ' . $fileName . ' is existed');
        }

        if (!$json = json_encode($data)) {
            throw new Exception('Wrong json format');
        }

        $handle = fopen(self::$folder . $fileName,'w+');
        fwrite($handle, $json);
        fclose($handle);
    }

    public function readFile($fileName) {
        return json_decode(file_get_contents(self::$folder . $fileName), true);
    }

    public function existFile($fileName) {
        return (bool) file_exists(self::$folder . $fileName);
    }
}
