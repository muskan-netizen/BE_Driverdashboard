<?php
use App\Model\Product;
?>



@foreach ($warehouses as $warehouse)

@php
$products = Product::where([
    'vendor_id' => $warehouse->id
])->whereIn('id', $product_ids)->get();

@endphp

<li id="warehouse_wise_product_{{ $warehouse->id}}">

<label>{{ strToUpper($warehouse->name)}}</label> @foreach($products as $key => $product)


	@foreach($product->variant as  $variant) @php if($variant->quantity <= 0 ){ continue; } @endphp
	<div class="product  <?= ($key == 0) ? 'produ' : ''?>">
		<div class="row">
			<div class="col-sm-6">
				<div class="prod">
					<div class="form-check">
						<input type="checkbox" class="form-check-input"
							name="vendor_product" id="vendor_product_{{$product->id}}"
							value="{{$variant->id}}"  onclick="getProductVariant({{$product->id}})"> <label></label>
					</div>
					<div class="prod-pic">
					 @if (isset($product->media[0]) && isset($product->media[0]->image))
                            <img alt="{{ $product->id }}" class="rounded-circle" src="{{ $product->media[0]->image->path['proxy_url'] . '30/30' . $product->media[0]->image->path['image_path'] }}">
                        @else
                      <img src="{{ asset('assets/images/bg-material.png')}}" alt="image">
                       @endif
					</div>
					<input type="hidden" name="vendor_id"
						id="vendor_id_{{$variant->id}}"
						value="{{$variant->product->vendor_id}}">
					
					  <span class="variant-title" ><?=   $product->title ?></span>
					  
				</div>
			</div>
			<div class="col-sm-6">
				<div class="row">
					<div class="col">
						<div class="stock">
							<p class="title">Stock</p>
							<p>{{ $variant->quantity}}</p>
						</div>
					</div>
					<div class="col">
						<div class="input-group">
							<p class="title">Qty</p>
							<span class="input-group-btn">

								<button type="button" class="btn-number minus" data-type="minus"
									onclick="minus({{$variant->id}},{{$variant->quantity}})"
									data-field="quant[]">
									<svg width="15" height="15" viewBox="0 0 15 15" fill="none"
										xmlns="http://www.w3.org/2000/svg">
                                                                                <circle
											cx="7.6099" cy="7.73002" r="6.76852" stroke="#4838CD"
											stroke-width="0.966932" />
                                                                                <path
											d="M4.2251 7.72998C5.69358 7.72998 7.98539 7.72998 7.98539 7.72998H11.3697"
											stroke="#4838CD" stroke-width="1.12809"
											stroke-linecap="round" />
                                                                            </svg>
								</button>
							</span> <input type="text" name="quant[]" disabled=""
								class="form-control input-number" value="1"
								id="range_input_{{$variant->id}}" min="0"
								max="{{$variant->quantity}}"> <span class="input-group-btn ">
								<button type="button" class="btn-number plus" data-type="plus"
									onclick="plus({{$variant->id}},{{$variant->quantity}})"
									data-field="quant[2]">
									<svg width="15" height="15" viewBox="0 0 15 15" fill="none"
										xmlns="http://www.w3.org/2000/svg">
                                                                                <circle
											cx="7.39408" cy="7.73002" r="6.76852" stroke="#4838CD"
											stroke-width="0.966932" />
                                                                                <path
											d="M7.77006 4.3457V11.1142M4.00977 7.72996H7.77006H11.1543"
											stroke="#4838CD" stroke-width="1.12809"
											stroke-linecap="round" />
                                                                            </svg>
								</button>
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div> @endforeach @endforeach</li>
@endforeach
