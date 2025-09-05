<?php

function getPreviewImgCatalog(string $photos): ?string
{
    if (!$photos) {
        return null;
    }

    $valArr = explode(';', $photos);

    if (!empty($valArr[0])) {
        return $valArr[0];
    }

    return null;
}
