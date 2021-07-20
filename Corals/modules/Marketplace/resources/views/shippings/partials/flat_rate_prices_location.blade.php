<div class="location-rate">
    <div class="row">
        {{--        <div class="col-md-4">--}}
        {{--            {!! CoralsForm::text("shipping_rate[$index][name]",'Marketplace::attributes.shipping.name',true, $rate['name']) !!}--}}
        {{--        </div>--}}
        <div class="col-md-4">
{{--            {{ Form::hidden('shipping_rate[$index][name]', $rate['name']??'FlatRate') }}--}}
            {!! CoralsForm::select("shipping_rate[$index][country]", 'Marketplace::attributes.shipping.country', \Settings::getCountriesList(), true, $rate['country'], [], 'select2') !!}
        </div>
        <div class="col-md-4">
            {!! CoralsForm::select("shipping_rate[$index][shipping_provider]", 'Marketplace::attributes.shipping.provider', \ListOfValues::get('marketplace_shipping_providers'), true, $rate['shipping_provider'], [], 'select2') !!}
        </div>
        <div class="col-md-4">
            {!! CoralsForm::select("shipping_rate[$index][shipping_method]", 'Marketplace::attributes.shipping.shipping_method_location', \Shipping::getShippingMethods(['Shippo']) , true, $rate['shipping_method'], ['class'=>'shipping_method'], 'select2') !!}
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 non-free"
             style="{{ data_get($rate,'shipping_method') != 'Free'?'':'display:none' }}">
            {!! CoralsForm::number("shipping_rate[$index][one_item_price]", 'Marketplace::attributes.shipping.one_item_price',true, $rate['one_item_price'],['step'=>0.01,'min'=>0,'max'=>999999]) !!}
        </div>
        <div class="col-md-4 non-free"
             style="{{ data_get($rate,'shipping_method') != 'Free'?'':'display:none' }}">
            {!! CoralsForm::number("shipping_rate[$index][additional_item_price]", 'Marketplace::attributes.shipping.additional_item_price', false, $rate['additional_item_price'],['step'=>0.01,'min'=>0,'max'=>999999]) !!}
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            {!! \CoralsForm::button('Marketplace::labels.package.remove_location', ['class' => 'btn btn-danger btn-sm flat-rate-remove-location']) !!}
        </div>
    </div>
</div>
