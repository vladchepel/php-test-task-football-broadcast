<?php

namespace App\Service;

use App\Entity\Match;
use App\Entity\Player;
use App\Entity\Stadium;
use App\Entity\Team;

class MatchBuilder
{
    public function build(string $id, array $logs): Match
    {
        $event = $this->extractStartMatchEvent($logs);

        $dateTime = $this->buildMatchDateTime($event);
        $tournament = $this->extractTournament($event);
        $stadium = $this->buildStadium($event);
        $homeTeam = $this->buildHomeTeam($event);
        $awayTeam = $this->buildAwayTeam($event);
        $match = new Match($id, $dateTime, $tournament, $stadium, $homeTeam, $awayTeam);

        $this->processLogs($match, $logs);
        $this->fillPositionsTotalTime($match);

        return $match;
    }

    private function extractStartMatchEvent(array $logs): array
    {
        foreach ($logs as $event) {
            if ($event['type'] !== 'startPeriod') {
                continue;
            }
            if (empty($event['details'])) {
                continue;
            }

            return $event;
        }

        throw new \Exception('Start match event not found.');
    }

    private function buildMatchDateTime(array $startMatchEvent): \DateTime
    {
        return new \DateTime($startMatchEvent['details']['dateTime']);
    }

    private function extractTournament(array $startMatchEvent): string
    {
        return $startMatchEvent['details']['tournament'];
    }

    private function buildStadium(array $startMatchEvent): Stadium
    {
        $stadiumInfo = $startMatchEvent['details']['stadium'];

        return new Stadium($stadiumInfo['country'], $stadiumInfo['city'], $stadiumInfo['stadium']);
    }

    private function buildHomeTeam(array $startMatchEvent): Team
    {
        return $this->buildTeam($startMatchEvent, 1);
    }

    private function buildAwayTeam(array $startMatchEvent): Team
    {
        return $this->buildTeam($startMatchEvent, 2);
    }

    private function buildTeam(array $event, string $teamNumber): Team
    {
        $teamInfo = $event['details']["team$teamNumber"];
        $players = [];
        foreach ($teamInfo['players'] as $playerInfo) {
            $players[] = new Player($playerInfo['number'], $playerInfo['name'], $playerInfo['position']);
        }

        return new Team($teamInfo['title'], $teamInfo['country'], $teamInfo['logo'], $players, $teamInfo['coach']);
    }

    private function processLogs(Match $match, array $logs): void
    {
        $period = 0;
        foreach ($logs as $event) {
            $minute = $event['time'];
            $details = $event['details'];

            switch ($event['type']) {
                case 'startPeriod':
                    $period++;

                    $players = $details['team1']['startPlayerNumbers'] ?? [];
                    if (count($players)) {
                        $this->goToPlay($match->getHomeTeam(), $players, $minute);
                    }
                    $players = $details['team2']['startPlayerNumbers'] ?? [];
                    if (count($players)) {
                        $this->goToPlay($match->getAwayTeam(), $players, $minute);
                    }
                    break;
                case 'finishPeriod':
                    if ($period === 2) {
                        $this->goToBenchAllPlayers($match->getHomeTeam(), $minute + 1);
                        $this->goToBenchAllPlayers($match->getAwayTeam(), $minute + 1);
                    }
                    break;
                case 'replacePlayer':
                    $team = $this->getTeamByName($match, $details['team']);
                    $team->getPlayer($details['inPlayerNumber'])->goToPlay($minute);
                    $team->getPlayer($details['outPlayerNumber'])->goToBench($minute);
                    break;
                case 'goal':
                    $team = $this->getTeamByName($match, $details['team']);
                    $team->addGoal();
                    $player = $team->getPlayer($details['playerNumber']);
                    $player->addGoal();
                    break;
                case 'yellowCard':
                    $team = $this->getTeamByName($match, $details['team']);
                    $player = $team->getPlayer($details['playerNumber']);
                    $player->addYellowCard();
                    break;
                case 'redCard':
                    $team = $this->getTeamByName($match, $details['team']);
                    $player = $team->getPlayer($details['playerNumber']);
                    $player->addRedCard();
                    break;
            }

            $match->addMessage(
                $this->buildMinuteString($period, $event),
                $event['description'],
                $this->buildMessageType($event)
            );
        }
    }

    private function buildMinuteString(int $period, array $event): string
    {
        $time = $event['time'];
        $periodEnd = $period == 1 ? 45 : 90;
        $additionalTime = $time - $periodEnd;

        return $additionalTime > 0 ? "$periodEnd + $additionalTime" : $time;
    }

    private function buildMessageType(array $event): string
    {
        switch ($event['type']) {
            case 'dangerousMoment':
                return Match::DANGEROUS_MOMENT_MESSAGE_TYPE;
            case 'yellowCard':
                return Match::YELLOW_CARD_MESSAGE_TYPE;
            case 'redCard':
                return Match::RED_CARD_MESSAGE_TYPE;
            case 'goal':
                return Match::GOAL_MESSAGE_TYPE;
            case 'replacePlayer':
                return Match::REPLACE_PLAYER_MESSAGE_TYPE;
            default:
                return Match::INFO_MESSAGE_TYPE;
        }
    }

    private function goToPlay(Team $team, array $players, int $minute): void
    {
        foreach ($players as $number) {
            $team->getPlayer($number)->goToPlay($minute);
        }
    }

    private function goToBenchAllPlayers(Team $team, int $minute)
    {
        foreach ($team->getPlayersOnField() as $player) {
            $player->goToBench($minute);
        }
    }

    private function getTeamByName(Match $match, string $name): Team
    {
        if ($match->getHomeTeam()->getName() === $name) {
            return $match->getHomeTeam();
        }

        if ($match->getAwayTeam()->getName() === $name) {
            return $match->getAwayTeam();
        }

        throw new \Exception(
            sprintf(
                'Team with name "%s" not found. Available teams: "%s" and "%s".',
                $name,
                $match->getHomeTeam()->getName(),
                $match->getAwayTeam()->getName()
            )
        );
    }

    private function getTeamTotalTimeByPositions(Team $team): array
    {
        $result = [];

        foreach ($team->getPlayers() as $player) {
            $position = $player->getPosition();
            $playerPlayTime = $player->getPlayTime();

            if (!isset($result[$position])) {
                $result[$position] = 0;
            }

            $result[$position] += $playerPlayTime;
        }

        return $result;
    }

    private function fillPositionsTotalTime(Match $match): void
    {
        $homePositionsTotalTime = $this->getTeamTotalTimeByPositions($match->getHomeTeam());
        $awayPositionsTotalTime = $this->getTeamTotalTimeByPositions($match->getAwayTeam());

        foreach ($homePositionsTotalTime as $position => $value) {
            $awayPositionsTotalTime[$position] += $value;
        }

        $match->setPositionsTotalTime($awayPositionsTotalTime);
    }
}
