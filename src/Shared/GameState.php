<?php

namespace Scarf\Shared;

enum GameState: string
{
    case WaitingForInput = 'WaitingForInput';
    case Finished = 'Finished';
}
