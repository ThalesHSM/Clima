<?php
namespace App\Models;

/**
 * Notícias de natureza e clima, vindas do feed RSS do Mongabay Brasil.
 *
 * Uso conservador, de acordo com a licença do Mongabay (CC BY-ND 4.0): guardamos apenas
 * título, data e link da matéria original (sem texto, sem imagens, sem traduzir ou reescrever
 * os títulos). A fonte é sempre exibida e o link leva para a matéria no site deles.
 */
class News
{
    public const SOURCE_NAME = 'Mongabay Brasil';
    public const SOURCE_URL = 'https://brasil.mongabay.com/';

    private const FEED_URL = 'https://brasil.mongabay.com/feed/';
    private const ALLOWED_HOST = 'brasil.mongabay.com';
    private const TTL = 1800; // 30 minutos de cache
    private const LIMIT = 6;

    /** Lista de ['title', 'link', 'date']. Se a busca falhar, usa o último cache; sem cache, devolve []. */
    public static function latest(): array
    {
        $file = dirname(__DIR__, 2) . '/data/news-cache.json';
        $cached = is_file($file) ? json_decode(file_get_contents($file), true) : null;

        if ($cached && time() - ($cached['fetched_at'] ?? 0) < self::TTL) {
            return $cached['items'];
        }

        $items = self::fetch();
        if ($items === null) {
            return $cached['items'] ?? [];
        }

        // Grava em arquivo temporário e renomeia, para nunca deixar um cache pela metade
        $tmp = $file . '.tmp';
        file_put_contents($tmp, json_encode(['fetched_at' => time(), 'items' => $items], JSON_UNESCAPED_UNICODE));
        rename($tmp, $file);

        return $items;
    }

    /** Lê o feed. Devolve null se não conseguir. */
    private static function fetch(): ?array
    {
        $context = stream_context_create(['http' => [
            'timeout' => 5,
            'header' => "User-Agent: ClimaEmAcao/1.0 (projeto educacional)\r\n",
        ]]);

        $xml = @file_get_contents(self::FEED_URL, false, $context);
        if ($xml === false) {
            return null;
        }

        libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET);
        if ($feed === false || !isset($feed->channel->item)) {
            return null;
        }

        $items = [];
        foreach ($feed->channel->item as $item) {
            $title = trim((string) $item->title);
            $link = trim((string) $item->link);
            $time = strtotime((string) $item->pubDate);

            // só aceita links https do próprio Mongabay Brasil
            $parts = parse_url($link);
            if ($title === '' || ($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== self::ALLOWED_HOST) {
                continue;
            }

            $items[] = [
                'title' => $title,
                'link' => $link,
                'date' => $time ? date('Y-m-d', $time) : null,
            ];
            if (count($items) === self::LIMIT) {
                break;
            }
        }

        return $items ?: null;
    }
}
