<?php

namespace Corals\Modules\Marketplace\Http\Controllers;

use Corals\Foundation\Http\Controllers\BaseController;
use Corals\Modules\Marketplace\Http\Requests\SKUBulkRequest;
use Corals\Modules\Marketplace\Http\Requests\SKURequest;
use Corals\Modules\Marketplace\Models\Attribute;
use Corals\Modules\Marketplace\Models\Product;
use Corals\Modules\Marketplace\Models\SKU;
use Corals\Modules\Marketplace\Services\SKUService;
use Faker\Provider\Uuid;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SKUBulkController extends BaseController
{
    protected $SKUService;

    public function __construct(SKUService $SKUService)
    {
        $this->SKUService = $SKUService;

        $this->resource_url = route(
            config('marketplace.models.sku.resource_route'),
            ['product' => request()->route('product') ?: '_']
        );

        $this->title = 'Marketplace::module.sku.title';
        $this->title_singular = 'Marketplace::module.sku.title_singular';

        parent::__construct();
    }

    protected function setTitle($product)
    {
        $this->setViewSharedData([
            'title' => trans('Marketplace::labels.sku.index_title', ['name' => $product->name, 'title' => $this->title])
        ]);
    }

    /**
     * @param SKURequest $request
     * @param Product $product
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getBulkGenerateForm(SKUBulkRequest $request, Product $product)
    {
        $this->setTitle($product);
        return view('Marketplace::sku.bulk.bulk_form', compact('product'));
    }

    /**
     * @param Request $request
     * @param Product $product
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function generateSKUs(SKUBulkRequest $request, Product $product)
    {
        $this->validate($request, ['options' => 'required']);

        $bulkCode = $this->generate($request->all(), $product);

        $generate_option = $request->get('generate_option', 'apply_unique') ?? 'apply_unique';

        if ($generate_option === 'skip') {
            return redirectTo($this->resource_url);
        }

        return redirectTo($this->resource_url . '/bulk-edit?generate_option=' . $generate_option . '&bulk=' . $bulkCode);
    }

    public function getBulkEditForm(Request $request, Product $product)
    {
        $this->setTitle($product);

        $generate_option = $request->get('generate_option', 'apply_unique') ?? 'apply_unique';

        $isFromBulk = !!$request->get('bulk');

        if ($isFromBulk) {
            $skus = $product
                ->sku()
                ->whereRaw("JSON_EXTRACT(marketplace_sku.properties,'$.bulk_code') = ?", [$request->get('bulk')])
                ->get();
        } else {
            $skus = $product->sku;
        }

        return view('Marketplace::sku.bulk.bulk_edit', compact('product', 'generate_option', 'skus', 'isFromBulk'));
    }

    /**
     * @param $data
     * @param $product
     * @return string
     */
    protected function generate($data, $product)
    {
        $attributes = Attribute::query()
            ->whereIn('id', array_keys(data_get($data, 'options')))
            ->orderBy('required', 'desc')
            ->get();

        $result = $this->get_all_options_combinations(data_get($data, 'options'));

        $hasSKUs = !!$product->sku()->count();

        $bulkCode = Uuid::uuid();

        $skuService = new SKUService();

        foreach ($result as $row) {
            $options = [];
            $skuCode = [$product->product_code];

            foreach ($row as $option) {
                [$optionId, $value] = explode(':', $option);

                $attribute = $attributes->where('id', $optionId)->first();

                switch ($attribute->type) {
                    case 'checkboxes':
                    case 'multi_values':
                        $options[$optionId] = [$value];
                        break;
                    default:
                        $options[$optionId] = $value;
                }

                switch ($attribute->type) {
                    case 'checkboxes':
                    case 'multi_values':
                    case 'select':
                    case 'radio':
                        $attributeOption = $attribute->options()->where('marketplace_attribute_options.id',
                            $value)->first();

                        $skuCode[] = $attributeOption->option_value;
                        break;
                    default:
                        $skuCode[] = $value;
                }
            }

            $code = strtolower(join('-', $skuCode));

            if ($hasSKUs && $product->sku()->where('code', $code)->exists()) {
                $skuCode[] = Str::random(3);
                $code = strtolower(join('-', $skuCode));
            }

            $data = [
                'code' => $code,
                'regular_price' => 0,
                'status' => 'inactive',
                'product_id' => $product->id,
                'shipping' => $product->shipping,
                'inventory' => 'bucket',
                'inventory_value' => 'in_stock',
                'properties' => [
                    'bulk_code' => $bulkCode
                ],
                'options' => $options
            ];

            $sku = $skuService->createSKUFromBulk($data);
        }

        return $bulkCode;
    }

    /**
     * @param $arrays
     * @return array|array[]
     */
    protected function get_all_options_combinations($arrays)
    {
        $result = [[]];

        foreach ($arrays as $property => $property_values) {
            $tmp = [];

            foreach ($result as $result_item) {
                foreach (Arr::wrap($property_values) as $property_value) {
                    $tmp[] = array_merge($result_item, [$property . ':' . $property_value]);
                }
            }

            $result = $tmp;
        }

        return $result;
    }

    public function postBulkEdit(SKUBulkRequest $request, Product $product)
    {
        try {
            $skus = $request->get('sku', []) ?? [];

            $generate_option = $request->get('generate_option', 'apply_unique') ?? 'apply_unique';

            if ($generate_option === 'apply_single') {
                $commonData = $request->only(['regular_price', 'sale_price', 'inventory_value', 'inventory', 'status']);
            } else {
                $commonData = [];
            }

            $image = null;

            foreach ($skus as $id => $skuData) {
                $sku = SKU::query()->find($id);

                if (data_get($skuData, 'delete') == 1) {
                    $this->SKUService->destroy($request, $sku);
                    continue;
                }

                $skuData = array_merge($commonData, $skuData);

                if (!$sku) {
                    continue;
                }

                $sku->update(Arr::only($skuData,
                    ['code', 'regular_price', 'sale_price', 'inventory_value', 'inventory', 'shipping', 'status']
                ));

                if ($request->has("sku.$id.clear") || $request->hasFile("sku.$id.image")) {
                    $sku->clearMediaCollection('marketplace-sku-image');
                }

                if ($request->hasFile("sku.$id.image") && !$request->has("sku.$id.clear")) {
                    $sku->addMedia($request->file("sku.$id.image"))
                        ->withCustomProperties(['root' => 'user_' . user()->hashed_id])
                        ->toMediaCollection('marketplace-sku-image');
                }

                if ($generate_option === 'apply_single' && $request->hasFile("image")) {
                    $sku->clearMediaCollection('marketplace-sku-image');

                    if (!$image) {
                        $image = $sku->addMedia($request->file("image"))
                            ->withCustomProperties(['root' => 'user_' . user()->hashed_id])
                            ->toMediaCollection('marketplace-sku-image');
                    } else {
                        $sku->addMedia($image->getPath(), $image->disk)
                            ->preservingOriginal()
                            ->setName($image->name)
                            ->withCustomProperties($image->custom_properties ?? [])
                            ->toMediaCollection('marketplace-sku-image');
                    }
                }
            }

            $message = [
                'level' => 'success',
                'message' => trans('Corals::messages.success.updated', ['item' => $this->title]),
                'action' => 'redirectTo',
                'url' => url($this->resource_url)
            ];
        } catch (\Exception $exception) {
            log_exception($exception, SKUBulkController::class, 'postBulkEdit');
            $message = ['level' => 'error', 'message' => $exception->getMessage()];
        }

        return response()->json($message);
    }
}
