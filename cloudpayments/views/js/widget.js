$(document).on('ready', function () {
    $(document).on('submit', '#prestashop_module_payment_cloudpayments', function(e) {
        e.preventDefault();
        var $this = $(this),
            pay_type = $this.find('[name=pay_type]').val();
            language = $.parseJSON($this.find('[name=widget_data]').val());
            language = language['language'];
            
        var widget = new cp.CloudPayments({language:[language]});
        widget[pay_type](
            $.parseJSON($this.find('[name=widget_data]').val()),
            $this.find('[name=success_url]').val()
        );
    });
});