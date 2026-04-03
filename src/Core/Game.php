<?php

namespace Scarf\Core;

use Scarf\Core\GameAction;
use Scarf\Shared\IGame;
use Scarf\Shared\IRepo;

final class Game implements IGame
{
    private IRepo $_repo;

    public function __construct(IRepo $repo)
    {
        $this->_repo = $repo;
    }

    public function InitGame(): array
    {
        $map = [
            '#######G#####',
            '###     #####',
            '### #########',
            '##  #########',
            '##          #',
            '##########  #',
            '#           #',
            '####### #####',
            '#           #',
            '# R #########',
        ];

        return $this->_repo->AddGame($map);
    }

    public function TrySolution(int $id, array $actions): array
    {
        $game = $this->_repo->GetGame($id);
        if ($game === []) {
            return ['result' => 'NotFound'];
        }


        $startGoalMap = $this->MapToDictionary($game['map']);
        $posStart = $startGoalMap['posStart'];
        $posGoal = $startGoalMap['posGoal'];
        $map = $startGoalMap['map'];
        $resMap = $startGoalMap['map'];

        if ($posStart === 'e,e' || $posGoal === 'e,e') {
            return ['result' => 'bad map'];
        }

        [$x, $y] = array_map('intval', explode(',', $posStart));

        foreach ($actions as $action) {
            if (is_string($action)) {
                $action = GameAction::tryFrom($action);
            }

            if (!$action instanceof GameAction) {
                return ['result' => 'bad actions'];
            }

            $nextX = $x;
            $nextY = $y;

            if ($action === GameAction::Up) {
                $nextY--;
            } elseif ($action === GameAction::Down) {
                $nextY++;
            } elseif ($action === GameAction::Left) {
                $nextX--;
            } elseif ($action === GameAction::Right) {
                $nextX++;
            }

            $nextPos = "$nextX,$nextY";
            if (!isset($map[$nextPos]) || $map[$nextPos] === '#') {
                return ['result' => 'incorrect', 'resMap' => $resMap];
            } else {
                $resMap["$x,$y"] = '+';
                $resMap[$nextPos] = 'R';
            }

            $x = $nextX;
            $y = $nextY;
        }

        if ("$x,$y" !== $posGoal) {
            return ['result' => 'incorrect', 'resMap' => $resMap];
        }

        $this->_repo->UpdateGame($id, $actions);

        return [
            'result' => 'correct',
            'resMap' => $resMap,
        ];
    }

    private function MapToDictionary(array|string $map): array
    {
        if (is_string($map)) {
            $map = explode("\n", str_replace('+', ' ', $map));
        }

        $mapDict = [];
        $posStart = 'e,e';
        $posGoal = 'e,e';
        foreach ($map as $y => $row) {
            foreach (str_split($row) as $x => $tile) {
                $key = "$x,$y";
                $mapDict[$key] = $tile;
                if ($tile === 'R') {
                    $posStart = $key;
                } elseif ($tile === 'G') {
                    $posGoal = $key;
                }
            }
        }

        return [
            'map' => $mapDict,
            'posStart' => $posStart,
            'posGoal' => $posGoal,
        ];
    }
}
