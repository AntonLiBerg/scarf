<?php


namespace Scarf\Core;

enum GameState: string
{
    case WaitingForInput = 'WaitingForInput';
    case Finished = 'Finished';
}
