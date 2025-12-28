<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class FileExist extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('remote_file_exists', [$this, 'remoteFileExists']),
        ];
    }

    public function remoteFileExists(string $url): bool
    {
        $ch = curl_init($url);
        $perm_cacert =  __DIR__ . '/../../../certs/cacert-2025-12-02.pem';
        curl_setopt($ch, CURLOPT_CAINFO, $perm_cacert);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
            CURLOPT_HTTPGET => true,
            CURLOPT_RANGE => '0-0',
        ]);
        $ok = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);

        $exists = ($ok !== false) && ($http >= 200 && $http < 400);

        // "exists" logic:
        $exists = ($ok !== false) && ($http >= 200 && $http < 400);
        $status = $exists ? 200 : 404;
        return $status >= 200 && $status < 400;
    }
}
