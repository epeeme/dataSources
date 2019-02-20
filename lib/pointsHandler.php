<?php

class PointsHandler
{
    public function getLPJSpoints($rank)
    {
        switch (true) {
        case $rank == 1 : 
            $lpjsPoints = 32; 
            break;
        case $rank == 2 : 
            $lpjsPoints = 26; 
            break;
        case ($rank == 3 || $rank == 4) : 
            $lpjsPoints = 20; 
            break;
        case ($rank >= 5 && $rank <= 8) : 
            $lpjsPoints = 14; 
            break;
        case ($rank >= 9 && $rank <= 16) : 
            $lpjsPoints = 8; 
            break;
        case ($rank >= 17 && $rank <= 32) : 
            $lpjsPoints = 4; 
            break;
        case ($rank >= 33 && $rank <= 64) : 
            $lpjsPoints = 2; 
            break;
        default : $lpjsPoints = 1; 
        }
        return $lpjsPoints;
    }
}