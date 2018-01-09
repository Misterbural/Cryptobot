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
use App\Models\ConditionnalOrder;

class BusinessConditionnalOrder
{
    /**
     * Constructor
     * @param int $id_conditionnal_order : Id of the conditial order we want to work on
     */
    public function __construct ($id_conditionnal_order)
    {
        if (!$conditionnal_order = new ConditionnalOrder($id_conditionnal_order))
        {
        }

    }
}
