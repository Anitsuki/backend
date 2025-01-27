<?php

namespace App\Console\Commands\LiveChart;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use voku\helper\HtmlDomParser;
use voku\helper\SimpleHtmlDomInterface;




class Schedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
            $data = $this->parseAnimeCard($card);
            if ($data === null) {
                continue;
            }
            $animeData[] = $data;
        }

        Cache::put('anime_schedule', $animeData);
    }

    /**
     * Parses an anime card and returns an array of data or null.
     *
     * @param SimpleHtmlDomInterface $card The HTML DOM interface representing the anime card.
     * @return array|null The parsed data as an associative array, or null if parsing fails.
     */
    private function parseAnimeCard(SimpleHtmlDomInterface $card): array | null
    {
        $mal_id = $card->findOneOrFalse('a.lc-anime-card--related-links--icon.mal', 0);
        // if there is no mal_id, return null
        if ($mal_id === false) {
            return null;
        }
        $mal_id = $mal_id->getAttribute('href');
        $mal_id = (int)preg_replace('/\D/', '', $mal_id);


        $attrs = $card->getAllAttributes();
        // get the title
        $title = $attrs['data-english'] ?? $attrs['data-romaji'];

        $timeElement = $card->findOneOrFalse('time', 0);
        $timeStamp = (int)$timeElement->getAttribute('data-timestamp');

        return [
            'title' => htmlspecialchars_decode($title),
            'time' => $timeStamp,
            'mal_id' => $mal_id
        ];
    }

    /**
     * Get the DOM parser instance.
     *
     * @return HtmlDomParser The DOM parser instance.
     */
    private function getDom(): HtmlDomParser
    {
        $cookie = urlencode(Storage::read('live_chart_cookie.json'));
        $url = 'www.livechart.me';


        $rawData = Http::withCookies([
            'preferences.schedule' => $cookie,
        ], 'www.livechart.me')
            ->withUserAgent(fake()->msedge())
            ->get("$url/schedule")
            ->throw();

        return HtmlDomParser::str_get_html($rawData->body());
    }
}
