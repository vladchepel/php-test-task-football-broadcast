<?php
namespace App\Entity;

class Player
{
    private const PLAY_PLAY_STATUS = 'play';
    private const BENCH_PLAY_STATUS = 'bench';

    private int $number;
    private string $name;
    private string $playStatus;
    private int $inMinute;
    private int $outMinute;
    private int $goals;
    private int $yellowCards;
    private bool $redCard;

    public function __construct(int $number, string $name)
    {
        $this->number = $number;
        $this->name = $name;
        $this->playStatus = self::BENCH_PLAY_STATUS;
        $this->inMinute = 0;
        $this->outMinute = 0;
        $this->goals = 0;
        $this->yellowCards = 0;
        $this->redCard = 0;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInMinute(): int
    {
        return $this->inMinute;
    }

    public function getOutMinute(): int
    {
        return $this->outMinute;
    }

    public function isPlay(): bool
    {
        return $this->playStatus === self::PLAY_PLAY_STATUS;
    }

    public function getPlayTime(): int
    {
        if(!$this->outMinute) {
            return 0;
        }

        return $this->outMinute - $this->inMinute;
    }

    public function goToPlay(int $minute): void
    {
        $this->inMinute = $minute;
        $this->playStatus = self::PLAY_PLAY_STATUS;
    }

    public function goToBench(int $minute): void
    {
        $this->outMinute = $minute;
        $this->playStatus = self::BENCH_PLAY_STATUS;
    }

    public function hasGoals(): bool
    {
        return $this->goals > 0;
    }

    public function addGoal(): void
    {
        $this->goals += 1;
    }

    public function addYellowCard(): void
    {
        $this->yellowCards += 1;
    }

    public function hasYellowCards(): bool
    {
        return $this->yellowCards > 0;
    }

    public function addRedCard(): void
    {
        $this->redCard += 1;
    }

    public function hasRedCard(): bool
    {
        return $this->redCard > 0 || $this->yellowCards > 1;
    }
}
