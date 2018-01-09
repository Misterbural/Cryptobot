<?php

namespace App\Business;

/**
* Class Candle
* @package App\Business
*
* Make buy or sell operations for a broker and a currency
*
* Write transactions in database and call broker api wrapper to execute them
*/

use App\Brokers;

class BusinessSupportAndResistance {

    /**
     * Constructeur
     */
    public function __construct ()
    {
    }

    /**
     * Fonction permettant de trouver les supports pour une collection de candles
     * @param array $candles : Candles que l'on veux analyser
     * @param int $candles_blocks_size : Taille des blocks de candles à analyser pour trouver les piques de valeurs (min 3). Toujours impair.
     * @param int $distance_tolerance : Pourcentage de tolérance pour considérer qu'une candle tape sur un support
     * @return mixed bool | array : Un tableau avec les supports et la force de chaque support, trié par force. False si erreur
     */
    public function get_supports ($candles, $candles_blocks_size, $distance_tolerance)
    {
        //Si candles_blocks_size pas impair, on kill
        if ($candles_blocks_size % 2 == 0)
        {
            return false;
        }

        //On récupère les pics de valeures basses
        $peaks = [];
        for ($i = 0; $i < count($candles); $i++)
        {
            if (!isset($candles[$i + ($candles_blocks_size - 1)]))
            {
                break;
            }

            $block = [];
            for ($j = 0; $j < $candles_blocks_size; $j++)
            {
                $block[] = $candles[$i + $j];
            }

            //On check si le block est un peak
            $is_peak = true;
            $index_middle_candle = ($candles_blocks_size - 1) / 2;

            for ($j = 0; $j < count($block); $j++)
            {
                if ($j == 0)
                {
                    continue;
                }
                
                if ($j <= $index_middle_candle)
                {
                    if ($block[$j]->close_price >= $block[$j - 1]->close_price)
                    {
                        $is_peak = false;
                    }
                }
                else
                {
                    if ($block[$j]->close_price <= $block[$j - 1]->close_price)
                    {
                        $is_peak = false;
                    }
                }
            }

            if (!$is_peak)
            {
                continue;
            }

            $peaks[] = $block;
        }
    
        return $peaks;
    }

    /**
     * Trouve les turning point minima pour un jeu de candles
     * @param array $candles : Collection de candles à analyser dans l'ordre de la date
     * @return array : Un tableau avec les candles qui sont des minima
     */
    public function get_minimas ($candles)
    {
        $minimas = [];

        for ($i = 0; $i < count($candles); $i++)
        {
            if (!isset($candles[$i - 2]))
            {
                continue;
            }

            if ($candles[$i - 2]->close_price > $candles[$i - 1]->close_price && $candles[$i - 1]->close_price < $candles[$i]->close_price)
            {
                $minimas[] = $candles[$i - 1];
            }
        }

        return $minimas;
    }

    /**
     * Filtre des minimas selon plusieurs paramètres
     * @param array $minimas : Tableau contenant les minimas sous forme de candles
     * @param \DateInterval $gap_between_minimas : Période minimum entre deux minimas (en cas de conflit, on conserve le plus bas)
     * @return array : Tableau contenant les minimas une fois les minimas non utiles retirés
     */
    public function filter_minimas ($minimas, \DateInterval $gap_between_minimas)
    {
        $indexes_minimas_to_keep = [];
        for ($i = 0; $i < count($minimas); $i++)
        {
            $current_minima_index = $i;

            $indexes_minimas_in_conflicts = [];

            //TQ on pas une période min entre deux point ok, on prend la période suivante & on répète
            $gap_is_ok = false;
            while (!$gap_is_ok)
            {
                //On ajoute le minima à la liste des conflits
                $indexes_minimas_in_conflicts[] = $current_minima_index;

                if (!isset($minimas[$current_minima_index + 1]))
                {
                    break;
                }

                $current_minima_open_time = new \DateTime($minimas[$current_minima_index]->open_time);
                $next_minima_open_time = new \DateTime($minimas[$current_minima_index + 1]->open_time);

                //On vérifie si la diff entre current_minima_open_time & next_minima_open_time est bien sup à la période min entre deux minimas
                $next_minima_open_time->sub($gap_between_minimas);
                if ($next_minima_open_time <= $current_minima_open_time)
                {
                    $current_minima_index ++;
                }
                else
                {
                    $gap_is_ok = true;
                }
            }

            //On cherche le plus petit minima
            $index_minima_to_keep = $indexes_minimas_in_conflicts[0];
            foreach ($indexes_minimas_in_conflicts as $index_minima)
            {
                if ($minimas[$index_minima]->close_price < $minimas[$index_minima_to_keep]->close_price)
                {
                    $index_minima_to_keep = $index_minima;
                }
            }
            $indexes_minimas_to_keep[] = $index_minima_to_keep;

            $i = $current_minima_index;
        }

        foreach ($minimas as $key => $minima)
        {
            if (!in_array($key, $indexes_minimas_to_keep))
            {
                unset($minimas[$key]);
            }
        }

        return $minimas;
    }
}
