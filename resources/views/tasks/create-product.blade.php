
<style>
.Categories-list li {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 12px 22px 12px 12px;
	border-bottom: 1px solid #E2E2E2;
	position: relative;
}

.Categories-list li:last-child {
	border-bottom: 0;
}

.warehouses .prod-list ul {
	padding: 0;
	margin: 0;
	list-style: none;
	height: 500px;
	overflow: hidden auto;
}
.content-page.main_outter_box{
	max-height: 100vh;
	overflow-y: scroll;
	padding-bottom: 100px;
}

.warehouses #create-subtask {
	margin: 40px auto !important;
	display: block;
}

.warehouses input.search {
	border-radius: 4px;
	font-weight: 400;
	padding-left: 20px;
	padding-right: 20px;
}

.warehouses .prod-search {
	background: #FFFFFF;
	border: 1px solid #ced4da;
	border-radius: 5px;
	padding: 10px 15px;
}

.warehouses .search-bar {
	position: relative;
	max-width: 265px;
	margin: 0 auto 15px;
}

.warehouses .search-bar button.btn {
	position: absolute;
	top: 0;
	right: 0;
	background: transparent;
	border: 0;
	padding: 4px 12px;
	height: 100%;
}

.prod-list li {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 12px 22px 12px 12px;
	border-bottom: 1px solid #E2E2E2;
	position: relative;
}

.prod-list li:last-child {
	border-bottom: 0;
}

.warehouses .prod-list li::after {
	content: " ";
	position: absolute;
	background: #dee2e6;
	width: 1px;
	height: 38px;
	top: 50%;
	right: 70px;
	transform: translateY(-50%);
}

.warehouses .prod-list ul {
	padding: 0;
	margin: 0;
	list-style: none;
	max-height: 500px;
	overflow: hidden auto;
}

.warehouses .prod-details ul, .Categories-list ul {
	padding: 0;
	margin: 0;
	list-style: none;
	max-height: 680px;
	overflow: hidden auto;
}

.warehouses .prod {
	display: flex;
	align-items: center;
	gap: 20px;
	font-style: normal;
	font-weight: 400;
	font-size: 16;
	text-align: center;
}

.warehouses .prod-pic {
	/* 	background: #E9E7FA; */
	border-radius: 6.11031px;
	width: 38px;
	height: 38px;
	text-align: center;
	padding: 5px 4px;
}
.warehouses .inventory-products .prod-pic {
	width: auto;	
	height: auto;
}
.warehouses .inventory-products span {
    word-break: break-word;
    text-align: left;
    width: 60%;
    display: inline-block;
}
.warehouses .prod-pic img{
	width: 38px;
	height: 38px;
}
.prod-pic img {
	width: 100%;
}

.warehouses .prod-list .form-check {
	position: relative;
	padding-left: 0;
}

.warehouses .prod-list .form-check input.form-check-input {
	border: 1px solid #6658DD;
	width: 18px;
	height: 18px;
	opacity: 0;
	position: absolute;
	z-index: 99;
	top: 0;
	left: 20px;
}

.warehouses .prod-list .form-check input[type=checkbox]:checked+label:after
	{
	background: #6658DD;
}

.warehouses .prod-list .form-check input[type=checkbox]:checked+label:before
	{
	opacity: 1;
	z-index: 9;
}

.warehouses .prod-list .form-check label {
	position: relative;
	width: 18px;
	height: 18px;
	margin: 0 !important;
}

.warehouses .prod-list .form-check label:after {
	content: " ";
	border: 1px solid #6658DD;
	width: 18px;
	height: 18px;
	position: absolute;
	left: 0;
	top: 5px;
	border-radius: 3px;
	background: transparent;
}

.warehouses .prod-list .form-check label::before {
	content: " ";
	width: 6px;
	height: 10px;
	top: 11px;
	left: 3px;
	position: absolute;
	transform: rotate(45deg) translateY(-50%);
	border-bottom: 2px solid #fff;
	border-right: 2px solid #fff;
	opacity: 0;
	background: transparent !important;
}

.warehouses .prod-list ul::-webkit-scrollbar {
	width: 3px;
}

.warehouses .prod-list ul::-webkit-scrollbar-track {
	background: #D9D9D9;
}

.warehouses .prod-list ul::-webkit-scrollbar-thumb {
	background: #6658DD;
}

.prod-details input.form-control.input-number {
	max-width: 25px;
	padding: 0;
	text-align: center;
	border: 0;
}

.prod-details .input-group {
	justify-content: center;
	gap: 12px;
	align-items: center;
	position: relative;
}

.prod-details button.btn-number {
	padding: 0;
	background: transparent;
	border: 0;
	cursor: pointer;
}

.prod-details li {
	padding: 12px;
}

.warehouses .prod-details  li::after {
	display: none;
}

.prod-list.prod-details li {
	display: block;
	border: 0;
}

.prod-list.prod-details .product {
	border-bottom: 1px solid #E2E2E2;
	padding: 18px 10px;
}

.prod-list.prod-details .product .row {
	align-items: center;
}

.prod-list.prod-details .product:last-child {
	border: 0;
}

.stock p {
	margin: 0;
	text-align: center;
}

.stock {
	position: relative;
	border-left: 1px solid #E2E2E2;
	border-right: 1px solid #E2E2E2;
}

p.title {
	position: absolute;
	top: -40px;
	left: 50%;
	transform: translateX(-50%);
}

.product.produ p.title {
	display: block;
}

.product p.title {
	display: none;
}

.select-bar, .bars {
	display: flex;
	gap: 10px;
}

.warehouses .bars .search-bar {
	position: relative;
	max-width: auto;
	margin: 0;
}

.select-bar button {
	background: transparent;
	border: 0;
	display: flex;
	align-items: center;
	padding: 0;
	gap: 10px;
	width: 267px;
	font-size: 12px;
}

.select-bar select {
	border-radius: 33px;
}

.warehouses .Categories-list  li {
	display: block;
}

.warehouses .Categories-list li:after {
	display: none;
}

.warehouses .Categories-list li .label-check {
	display: block;
	width: 100%;
	cursor: pointer;
}

.warehouses .Categories-list li .label-check:after {
	background: transparent;
	border: 0;
	right: 0;
	left: auto;
}

.warehouses .Categories-list .form-check label::before {
	right: 9px;
	left: unset;
}

.warehouses .Categories-list .form-check input[type=checkbox]:checked+label
	{
	color: #6658DD;
}

.variant-title {
	text-align: left;
	word-break: break-all;
}

@media only screen and (max-width: 1400px) {
	.warehouses .prod-list ul{
		max-height:310px;
	}
	.warehouses .prod-list ul{
		height: 310px;
	}

}	

@media only screen and (max-width: 575px) {
	.prod-list.prod-details .prod {
		margin-bottom: 20px;
	}
	p.title {
		position: relative;
		top: 0;
		left: 0;
		transform: translateX(0);
		margin: 0 0 0 0;
	}
	.prod-details .input-group {
		width: 100%;
	}
	.stock {
		position: relative;
		display: flex;
		align-items: center;
		gap: 20px;
		border-left: 0;
	}
	.product p.title {
		display: block;
	}
}
</style>