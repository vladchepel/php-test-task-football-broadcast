<?php

namespace App\Entity;

class Match
{
    public const INFO_MESSAGE_TYPE             = 'info';
    public const DANGEROUS_MOMENT_MESSAGE_TYPE = 'dangerousMoment';
    public const GOAL_MESSAGE_TYPE             = 'goal';
    public const YELLOW_CARD_MESSAGE_TYPE      = 'yellowCard';
    public const RED_CARD_MESSAGE_TYPE         = 'redCard';
    public const REPLACE_PLAYER_MESSAGE_TYPE   = 'replacePlayer';

    private const MESSAGE_TYPES = [
        self::INFO_MESSAGE_TYPE,
        self::DANGEROUS_MOMENT_MESSAGE_TYPE,
        self::GOAL_MESSAGE_TYPE,
        self::YELLOW_CARD_MESSAGE_TYPE,
        self::RED_CARD_MESSAGE_TYPE,
        self::REPLACE_PLAYER_MESSAGE_TYPE,
    ];

    private const POSITIONS_FULL_NAME = [
        'В' => 'Вратарь',
        'З' => 'Защитник',
        'П' => 'Полузащитник',
        'Н' => 'Нападающий'
    ];

    private string $id;
    private \DateTime $date;
    private string $tournament;
    private Stadium $stadium;
    private Team $homeTeam;
    private Team $awayTeam;
    private array $messages;
    private array $positionsTotalTime;

    public function __construct(string $id, \DateTime $date, string $tournament, Stadium $stadium, Team $homeTeam, Team $awayTeam)
    {
        $this->id = $id;
        $this->date = $date;
        $this->tournament = $tournament;
        $this->stadium = $stadium;
        $this->homeTeam = $homeTeam;
        $this->awayTeam = $awayTeam;
        $this->messages = [];
        $this->positionsTotalTime = [];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function getTournament(): string
    {
        return $this->tournament;
    }

    public function getStadium(): Stadium
    {
        return $this->stadium;
    }

    public function getHomeTeam(): Team
    {
        return $this->homeTeam;
    }

    public function getAwayTeam(): Team
    {
        return $this->awayTeam;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function addMessage(string $minute, string $text, string $type): void
    {
        $this->assertCorrectType($type);

        $this->messages[] = [
            'minute' => $minute,
            'text'   => $text,
            'type'   => $type,
        ];
    }

    public function getPositionsTotalTime(): array
    {
        return $this->positionsTotalTime;
    }

    public function setPositionsTotalTime(array $array): void
    {
        $this->positionsTotalTime = $array;
    }

    public function getPositionFullName(string $key): string
    {
        return self::POSITIONS_FULL_NAME[$key];
    }

    private function assertCorrectType(string $type): void
    {
        if (!in_array($type, self::MESSAGE_TYPES, true)) {
            throw new \Exception(
                sprintf(
                    'Message type "%s" not supported. Available types: "%s".',
                    $type,
                    implode('", "', self::MESSAGE_TYPES)
                )
            );
        }
    }
}
