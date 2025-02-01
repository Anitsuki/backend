<?php

namespace App\Console\Commands\LiveChart;

use App\seasons;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use voku\helper\HtmlDomParser;
use voku\helper\SimpleHtmlDomInterface;

class TopAnime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:top-anime';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'scrap top anime from livechart.me';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $DOM = $this->getDom();
        // get anime cards as a collection
        $cards =  $DOM->getElementsByTagName('article');
        $animeData = [];
        // loop through each card
        foreach ($cards as $card) {
            if (count($animeData) >= 10) {
                break;
            }

            $data = $this->parseAnimeCard($card);
            if ($data === null) {
                continue;
            }
            $animeData[] = $data;
        }

        Cache::put('anime_top', $animeData);
    }


    private function parseAnimeCard(SimpleHtmlDomInterface $card): array | null
    {
        $mal_id = $card->findOneOrFalse('a.mal-icon', 0);


        // if there is no mal_id, return null
        if ($mal_id === false) {
            return null;
        }
        $mal_id = $mal_id->getAttribute('href');
        $mal_id = (int)preg_replace('/\D/', '', $mal_id);


        $attrs = $card->getAllAttributes();
        // get the title
        $title = $attrs['data-english'] ?? $attrs['data-romaji'];

        // get the synopsis
        $animeSynopsis = $card->findOneOrFalse('.anime-synopsis');
        $synopsis = $animeSynopsis->findMultiOrFalse('p');

        if ($synopsis !== false) {
            $synopsisText = $synopsis->text();
        }

        //
        $tags = $card->findOne('.anime-tags')->children()->text();

        // download anime thumbnail
        $src = $card->find('img', 0)->getAttribute('src');
        $this->downloadThumbnail($src, $mal_id);

        return [
            'title' => htmlspecialchars_decode($title),
            'synopsis' => $synopsisText ?? null,
            'mal_id' => $mal_id,
            'tags' => $tags ?? null
        ];
    }

    private function downloadThumbnail(string $url, int $mal_id): void
    {
        if (Storage::exists("anime/thumbnails/$mal_id.jpg")) {
            return;
        }
        $image = Http::withUserAgent(fake()->msedge())->get($url);
        // echo $image;
        Storage::put("anime/thumbnails/$mal_id.jpg", $image);
    }
    /**
     * Get the DOM parser instance.
     *
     * @return HtmlDomParser The DOM parser instance.
     */
    private function getDom(): HtmlDomParser
    {
        $year = date('Y');
        $season = seasons::winter->value;

        $url = "https://www.livechart.me/$season-$year/tv";

        $rawData = Http::withUserAgent(fake()->msedge())
            ->get($url)
            ->throw();


        return HtmlDomParser::str_get_html($rawData->body());
    }
}
