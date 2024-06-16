(function ($) {

    var isBookingConfirmBoxShowed = false;

    new Vue({
        el: '#bravo-checkout-page',
        data: {
            onSubmit: false,
            message: {
                content: '',
                type: false
            }
        },
        methods: {
            doCheckout() {
                console.log("dsfsdfsdf");
                $("#anotherConfirmationBox").html("Please wait...");
                /*if(!isBookingConfirmBoxShowed) {
                    isBookingConfirmBoxShowed = confirm('By continue, you are agree to our terms and conditions');
                }*/
                isBookingConfirmBoxShowed = true;
                // console.log(isBookingConfirmBoxShowed);
                if (isBookingConfirmBoxShowed === true) {
                    var me = this;

                    if (this.onSubmit) return false;

                    if (!this.validate()) return false;

                    this.onSubmit = true;

                    $.ajax({
                        url: bookingCore.routes.checkout,
                        data: $('.booking-form').find('input,textarea,select').serialize(),
                        method: "post",
                        success: function (res) {
                            $("#anotherConfirmationBox").html("PROCEED TO CHECKOUT");

                            if (!res.status && !res.url) {
                                me.onSubmit = false;
                            }

                            if (res.elements) {
                                for (var k in res.elements) {
                                    $(k).html(res.elements[k]);
                                }
                            }

                            if (res.message) {
                                me.message.content = res.message;
                                me.message.type = res.status;
                            }

                            if (res.url) {
                                setTimeout((e)=>{
                                    window.location.href = res.url
                                }, 1200);
                            }

                            if (res.errors && typeof res.errors == 'object') {
                                var html = '';
                                for (var i in res.errors) {
                                    html += res.errors[i] + '<br>';
                                }
                                me.message.content = html;
                            }

                        },
                        error: function (e) {
                            $("#anotherConfirmationBox").html("PROCEED TO CHECKOUT");
                            me.onSubmit = false;
                            if (e.responseJSON) {
                                me.message.content = e.responseJSON.message ? e.responseJSON.message : 'Booking cannot be completed at the moment';
                                me.message.type = false;
 
                                let res = e.responseJSON;
                                if (res.errorCode === "loginRequired") {
                                    
                                }

                                //console.log("res", res);

                            } else {
                                if (e.responseText) {
                                    me.message.content = e.responseText;
                                    me.message.type = false;
                                }
                            }
                        }
                    });
                }
            },
            validate() {
                return true;
            }
        }
    })
})(jQuery)
