<?php

namespace App\Contracts;

use App\Models\Season;

interface SportApiInterface
{
    public function getLeagues();
    public function getTeams();

    public function getSeasonTeams(Season $season);

    public function getGames();

    public function getSeasonGames(Season $season);

    public function getLiveGames();

}
