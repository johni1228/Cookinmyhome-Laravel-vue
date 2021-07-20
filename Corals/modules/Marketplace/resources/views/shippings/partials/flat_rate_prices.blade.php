<div>
    <div class="flat-rates">
        @foreach(\ShippingPackages::getFlatShippingRates($product) as $index => $rate)
            @include('Marketplace::shippings.partials.flat_rate_prices_location', compact('index','rate'))
        @endforeach
    </div>
    <div class="form-group">
        <span data-name="shipping_rate"></span>
    </div>
    <hr/>
    <div class="row">
        <div class="col-md-6">
            {!! \CoralsForm::button('Marketplace::labels.package.add_location', ['class' => 'btn btn-primary','id' => 'flat-rate-add-location']) !!}
        </div>
    </div>
</div>

@push('partial_js')
    <script>
        $(document).on('click', '#flat-rate-add-location', function (event) {
            let index = $('.location-rate').length;
            let url = "{{ url('marketplace/shippings/get-rate-price-location') }}/" + index;
            $.get(url, function (response) {
                $('.flat-rates').append(response);
            });
        })

        $(document).on('click', '.flat-rate-remove-location', function (event) {
            $(this).closest('.location-rate').remove();
        });

        $(document).on('change', '.shipping_method', function (event) {
            let ele = $(this);

            let nonFree = $(this).closest('.location-rate').find('.non-free');

            if (ele.val() === 'Free') {
                nonFree.find('input').val(0);
                nonFree.hide();
            } else {
                nonFree.fadeIn();
            }
        })
    </script>
@endpush
