{!! Form::open( ['url' => url($urlPrefix.'checkout/step/shipping-method'),'method'=>'POST','class'=>'ajax-form','id'=>'checkoutForm']) !!}
<div class="row">
    <div class="col-md-8 shipping-options">
        <h4>@lang('Marketplace::labels.settings.shipping.select_method')</h4>
        @if($shipping_methods)
            @foreach($shipping_methods as $store_id => $store_shipping_methods)
                @php


                    $store = \Corals\Modules\Marketplace\Models\Store::find($store_id);
                    $shippingOptions = \Illuminate\Support\Arr::pluck($store_shipping_methods['options']??[],'label','key');
                    if($shippingOptions){
                        $shippable_items[$store_id] = [];
                    }
                    $shippingProducts = $store_shipping_methods['products']??[];
                @endphp

                @if($shippingOptions || $shippingProducts)
                    <h5>{{ $store->name }}</h5>
                    @if($shippingOptions)
                        {!! CoralsForm::radio('selected_shipping_methods['.$store_id.'][main]', trans( 'Marketplace::labels.checkout.shipping_options',['products'=>implode(',',$shippable_items[$store_id]??[])]), true, $shippingOptions,array_keys($shippingOptions)[0]??null) !!}
                    @endif
                    @foreach($shippingProducts as $key => $product)
                        @php
                            $rate = current($product);
                            $options = \Illuminate\Support\Arr::pluck($product,'label','key');
                        @endphp
                        @if(empty($options))
                            <div class="form-group">
                                <span data-name="selected_shipping_methods[{{$store_id}}][{{$key}}]"></span>
                                <p class="d-block label label-warning badge badge-warning p-15" data-><i
                                            class="fa fa-info-circle"></i> @lang('Marketplace::labels.settings.shipping.no_available_shipping',['store'=>$store->name,'products'=>implode(',',$shippable_items[$store_id]??[])])
                                </p>
                            </div>
                        @else
                            {!! CoralsForm::radio('selected_shipping_methods['.$store_id.']['.$key.']', trans( 'Marketplace::labels.checkout.shipping_options',['products'=>$rate['product_name']]), true, $options, array_keys($options)[0]??null) !!}
                        @endif
                    @endforeach
                @endif
                @if(!empty($shippable_items[$store_id]))
                    @foreach($shippable_items[$store_id] as $shippable_item_hashed_id =>$shippable_item_name)
                        <div class="form-group">

                            <span data-name="selected_shipping_methods[{{$store_id}}][{{$shippable_item_hashed_id}}]"></span>


                            <p class="d-block label label-warning badge badge-warning p-15" data-><i
                                        class="fa fa-info-circle"></i> @lang('Marketplace::labels.settings.shipping.no_available_shipping',['store'=>$store->name,'products'=>$shippable_item_name])

                            </p>
                        </div>

                    @endforeach
                @endif
            @endforeach
        @else
            <div class="form-group">
                <span data-name="selected_shipping_methods"></span>
            </div>
            <span class="label label-warning" data-><i
                        class="fa fa-info-circle"></i> @lang('Marketplace::labels.settings.shipping.no_available_shipping')</span>
        @endif

    </div>
</div>

{!! Form::close() !!}
