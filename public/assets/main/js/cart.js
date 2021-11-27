$(document).ready(function () {
    showCart();

    $(document).on('click', '#toggle-cart', function () {
        showCart();
    });

});

function showCart() {
    $.getJSON("/api/v1/cart", function (data) {

    }).done(function(data){
        readItemsTemplate(data);
    }).catch(function(){
        $(".cart-content").html(`<p class="empty-cart">Your cart is empty. But it's easy to fix! Go shopping!</p>`);
    });
}

function readItemsTemplate(data) {

    var read_items_html = ``;
    var total = 0;

    if (data.length > 0) {
        read_items_html += `
        <table class='cart-table table'>
            <tr>
                <th class='w-5-pct bg-dark text-white' style="text-align: center">Product</th>
                <th class='w-5-pct bg-dark text-white' style="text-align: center">Price</th>
                <th class='w-5-pct bg-dark text-white' style="text-align: center">Amount</th>
                <th class='w-5-pct bg-dark text-white' style="text-align: center">Total</th>
                <th class='w-5-pct bg-dark text-white' style="text-align: center">Action</th>
             </tr>`;
    }

    var formatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    });

    data.forEach(cartItem => {

        total += cartItem['product']['price'] * cartItem['amount'];

        read_items_html += `
        <tr class="item" data-prod-code="` + cartItem['product']['code'] + `">
            <td style="vertical-align: middle;text-align: center">` + cartItem['product']['name'] + `</td>
            <td style="vertical-align: middle;text-align: center">` + formatter.format(cartItem['product']['price']) + `</td>
            <td style="vertical-align: middle;text-align: center" class="amount">` + cartItem['amount'] + `</td>
            <td style="vertical-align: middle;text-align: center" class="total">` + formatter.format(cartItem['amount'] * cartItem['product']['price']) + `</td>
            <td style="vertical-align: middle;text-align: center"><button class="delete_cart btn btn-danger"><i class="bi bi-trash"></i></td>
        </tr>
        `;
    });

    if (window.location.pathname === '/cart') {
        $("#cart-container").css("overflow-y", "hidden").css("height", "auto");
    }

    if (total === 0) {
        read_items_html += `<p class="empty-cart">Your cart is empty. But it's easy to fix! Go shopping!</p>`;
    } else {
        read_items_html += `
            <tr>
                <td style="vertical-align: middle;text-align: right" colspan="3"><b>Total : </b></td>
                <td style="vertical-align: middle;text-align: center" class="total"><b>` + formatter.format(total) + ` </b></td>
                <td></td>
            </tr>
        </table>`;
    }

    $(".cart-content").html(read_items_html);
    show();
}
async function deleteItem(productCode) {
    let url = "/api/v1/cart/" + productCode;
    return await fetch(url, {method: 'DELETE'});
}
$(document).on('click', '.delete_cart', function (e) {
    e.preventDefault();
    deleteItem($(this).closest('tr').data('prod-code')).then(function (res) {
        console.log(res.status);
        showCart();
    })
})
function show(){
    if(window.location.pathname === '/cart/'){
        document.querySelectorAll('.amount').forEach(function (element, index) {
            if(index < $('.amount').length/2) return;
            let amount = parseInt(element.textContent);
            $('.amount').eq(index).html('<input type="number" min="1"\n' +
                    '                                                    value="' + amount + '" class="namount" style="vertical-align: middle;text-align: center">');
        });

    }
}
async function fetchCart(amount, productCode) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('amount', parseInt(amount));
    urlParams.set('code', productCode);
    let url = "/api/v1/cart/";
    return await fetch(url + "?" + urlParams, {method: 'PATCH'});
}
$(document).on('focusout', 'input[type="number"].namount', function (e) {
    if($(this).val()){
        e.preventDefault();
        fetchCart($(this).val(), $(this).closest('tr').data('prod-code')).then(function (res) {
            console.log(res.status);
            if(res.status === 200){
                $(location).attr('.total');
            }
            if(res.status === 400){
                res.json().then(data => alert(data.message));
            }
            showCart();
        })
    }
})
