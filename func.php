<?php

function floordec($zahl,$decimals=2){
    return floor($zahl*pow(10,$decimals))/pow(10,$decimals);
}