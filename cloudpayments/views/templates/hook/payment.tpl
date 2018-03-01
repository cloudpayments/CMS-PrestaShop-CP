<div class="row">
	<div class="col-xs-12 col-md-6">
		<p class="payment_module">
			<a href="javascript:void(0);" onclick="pay();" title="{l s='Pay with CloudPayments' mod='cloudpayments'}" rel="nofollow" class="cloudpayments">
				{l s='Pay with CloudPayments' mod='cloudpayments'}
			</a>
		</p>
	</div>
</div>
<style>
	p.payment_module a.cloudpayments {
    	background: url({$path}views/img/logo_45x45.png) 23px 20px no-repeat;
	}
	p.payment_module a.cloudpayments:hover {
	    background-color: #f6f6f6;
	}
</style>
<script>
this.pay = function () {
    var widget = new cp.CloudPayments();
    widget.{$payType}({ // options
            publicId: '{$publicId}',  //id из личного кабинета
            description: '{$description}', //назначение
            amount: {$totalPay}, //сумма
            currency: '{$currency}', //валюта
            invoiceId: {$cartId},
		    accountId: '{$accountId}',
			data: {$additionalData}
        },
        function (options) { // success
            location.href = '{$redirectUrl}';
        });
};
</script>