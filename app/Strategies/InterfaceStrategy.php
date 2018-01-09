<?php

namespace App\Strategies;

/**
* Interface InterfaceStrategy
* @package App\Strategies
*
* Stratgies interface
*/

interface InterfaceStrategy {

    /**
     * Params mandatory for the strategy
     */
    public strategy_name; //Must be uniq
    public strategy_description; //Describe strategy principles
    public strategy_params; //Params for the strategy
    public broker_name; //Name of the broker the strategy must work on

    /**
     * Buy method for this strategy.
     * This method must check if we should buy and do the buying
     * @return null;
     */
    public function buy ();
    
    /**
     * Sell method for this strategy.
     * This method must check if we should sell and do the selling
     * @return null;
     */
    public function sell ();
}
