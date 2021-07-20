<?php

namespace Corals\Modules\Marketplace\Services;

use Corals\Foundation\Services\BaseServiceClass;
use Corals\Modules\Marketplace\Classes\Marketplace;
use Corals\Modules\Marketplace\Models\Tag;
use Corals\Modules\Marketplace\Traits\DownloadableController;
use Corals\Modules\Utility\Traits\Gallery\ServiceHasGalleryTrait;
use Illuminate\Support\Arr;

class ProductService extends BaseServiceClass
{
    use DownloadableController, ServiceHasGalleryTrait;

    public $sku_attributes = [
        'regular_price',
        'sale_price',
        'code',
        'inventory',
        'inventory_value',
        'allowed_quantity'
    ];

    public $skipParameters = [
        'shipping_rate',
        'variation_options',
        'create_gateway_product',
        'tax_classes',
        'categories',
        'tags',
        'posts',
        'private_content_pages',
        'downloads_enabled',
        'downloads',
        'cleared_downloads',
        'external',
        'price_per_classification',
        'attribute_sets',
        'set_attribute_options',
        'gallery_new',
        'gallery_deleted',
        'gallery_favorite',
    ];

    public function getRequestData($request)
    {
        $excludedRequestParams = array_merge($this->skipParameters, $this->sku_attributes);

        if (is_array($request)) {
            $data = \Arr::except($request, $excludedRequestParams);
        } else {
            $data = $request->except($excludedRequestParams);
        }

        $data = \Store::setStoreData($data);

        $data = $this->setShippingData($data);

        return $data;
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function setShippingData($data)
    {
        if (!isset($data['shipping']['enabled'])) {
            $data['shipping']['enabled'] = 0;
        }

        return $data;
    }

    public function postStore($request, &$additionalData)
    {
        $this->handleGalleryInputs($request, $this->model);
    }

    protected function postStoreUpdate($request, $additionalData)
    {
        $product = $this->model;

        if ($product->type == "simple") {
            $sku_data = $request->only(array_merge($this->sku_attributes, ['status']));
            $sku = $product->sku->first();

            if ($sku) {
                $sku->update($sku_data);
            } else {
                $sku = $product->sku()->create($sku_data);
            }

            $this->handleAttributeSetsOptions($request, $product, $sku);
        } else {
            $this->handleAttributeSetsOptions($request, $product);
        }

        $attributes = [];

        if ($product->type == "variable") {
            foreach ($request->get('variation_options', []) as $option) {
                $attributes[$option] = [
                    'sku_level' => true,
                ];
            }
        }

        $product->attributes()->sync($attributes);
        $product->attributeSets()->sync($request->get('attribute_sets', []));
        $product->categories()->sync($request->get('categories', []));
        $product->tax_classes()->sync($request->get('tax_classes', []));

        $tags = $this->getTags($request);

        $product->tags()->sync($tags);

        $product->posts()->sync($request->get('posts', []));

        $this->handleDownloads($request, $product);

        $product->indexRecord();

        // handle shipping rates
        if ($request->input('shipping.shipping_option') == 'flat_rate_prices') {
            $shipping_rates = $request->get('shipping_rate', []);

            $product->shippingRates()->delete();

            foreach ($shipping_rates as $rate) {
                $product->shippingRates()->create([
                    'name' => $rate['name'],
                    'rate' => $rate['one_item_price'],
                    'country' => $rate['country'],
                    'store_id' => $product->store_id,
                    'priority' => 10,
                    'shipping_method' => Arr::get($rate, 'shipping_method'),
                    'properties' => Arr::only($rate, [
                        'shipping_provider',
                        'additional_item_price'
                    ]),
                ]);
            }
        } else {
            $product->shippingRates()->delete();
        }
    }

    /**
     * @param $request
     * @return array
     */
    protected function getTags($request)
    {
        $tags = [];

        $requestTags = $request->get('tags', []);

        foreach ($requestTags as $tag) {
            if (is_numeric($tag)) {
                array_push($tags, $tag);
            } else {
                try {
                    $newTag = Tag::create([
                        'name' => $tag,
                        'slug' => \Str::slug($tag)
                    ]);

                    array_push($tags, $newTag->id);
                } catch (\Exception $exception) {
                    continue;
                }
            }
        }

        return $tags;
    }

    /**
     * @param $request
     * @param $model
     * @throws \Exception
     */
    public function destroy($request, $model)
    {
        $product = $model;

        $gateways = \Payments::getAvailableGateways();

        foreach ($gateways as $gateway => $gateway_title) {
            $Marketplace = new Marketplace($gateway);
            if (!$Marketplace->gateway->getConfig('manage_remote_product')) {
                continue;
            }

            $Marketplace->deleteProduct($product);
            $product->setGatewayStatus($this->gateway->getName(), 'DELETED', null);
        }

        $product->clearMediaCollection('product-downloads');
        $product->clearMediaCollection($product->galleryMediaCollection);

        $product->delete();
        $product->unIndexRecord();
    }

    protected function handleAttributeSetsOptions($request, $product, $sku = null)
    {
        if (is_null($sku)) {
            $sku = $product;
        }

        $sku->options()->delete();

        $options = [];

        $requestParameter = 'set_attribute_options';

        $requestHasOptions = $request->has($requestParameter);

        if ($requestHasOptions) {
            foreach ($request->get($requestParameter, []) ?? [] as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $value_option) {
                        $options[] = [
                            'attribute_id' => $key,
                            'value' => $value_option
                        ];
                    }
                } else {
                    $options[] = [
                        'attribute_id' => $key,
                        'value' => $value
                    ];
                }
            }

            $sku->options()->createMany($options);
        }
    }
}
