<div class="grid-item">
    <div class="product-card">
        @if($product->discount)
            <div class="product-badge text-danger">{{ $product->discount }}% Off</div>
        @endif
        @if(\Settings::get('marketplace_rating_enable',true) == 'true')
            @include('partials.components.rating',['rating'=> $product->averageRating(1)[0],'rating_count'=>null])
        @endif
        <div style="min-height: 170px;" class="mt-4">
            <a class="product-thumb" href="{{ url('shop/'.$product->slug) }}">
                <img src="{{ $product->image }}" alt="{{ $product->name }}" class="mx-auto"
                     style="max-height: 150px;width: auto;">
            </a>
        </div>
        <h3 class="product-title">
            <a href="{{ url('shop/'.$product->slug) }}">{{ $product->name }}</a>
        </h3>
        <h4 class="product-price">
            @if($product->discount)
                <del>{{ \Payments::currency($product->regular_price) }}</del>
            @endif
            {!! $product->price !!}
        </h4>
        <div class="product-buttons">
            @if(\Settings::get('marketplace_wishlist_enable', 'true') == 'true')
                @include('partials.components.wishlist',['wishlist'=> $product->inWishList()])
            @endif
            @if(!$product->isSimple || $product->attributes()->count())
                @if($product->external_url)
                    <a href="{{ $product->external_url }}" target="_blank" class="btn btn-outline-primary btn-sm"
                       title="Buy Product">
                        @lang('corals-marketplace-master::labels.partial.buy_product')
                    </a>
                @else
                    <a href="{{ url('shop/'.$product->slug) }}" class="btn btn-outline-primary btn-sm">
                        @lang('corals-marketplace-master::labels.partial.add_to_cart')
                    </a>
                @endif
            @else
                @php $sku = $product->activeSKU(true); @endphp
                @if($sku->stock_status == "in_stock")
                    @if($product->external_url)
                        <a href="{{ $product->external_url }}" target="_blank" class="btn btn-outline-primary btn-sm"
                           title="Buy Product">
                            @lang('corals-marketplace-master::labels.partial.buy_product')
                        </a>
                    @else
                        <a href="{{ url('cart/'.$product->hashed_id.'/add-to-cart/'.$sku->hashed_id) }}"
                           data-action="post" data-page_action="updateCart"
                           class="btn btn-outline-primary btn-sm">
                            @lang('corals-marketplace-master::labels.partial.add_to_cart')
                        </a>
                    @endif
                @else
                    <a href="#" class="btn btn-sm btn-outline-danger"
                       title="Out Of Stock">
                        @lang('corals-marketplace-master::labels.partial.out_stock')
                    </a>
                @endif

            @endif
            <div class="mt-3">
                <span class="text-medium"><i
                            class="fa fa-home"></i> @lang('corals-marketplace-master::labels.template.product_single.store')</span>
                <a class="" href="{{ $product->store->getUrl() }}"><b>{{ $product->store->name }}</b></a>
            </div>
        </div>
    </div>
</div>
