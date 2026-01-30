<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RssController extends Controller
{
    public function index(): Response
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>TFOМС Краснодарского края</title>
        <description>Лента новостей (заглушка)</description>
        <link>http://localhost:8080/</link>
    </channel>
</rss>
XML;

        return response($xml, 200, ['Content-Type' => 'application/rss+xml']);
    }
}
