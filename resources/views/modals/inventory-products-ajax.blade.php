<?php if(count($products)  > 0) { ?>
  @foreach ($products as $product)

  
 @php
 
 if(!empty($product->media->first()) && !empty($product->media->first()->image))
 {
    $path = $product->media->first()->image->path['proxy_url'] . '30/30' . $product->media->first()->image->path['image_path'];

 }
 else{
	$path = asset('assets/images/bg-material.png') ;
 }
 @endphp
<li>
	<div class="prod ">
		<div class="prod-pic">
          <img alt="{{ $product->id }}" class="rounded-circle" src="{{  $path }}">
		</div>
   @php
     if(!empty($product->translation_one)){
		
		$title = $product->translation_one->title;
		}else{
		$title = $product->title;
		}
		@endphp
		
		
		@if(empty($title))
           <span>{{$product->sku}}</span>
        @else
        <span>{{$title}}</span>
        
        @endif
		
	</div>
	</div>
	<div class="form-check">
		<input type="checkbox" class="form-check-input"
			onclick="getProductName({{$product->id}})"
			id="product_id_{{$product->id}}" name="product-check"
			value="{{$product->id}}"> <label></label>
	</div>
</li>
@endforeach 
<?php } else {?>

<li>
	<div class="prod">
		<div class=""></div>
		No Results Found
	</div>
</li>
<?php  }?>