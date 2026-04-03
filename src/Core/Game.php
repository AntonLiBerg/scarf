<?php

namespace Scarf\Core;

use Scarf\Core\GameAction;
use Scarf\Shared\IGame;
use Scarf\Shared\IRepo;
use Scarf\Shared\UAscii;

final class Game implements IGame
{
    private const MAPS = [
        [
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
        ],
        [
            '#############',
            '###########G#',
            '#########   #',
            '######### ###',
            '#####     ###',
            '##### #######',
            '#     #######',
            '# ###########',
            '#R###########',
            '#############',
        ],
        [
            '#############',
            '#G        ###',
            '######### ###',
            '###       ###',
            '### #########',
            '### #########',
            '###     #####',
            '####### #####',
            '#######    R#',
            '#############',
        ],
        [
            '#############',
            '#R    #######',
            '##### #######',
            '##### #######',
            '#####     ###',
            '######### ###',
            '######### ###',
            '######### ###',
            '#########  G#',
            '#############',
        ],
        [
            '#############',
            '#######    R#',
            '####### #####',
            '###     #####',
            '### #########',
            '###       ###',
            '######### ###',
            '#         ###',
            '#G###########',
            '#############',
        ],
    ];

    private const POSITION_NOT_FOUND = 'e,e';
    private const RESULT_NOT_FOUND = 'NotFound';
    private const RESULT_BAD_MAP = 'bad map';
    private const RESULT_BAD_ACTIONS = 'bad actions';
    private const RESULT_INCORRECT = 'incorrect';
    private const RESULT_CORRECT = 'correct';

    private IRepo $_repo;

    public function __construct(IRepo $repo)
    {
        $this->_repo = $repo;
    }

    public function InitGame(): array
    {
        return $this->_repo->AddGame(self::MAPS[array_rand(self::MAPS)]);
    }

    public function TrySolution(int $id, array $actions): array
    {
        $game = $this->_repo->GetGame($id);
        if ($game === []) {
            return ['result' => self::RESULT_NOT_FOUND];
        }

        $startGoalMap = $this->MapToDictionary($game['map']);
        $posStart = $startGoalMap['posStart'];
        $posGoal = $startGoalMap['posGoal'];
        $map = $startGoalMap['map'];
        $resMap = $startGoalMap['map'];

        if ($posStart === self::POSITION_NOT_FOUND || $posGoal === self::POSITION_NOT_FOUND) {
            return ['result' => self::RESULT_BAD_MAP];
        }

        [$x, $y] = array_map('intval', explode(',', $posStart));

        foreach ($actions as $action) {
            if (is_string($action)) {
                $action = GameAction::tryFrom($action);
            }

            if (!$action instanceof GameAction) {
                return ['result' => self::RESULT_BAD_ACTIONS];
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
            if (!isset($map[$nextPos]) || $map[$nextPos] === UAscii::WALL) {
                return ['result' => self::RESULT_INCORRECT, 'resMap' => $resMap];
            } else {
                $resMap["$x,$y"] = UAscii::PLUS;
                $resMap[$nextPos] = UAscii::ROBOT;
            }

            $x = $nextX;
            $y = $nextY;
        }

        if ("$x,$y" !== $posGoal) {
            return ['result' => self::RESULT_INCORRECT, 'resMap' => $resMap];
        }

        $this->_repo->UpdateGame($id, $actions);

        return [
            'result' => self::RESULT_CORRECT,
            'resMap' => $resMap,
        ];
    }

    private function MapToDictionary(array|string $map): array
    {
        if (is_string($map)) {
            $map = explode(UAscii::NEWLINE, str_replace(UAscii::PLUS, UAscii::SPACE, $map));
        }

        $mapDict = [];
        $posStart = self::POSITION_NOT_FOUND;
        $posGoal = self::POSITION_NOT_FOUND;
        foreach ($map as $y => $row) {
            foreach (str_split($row) as $x => $tile) {
                $key = "$x,$y";
                $mapDict[$key] = $tile;
                if ($tile === UAscii::ROBOT) {
                    $posStart = $key;
                } elseif ($tile === UAscii::GOAL) {
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
