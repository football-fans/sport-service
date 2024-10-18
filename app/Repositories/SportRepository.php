<?php

namespace App\Repositories;

use App\Models\Game;
use App\Models\League;
use App\Models\Season;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class SportRepository
{
    public function createOrGetSportByUniqueName($uniqueName)
    {
        $sport = Sport::where('uname', $uniqueName)->first();
        if (!$sport) {
            $sport = $this->createSport($uniqueName, $uniqueName);
        }

        return $sport;
    }

    public function getCurrentSeasonsBySport(Sport $sport)
    {
        $subQuery = Season::query()
            ->select('league_id', DB::raw('MAX(year) as max_year'))
            ->groupBy('league_id');

        return Season::select('seasons.id', 'seasons.league_id', 'seasons.year')
            ->joinSub($subQuery, 'latest_seasons', function($join) {
                $join->on('seasons.league_id', '=', 'latest_seasons.league_id')
                    ->on('seasons.year', '=', 'latest_seasons.max_year');
            })
            ->with('league')
            ->get();
    }

    public function createSport($uname, $name, $logo = null, $isActive = true)
    {
        $sport = new Sport();
        $sport->uname = $uname;
        $sport->name = $name;
        $sport->logo = $logo;
        $sport->is_active = $isActive;
        $sport->save();

        return $sport;
    }
    public function updateOrCreateLeagues(array $data)
    {
        League::upsert($data, ['sport_id', 'uid'], ['name', 'api_logo']);
    }

    public function updateOrCreateSeasons(array $data)
    {
        Season::upsert($data, ['league_id', 'year']);
    }

    public function updateOrCreateTeams($data)
    {
        Team::upsert($data, ['sport_id', 'uid'], ['name', 'code', 'api_logo']);
    }

    public function updateOrCreateGames($data)
    {
        Game::upsert($data, ['sport_id', 'uid'], ['season_id', 'api_date', 'venue', 'long_status', 'short_status', 'teams', 'score', 'goals', 'home_team_id', 'away_team_id']);
    }
}


