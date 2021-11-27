
$(document).ready(function () {
    hideCCD();
});
$(document).on('click', '#checkout_paymentDetails', function () {
    hideCCD();
});

function hideCCD(){
    if($("#checkout_paymentDetails").val() == "Credit Card"){
        $('#ccd').show();
    }
    else {
        $('#ccd').hide();
    }
}