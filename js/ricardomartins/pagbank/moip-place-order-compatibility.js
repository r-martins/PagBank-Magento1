(function($) {
    moipPlaceOrder = function(){
        visibilyloading();
        var form = new VarienForm('onestep_form', true);
        if(form.validator.validate())	{
            updateOrderMethod();
            return true;
        } else {
            visibilyloading('end');
            getErroDescription();
            jQuery('#ErrosFinalizacao').modal();
            jQuery(".moip-place-order").show();
            jQuery('.validation-advice').delay(5000).fadeOut("slow");
            return false;
        }
    }
})(jQuery);