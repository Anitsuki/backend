<?php

namespace App\Classes;

use Error;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

// TODO cache data or save in DB??
class MyAnimeList
{
    // private $mal_req_count;

    public function __construct()
    {
        Cache::add('mal_req_count', 0, now()->addSeconds(60));

        if (Cache::get('mal_req_count') >= 60) {
            throw new Error("Error: Rate Limit!");
            return;
        }
    }

    public function getAnimeById(int $id): array | null
    {
        $req = Http::get("https://api.jikan.moe/v4/anime/$id/full");

        if ($req->status() !== 200) {
            return null;
        }

        Cache::increment('mal_req_count');
        return $req->headers();
        return $req->json('data');
    }

    public function searchAnime(string $query): array | null
    {
        $req = Http::get('https://api.jikan.moe/v4/anime', [
            'q' => $query,
        ]);

        if ($req->status() !== 200) {
            return null;
        }
        // return $req->headers();
        Cache::increment('mal_req_count');
        return $req->json();
    }
}
