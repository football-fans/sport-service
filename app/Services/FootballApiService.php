<?php

namespace App\Services;

use App\Contracts\SportApiInterface;
use App\Models\Season;
use App\Models\Sport;
use App\Repositories\SportRepository;

class FootballApiService implements SportApiInterface
{
    const LEAGUE_IDS = [1,2,3,4,9,30,39,61,78,140,135,569];
    protected $sportRepository;
    protected $sport;
    public function __construct(SportRepository $sportRepository, Sport  $sport)
    {
        $this->sportRepository = $sportRepository;
        $this->sport = $sport;
    }

    public function getLeagues()
    {
        $data = $this->send('leagues', ['current' => 'true']);
        $response = data_get($data, 'response');
        if (!$response) {
            return [];
        }
        $leaguesData = [];
        $seasonsData = [];
        foreach ($response as $item) {
            $league = $item['league'];
            if (!in_array($league['id'], self::LEAGUE_IDS)) {
                continue;
            }
            $leaguesData[] = [
                'sport_id' => $this->sport->id,
                'uid' => $league['id'],
                'name' => $league['name'],
                'api_logo' => $league['logo'],
            ];
            foreach($item['seasons'] as $season) {
                if ($season['current'] === true) {
                    $seasonsData[] = [
                        'league_id' => $league['id'],
                        'year' => $season['year'],
                    ];
                }
            }
        }

        $this->sportRepository->updateOrCreateLeagues($leaguesData);
        $dbLeagues = $this->sport->leagues()->pluck('id', 'uid')->toArray();
        foreach ($seasonsData as &$seasonData) {
            $seasonData['league_id'] = $dbLeagues[$seasonData['league_id']];
        }
        $this->sportRepository->updateOrCreateSeasons($seasonsData);

        return 0;
    }

    public function getTeams()
    {
        $dbSeasons = $this->sportRepository->getCurrentSeasonsBySport($this->sport);
        foreach ($dbSeasons as $dbSeason) {
            $this->getSeasonTeams($dbSeason);
        }
    }

    public function getSeasonTeams(Season $season)
    {
        $leagueId = $season->league->uid;
        $data = $this->send('teams', ['league' => $leagueId, 'season' => $season->year]);
        $response = data_get($data, 'response');
        if (!$response) {
            return null;
        }

        $dbTeams = $this->sport->teams;
        $teamsData = [];
        $teamsIds = [];
        foreach ($response as $item) {
            $team = $item['team'];
            $dbTeam = $dbTeams->where('uid', $team['id'])->first();
            if (!$dbTeam) {
                $teamsData [] = [
                    'sport_id' => $this->sport->id,
                    'uid' => $team['id'],
                    'name' => $team['name'],
                    'code' => $team['code'],
                    'api_logo' => $team['logo']
                ];
                $teamsIds[] = $team['id'];
            }
        }

        if (!empty($teamsData)) {
            $this->sportRepository->updateOrCreateTeams($teamsData);
            $teamsIds = $this->sport->teams()->whereIn('uid', $teamsIds)->pluck('id')->toArray();
            $season->teams()->syncWithoutDetaching($teamsIds);
        }
    }

    public function getGames()
    {
        $dbSeasons = $this->sportRepository->getCurrentSeasonsBySport($this->sport);
        foreach ($dbSeasons as $dbSeason) {
            $this->getSeasonGames($dbSeason);
        }
    }

    public function getSeasonGames(Season $season)
    {
        $leagueId = $season->league->uid;
        $data = $this->send('fixtures', ['league' => $leagueId, 'season' => $season->year]);
        $response = data_get($data, 'response');
        if (!$response) {
            return null;
        }

        $dbTeams = $this->sport->teams()->get();
        $gamesData = [];
        $teamsData = [];
        $teamsIds = [];
        $gTeamsIds = [];

        foreach ($response as $item) {
            $fixture = $item['fixture'];
            $teams = $item['teams'];

            foreach ($teams as $team) {
                $dbTeam = $dbTeams->where('uid', $team['id'])->first();
                if (!$dbTeam) {
                    $teamsData[] = [
                        'sport_id' => $this->sport->id,
                        'uid' => $team['id'],
                        'name' => $team['name'],
                        'code' => null,
                        'api_logo' => $team['logo']
                    ];
                }
            }
            $gamesData[] = [
                'sport_id' => $this->sport->id,
                'season_id' => $season->id,
                'uid' => $fixture['id'],
                'api_date' => $fixture['date'],
                'venue' => json_encode($fixture['venue']),
                'long_status' => $fixture['status']['long'],
                'short_status' => $fixture['status']['short'],
                'teams' => json_encode($teams),
                'score' => json_encode($item['score']),
                'goals' => json_encode($item['goals']),
                'home_team_id' => $teams['home']['id'],
                'away_team_id' => $teams['away']['id'],
            ];
            $gTeamsIds[] = $teams['home']['id'];
            $gTeamsIds[] = $teams['away']['id'];
        }
        if (!empty($teamsData)) {
            $this->sportRepository->updateOrCreateTeams($teamsData);
            $teamsIds = $this->sport->teams()->whereIn('uid', $teamsIds)->pluck('id')->toArray();
            $season->teams()->syncWithoutDetaching($teamsIds);
        }

        $dTeamsIds = $this->sport->teams()->whereIn('uid', array_unique($gTeamsIds))->pluck('id', 'uid')->toArray();
        foreach ($gamesData as &$game) {
            $game['home_team_id'] = $dTeamsIds[$game['home_team_id']];
            $game['away_team_id'] = $dTeamsIds[$game['away_team_id']];
        }
        $this->sportRepository->updateOrCreateGames($gamesData);
    }

    public function getLiveGames()
    {
        // TODO: Implement getLiveGames() method.
    }

    public function send($endpoint = '', $params = [], $decode = true)
    {
        $curl = curl_init();
        $queryString = http_build_query($params);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://v3.football.api-sports.io/' . $endpoint . '?' . $queryString,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'x-apisports-key: ' . config('sport_api.football_api_key'), // Correct header for API key
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        if ($decode) {
            return json_decode($response,true);
        }

        return $response;
    }
}
