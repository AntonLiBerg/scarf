<?php


namespace Scarf\Core;

enum GameState: string
{
    case WaitForStart = "WaitForStart";
    case WaitingForInput = 'WaitingForInput';
    case ShowResult = 'ShowResult';
}
