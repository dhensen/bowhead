<?php

namespace Bowhead\Console\Commands;

use Bowhead\Traits\OHLC;
use Bowhead\Util\Poloniex\CurrencyPair;
use Illuminate\Console\Command;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Factory;

class PoloniexWebsocketCommand extends Command
{

    use OHLC;

    const POLONIEX_WEBSOCKET_URL = 'wss://api2.poloniex.com';

    protected $signature = 'bowhead:websocket_poloniex';

    protected $description = 'Connect to the poloniex websocket and store OHLC/ticker data';

    protected $instruments = [CurrencyPair::USDT_BTC];

    public function handle()
    {
        $loop = Factory::create();
        $connector = new Connector($loop);

        $connector(self::POLONIEX_WEBSOCKET_URL)
            ->then(function (WebSocket $conn) {

                // subscribe to ticker
                $conn->send('{"command": "subscribe","channel":1002}');

                $conn->on('message', function (MessageInterface $msg) use ($conn) {

                    $data = json_decode($msg->getPayload());

                    /**
                     * [0] => channelId
                     * [1] => ?
                     * [2] => [
                     *      [0] => currencyPairId ----> converted to poloniex' currencyPair string
                     *      [1] => last
                     *      [2] => lowestAsk
                     *      [3] => highestBid
                     *      [4] => percentChange
                     *      [5] => baseVolume
                     *      [6] => quoteVolume
                     *      [7] => isFrozen
                     *      [8] => 24hrHigh
                     *      [9] => 24hrLow
                     * ]
                     */

                    switch ($data[0]) {
                        case '1002':
                            // the first channel message contains a 1 in the second element
                            if ($data[1] === 1) {
                                return;
                            }
                            $data[2][0] = CurrencyPair::getInstrument($data[2][0]);
                            if (!in_array($data[2][0], $this->instruments)) {
                                return;
                            }
                            $this->printTickerData($data[2]);
                            $this->mappedMarkOHLC($data[2]);
                            break;
                        default:
                            print_r($data);
                            break;
                    }
                });

                $conn->on('close', function ($code = null, $reason = null) {
                    /** log errors here */
                    echo "Connection closed ({$code} - {$reason})\n";
                });

            }, function (\Exception $e) use ($loop) {
                /** hard error */
                echo "Could not connect: {$e->getMessage()}\n";
                $loop->stop();
            });

        $loop->run();
    }

    private function printTickerData($data)
    {
        $keys = [
            0 => 'currency_pair_id',
            1 => 'last',
            2 => 'lowest_ask',
            3 => 'highest_bid',
            4 => 'percent_change',
            5 => 'base_volume',
            6 => 'quote_volume',
            7 => 'is_frozen',
            8 => '24_hr_high',
            9 => '24_hr_low',
        ];

        print_r(array_combine($keys, $data));
    }

    /**
     * This calls OHLC::markOHLC after mapping the poloniex ticker data to bitfinex data.
     * TODO: create an interface for the OHLC::markOHLC method because this is ugly
     *
     * @see OHLC::markOHLC()
     * @param $ticker_data
     * @return bool
     */
    private function mappedMarkOHLC($ticker_data)
    {
        /**
         * Base volume is the volume in terms of the first currency pair. Quote volume is the volume in terms of the second currency pair.
         * For example, for BTC/BBR, base volume would be BTC and quote volume would be BBR.
         */

        $data = [
            7 => $ticker_data[1],
            8 => $ticker_data[6],  # use quote_volume because we use poloniex' USDT_BTC
        ];

        print_r($data);

        return $this->markOHLC($data, 1, 'BTC/USD');
    }
}