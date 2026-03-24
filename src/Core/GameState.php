<?php


namespace Scarf\Core;
enum GameActions: string
{
   case WaitForStart = "WaitForStart";
   case WaitingForInput = 'WaitingForInput';
   case ShowResult = 'ShowResult';
}
