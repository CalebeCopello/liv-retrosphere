<?php

declare(strict_types=1);

/** Generate catalog JSON from a MediaWiki API response saved locally. */
function uuid(string $value): string
{
    $hash = md5($value);

    return substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-5'.substr($hash, 13, 3).'-a'.substr($hash, 17, 3).'-'.substr($hash, 20, 12);
}

function clean(string $value): string
{
    $value = preg_replace('/<ref[^>]*>.*?<\/ref>|<ref[^>]*\/>/si', '', $value);
    $value = preg_replace('/\{\{[^{}]*\}\}/', '', $value);
    $value = preg_replace('/\[\[([^\]|]+)\|([^\]]+)\]\]/', '$2', $value);
    $value = preg_replace('/\[\[([^\]]+)\]\]/', '$1', $value);
    $value = preg_replace('/<sup[^>]*>.*?<\/sup>/si', '', $value);
    $value = strip_tags(str_replace("''", '', $value));

    return trim(html_entity_decode($value));
}

function slug(string $name): string
{
    if (function_exists('iconv')) {
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
    }

    return trim((string) preg_replace('/-+/', '-', preg_replace('/[^a-z0-9]+/', '-', strtolower($name))), '-');
}

$api = json_decode(file_get_contents($argv[1] ?? '/tmp/master-system-wikitext.json'), true, 512, JSON_THROW_ON_ERROR);
$wiki = $api['parse']['wikitext'];
$nesDevelopers = json_decode(file_get_contents(__DIR__.'/../data/nes-developer-catalog.json'), true, 512, JSON_THROW_ON_ERROR);
$nesPublishers = json_decode(file_get_contents(__DIR__.'/../data/nes-publisher-catalog.json'), true, 512, JSON_THROW_ON_ERROR);
$known = static fn (array $items): array => array_column($items, 'id', 'slug');
$developers = $known($nesDevelopers);
$publishers = $known($nesPublishers);
$developerCatalog = [];
$publisherCatalog = [];
$games = [];
$usedSlugs = [];

foreach (preg_split('/\n\|-\s*(?:id="[^"]+")?\n/', $wiki) as $row) {
    $line = strtok($row, "\n");
    if (! str_starts_with((string) $line, '|') || substr_count((string) $line, '||') < 6) {
        continue;
    }
    $cells = array_map('trim', explode('||', substr($line, 1)));
    if (count($cells) < 7) {
        continue;
    }
    [$titleCell, $developerCell, $publisherCell, $jp, $na, $pal, $br] = array_slice($cells, 0, 7);
    $title = clean(explode('<br>', $titleCell)[0]);
    if ($title === '') {
        continue;
    }
    $gameSlug = slug($title);
    if (isset($usedSlugs[$gameSlug])) {
        $gameSlug .= '-'.substr(md5($titleCell), 0, 6);
    }
    $usedSlugs[$gameSlug] = true;
    $ids = static function (string $cell, array &$map, array &$catalog, string $kind): array {
        $ids = [];
        foreach (preg_split('/<br\s*\/?>/i', $cell) as $raw) {
            $name = clean($raw);
            if ($name === '') {
                continue;
            }
            $key = slug($name);
            $id = $map[$key] ??= uuid("master-system:$kind:$key");
            $catalog[$key] = ['id' => $id, 'name' => $name, 'slug' => $key, 'source_url' => null];
            $ids[] = $id;
        }

        return array_values(array_unique($ids));
    };
    $developerIds = $ids($developerCell, $developers, $developerCatalog, 'developer');
    $publisherIds = $ids($publisherCell, $publishers, $publisherCatalog, 'publisher');
    $dates = ['JP' => $jp, 'NA' => $na, 'PAL' => $pal, 'BR' => $br];
    $releaseDates = [];
    foreach ($dates as $region => $date) {
        if (preg_match('/\{\{dts\|(\d{4})(?:\|(\d{2}))?(?:\|(\d{2}))?/', $date, $m)) {
            $releaseDates[$region] = $m[1].(isset($m[2]) ? '-'.$m[2] : '').(isset($m[3]) ? '-'.$m[3] : '');
        }
    }
    $firstDate = $releaseDates === [] ? null : reset($releaseDates);
    $games[] = ['id' => uuid("master-system:game:$gameSlug"), 'name' => $title, 'slug' => $gameSlug, 'image' => null, 'alternate_names' => [], 'platform_ids' => ['cdc80fb6-5721-4a66-85f4-142a408c1f15'], 'initial_release_year' => $firstDate === null ? 1985 : (int) substr($firstDate, 0, 4), 'first_release_date' => $firstDate, 'first_release_regions' => array_keys($releaseDates), 'release_dates' => $releaseDates, 'description' => null, 'source_url' => 'https://en.wikipedia.org/wiki/List_of_Master_System_games', 'developer_ids' => $developerIds, 'publisher_ids' => $publisherIds, 'publisher_regions' => array_fill_keys($publisherIds, []), 'data_issues' => []];
}
sort($developerCatalog);
sort($publisherCatalog);
file_put_contents(__DIR__.'/../data/master-system-developer-catalog.json', json_encode(array_values($developerCatalog), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
file_put_contents(__DIR__.'/../data/master-system-publisher-catalog.json', json_encode(array_values($publisherCatalog), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
file_put_contents(__DIR__.'/../data/master-system-game-catalog.json', json_encode($games, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
printf("Generated %d games, %d developers, and %d publishers.\n", count($games), count($developerCatalog), count($publisherCatalog));
