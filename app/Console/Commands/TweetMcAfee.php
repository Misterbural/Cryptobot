<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessTransaction;
use OwlyCode\StreamingBird\StreamReader;
use OwlyCode\StreamingBird\StreamingBird;
use thiagoalessio\TesseractOCR\TesseractOCR;

use Log;

class TweetMcAfee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:twitter_mcafee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'buy when mcafee tweet coin of the week';

    /**
    * List of currencies for trading
    *
    * @var array
    */
    protected $currencies;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $twitterConnection = new StreamingBird('7TwekgBwMzCxJLQ8HE1MLkzQr', 'e3SmsqlIfx6Cu7egIZwogGJRa8DkS9oMlu6efEr2sy0Lrv4kSL', '848614492422451201-IV52SNG9UxAvAkIB8mQOE1rzTbk9CuI', 'GutB6U0SFZhMyLJ4QH2xR7Kre5lkzOaLx7luT7QGc48fy');

        //BureauEliott 450094761
        //officialmcafee : 961445378
        $twitterConnection->createStreamReader(StreamReader::METHOD_FILTER)->setFollow(['450094761'])->consume(
            function ($tweet)
            {
                //BTC to spend
                $invest = 0.0015;
                $bittrex_transaction = new BusinessTransaction('bittrex' ,'mcafee');

                if (!array_key_exists("text", $tweet)) {
                    return false;
                }
                $text = $tweet["text"];

                if (stripos($text, "coin") === false) {
                    return false;
                }

                if (stripos($text, "week") === false && stripos($text, "day") === false) {
                    return false;
                }

                if (!array_key_exists("media", $tweet["entities"])) {
                    return false;
                }

                $img_url = $tweet["entities"]["media"][0]["media_url_https"];
                $img_path = '/home/mcafee.jpg';


                file_put_contents($img_path, file_get_contents($img_url)); 

                $ocr = new TesseractOCR($img_path);
                $text_img = $ocr->run();

                preg_match('#\(([^\)]*)\)#', $text_img, $match);
                $currency = $match[1];
                $market = 'BTC-' . $currency;

                $price_ask = $bittrex_transaction->get_market_ask_rate($market);
                if(!$price_ask) {
                    return false;
                }

                $rate_buy = round($price_ask + $price_ask * 0.1, 8);
                $quantity = round($invest / $rate_buy, 8);

                $order_id = $bittrex_transaction->buy($market, $quantity, $rate_buy);

		if(!$order_id) {
		    return false;
		}
                sleep(5);

                $order = $bittrex_transaction->get_order($order_id);

                $quantity = $order['actual_quantity'];
                if ($quantity != $order['quantity']) {
                    $bittrex_transaction->cancel($order_id);
                }

                $rate_sell = round($rate_buy + $rate_buy * 0.7, 8);
		echo "HEELLLLLOOOOO";
                $order_id = $bittrex_transaction->sell($market, $quantity, $rate_sell);
		var_dump($order_id); die();

                sleep(55);

                $order = $bittrex_transaction->get_order($order_id);
                if ($order['actual_quantity'] == $order['quantity']) {
                    return true;
                }

                $bittrex_transaction->cancel($order_id);
                $price_bid = $bittrex_transaction->get_market_bid_rate($market);

                $rate_sell = round($price_bid - $price_bid * 0.1, 8);
                $order_id = $bittrex_transaction->sell($market, $quantity, $rate_sell);
                return true;
            }
        );

    }
}
