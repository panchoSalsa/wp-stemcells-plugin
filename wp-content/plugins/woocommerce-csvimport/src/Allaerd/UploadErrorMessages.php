<?php
/**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 07/06/16
 * Time: 13:13
 */

namespace Allaerd;

class UploadErrorMessages
{
    /**
     *
     * Convert an upload error number to a message
     *
     * @param $code
     * @return string
     *
     */
    public static function convertErrorToMessage($code)
    {
        switch ($code) {
            case 1:
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
            case 2:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case 3:
                $message = "The uploaded file was only partially uploaded";
                break;
            case 4:
                $message = "No file was uploaded";
                break;
            case 5:
                $message = "Missing a temporary folder";
                break;
            case 6:
                $message = "Failed to write file to disk";
                break;
            case 7:
                $message = "File upload stopped by extension";
                break;

            default:
                $message = "Unknown upload error";
                break;
        }

        return $message;
    }
}