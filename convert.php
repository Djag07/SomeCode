<?php
/*
Two methods to convert images from jpeg to webp and from webp to jpeg
*/

/**
 * Method converts and resizes image from webp to jpeg
 *
 * @param int $bitrixImageId Image id in bitrix image table
 * @param int $resizeWidth Resize width in pixels (default 0)
 * @param int $resizeHeight Resize height in pixels (default 0)
 * @param int $quality JPEG convert quality (max 100, 75 default)
 *
 * @return array|bool Array with image info: ['SRC', 'WIDTH', 'HEIGHT', 'SIZE']
 */
public static function convertFromWebpToJpeg(int $bitrixImageId, int $resizeWidth = 0, int $resizeHeight = 0, int $quality = 75): array
{
    if (0 >= $bitrixImageId) {
        return false;
    }

    $arImageInfo = \CFile::GetFileArray($bitrixImageId);

    if ($resizeWidth < intval($arImageInfo['WIDTH']) || $resizeHeight < intval($arImageInfo['HEIGHT'])) {
        $arResizedImageInfo = \CFile::ResizeImageGet(
            $bitrixImageId,
            array(
                'width' => $resizeWidth,
                'height' => $resizeHeight
            ),
            BX_RESIZE_IMAGE_PROPORTIONAL,
            false
        );
        list($width, $height, $type, $attr) = getimagesize($_SERVER['DOCUMENT_ROOT'] . $arResizedImageInfo['src']);
        $arImageInfo['SRC'] = $arResizedImageInfo['src'];
        $arImageInfo['WIDTH'] = $width;
        $arImageInfo['HEIGHT'] = $height;
        $arImageInfo['SIZE'] = filesize($_SERVER['DOCUMENT_ROOT'] . $arResizedImageInfo['src']);
    }

    if ('image/webp' === strval($arImageInfo['CONTENT_TYPE'])) {
        $fileName = substr($arImageInfo['FILE_NAME'], 0, strpos($arImageInfo['FILE_NAME'], '.')) . '_.jpeg';
        $filePath = substr($arImageInfo['SRC'], 0, strrpos($arImageInfo['SRC'], '/')) . '/' . $fileName;
        $fullFilePath = $_SERVER['DOCUMENT_ROOT'] . $filePath;

        if (!file_exists($fullFilePath)) {
            $oWebpImage = imagecreatefromwebp($_SERVER['DOCUMENT_ROOT'] . $arImageInfo['SRC']);
            imagejpeg($oWebpImage, $fullFilePath, $quality);
            imagedestroy($oWebpImage);
        }

        $arImageInfo['SRC'] = $filePath;
        $arImageInfo['SIZE'] = filesize($fullFilePath);
    }

    return array(
        'SRC' => $arImageInfo['SRC'],
        'WIDTH' => $arImageInfo['WIDTH'],
        'HEIGHT' => $arImageInfo['HEIGHT'],
        'SIZE' => $arImageInfo['SIZE']
    );
}


 /**
 * Method converts and resizes image from jpeg to webp
 *
 * @param int $bitrixImageId Image id in bitrix image table
 * @param int $resizeWidth Resize width in pixels (default 0)
 * @param int $resizeHeight Resize height in pixels (default 0)
 * @param int $quality WEBP convert quality (max 100, 75 default)
 *
 * @return array|bool Array with image info: ['ID', 'SRC', 'WIDTH', 'HEIGHT', 'SIZE']
 */
public static function convertFromJpegToWebp(int $bitrixImageId, int $resizeWidth = 0, int $resizeHeight = 0, int $quality = 75): array
{
    if (0 >= $bitrixImageId) {
        return false;
    }

    $arImageInfo = \CFile::GetFileArray($bitrixImageId);

    if ($resizeWidth < intval($arImageInfo['WIDTH']) || $resizeHeight < intval($arImageInfo['HEIGHT'])) {
        $arResizedImageInfo = \CFile::ResizeImageGet(
            $bitrixImageId,
            array(
                'width' => $resizeWidth,
                'height' => $resizeHeight
            ),
            BX_RESIZE_IMAGE_PROPORTIONAL,
            false
        );
        list($width, $height, $type, $attr) = getimagesize($_SERVER['DOCUMENT_ROOT'] . $arResizedImageInfo['src']);
        $arImageInfo['SRC'] = $arResizedImageInfo['src'];
        $arImageInfo['WIDTH'] = $width;
        $arImageInfo['HEIGHT'] = $height;
        $arImageInfo['SIZE'] = filesize($_SERVER['DOCUMENT_ROOT'] . $arResizedImageInfo['src']);
    }

    if ('image/jpeg' === strval($arImageInfo['CONTENT_TYPE'])) {
        $fileName = substr($arImageInfo['FILE_NAME'], 0, strpos($arImageInfo['FILE_NAME'], '.')) . '.webp';
        $filePath = substr($arImageInfo['SRC'], 0, strrpos($arImageInfo['SRC'], '/')) . '/' . $fileName;
        $fullFilePath = $_SERVER['DOCUMENT_ROOT'] . $filePath;

        if (!file_exists($fullFilePath)) {
            $oJpegImage = imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'] . $arImageInfo['SRC']);
            imagewebp($oJpegImage, $fullFilePath, $quality);
            imagedestroy($oJpegImage);
        }

        $arImageInfo['SRC'] = $filePath;
        $arImageInfo['SIZE'] = filesize($fullFilePath);
    }

    return array(
        'ID' => $arImageInfo['ID'],
        'SRC' => $arImageInfo['SRC'],
        'WIDTH' => $arImageInfo['WIDTH'],
        'HEIGHT' => $arImageInfo['HEIGHT'],
        'SIZE' => $arImageInfo['SIZE']
    );
}
?>
