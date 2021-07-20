<?php

namespace Corals\Modules\Marketplace\Classes\Shippings;

use Corals\Modules\Marketplace\Contracts\ShippingContract;

/**
 * Class Fixed.
 */
class FlatRate implements ShippingContract
{
    public $rate;
    public $product_rates;
    private $rates_processed;
    public $description;
    public $name;
    public $rule_id;
    public $properties;


    /**
     * FlatRate constructor.
     * @param array $options
     */
    public function __construct($options = [])
    {
    }

    public function methodClass()
    {
        return "FlatRate";
    }

    /**
     * @param array $options
     * @return mixed|void
     */
    public function initialize($options = [])
    {


        $this->product_rates = $options['product_rates'] ?? [];

        if ($this->product_rates) {

            \Logger($this->product_rates);

            $this->rates_processed = $this->processProductRates($this->product_rates);

            \Logger($this->rates_processed);

        } else {
            $this->product_rates = [];

        }

        $this->rate = $options['rate'] ?? 0;
        $this->name = $options['name'] ?? '';
        $this->rule_id = $options['id'] ?? '';
        $this->description = $options['description'] ?? '';
        $this->properties = $options['properties'] ?? [];

    }

    /**
     * @param $to_address
     * @param $shippable_items
     * @param null $user
     * @return mixed|string
     */
    public function getAvailableShippingRates($to_address, $shippable_items, $user = null)
    {


        $available_rates = [];


        foreach ($shippable_items as $cart_item) {

            if (array_key_exists($cart_item->getHash(), $this->product_rates)) {
                $rule_id = $this->product_rates[$cart_item->getHash()]['id'];
                $rule_name = $this->product_rates[$cart_item->getHash()]['properties']['shipping_provider'];
                $amount = $this->rates_processed[$cart_item->getHash()]['first_item'];
                $additionalItemPrice = $this->rates_processed[$cart_item->getHash()]['additional'];
            } else {
                $rule_id = $this->rule_id;
                $rule_name = $this->name;
                $amount = $this->rate;
                $additionalItemPrice = data_get($this->properties, 'additional_item_price', $amount);
            }
            $key = $this->methodClass() . '|' . $rule_name . '|' . $rule_id . "|" . $cart_item->getHash();


            if ($cart_item->qty > 1) {
                $additionalItemPrice *= ($cart_item->qty - 1);
            } else {
                $additionalItemPrice = 0;
            }
            $amount += $additionalItemPrice;


            $available_rates[$key] = [
                'provider' => $rule_name,
                'shipping_method' => $this->methodClass(),
                'service' => '',
                'currency' => \Payments::admin_currency_code(),
                'amount' => $amount,
                'qty' => $cart_item->qty,
                'shipping_rule_id' => $rule_id,
                'estimated_days' => '',
                'description' => $this->description,
                'product_id' => $cart_item->id->product->id,
                'product_name' => $cart_item->id->product->name,
                'cart_ref_id' => $cart_item->getHash(),

            ];

        }

        return $available_rates;
    }


    public function createShippingTransaction($shipping_reference)
    {

        $shipping = [];

        $shipping['status'] = 'pending';
        $shipping['label_url'] = '';
        $shipping['tracking_number'] = '';

        return $shipping;
    }


    public function track($tracking_details)
    {
        try {
            $tracking_status = [];
            return $tracking_status;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    private function processProductRates($rates)
    {

        $rate_matrix = [];

        uasort($rates, function ($a, $b) {
            return $a['rate'] <=> $b['rate'];
        });

        $rates = array_reverse($rates);

        $highest_rate = true;
        foreach ($rates as $item_hash => $rate) {

            $additonal_item_price = data_get($rate['properties'], 'additional_item_price', $rate['rate']);

            if ($highest_rate) {
                $rate_matrix[$item_hash]['first_item'] = $rate['rate'];
                $rate_matrix[$item_hash]['additional'] = $additonal_item_price;
                $highest_rate = false;
            } else {
                $rate_matrix[$item_hash]['first_item'] = $additonal_item_price;
                $rate_matrix[$item_hash]['additional'] = $additonal_item_price;
            }

        }

        return $rate_matrix;
    }

}
