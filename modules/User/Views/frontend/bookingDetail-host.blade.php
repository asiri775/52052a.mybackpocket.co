@extends('layouts.yellow_user')
<link rel="stylesheet" type="text/css" href="{{ URL::asset('user_assets/css/page.css') }}">
@section('content')

    <?php
    //dd($row);
    
    $startTime = null;
    $endTime = null;
    
    $showTimingOption = true;
    
    if ($space->available_from == null) {
        $space->available_from = '00:00';
    }
    
    if ($space->available_to == null) {
        $space->available_to = '23:59';
    }
    
    if ($space->long_term_rental == 1) {
        // $showTimingOption = false;
        $startTime = $space->available_from;
        $endTime = $space->available_to;
    }
    
    $startdate = $booking->start_date;
    $startdate1 = explode(' ', $startdate);
    $startDate = $startdate1[0];
    $starthour = $startdate1[1];
    $startTime = rtrim($startdate1[1], ':00');
    $enddate = $booking->end_date;
    $enddate1 = explode(' ', $enddate);
    $endDate = $enddate1[0];
    $endhour = $enddate1[1];
    $endTime = rtrim($enddate1[1], ':00');
    //below was before in else condition now move into true so always worked
    if (true) {
        if (isset($starthour) && trim($starthour) != null && trim($starthour)) {
            $startTime = trim($starthour);
        }
    
        if (isset($endhour) && trim($endhour) != null && trim($endhour)) {
            $endTime = trim($endhour);
        }
    
        if (isset($booking->start_date) && $booking->start_date != null) {
            $startDate = trim($booking->start_date);
            if ($startDate == date('Y-m-d')) {
                $startTimeData = date('H:i');
                if ($startTimeData > $startTime) {
                    $startTimeDataExploded = explode(':', $startTimeData);
                    if ($startTimeDataExploded[1] > 0) {
                        $startTimeHR = $startTimeDataExploded[0];
                        $startTimeHR = $startTimeHR + 1;
                        if (strlen($startTimeHR) == 1) {
                            $startTimeHR = '0' . $startTimeHR;
                        }
                        $nextNearestHour = $startTimeHR . ':00';
                        if ($nextNearestHour < $space->available_to) {
                            $startTime = $nextNearestHour;
                        }
                    } else {
                        $startTime = $startTimeData;
                    }
                }
            }
        }
    }
    if ($space->min_hour_stays != null && $startTime != null && $endTime == null) {
        $startTimeExploded = explode(':', $startTime);
        $startTimeHR = trim($startTimeExploded[0]);
        if ($startTimeHR > 0) {
            $startTimeHR = $startTimeHR + $space->min_hour_stays;
            $endTime = $startTimeHR . ':00';
        }
    }
    
    if ($startTime != null) {
        if ($startTime < $space->available_from) {
            $startTime = $space->available_from;
        }
        if ($startTime > $space->available_to) {
            $startTime = $space->available_from;
        }
    }
    
    if ($endTime != null) {
        if ($endTime < $space->available_from) {
            $endTime = $space->available_to;
        }
        if ($endTime > $space->available_to) {
            $endTime = $space->available_to;
        }
    }
    
    if ($startTime != null && $endTime != null) {
        $startTimeExploded = explode(':', $startTime);
        $endTimeExploded = explode(':', $endTime);
        $startTimeHR = trim($startTimeExploded[0]);
        $endTimeHR = trim($endTimeExploded[0]);
        if ($startTimeHR > 0 && $endTimeHR > 0) {
            $diffHour = $endTimeHR - $startTimeHR;
            if ($diffHour < $space->min_hour_stays) {
                $startTimeHR = $startTimeHR + $space->min_hour_stays;
                $endTime = $startTimeHR . ':00';
            }
        }
    }
    
    $timesNotAvailable = $space->getTimesNotAvailable();
    $allDayTimeSlots = \App\Helpers\Constants::getTimeSlots();
    
    $startEndDate = '';
    
    $startDate = null;
    $toDate = null;
    
    if (isset($booking->end_date)) {
        if (!empty(trim($booking->start_date)) && !empty(trim($booking->end_date))) {
            $startDate = $startdate1[0];
            $toDate = $enddate1[0];
            $startEndDate = date('m/d/Y', strtotime(trim($booking->start_date))) . ' - ' . date('m/d/Y', strtotime(trim($booking->end_date)));
        }
    }
    
    $start_hour_state = null;
    $end_hour_state = null;
    
    if ($startTime != null) {
        $start_hour_state = \App\Helpers\CodeHelper::getAMPMFromHourTime($startTime);
        $startTime = \App\Helpers\CodeHelper::getSmallMinTimeFromHourTime($startTime);
    }
    
    if ($endTime != null) {
        $end_hour_state = \App\Helpers\CodeHelper::getAMPMFromHourTime($endTime);
        $endTime = \App\Helpers\CodeHelper::getSmallMinTimeFromHourTime($endTime);
    }
    
    //$showTimingOption = true;
    $startTime = substr($startdate1[1], 0, 5);
    $endTime = substr($enddate1[1], 0, 5);
    ?>
    <div class="content sm-gutter mb-5">
        <!-- START BREADCRUMBS-->
        <div class="bg-white">
            <div class="container-fluid pl-5">
                <ol class="breadcrumb breadcrumb-alt bg-white mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('user.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Booking Details</li>
                </ol>
            </div>
        </div>
        <!-- END BREADCRUMBS -->
        <!-- START CONTAINER FLUID -->
        <div class="container-fluid p-5">

            <div class="row">

                <div class="col-12">
                    @if (session()->has('success'))
                        <div class="alert alert-success">
                            @if (is_array(session('success')))
                                <ul>
                                    @foreach (session('success') as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            @else
                                {{ session('success') }}
                            @endif
                        </div>
                    @endif
                    @if (session()->has('error'))
                        <div class="alert alert-danger">
                            @if (is_array(session('error')))
                                <ul>
                                    @foreach (session('error') as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            @else
                                {{ session('error') }}
                            @endif
                        </div>
                    @endif
                </div>

                <div class="col-12">
                    <div class="title booking-details-head title-fonts sub-title">
                        <h4 class="text-uppercase mb-3">
                            <strong>Booking Details</strong>
                        </h4>
                    </div>
                </div>

                <div class="col-lg-6 col-sm-12 table-booking-view">

                    <div class="booking-h-actions">
                        <ul>
                            <li>
                                <a data-toggle="modal" data-target="#sendbookingmodal">
                                    <i class="fa fa-paper-plane-o" aria-hidden="true"></i>
                                    <span>Send Booking</span>
                                </a>
                            </li>
                            <li>
                                <a data-toggle="modal" id="reschedulebutton" data-target="">
                                    <i class="fa fa-calendar" aria-hidden="true"></i>
                                    <span>Modify</span>
                                </a>
                            </li>
                            <li>
                                <a data-toggle="modal" data-target="#cancelmodal">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                    <span>Cancel</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.booking.invoice', ['code' => $booking->code]) }}">
                                    <i class="fa fa-file-text" aria-hidden="true"></i>
                                    <span>Invoice</span>
                                </a>
                            </li>
                            <li>
                                <a id="changestatus">
                                    <i class="fa fa-check-square-o" aria-hidden="true"></i>
                                    <input id="bookingstatus" type="hidden" value="{{ $booking->status }}">
                                    <span>Change Status</span>
                                </a>
                            </li>
                            <li>
                                <a id="extendbooking" data-toggle="modal" data-target="#extendmodal">
                                    <i class="fa fa-exchange" aria-hidden="true"></i>
                                    <input id="bookingstatus" type="hidden" value="{{ $booking->status }}">
                                    <span>Extend</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card card-default full-height card-bordered p-4 card-radious">
                        <div class="row book-table mb-2">
                            <div class="col-lg-3 col-sm-3 col-md-3">
                                <div class="date-start text-center mt-3">
                                    <div class="calendar-day">
                                        @php
                                            $date = $booking->start_date;
                                        @endphp
                                        <div class="day-name">{{ date('d', strtotime($date)) }}</div>
                                        <div class="m-name">{{ date('F', strtotime($date)) }}</div>
                                        <div class="m-name">{{ date('Y', strtotime($date)) }}</div>
                                        <div class="status-btn <?= $booking->statusClass() ?>"><?= $booking->statusText() ?>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-9 col-sm-9 col-md-9">
                                <div class="book-details pl-3">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td></td>
                                                <td colspan="3" class="td-id text-uppercase">Booking
                                                    #{{ $booking->id }}</td>
                                            </tr>
                                            <tr>
                                                <td class="w-20">
                                                    <!---
                                                             <img height="30" width="30" src="/icon/MO_logo.svg">
                        -->
                                                    <a href="{{ route('user.profile.publicProfile', ['id' => $booking->vendor_id]) }}"
                                                        title="Host Profile">
                                                        <span class="thumbnail-wrapper circular inline">
                                                            <img src="{{ $booking->vendor->getAvatarUrl() }}"
                                                                alt=""
                                                                data-src="{{ $booking->vendor->getAvatarUrl() }}"
                                                                data-src-retina="{{ $booking->vendor->getAvatarUrl() }}"
                                                                width="45" height="45">
                                                        </span></a>
                                                </td>
                                                <td colspan="3" style="text-align:center;"><a
                                                        href= "{{ route('space.detail', ['slug' => $space->slug]) }}">{{ $space->title }}</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="w-20">
                                                    <span class="material-icons" data-toggle="tooltip" data-placement="top"
                                                        title="Arrival Date">
                                                        flight_land
                                                    </span>
                                                </td>
                                                <td class="w-40">
                                                    {{ date('F d, Y', strtotime($booking->start_date)) }}
                                                </td>
                                                <td class="w-20">
                                                    <span class="material-icons" data-toggle="tooltip" data-placement="top"
                                                        title="Arrival Time">
                                                        access_time
                                                    </span>
                                                </td>
                                                <td class="w-40">
                                                    {{ date('g:i A', strtotime($booking->start_date)) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="w-20">
                                                    <span class="material-icons" data-toggle="tooltip" data-placement="top"
                                                        title="Departure Date">
                                                        flight_takeoff
                                                    </span>
                                                </td>
                                                <td class="w-40">
                                                    {{ date('F d, Y', strtotime($booking->end_date)) }}
                                                </td>
                                                <td class="w-20">
                                                    <span class="material-icons" data-toggle="tooltip"
                                                        data-placement="top" title="Departure Time">
                                                        access_time
                                                    </span>
                                                </td>
                                                <td class="w-40">
                                                    {{ date('g:i A', strtotime($booking->end_date)) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="w-20">
                                                    <a href="{{ route('user.profile.publicProfile', ['id' => $booking->customer_id]) }}"
                                                        title="Guest Profile">
                                                        <span class="thumbnail-wrapper circular inline">
                                                            <img src="{{ $booking->customer->getAvatarUrl() }}"
                                                                alt=""
                                                                data-src="{{ $booking->customer->getAvatarUrl() }}"
                                                                data-src-retina="{{ $booking->customer->getAvatarUrl() }}"
                                                                width="45" height="45">
                                                        </span></a>
                                                    <!---
                                                                   <span class="material-icons" data-toggle="tooltip" data-placement="top"
                                                                                                                title="guest profile">
                                                                                                                person
                                                                                                    </span>
                                                                                                            --->

                                                </td>
                                                <td colspan="3" class="w-40">
                                                    <a href="{{ route('user.profile.publicProfile', ['id' => $booking->customer_id]) }}">{{ $booking->first_name }} {{ $booking->last_name }}</a> |
                                                    {{ $booking->total_guests }} <?php if ($booking->total_guests > 1) {
                                                        echo 'Guests';
                                                    } else {
                                                        echo 'Guest';
                                                    } ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="row item-table">
                            <div class="col-sm-12">
                                <h3 class="mt-3 mb-3 text-center">Rates and Fees</h3>
                                @include('User::frontend._booking_rate_table', ['role' => 'host'])

                                <div class="view-btn text-center mt-4 mb-3 bottom-btn">
                                    {{-- <button class="btn btn-primary btn-lg mb-2" data-toggle="modal"
                                        data-target="#myModal">`</button> --}}
                                    <button class="btn btn-primary btn-lg mb-2" data-toggle="modal"
                                        data-target="#myModal">Cancel</button>
                                    <button data-toggle="modal" data-target="#contactBookModal"
                                        class="btn btn-primary btn-lg mb-2">Contact Host</button>
                                </div>
                            </div>
                        </div>

                        <div class="row item-table">
                            <div class="col-sm-12">

                                <div
                                    class="card mt-3 card-default full-height-n card-bordered p-4 card-radious payment-card">
                                    <h2>Payment Details</h2>
                                    <?php
                            if(count($payments) > 0){
                                foreach ($payments as $payment) {
                                    ?>
                                    <div class="payment-info">
                                        <div class="info">
                                            <h6>PAYMENT METHOD</h6>
                                            <div class="info-data">
                                                <h5 class="payment-method" style="width:200px;text-align:center;">
                                                    {{ strtoupper($payment['method']) }}</h5>
                                                <div class="information" style="margin-left:10%;width:100%;">
                                                    <h6 style="font-weight:900;width:200px;">PAYMENT CONFIRMATION</h6>
                                                    <p>REFERENCE#: {{ $payment['ref'] }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="amount">
                                            <h6>Status</h6>
                                            <h5 style="width:100px;text-align:center;"
                                                class="status-btn {{ $payment['status'] == 'completed' ? 'confirmed' : 'pending' }} success">
                                                {{ strtoupper($payment['status'] == 'completed' ? 'confirmed' : 'pending') }}
                                            </h5>
                                        </div>
                                        <div class="amount">
                                            <h6>Amount</h6>
                                            <h1>{{ $payment['amount'] }}</h1>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }else{
                                ?>
                                    <p class="no-res">No Payments Found</p>
                                    <?php
                            }
                            ?>

                                    <div class="payment-actions">
                                        <a data-toggle="modal" data-target="#sendInvoice" href="javascript:;"
                                            class="btn btn-primary reverse">Email Invoice</a>
                                        <a target="_blank"
                                            href="{{ route('site.downloadInvoice', $booking->code) }}"
                                            class="btn btn-primary reverse">Download Invoice</a>
                                        <a data-toggle="modal" data-target="#issuePromoCredits" href="javascript:;"
                                            class="btn btn-primary reverse">Issue Credit</a>
                                    </div>

                                </div>


                            </div>
                        </div>
                    </div>
                    <!--card-->
                </div>
                <!-- Modal -->

                <div class="modal fade" id="emailBookingDetails" role="dialog">
                    <div class="modal-dialog">
                        <!-- Modal content-->
                        <div class="modal-content ">
                            <div class="modal-header  justify-content-between text-center">
                                <h5 style="font-family:Montserrat;16pt;font-weight:900;"
                                    class="modal-title justify-content-between text-center w-100">Email Booking Details
                                </h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <hr style="width:94%;text-align:left;margin-left:15">
                            <div class="modal-body justify-content-between text-center">
                                <form method="post" action="{{ route('user.booking.sendBookingDetails') }}"
                                    style="margin-top:5px;">
                                    @csrf
                                    <input type="hidden" name="object_id" value="<?= $booking->id ?>">
                                    <div class="form-group">
                                        <label class="text-left w-100" for="">Recipient</label>
                                        <input type="text"
                                            value="<?= old('recepient', $booking->customer != null ? $booking->customer->email : '') ?>"
                                            placeholder="Enter recepient email" name="recepient" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label class="text-left w-100" for="">Message</label>
                                        <textarea style="height: 300px !important;" name="message" class="form-control" placeholder="Enter Message"
                                            id="" cols="30" rows="10"><?= old(
                                                'message',
                                                \App\Helpers\CodeHelper::replaceMaskFields(\App\Helpers\Constants::getDefaultBookingDetails(), [
                                                    'spaceName' => $space->title,
                                                    'from' => date('F d, Y g:i A', strtotime($booking->start_date)),
                                                    'to' => date('F d, Y g:i A', strtotime($booking->end_date)),
                                                    'total' => '$' . $booking->total,
                                                ]),
                                            ) ?></textarea>
                                    </div>
                            </div>
                            <div class="modal-footer justify-content-center text-center">
                                <button type="submit" id="sendbookingconfirmyes" class="btn btn-primary">Send
                                    Invoice</button>
                                <button type="button" style="margin-top:-1px;" class="btn btn-primary w-40"
                                    data-dismiss="modal">Cancel</button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal fade plan-style-form" id="sendInvoice" role="dialog">
                    <div class="modal-dialog">
                        <!-- Modal content-->
                        <div class="modal-content ">
                            <div class="modal-header  justify-content-between text-center">
                                <h5 style="font-family:Montserrat;16pt;font-weight:900;"
                                    class="modal-title justify-content-between text-center w-100"><img width="30"
                                        height="30" src="<?php echo url('/icon/send-email.svg'); ?>">&nbsp;&nbsp;SEND INVOICE</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <hr style="width:94%;text-align:left;margin-left:15">
                            <div class="modal-body justify-content-between text-center">
                                <form method="post" action="{{ route('user.booking.sendEmailInvoice') }}"
                                    style="margin-top:5px;">
                                    @csrf
                                    <input type="hidden" name="object_id" value="<?= $booking->id ?>">
                                    <div class="form-group">
                                        <label class="text-left w-100" for="">Recipient</label>
                                        <input type="text"
                                            value="<?= old('recepient', $booking->customer != null ? $booking->customer->email : '') ?>"
                                            placeholder="Enter recepient email" name="recepient" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label class="text-left w-100" for="">Message</label>
                                        <textarea style="height: 300px !important;" name="message" class="form-control" placeholder="Enter Message"
                                            id="" cols="30" rows="10"><?= old(
                                                'message',
                                                \App\Helpers\CodeHelper::replaceMaskFields(\App\Helpers\Constants::getDefaultInvoiceText(), [
                                                    'name' => $booking->customer->first_name,
                                                    'businessName' => auth()->user()->business_name,
                                                    'businessAddress' =>
                                                        auth()->user()->address .
                                                        ', ' .
                                                        auth()->user()->address2 .
                                                        '
' .
                                                        auth()->user()->city .
                                                        ', ' .
                                                        auth()->user()->state .
                                                        ', ' .
                                                        auth()->user()->zip_code,
                                                        'businessContactNo' => auth()->user()->phone,
                                                        'businessContactEmail' => auth()->user()->email,
                                                ]),
                                            ) ?></textarea>
                                    </div>
                            </div>
                            <div class="modal-footer justify-content-center text-center">
                                <button type="submit" id="sendbookingconfirmyes" class="btn btn-primary">Send
                                    Invoice</button>
                                <button type="button" style="margin-top:-1px;" class="btn btn-primary w-40"
                                    data-dismiss="modal">Cancel</button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal fade" id="sendbookingmodal" role="dialog">
                    <div class="modal-dialog">
                        <!-- Modal content-->
                        <div class="modal-content ">
                            <div class="modal-header  justify-content-between text-center">
                                <h5 style="font-family:Montserrat;16pt;font-weight:900;"
                                    class="modal-title justify-content-between text-center w-100"><img width="30"
                                        height="30" src="<?php echo url('/icon/mo_share1.svg'); ?>">&nbsp;&nbsp;BOOKING
                                    #{{ $booking->id }}: SHARE</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <hr style="width:94%;text-align:left;margin-left:15">
                            <div class="modal-body justify-content-between text-center">
                                <form method="post" action="{{ route('user.booking.sendbooking') }}"
                                    style="margin-top:5px;">
                                    @csrf
                                    <label>Please share the destination E-Mail Address: </label>
                                    <input type="hidden" class="form-control" name="booking_id" id="booking_id"
                                        value="{{ $booking->id }}">
                                    <center>
                                        <input type="email" style="margin-top:15px;" placeholder="email@myoffice.ca"
                                            class="form-control form-rounded w-50" name="sendemailaddress"
                                            id="sendemailaddress" value="">
                            </div>
                            <div class="modal-footer justify-content-center text-center">
                                <button type="submit" id="sendbookingconfirmyes" class="btn btn-primary">Send
                                    Message</button>
                                <button type="button" style="margin-top:-1px;" class="btn btn-primary w-40"
                                    data-dismiss="modal">Cancel</button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal fade plan-style-form" id="issuePromoCredits" role="dialog">
                    <div class="modal-dialog">
                        <!-- Modal content-->
                        <div class="modal-content ">
                            <div class="modal-header  justify-content-between text-center">
                                <h5 style="font-family:Montserrat;16pt;font-weight:900;"
                                    class="modal-title justify-content-between text-center w-100"><img width="30"
                                        height="30" src="<?php echo url('/icon/issue-credit.svg'); ?>">&nbsp;&nbsp;ISSUE CREDIT</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <hr style="width:94%;text-align:left;margin-left:15">
                            <div class="modal-body justify-content-between text-center">
                                <form method="post" action="{{ route('user.booking.issueCredit') }}"
                                    style="margin-top:5px;">
                                    @csrf
                                    <input type="hidden" name="object_id" value="<?= $booking->id ?>">
                                    <input type="hidden" name="object_model" value="booking">
                                    <div class="form-group">
                                        <label class="text-left w-100" for="">Recipient</label>
                                        <input type="text"
                                            value="<?= old('recepient', $booking->customer != null ? $booking->customer->email : '') ?>"
                                            placeholder="Enter recepient email" name="recepient" class="form-control">
                                    </div>
                                    <div class="form-group with-icon-left">
                                        <span class="icon-left">$</span>
                                        <label class="text-left w-100" for="">Amount</label>
                                        <input type="text" value="<?= old('amount', $booking->total) ?>"
                                            placeholder="Enter amount" name="amount" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label class="text-left w-100" for="">Transaction Type</label>
                                        <select name="type" id="" class="form-control">
                                            <option <?= old('type', '') === 'promo' ? 'selected' : '' ?>
                                                value="promo">Promo Credits</option>
                                            <option <?= old('type', '') === 'refund' ? 'selected' : '' ?>
                                                value="refund">Refund</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="text-left w-100" for="">Reference</label>
                                        <input type="text" value="<?= old('reference', 'Booking #' . $booking->id) ?>"
                                            placeholder="Enter reference" name="reference" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label class="text-left w-100" for="">Notes</label>
                                        <textarea name="notes" id="" cols="30" rows="3"
                                            placeholder="Write the reason for the credit here" class="form-control h-auto"><?= old('notes', '') ?></textarea>
                                    </div>
                            </div>
                            <div class="modal-footer justify-content-center text-center">
                                <button type="submit" id="sendbookingconfirmyes" class="btn btn-primary">Issue
                                    Credit</button>
                                <button type="button" style="margin-top:-1px;" class="btn btn-primary w-40"
                                    data-dismiss="modal">Cancel</button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
                <!-- Modal -->


                <!-- Modal -->
                <div class="modal fade" id="extendmodal" role="dialog">
                    <div class="modal-dialog modal-md">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h5 style="font-family:Montserrat;16pt;font-weight:900;"
                                    class="modal-title justify-content-between text-center w-100"><img width="30"
                                        height="30" src="<?php echo url('/icon/mo_share1.svg'); ?>">&nbsp;&nbsp;BOOKING
                                    #{{ $booking->id }}: EXTEND</h5>
                            </div>
                            <hr style="width:94%;text-align:left;margin-left:15">
                            <div class="modal-body justify-content-center text-center">
                                <center>
                                    <h6 style="font-size:10pt;margin-bottom:5px;width:200px;font-weight:300;"
                                        class="text-center">Please select the length of time you wish to Extend this
                                        booking</h6>
                                </center>
                                <form id="extendtimeform" action="{{ route('user.booking.extendbooking') }}"
                                    style="position:relative;margin-top:12px;"method="post">
                                    @csrf
                                    <p></p>
                                    <input type="hidden" class="form-control" name="booking_id" id="booking_id"
                                        value="{{ $booking->id }}">
                                    <input type="hidden" class="form-control" name="start_date" id="start_date"
                                        value="{{ $booking->start_date }}">
                                    <input type="hidden" class="form-control" name="end_date" id="end_date"
                                        value="{{ $booking->end_date }}">
                                    <center>
                                        <select class="form-control  w-50" name="extendtime" id="extendtime">
                                            <option value="" style="text-align:center;">--Select Extend Time--
                                            </option>
                                            <option value="1 Hour" style="text-align:center;">1 Hour</option>
                                            <option value="2 Hour" style="text-align:center;">2 Hour</option>
                                            <option value="3 Hour" style="text-align:center;">3 Hour</option>
                                        </select>
                                    </center>
                            </div>
                            <div class="modal-footer" style="justify-content: center;">
                                <button type="submit" id="confirmyes" class="btn btn-primary disabled">Yes</button>
                                <button type="button" id="confirmno" style="margin-top:-1px;" class="btn btn-primary"
                                    data-dismiss="modal">No</button>
                            </div>

                            @php
                                $user = auth()->user();
                            @endphp
                            <input type="hidden" name="payment_gateway" value="two_checkout_gateway">
                            <input type="hidden" id="merchantPgIdentifier" name="merchantPgIdentifier" value="205">
                            <input type="hidden" id="secret_id" name="secret_id" value="2001">
                            <input type="hidden" id="currency" name="currency" value="CAD">
                            <input type="hidden" id="amount" name="amount" value="">
                            <input type="hidden" id="orderId" name="orderId" value="">
                            <input type="hidden" id="invoiceNumber" name="invoiceNumber" value="">
                            <input type="hidden" id="successUrl" name="successUrl"
                                value="{{ \App\Helpers\CodeHelper::withAppUrl('gateway/confirm/extend/two_checkout_gateway/' . $booking->id) }}">
                            <input type="hidden" id="errorUrl" name="errorUrl"
                                value="{{ \App\Helpers\CodeHelper::withAppUrl('gateway/cancel/extend/two_checkout_gateway/' . $booking->id) }}">
                            <input type="hidden" id="storeName" name="storeName" value="name205">
                            <input type="hidden" id="transactionType" name="transactionType" value="">
                            <input type="hidden" id="timeout" name="timeout" value="">
                            <input type="hidden" id="transactionDateTime" name="transactionDateTime"
                                value="{{ date('Y-m-d') }}">
                            <input type="hidden" id="language" name="language" value="EN">
                            <input type="hidden" id="credits" name="credits" value="">
                            <input type="hidden" id="txnToken" name="txnToken" value="">
                            <input type="hidden" id="itemList" name="itemList" value="Deposit">
                            <input type="hidden" id="otherInfo" name="otherInfo" value="">
                            <input type="hidden" id="merchantCustomerPhone" name="merchantCustomerPhone"
                                value="04353563535">
                            <input type="hidden" id="merchantCustomerEmail" name="merchantCustomerEmail"
                                value="customer@gmail.com">

                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Modal -->


                <!-- Modal -->
                <div class="modal fade" id="schedulemodal1" role="dialog">
                    <div class="modal-dialog">

                        <!-- Modal content-->
                        <div class="modal-content">
                            <div class="modal-header justify-content-between">
                                <h5 style="font-family:Montserrat;font-size:16pt;font-weight:900;"
                                    class="modal-title text-center w-100"><img width="30" height="30"
                                        src="<?php echo url('/icon/mo_cancel.svg'); ?>" />&nbsp;&nbsp;BOOKING #{{ $booking->id }}: SCHEDULE
                                    <hr>
                                </h5>
                            </div>
                            <div class="modal-body justify-content-between">
                                <p class="text-center" style="font-size:12pt;">
                                    Sorry, you cannnot Re-Schedule a booking once you have Checked IN.
                                </p>
                            </div>
                            <div class="modal-footer justify-content-center">
                                <button type="button" class="btn btn-primary" data-dismiss="modal">Ok</button>

                            </div>
                        </div>

                    </div>
                </div>
                <!-- Modal -->


                <!-- Modal -->
                <div class="modal fade" id="cancelmodal" role="dialog">
                    <div class="modal-dialog">

                        <!-- Modal content-->
                        <div class="modal-content">
                            <div class="modal-header justify-content-between">
                                <h5 style="font-family:Montserrat;font-size:16pt;font-weight:900;"
                                    class="modal-title text-center w-100"><img width="30" height="30"
                                        src="<?php echo url('/icon/mo_cancel.svg'); ?>" />&nbsp;&nbsp;BOOKING #{{ $booking->id }}: CANCEL
                                    <hr>
                                </h5>
                            </div>
                            <div class="modal-body justify-content-between">
                                <p class="text-center" style="font-size:12pt;">Are you sure you want to Cancel this
                                    Booking?</p>
                                <p class="text-center" style="font-size:8pt;">Cancellation Fees may Apply.</p>
                            </div>
                            <div class="modal-footer justify-content-center">
                                <form method="post" action="{{ route('user.bookings.get.cancel') }}">
                                    @csrf
                                    <input type="hidden" class="form-control" name="booking_id" id="booking_id"
                                        value="{{ $booking->id }}">
                                    <button type="submit" id="cancelconfirmyes"
                                        class="btn btn-primary modalbtn">Yes</button>
                                </form>
                                <button type="button" class="btn btn-primary" data-dismiss="modal">No</button>
                            </div>
                        </div>

                    </div>
                </div>
                <!-- Modal -->


                <div class="modal fade" id="schedulemodal" role="dialog">
                    <div class="modal-dialog">

                        <!-- Modal content-->
                        <div class="modal-content" style="padding-bottom:15px;">
                            <div class="modal-header text-center justify-content-between">
                                <h5 style="font-family:Montserrat;font-size:16pt;font-weight:900;"
                                    class="modal-title tex-center w-100"><img width="30" height="30"
                                        src="<?php echo url('/icon/mo_calendar.svg'); ?>" />&nbsp;&nbsp;BOOKING #{{ $booking->id }}: SCHEDULE
                                    <hr>
                                </h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <hr>

                            <div class="modal-body text-center" style="margin-top:-13%;">
                                <p id="msgbox" style="margin-top:10%;"></p>
                                <script type="text/javascript" src="{{ asset('js/datepikernew.js') }}"></script>

                                <style>
                                    #view`Calendar {
                                        text-align: center;
                                        display: block;
                                        width: 100%;
                                        color: rgb(81, 145, 250);
                                        margin-bottom: 15px;
                                        font-weight: 600;
                                    }

                                    #viewAvailabilityCalendar:hover {
                                        color: rgb(25, 93, 250);
                                        text-decoration: none;
                                    }

                                    .fc-button {
                                        text-transform: capitalize !important;
                                    }

                                    .fc-event-time {
                                        display: none;
                                    }

                                    .fc-event {
                                        cursor: pointer;
                                    }

                                    .fc-event-title {
                                        word-break: break-all;
                                        white-space: normal;
                                    }

                                    #alreadyBookedFor {
                                        display: none;
                                    }

                                    #alreadyBookedFor label {
                                        color: #333;
                                        font-size: 13px;
                                    }

                                    #alreadyBookedFor ul {
                                        padding: 0;
                                        margin: 0;
                                        list-style: none;
                                    }

                                    #alreadyBookedFor ul li {
                                        font-size: 13px;
                                        font-weight: 400;
                                    }

                                    #availabilityTimeCalendar .fc-v-event,
                                    #availabilityTimeCalendar .fc-event {
                                        background: #ed5959;
                                        border-color: #ed5959;
                                        text-align: center;
                                        color: #fff;
                                    }

                                    #availabilityTimeCalendar .fc-daygrid-event-dot {
                                        display: none;
                                    }

                                    #availabilityTimeCalendar .fc-more-link {
                                        background: #ed5959;
                                        word-break: break-all;
                                        white-space: normal;
                                        color: #fff;
                                        padding: 5px;
                                        border-radius: 5px;
                                    }

                                    #availabilityTimeCalendar .fc-event {
                                        background: #ed5959;
                                        word-break: break-all;
                                        white-space: normal;
                                        color: #fff;
                                        padding: 0 5px !important;
                                        font-weight: normal !important;
                                    }

                                    #spaceBookBtn.disabled {
                                        opacity: 0.3;
                                    }

                                    button#reschedulebutton.btn.btn-primary.modalbtn.disabled {
                                        background-color: lightgrey;
                                    }

                                    ::-webkit-calendar-picker-indicator {
                                        margin-right: 15px;
                                    }

                                    .dropdown-container select::-ms-expand {
                                        display: none;
                                    }

                                    select::-webkit-dropdodown-picker-indicator {
                                        margin-right: 10px;
                                    }
                                </style>
                                <input type="hidden" id="booking_id" name="booking_id" value="{{ $booking->id }}" />
                                <div class="date-select"
                                    style="background-color:#FFFFFF;padding-left:10px;padding-right:10px;padding-bottom:5px;">
                                    <div class="detailsbooking fulwidthm left mgnB10 pdgB15 dobordergry mt-5 mb-3">
                                        <div class="input-daterange" id="datepicker">
                                            <div
                                                class="detailformrow mgnB15 left fulwidthm @if (!$showTimingOption) non-time @endif">
                                                <div class="dateInputC justify-content-between">
                                                    <img width="30" height="30" src="<?php echo url('/icon/mo_arrive.svg'); ?>">
                                                    <input id="dpd1x1" name="newstart_date"
                                                        style="width:35%;border-radius:10px;border-top:1px solid lightgrey;border-left:1px solid lightgrey;border-color:lightgrey;height:50px;"
                                                        type="date" value="<?php echo date('Y-m-d', strtotime($booking->start_date)); ?>">
                                                    &nbsp;&nbsp;
                                                    <select class="selectsearch"
                                                        style="height:50px;width:26%;border-radius:10px;border-color:lightgrey;"
                                                        name="newstart_hour" id="start_hour">
                                                        <option value="">Start Time</option>
                                                        @foreach ($allDayTimeSlots as $slot)
                                                            {{-- @if ($slot >= $space->available_from && $slot <= $space->available_to) --}}
                                                                <option @if ($slot == "00:00") selected  @endif
                                                                    value="{{ $slot }}"> 
                                                                    {{ $slot }}</option>
                                                            {{-- @endif --}}
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="clearfix">
                                                &nbsp;&nbsp;&nbsp;&nbsp;
                                            </div>
                                            <div
                                                class="detailformrow mgnB15 left fulwidthm  @if (!$showTimingOption) non-time @endif">
                                                <div class="dateInputC justify-content-between">
                                                    <img width="30" height="30" src="<?php echo url('/icon/mo_depart.svg'); ?>">
                                                    <input id="dpd2x2" name="newend_date" placeholder="End Date"
                                                        style="width:35%;border-radius:10px;border-left:1px solid lightgrey;border-top:1px solid lightgrey;border-color:lightgrey;height:50px;"
                                                        type="date" value="<?php echo date('Y-m-d', strtotime($booking->end_date)); ?>">
                                                    &nbsp;&nbsp;
                                                    <select class="selectsearch"
                                                        style="width:26%;height:50px;border-radius:10px;border-color:lightgrey;"
                                                        name="newend_hour" id="end_hour">
                                                        <option value="" selected>End Time</option>
                                                        @foreach ($allDayTimeSlots as $slot)
                                                            {{-- @if ($slot >= $space->available_from && $slot <= $space->available_to) --}}
                                                                <option @if ($slot == "23:00") selected  @endif
                                                                    value="{{ $slot }}">
                                                                    {{ $slot }}</option>
                                                            {{-- @endif --}}
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="clearfix"></div>
                                        <div id="msgx-error" align="left" class="alert alert-danger"
                                            style="display:none;"></div>
                                        <div id="msgx-success" align="left" class="alert alert-success"
                                            style="display:none;"></div>
                                        <div id="loading-image" align="center" style="display:none;">
                                            <img src="/images/loading.gif" width="100px">
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="modal-footer justify-content-between">
                                <form method="post" action="{{ route('user.booking.reschedule') }}">
                                    @csrf
                                    <input type="hidden" class="form-control" name="booking_id" id="booking_id"
                                        value="{{ $booking->id }}">
                                    <input type="hidden" class="form-control" name="newstart_date" id="newstart_date"
                                        value="">
                                    <input type="hidden" class="form-control" name="newend_date" id="newend_date"
                                        value="">
                                    <button type="submit" id="rescheduleyes" class="btn btn-primary modalbtn">Save
                                        Changes</button>
                                    <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
                                </form>
                            </div>
                            <div class="detailformrow mgnB15  fulwidth avl-cal text-center w-100">
                                <a href="" id="openCalendar"
                                    style="font-size:10pt;margin-left:5px;margin-bottom:10px;">Click Here to See
                                    Availability Calendar</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Modal -->


                <!-- Modal -->
                <div class="modal fade" id="termsmodal" role="dialog">
                    <div class="modal-dialog modal-dialog-scrollable modal-lg">

                        <!-- Modal content-->
                        <div class="modal-content">
                            <div class="modal-header justify-content-between">
                                <h5 style="font-family:Montserrat;font-size:16pt;font-weight:900;"
                                    class="modal-title text-center w-100">&nbsp;&nbsp;Your Host's Terms of Service
                                    <hr />
                                </h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <p class="text-center" style="font-size:12pt;">
                                    <?php echo $space->tos; ?>
                                </p>
                            </div>
                            <div class="modal-footer justify-content-center">
                                <button type="button" class="btn btn-primary" data-dismiss="modal">Ok</button>

                            </div>
                        </div>

                    </div>
                </div>
                <!-- Modal -->



                <!-- Modal -->

                <div id="availabilityCalendar" class="modal" tabindex="-1" role="dialog"
                    style="vertical-align: middle;">
                    <div class="modal-dialog modal-lg modal-dialog-centered" style="vertical-align: middle;">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 style="font-family:Montserrat;font-size:16pt;font-weight:900;" class="modal-title">
                                    Availability Calendar</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p style="text-align: center;padding: 15px;font-size: 17px;font-weight: 600;">Unavailable
                                    times are
                                    marked in calendar, rest time can be booked.</p>
                                <div id='availabilityTimeCalendar'></div>
                            </div>
                        </div>
                    </div>
                </div>




                <!-- Modal -->
                <div class="modal fade" id="contacthostmodal" role="dialog">
                    <div class="modal-dialog">

                        <!-- Modal content-->
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 style="font-family:Montserrat;font-size:16pt;font-weight:900;" class="modal-title">
                                    Contact Guest</h4>
                            </div>
                            <div class="modal-body">
                                <form method="post" class="" action="{{ route('user.booking.contactguest') }}">
                                    @csrf
                                    <input type="hidden" class="form-control" name="booking_id" id="booking_id"
                                        value="{{ $booking->id }}">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="email" id="email"
                                            value="{{ $booking->email }}">
                                    </div>

                                    <div class="form-group">
                                        <textarea rows="4" cols="50" id="emailtext1" name="emailtext1" style="align-content:left;">Your Guest has not yet checked in for #Booking No. {{ $booking->id }}. Please manually CheckIN the guest or contact them to verify if they are still going to complete their scheduled booking. 
											Booking Details : 
											</textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Listing Name:</label>
                                        <input type="text" class="form-control" name="listing_name" id="listing_name"
                                            value="{{ $space->title }}">
                                    </div>

                                    <div class="form-group">
                                        <label>Arrival Time:</label>
                                        <input type="text" class="form-control" name="arrival_time" id="arrival_time"
                                            value="{{ $booking->start_date }}">
                                    </div>

                                    <div class="form-group">
                                        <label>Departure Time:</label>
                                        <input type="text" class="form-control" name="departure_time"
                                            id="departure_time" value="{{ $booking->end_date }}">
                                    </div>

                                    <div class="form-group">
                                        <label>Cancellation Fee:</label>
                                        <input type="text" class="form-control" name="cancellation_fee"
                                            id="cancellation_fee" value="">
                                    </div>

                                    <button type="submit" id="sendemail" class="btn btn-info">Send Email</button>
                                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                                </form>
                            </div>
                            <div class="modal-footer">
                            </div>
                        </div>

                    </div>
                </div>
                <!-- Modal -->

                <!-- Modal -->
                <div class="modal fade" id="thankyoumodal" role="dialog">
                    <div class="modal-dialog">

                        <!-- Modal content-->
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 style="font-family:Montserrat;font-size:16pt;font-weight:900;" class="modal-title">
                                    Thank you Email.</h4>
                            </div>
                            <div class="modal-body">
                                <form method="post" class="" action="{{ route('user.booking.thankyouemail') }}">
                                    @csrf
                                    <input type="hidden" class="form-control" name="booking_id" id="booking_id"
                                        value="{{ $booking->id }}">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="email" id="email"
                                            value="{{ $booking->email }}">
                                    </div>

                                    <div class="form-group">
                                        <textarea rows="4" cols="50" id="emailtext" style="align-content:left;">Thanks for completing the booking for the space mentioned below :</textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Listing Name:</label>
                                        <input type="text" class="form-control" name="listing_name" id="listing_name"
                                            value="{{ $space->title }}">
                                    </div>

                                    <div class="form-group">
                                        <label>Arrival Time:</label>
                                        <input type="text" class="form-control" name="arrival_time" id="arrival_time"
                                            value="{{ $booking->start_date }}">
                                    </div>

                                    <div class="form-group">
                                        <label>Departure Time:</label>
                                        <input type="text" class="form-control" name="departure_time"
                                            id="departure_time" value="{{ $booking->end_date }}">
                                    </div>

                                    <button type="submit" id="sendemail" class="btn btn-info">Send Email</button>
                                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                                </form>
                            </div>
                            <div class="modal-footer">
                            </div>
                        </div>

                    </div>
                </div>
                <!-- Modal -->

                <div class="modal fade confirm-dialog" id="myModal" role="dialog">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-body">
                                <form method="post" action="{{ route('user.bookings.get.cancel') }}">
                                    @csrf
                                    <input type="hidden" class="form-control" name="booking_id" id="booking_id"
                                        value="{{ $booking->id }}">
                                    <h4>Are you sure want to cancel the booking?</h4>
                                    <div class="actions">
                                        <button type="submit" class="btn btn-primary reverse">Continue</button>
                                        <a href="javascript:;" class="btn btn-primary" data-dismiss="modal">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>
                <script>
                    let blockedTimes = <?= json_encode($timesNotAvailable) ?>;
                    let availabilityTimeCalendar = null;

                    function showAvailabilityCalendarModal() {
                        $("#schedulemodal").modal("hide");
                        $("#availabilityCalendar").modal("show");
                        availabilityTimeCalendar = new FullCalendar.Calendar(document.getElementById('availabilityTimeCalendar'), {
                            eventSources: [{
                                url: '{{ route('space.vendor.availability.availableDates') }}?id={{ $space->id }}',
                            }],
                            headerToolbar: {
                                left: 'prevYear,prev,next,nextYear today',
                                center: 'title',
                                right: 'dayGridMonth,timeGridWeek,timeGridDay'
                            },
                            initialView: 'dayGridMonth',
                            dayMaxEvents: true,
                            navLinks: true,
                            eventClick: function(eventInfo) {
                                let eventId = eventInfo.event.id;
                            }
                        });
                        availabilityTimeCalendar.render();
                    }


                    function showNotification(message, type = "error") {
                        window.webAlerts.push({
                            type: type,
                            message: message
                        });
                        // switch (type) {
                        // 	case "error":
                        // 		toastr.error(message);
                        // 		$("#msgx-error").html(message);
                        // 		break;
                        // 	case "success":
                        // 		toastr.success(message);
                        // 		break;
                        // 	default:
                        // 		toastr.info(message);
                        // 		break;
                        // }
                    }

                    function redirecttocheckout() {


                    }

                    function addToCart(showAlerts = true) {
                        startDate1 = $("#end_date").val();
                        startDate2 = startDate1.split(" ");
                        startDate = startDate2[0];

                        extendtime = $("#extendtime").val();
                        if (extendtime == "1 Hour") {
                            endDate1 = new Date($("#end_date").val());
                            endDate1 = endDate1.setTime(endDate1.getTime() + (1 * 60 * 60 * 1000));
                        }
                        if (extendtime == "2 Hours") {
                            endDate1 = new Date($("#end_date").val());
                            endDate1 = endDate1.setTime(endDate1.getTime() + (2 * 60 * 60 * 1000));
                        }
                        if (extendtime == "3 Hours") {
                            endDate1 = new Date($("#end_date").val());
                            endDate1 = endDate1.setTime(endDate1.getTime() + (3 * 60 * 60 * 1000));
                        }
                        endDate1 = moment(endDate1).format("YYYY-MM-DD HH:MM:SS");
                        endDate2 = endDate1.split(" ");
                        endDate = endDate2[0];

                        startHour = startDate2[1];
                        endHour = endDate2[1];
                        startAmpPm = 'AM';
                        endAmpPm = 'AM';
                        extraPrices = 0;
                        totalAduts = 0;

                        @if (Auth::check())
                        @else
                            let currentUrl = "{{ Request::url() }}";
                            let loginRedirectUrl = "{{ route('auth.redirectLogin') }}";
                            let queryData = {
                                start_hour: startHour,
                                to_hour: endHour,
                                start: startDate,
                                end: endDate,
                            };
                            let queryParams = "";
                            for (let queryKey in queryData) {
                                queryParams += queryKey + "=" + queryData[queryKey] + "&";
                            }
                            queryParams = queryParams.slice(0, -1);
                            let currentUrlPath = encodeURIComponent((currentUrl + '?' + queryParams));
                            //console.log(currentUrlPath);
                            loginRedirectUrl = loginRedirectUrl + '?redirect=' + currentUrlPath;
                            //console.log(loginRedirectUrl);
                            window.location.href = loginRedirectUrl;
                        @endif

                        $.post("{{ route('booking.addToCart') }}", {
                            service_id: {{ $booking->object_id }},
                            service_type: "space",
                            start_date: startDate,
                            end_date: endDate,
                            start_ampm: startAmpPm,
                            end_ampm: endAmpPm,
                            start_hour: startHour,
                            end_hour: endHour,
                            extra_price: extraPrices,
                            adults: totalAduts
                        }, function(response) {
                            console.log(response);
                            if (response.status == 0) {
                                if (showAlerts) {
                                    showNotification(response.message);
                                }
                            } else if (response.status == 1) {
                                window.location.href = response.url;
                            }
                        }).fail(function(response) {
                            response = response.responseJSON;
                            showNotification(response.message);
                        });


                    }

                    $(document).on("click", "#openCalendar", function() {
                        showAvailabilityCalendarModal();
                        return false;
                    });

                    function jqueryLoaded() {
                        $('.submit-group a[name="submit"]').attr("id", "spaceBookBtn");
                        $('#spaceBookBtn').addClass('disabled');

                        $("#alreadyBookedFor").hide();

                        $(document).on("change", 'input[name="start"]', function() {
                            checkTimeAvailability();
                        });

                        $(document).on("change", 'input[name="end"]', function() {
                            checkTimeAvailability();
                        });

                        $(document).on("change", 'select[name="start_hour"]', function() {
                            checkTimeAvailability();
                        });

                        $(document).on("change", 'select[name="end_hour"]', function() {
                            checkTimeAvailability();
                        });

                        $(document).on("change", 'select[name="start_ampm"]', function() {
                            checkTimeAvailability();
                        });

                        $(document).on("change", 'select[name="end_ampm"]', function() {
                            checkTimeAvailability();
                        });

                        setTimeout(e => {
                            checkTimeAvailability(false);
                        }, 2500);

                    }


                    $(document).ready(function() {
                        function alignModal() {
                            var modalDialog = $(this).find(".modal-dialog");
                            modalDialog.css("margin-top", Math.max(0, ($(window).height() - modalDialog.height()) / 2));
                        }
                        $(".modal").on("shown.bs.modal", alignModal);
                        $(window).on("resize", function() {
                            $(".modal:visible").each(alignModal);
                        });

                        $("#hostterms").click(function() {
                            $("#termsmodal").modal();
                        });


                        $(".dropdown-toggle").next(".dropdown-menu").children().on("click", function() {
                            $(this).closest(".dropdown-menu").prev(".dropdown-toggle").text($(this).text());
                            $("#changetostatus").val($(this).attr("data-value"));
                        });

                        $("#confirmmodal1 #confirmyes").click(function() {
                            id1 = $("#confirmmodal1 #booking_id").val()
                        });

                        /*
                        $("#extendmodal #confirmyes").click(function()
                        {
                        	id1=$("#extendmodal #booking_id").val();
                        	start1=$("#extendmodal #start_date").val();
                        	end1=$("#extendmodal #end_date").val();
                        	extendtime1=$("#extendmodal #extendtime").val();
                        	$.ajaxSetup({
                        					headers: {
                        						'X-CSRF-TOKEN': jQuery('meta[nxame="csrf-token"]').attr('content')
                        							  }
                        						});
                        						$.ajax({
                        							type: 'post',
                        							url: '{!! URL::route('user.booking.extendbooking') !!}',
                        							data: {'booking_id':id1},
                        							success: function (data1) 
                        							{
                        								alert(data1);
                        								if (data=="success")
                        								{
                        									
                        								}
                        								if (data=="error")
                        								{
                        								
                        								}
                        							},
                        							error: function(xhr, status, errorThrown) 
                        							{
                        								
                        							}
                        						});
                        	
                        });	
                        	*/

                        /*
                        $("#completemodal #confirmyes").click(function()
                        		{
                        				id1=$("#id").val();
                        				$.ajaxSetup({
                        					headers: {
                        						'X-CSRF-TOKEN': jQuery('meta[nxame="csrf-token"]').attr('content')
                        							  }
                        						});
                        						$.ajax({
                        							type: 'post',
                        							url: '{!! URL::route('user.booking.completebooking') !!}',
                        							data: {'id':id1},
                        							success: function (data) {
                        								alert(data);
                        								/*
                        								if (data=="success")
                        								{
                        					$(".booking-h-statuses #checkoutstatus span.box").html("<i class='fa fa-check'></i>");
                        					$(".booking-h-statuses #checkoutstatus").addClass("completed");
                        					$(".booking-h-statuses #completedstatus span.box").html("<i class='fa fa-check'></i>");
                        					$(".booking-h-statuses #completedstatus").addClass("completed");
                        								}
                        								if (data=="error")
                        								{
                        							return false;
                        								}
                        								},
                        							error: function(xhr, status, errorThrown) 
                        							{
                        								
                        							}
                        						});
                        							
                        		});
                        							*/
                        $(".booking-h-actions #reschedulebutton").click(function() {
                            bookingstatus = $("#bookingstatus").val();
                            if (bookingstatus == "scheduled" || bookingstatus == "booked") {
                                $("#schedulemodal").modal();
                            } else {
                                $("#schedulemodal1").modal();
                                /*
                                $("#schedulemodal .modal-header").addClass('justify-content-between');
                                $("#schedulemodal .modal-header").html("<img width='30px' height='30px' src='/icon/mo_cancel.svg' /><h5 style='font-family;Montserrat;font-weight:900;' class='modal-title text-center w-100'>Booking #{{ $booking->id }}: SCHEDULE</h5>");
                                $("#schedulemodal .modal-body").html("<hr><br/><p style='font-size:10pt;'>Sorry, you cannot Re-Schedule a booking once you have Checked IN.</p>");
                                $("#schedulemodal .modal-footer").html("<button type='button'   class='btn btn-primary'  data-dismiss='modal'>Ok</button>");
                                $("#schedulemodal #openCalendar").hide();
                                $("#schedulemodal").modal();
                                */
                            }
                        });

                        $(".booking-h-actions #changestatus").click(function() {
                            bookingstatus = $("#bookingstatus").val();
                            $("#confirmmodal1").modal();
                        });

                        elementselector =
                            "#schedulemodal #dpd1x1, #schedulemodal #dpd2x2, #schedulemodal #start_hour, #schedulemodal #end_hour";
                        $(elementselector).change(function() {
                            id1 = $("#schedulemodal #booking_id").val();
                            start1 = $("#schedulemodal #dpd1x1").val();
                            end1 = $("#schedulemodal #dpd2x2").val();
                            starttime1 = $("#schedulemodal #start_hour").val();
                            endtime1 = $("#schedulemodal #end_hour").val();
                            startdate1 = Date.parse(start1 + ' ' + starttime1);
                            enddate1 = Date.parse(end1 + ' ' + endtime1);
                            $("#newstart_date").val(start1 + ' ' + starttime1);
                            $("#newend_date").val(end1 + ' ' + endtime1);
                            $.ajaxSetup({
                                headers: {
                                    'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content')
                                }
                            });
                            $.ajax({
                                type: 'post',
                                url: '{!! URL::route('user.booking.verifySelectedTimes2') !!}',
                                data: {
                                    'id': id1,
                                    'start_date': start1,
                                    'end_date': end1,
                                    'start_time': starttime1,
                                    'end_time': endtime1
                                },
                                success: function(data1) {
                                    if (data1.status == "success") {
                                        $("#schedulemodal .modal-body p").css('color', 'green');
                                        $("#schedulemodal .modal-body p").html(
                                            'The Date/Time you selected is Available.');
                                    }
                                    if (data1.status == "bookingexists") {
                                        $("#schedulemodal .modal-body p").css('color', 'red');
                                        $("#schedulemodal .modal-body p").html(
                                            'The Date/Time you selected is Unavailable. Please make another selection.'
                                        );
                                    }
                                    if (data1.status == "error1") {
                                        $("#schedulemodal .modal-body p").css('color', 'red');
                                        $("#schedulemodal .modal-body p").html(
                                            'Booking Start Date Time cannot be lesser than current time. Please make another selection.'
                                        );
                                    }
                                    if (data1.status == "error2") {
                                        $("#schedulemodal .modal-body p").css('color', 'red');
                                        $("#schedulemodal .modal-body p").html(
                                            'Booking End Date Time cannot be lesser than current time. Please make another selection.'
                                        );
                                    }
                                    if (data1.status == "endtimeerror") {
                                        $("#schedulemodal .modal-body p").css('color', 'red');
                                        $("#schedulemodal .modal-body p").html(
                                            'Booking End Date Time cannot be lesser than current time. Please make another selection.'
                                        );
                                    }

                                },
                                error: function(xhr, status, errorThrown) {

                                }
                            });

                        });


                        $("#extendmodal #extendtime").change(function() {
                            id1 = $("#extendmodal #booking_id").val();
                            start1 = $("#extendmodal #start_date").val();
                            end1 = $("#extendmodal #end_date").val();
                            extendtime1 = $("#extendmodal #extendtime").val();

                            $.ajaxSetup({
                                headers: {
                                    'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content')
                                }
                            });
                            $.ajax({
                                type: 'post',
                                url: '{!! URL::route('user.booking.verifySelectedTimes1') !!}',
                                data: {
                                    'id': id1,
                                    'start_date': start1,
                                    'end_date': end1,
                                    'extendtime': extendtime1
                                },
                                success: function(data1) {
                                    if (data1.status == "success") {
                                        $("#extendmodal #confirmyes").removeClass('disabled');
                                        $("#extendmodal .modal-body p").css('color', 'green');
                                        $("#extendmodal .modal-body p").html(
                                            'Extended Booking Time Available');

                                    }
                                    if (data1.status == "bookingexists") {
                                        $("#extendmodal #confirmyes").addClass('disabled');
                                        $("#extendmodal .modal-body p").css('color', 'red');
                                        $("#extendmodal .modal-body p").html(
                                            'Extended Booking Time Not Available');

                                    }
                                },
                                error: function(xhr, status, errorThrown) {

                                }
                            });


                            $.ajax({
                                url: '{{ route('gateway.extend.update') }}',
                                data: {
                                    "extendtime": extendtime1,
                                    "booking_id": id1
                                },
                                type: 'GET',
                                success: function(data) {
                                    var json = $.parseJSON(data);
                                    $('#amount').val(json.amount);
                                    $('#orderId').val(json.orderId);
                                    $('#invoiceNumber').val(json.orderId);
                                    $('#txnToken').val(json.txnToken);
                                    $('#successUrl').val(json.successUrl);
                                }
                            });


                        });
                    });
                </script>

                <!-- Modal -->
                <div class="modal fade" id="confirmmodal1" role="dialog">
                    <div class="modal-dialog modal-md">
                        <div class="modal-content">
                            <div class="modal-header justify-content-between">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 style="font-family:Montserrat;font-size:16pt;font-weight:900;"
                                    class="modal-title text-center w-100"><img width="30" height="30"
                                        src="<?php echo url('/icon/mo_statuschange.svg'); ?>" />&nbsp;&nbsp; BOOKING #{{ $booking->id }}: MODIFY
                                    STATUS
                                    <hr>
                                </h4>
                            </div>

                            <div class="modal-body" style="text-align:center;width:100%;padding-top:19px;">
                                <form method="post" action="{{ route('user.booking.statuschange') }}">
                                    @csrf
                                    <label for="confirmtype1" style="">CURRENT STATUS</label><br />
                                    <div id="status-btn"
                                        style="margin-top:4px;padding-top:9px;justify-content:center;height:40px;width:200px;"
                                        class="status-btn <?= $booking->statusClass() ?>">
                                        <?php
                                        if ($booking->statusText() == 'checkedin') {
                                            echo 'CHECKED IN';
                                        } elseif ($booking->statusText() == 'checkedout') {
                                            echo 'CHECKED OUT';
                                        } else {
                                            echo $booking->statusText();
                                        }
                                        ?>
                                    </div><br />
                                    <input type="hidden" class="form-control" name="id" id="id"
                                        value="{{ $booking->id }}">
                                    <input type="hidden" class="form-control" name="changetostatus" id="changetostatus"
                                        value="">
                                    <label for="changetotype" style="margin-top:8px;">CHANGE STATUS TO</label>
                                    <div class="dropdown" id="changedropdown">
                                        <button class="btn btn-primary dropdown-toggle" style="width:180px;"
                                            type="button" id="dropdownMenuButton" data-toggle="dropdown"
                                            aria-haspopup="true" aria-expanded="false">
                                            ---Select---
                                        </button>
                                        <div class="dropdown-menu" style="padding-left:15px;width:180px;"
                                            aria-labelledby="dropdownMenuButton">
                                            <a class=''
                                                style='text-align:center;margin-top:5px;border-radius:20px;background-color:#ffd700;width:150px;height:30px;color:#fff;'
                                                href='#' data-value="{{\App\Helpers\Constants::BOOKING_STATUS_BOOKED}}">BOOKED</a>
                                            <a class=''
                                                style='text-align:center;margin-top:5px;border-radius:20px;background-color:#ffC107;width:150px;height:30px;'
                                                href='#' data-value="{{\App\Helpers\Constants::BOOKING_STATUS_CHECKED_IN}}">CHECKED IN</a>
                                            <a class=''
                                                style='text-align:center;margin-top:5px;border-radius:20px;background-color:#00FFFF;width:150px;height:30px;'
                                                href='#' data-value="{{\App\Helpers\Constants::BOOKING_STATUS_CHECKED_OUT}}">CHECKED OUT</a>
                                            <a class=''
                                                style='text-align:center;margin-top:5px;border-radius:20px;background-color:#7aa41f;width:150px;height:30px;color:#fff;'
                                                href='#' data-value="{{\App\Helpers\Constants::BOOKING_STATUS_COMPLETED}}">COMPLETED</a>
                                            <a class=''
                                                style='text-align:center;margin-top:5px;border-radius:20px;background-color:#d0341c;width:150px;height:30px;color:#fff;'
                                                href='#' data-value="{{\App\Helpers\Constants::BOOKING_STATUS_NO_SHOW}}">NO SHOW</a>
                                            <a class=''
                                                style='text-align:center;margin-top:5px;border-radius:20px;background-color:#d0341c;width:150px;height:30px;color:#fff;'
                                                href='#' data-value="{{\App\Helpers\Constants::BOOKING_STATUS_CANCELLED}}">CANCELLED</a>
                                        </div>
                                    </div>
                                    <!---
                      <details class="dropdown">
                       <summary	 role="button">
                       <a class="button">--Select--</a>
                       </summary>
                       <ul>
                       <li style="background-colour:#fc8800;width:150px;height:30px;"><a class='bookedclass' style="background-colour:#fc8800;width:150px;height:30px;" href="#">Booked</a></li>
                       <li style="background-colour:#ffd700;width:150px;height:30px;"><a style="background-colour:#fc8800;width:150px;height:30px;" href="#">Checked IN</a></li>
                       <li style="background-colour:#00ffff;width:150px;height:30px;"><a style="background-colour:#fc8800;width:150px;height:30px;" href="#">Checked OUT</a></li>
                       <li style="background-colour:#6b8e23;width:150px;height:30px;"><a style="background-colour:#fc8800;width:150px;height:30px;" href="#">Complete</a></li>
                      </ul>
                      </details>
                      -->
                            </div>
                            <div class="modal-footer" style="justify-content:center;">
                                <button type="submit" id="confirmyes" class="btn btn-primary">Yes</button>
                                <button type="button" id="confirmno" style="margin-top:-1px;" class="btn btn-primary"
                                    data-dismiss="modal">No</button>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Modal -->


                <!-- Modal -->
                <div class="modal fade" id="confirmmodal" role="dialog">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 style="Montserrat;font-size:16pt;font-weight:900;" class="modal-title">Confirm Modal
                                </h4>
                            </div>
                            <div class="modal-body">
                                <p></p>
                                <input type="hidden" class="form-control" id="confirmtype" value="">
                                <input type="hidden" class="form-control" name="id" id="id"
                                    value="{{ $booking->id }}">
                            </div>
                            <div class="modal-footer">
                                <button type="button" id="confirmyes" class="btn btn-default"
                                    data-dismiss="modal">Yes</button>
                                <button type="button" id="confirmno" class="btn btn-default"
                                    data-dismiss="modal">No</button>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-lg-6 col-sm-12 tab-view">

                    <div class="space-host-actions">

                        @include('User::frontend._add_to_calendar', [
                            'event' => 'Booking at ' . $booking->service->title,
                            'from' => $booking->start_date,
                            'to' => $booking->end_date,
                            'class' => 'btn btn-primary',
                        ])

                        <a class="btn btn-primary" href="javascript:;" data-toggle="modal"
                            data-target="#emailBookingDetails"><span class="material-icons">email</span>
                            <h4 class="mt-2">Email</h4>
                        </a>

                        <a class="btn btn-primary"
                            href="{{ route('site.downloadInvoice', $booking->code) }}"
                            target="_blank"><span class="material-icons">email</span>
                            <h4 class="mt-2">Download</h4>
                        </a>

                        <a class="btn btn-primary" href="{{ route('user.booking.invoice', $booking->code) }}?print=true"
                            target="_blank"><span class="material-icons">print</span>
                            <h4 class="mt-2">Print</h4>
                        </a>

                    </div>

                    <div class="row mt-3 pl-2">
                        <?php
                                if($guestReview==null){
                                ?>

                        <?php }elseif($guestReview->status){ ?>
                        <div class="promotion" style="width:100%;border-radius:25px;">
                            <div class="card" style="border-radius:25px;">
                                <div class="card-body">
                                    <div class="reviewed-card">
                                        <h2>Guest Review</h2>
                                        <div class="head">
                                            <div class="left">
                                                <div class="reviewer-image"
                                                    style="background-image: url('{{ $guestReview->author->getAvatarUrl() }}')">
                                                </div>
                                                <div class="head">
                                                    <h4><?= trim($guestReview->title) ?></h4>
                                                    <div class="body">
                                                        <p><?= trim($guestReview->content) ?></p>
                                                    </div>
                                                    <p class="time-name">
                                                        <?= $guestReview->author->first_name ?>
                                                        <?= $guestReview->author->last_name ?>,
                                                        <?= date('d M, Y', strtotime($guestReview->created_at)) ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="right">
                                                <div class="average-rate">
                                                    <h4><?= number_format($guestReview->rate_number, 1) ?></h4>
                                                    <p>User Rating</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>


                        <?php 
                                if($hostReview==null){
                                ?>
                        <div class="promotion mt-6" style="width:100%;border-radius:25px;">
                            <div class="card no-border" style="border-radius:25px;">
                                <div class="card-header">RATE YOUR GUEST</div>
                                <div class="card-body">
                                    <h5 class="card-title">No Review Yet</h5>
                                    <p class="card-text">
                                        <input type="button" data-toggle="modal"
                                            data-target="#rateGuestSingleBooking<?= $booking->id ?>"
                                            class="btn btn-primary" value="SUBMIT REVIEW">
                                    </p>
                                </div>
                            </div>
                        </div>
  
                        <div class="clearfix">&nbsp;&nbsp;</div>
                        <?php }else{ ?>
                        <div class="promotion mt-6" style="width:100%;border-radius:25px;">
                            <div class="card no-border" style="border-radius:25px;">
                                <div class="card-body">
                                    <div class="reviewed-card">
                                        <h2>Host Review</h2>
                                        <div class="head">
                                            <div class="left">
                                                <div class="reviewer-image"
                                                    style="background-image: url('{{ $hostReview->author->getAvatarUrl() }}')">
                                                </div>
                                                <div class="head">
                                                    <h4><?= trim($hostReview->title) ?></h4>
                                                    <div class="body">
                                                        <p><?= trim($hostReview->content) ?></p>
                                                    </div>
                                                    <p class="time-name">
                                                        <?= $hostReview->author->first_name ?>
                                                        <?= $hostReview->author->last_name ?>,
                                                        <?= date('d M, Y', strtotime($hostReview->created_at)) ?>
                                                        <?php
                                                        if($hostReview->status =="pending"){
                                                            echo ' - Approval Pending';
                                                        }
                                                        ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="right">
                                                <div class="average-rate">
                                                    <h4><?= number_format($hostReview->rate_number, 1) ?></h4>
                                                    <p>User Rating</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>

                    </div>


                    <div class="d-none title title-fonts sub-title">
                        <h4 class="text-uppercase mb-3">
                            <strong>&nbsp;</strong>
                        </h4>
                    </div>
                    <div class="d-none card taber-card card-default full-height card-bordered p-4 card-radious">
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs nav-tabs-fillup d-none d-md-flex d-lg-flex d-xl-flex"
                            data-init-reponsive-tabs="dropdownfx">
                            <li class="nav-item">
                                <a href="#" class="active" data-toggle="tab" data-target="#slide1"><span>About the
                                        Space</span></a>
                            </li>
                            <li class="nav-item">
                                <a href="#" data-toggle="tab" data-target="#slide2" class=""><span>House
                                        Rules</span></a>
                            </li>
                            <li class="nav-item">
                                <a href="#" data-toggle="tab" data-target="#slide3"
                                    class=""><span>FAQs</span></a>
                            </li>
                            <li class="nav-item">
                                <a href="#" data-toggle="tab" data-target="#slide4"
                                    class=""><span>Amenities</span></a>
                            </li>
                        </ul>
                        @php
                            if ($service = $booking->service) {
                                $translation = $service->translateOrOrigin(app()->getLocale());
                            }
                            $spaceSettings = \Modules\Core\Models\Settings::getSettings('space');
                        @endphp
                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div class="tab-pane slide-left active" id="slide1">
                                <div class="row">
                                    <div class="col-12">
                                        <h3>{!! $booking->service->title !!}</h3>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="image mb-3">
                                            <img src="{{ $booking->service->image_url }}">
                                        </div>
                                        <div class="icon-row small-space-info">
                                            <div class="icon-div"><span class="material-icons">location_on</span>
                                            </div>
                                            <div class="icon-details">
                                                <a href="https://www.google.com/maps/place/{{ urlencode($booking->service->address) }}"
                                                    target="_blank">
                                                    {{ $booking->service->address }}
                                                </a>
                                            </div>
                                        </div>
                                        @php
                                            $userDetails = $booking->vendor;

                                        @endphp
                                        <div class="icon-row small-space-info">
                                            <div class="icon-div"><span class="material-icons">phone</span>
                                            </div>
                                            <div class="icon-details"><a
                                                    href="tel:{{ $userDetails->phone }}">{{ $userDetails->phone }}</a>
                                            </div>
                                        </div>
                                        <div class="icon-row small-space-info">
                                            <div class="icon-div"><span class="material-icons">location_on</span>
                                            </div>
                                            <div class="icon-details "><a
                                                    href="mailto:{{ $userDetails->email }}">{{ $userDetails->email }}</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-8 pr-5 pl-5">
                                        <div class="disable-formatting">{!! $booking->service->content !!}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane slide-left" id="slide2">
                                <div class="row column-seperation">
                                    <div class="col-lg-12">
                                        <h3>House Rules</h3>
                                        <div class="disable-formatting disable-formatting-rules">{!! $booking->service->house_rules != null
                                            ? $booking->service->house_rules
                                            : $spaceSettings['space_default_house_rules'] !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane slide-left" id="slide3">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <h3>FAQs</h3>
                                        <div class="card-group space-list-faqs horizontal" id="accordion"
                                            role="tablist" aria-multiselectable="true">
                                            <?php
                                            if ($translation->faqs) {
                                                if (count($translation->faqs) <= 0) {
                                                    $translation->faqs = json_decode($spaceSettings['space_default_faqs'], true);
                                                }
                                            } else {
                                                $translation->faqs = json_decode($spaceSettings['space_default_faqs'], true);
                                            }
                                            ?>
                                            @if ($translation->faqs)
                                                @php $i = 1; @endphp
                                                @foreach ($translation->faqs as $item)
                                                    <div class="card card-default m-b-0"
                                                        style="border: 1px solid rgba(18, 18, 18, 0.1)">
                                                        <div class="card-header " role="tab"
                                                            id="heading{{ $booking->convertNumberToWord($i) }}">
                                                            <div class="card-title">
                                                                <a style="text-decoration: none" data-toggle="collapse"
                                                                    class="{{ $i != 1 ? 'collapsed' : '' }}"
                                                                    data-parent="#accordion"
                                                                    href="#collapse{{ $booking->convertNumberToWord($i) }}"
                                                                    aria-expanded="{{ $i == 1 }}"
                                                                    aria-controls="collapse{{ $booking->convertNumberToWord($i) }}">
                                                                    {{ $item['title'] }}
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <div style="visibility: visible"
                                                            id="collapse{{ $booking->convertNumberToWord($i) }}"
                                                            class="collapse {{ $i == 1 ? 'show' : '' }}"
                                                            role="tabcard"
                                                            aria-labelledby="heading{{ $booking->convertNumberToWord($i) }}">
                                                            <div class="card-body">
                                                                {{ $item['content'] }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @php $i++ @endphp
                                                @endforeach
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane slide-left" id="slide4">
                                @php
                                    $row = Modules\Space\Models\Space::where('id', $booking->service->id)
                                        ->with(['location', 'translations', 'hasWishList'])
                                        ->first();

                                @endphp
                                @if (!empty($row->location->name))
                                    @php
                                        $location = $row->location->translateOrOrigin(app()->getLocale());
                                    @endphp
                                @endif
                                <div class="g-rules">
                                    <div class="description">

                                        {{-- <div class="row">
                                        <div class="col-lg-4">
                                            <div class="key">Space Type</div>
                                        </div>
                                        <div class="col-lg-8">
                                            <div class="value">Entire Home</div>
                                        </div>
                                    </div> --}}
                                        {{-- @endif --}}
                                        {{-- @if ($row->bathroom != '') --}}
                                        <div class="row">
                                            <div class="col-lg-3">
                                                <div class="key" style="font-weight: 600;">Capacity:</div>
                                            </div>
                                            <div class="col-lg-8">
                                                <div class="value">{{ $booking->total_guests }}
                                                    {{ $booking->total_guests > 1 ? 'Guests' : 'Guest' }}</div>
                                            </div>
                                        </div>
                                        {{-- @endif --}}
                                        @if ($row->bathroom != '')
                                            <div class="row">
                                                <div class="col-lg-3">
                                                    <div class="key" style="font-weight: 600;">Bathrooms:</div>
                                                </div>
                                                <div class="col-lg-8">
                                                    <div class="value">{{ $row->bathroom }}</div>
                                                </div>
                                            </div>
                                        @endif
                                        @if ($row->available_from != '')
                                            <div class="row">
                                                <div class="col-lg-3">
                                                    <div class="key" style="font-weight: 600;">Check In Time:</div>
                                                </div>
                                                <div class="col-lg-8">
                                                    <div class="value">
                                                        {{ date('h:i A', strtotime($row->available_from)) }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if ($row->available_to != '')
                                            <div class="row">
                                                <div class="col-lg-3">
                                                    <div class="key" style="font-weight: 600;">Check Out Time:</div>
                                                </div>
                                                <div class="col-lg-8">
                                                    <div class="value">
                                                        {{ date('h:i A', strtotime($row->available_to)) }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if ($row->bed != '')
                                            <div class="row">
                                                <div class="col-lg-3">
                                                    <div class="key" style="font-weight: 600;">Beds</div>
                                                </div>
                                                <div class="col-lg-8">
                                                    <div class="value">{{ $row->bed }}</div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @php
                                    $terms_ids = $row->terms->pluck('term_id');

                                    $attributes_terms = \Modules\Core\Models\Terms::query()
                                        ->with(['translations', 'attribute'])
                                        ->find($terms_ids)
                                        ->pluck('id')
                                        ->toArray();
                                    $attributes = \Modules\Core\Models\Terms::where('attr_id', 4)->get();

                                @endphp
                                <br>
                                <h3>AMENITIES</h3>
                                <ul class="aminitlistingul mgnT20">
                                    @if (!empty($terms_ids) and !empty($attributes))
                                        @foreach ($attributes as $attribute)
                                            @if (empty($attribute['parent']['hide_in_single']))
                                                @php $terms = $attribute['child'] @endphp
                                                <li
                                                    class="detaillistingli {{ in_array($attribute->id, $attributes_terms) ? '' : 'not' }} fulwidthm mgnB10">
                                                    <i class="aminti_icon {{ $attribute->icon }}"></i>
                                                    <span style="font-size: 13.5px; font-weight:400;font-family:Roboto"
                                                        class="aminidis">{{ $attribute->name }}</span>
                                                </li>
                                            @endif
                                        @endforeach
                                    @endif
                                </ul>
                            </div>
                        </div>

                    </div>
                    <div class="d-none view-btn text-center bottom-btn">
                        <a target="_blank" href="{{ $row->getDetailUrl($include_param ?? true) }}">
                            <button class="btn btn-primary btn-lg mb-2">View Full Listing Details</button>
                        </a>

                    </div>
                </div>
            </div>
        </div>
        <!--row end booking-->
    </div>
    <!-- END CONTAINER FLUID -->
    <div class="container link-icon d-none justify-content-center pb-5">
        <div class="row mt-3 mb-5 text-center">
            <div class="col-xs-12 col-sm-12">
                <div class="btn-icon">
                    @include('User::frontend._add_to_calendar', [
                        'event' => 'Booking at ' . $booking->service->title,
                        'from' => $booking->start_date,
                        'to' => $booking->end_date,
                    ])
                </div>
                <div class="btn-icon">
                    <a style="text-decoration: none" href="{{ route('user.booking.email', $booking->id) }}"><span
                            class="material-icons">email</span>
                        <h4 class="mt-2">Email</h4>
                    </a>
                </div>
                <div class="btn-icon">
                    <a style="text-decoration: none"
                        href="{{ route('user.booking.invoice', $booking->code) }}?print=true" target="_blank"><span
                            class="material-icons">print</span>
                        <h4 class="mt-2">Print</h4>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="container info-div d-none">
        <div class="row mt-5 mb-5">
            <div class="col-sm-12 col-lg-12">
                <div class="row">
                    <div class="col-xs-12 col-sm-4 mb-5">
                        <div class="second-div">
                            <div class="image"
                                style="background-image:url({{ asset('user_assets/img/grow-bussiness.jpg') }})">
                            </div>
                            <h3 class="mt-3 mb-3">Grow Your Business</h3>
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis maximus tempus leo
                                nec interdum. Vivamus id lorem eget sapien consequat euismod id eget
                                libero. </p>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-4 mb-5">
                        <div class="second-div">
                            <div class="image"
                                style="background-image:url({{ asset('user_assets/img/learn.jpg') }})">
                            </div>
                            <h3 class="mt-3 mb-3">Learn</h3>
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis maximus tempus leo
                                nec interdum. Vivamus id lorem eget sapien consequat euismod id eget
                                libero. </p>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-4 mb-5">
                        <div class="second-div">
                            <div class="image"
                                style="background-image:url({{ asset('user_assets/img/take-brake.jpg') }})"></div>
                            <h3 class="mt-3 mb-3">Take a Break</h3>
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis maximus tempus leo
                                nec interdum. Vivamus id lorem eget sapien consequat euismod id eget
                                libero. </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @include('User::frontend._rate_guest')

@endsection
