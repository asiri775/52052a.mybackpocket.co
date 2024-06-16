<?php

namespace Modules\Space\Controllers;

use App\Helpers\CodeHelper;
use App\Helpers\Constants;
use ICal\ICal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Modules\Booking\Models\Booking;
use Modules\FrontendController;
use Modules\Space\Models\Space;
use Modules\Space\Models\SpaceBlockTime;
use Modules\Space\Models\SpaceDate;
use Modules\User\Models\User;

use Modules\Space\Models\PostalCodesAndTimeZone;
use Modules\Space\Models\Timezones_Reference;

class AvailabilityController extends FrontendController
{

    protected $spaceClass;
    /**
     * @var SpaceDate
     */
    protected $spaceDateClass;

    /**
     * @var Booking
     */
    protected $bookingClass;

    protected $indexView = 'Space::frontend.user.availability';

    public function __construct()
    {
        parent::__construct();
        $this->spaceClass = Space::class;
        $this->spaceDateClass = SpaceDate::class;
        $this->bookingClass = Booking::class;
    }

    public function callAction($method, $parameters)
    {
        if (!Space::isEnable()) {
            return redirect('/');
        }
        return parent::callAction($method, $parameters); // TODO: Change the autogenerated stub
    }

    public function confirmBlockDate(Request $request, $id)
    {
        $startDateMain = $request->get('start');
        $toDateMain = $request->get('end');
        $status = 'ok';
        $message = '';
        if ($startDateMain != null && $toDateMain != null) {
            $bookingBetween = Booking::whereRaw("`status` != 'draft' and `object_model` = 'space' and `object_id` = " . $id . " and ( (`start_date` BETWEEN '" . $startDateMain . "' and '" . $toDateMain . "') OR (`end_date` BETWEEN '" . $startDateMain . "' and '" . $toDateMain . "') OR ('$startDateMain' BETWEEN `start_date` and `end_date`) OR ('$toDateMain' BETWEEN `start_date` and `end_date`) )")->orderBy('start_date')->get();
            if ($bookingBetween != null && count($bookingBetween) > 0) {
                $status = 'error';
                $message = 'There are some booking(s) in select date';
            }
            if ($status == 'ok') {
                $blockedBetween = SpaceBlockTime::whereRaw("`bravo_space_id` = " . $id . " and ( (`from` BETWEEN '" . $startDateMain . "' and '" . $toDateMain . "') OR (`to` BETWEEN '" . $startDateMain . "' and '" . $toDateMain . "') OR ('$startDateMain' BETWEEN `from` and `to`) OR ('$toDateMain' BETWEEN `from` and `to`) )")->get();
                if ($blockedBetween != null && count($blockedBetween) > 0) {
                    $status = 'error';
                    $message = 'Selected date already blocked';
                }
            }
        }
        return response()->json(['status' => $status, 'message' => $message]);
    }
 
    public function calendarEvents()
    {
        $events = [];
        $id = $_GET['id'];

        $bookings = Booking::where('object_model', 'space')->where('object_id', $id)->get();
        // $bookings = Booking::whereIn('status', [Booking::PROCESSING, Booking::PARTIAL_PAYMENT])->where('object_model', 'space')->where('object_id', $id)->get();
        if ($bookings != null) {
            foreach ($bookings as $booking) {
                $customerName = '';
                $customer = \App\Models\User::where('id', $booking->customer_id)->first();
                if ($customer != null) {
                    $customerName = $customer->last_name;
                }  
                $events[] = [
                    'title' => date('H:i', strtotime($booking->start_date)) . " - " . date('H:i', strtotime($booking->end_date)) . ': #' . $booking->id . ' ' . $customerName,
                    'start' => $booking->start_date,
                    'end' => $booking->end_date,
                    'classNames' => ['processing'],
                    'url' => url('user/booking-details/' . $booking->id),
                    'other' => [
                        'id'=> $booking->id,
                        'spaceId'=> $booking->object_id,
                        'startDate'=> $booking->start_date,
                        'endDate'=> $booking->end_date,
                    ]
                ];
            }
        }

        $bookings = Booking::whereIn('status', [
            Constants::BOOKING_STATUS_BOOKED,
            Constants::BOOKING_STATUS_CHECKED_IN,
            Constants::BOOKING_STATUS_CHECKED_OUT,
            Constants::BOOKING_STATUS_COMPLETED
        ])->where('object_model', 'space')->where('object_id', $id)->get();
        if ($bookings != null) {
            foreach ($bookings as $booking) {
                $customerName = '';
                $customer = \App\Models\User::where('id', $booking->customer_id)->first();
                if ($customer != null) { 
                    $customerName = $customer->last_name;
                }
                $events[] = [
                    'title' => date('H:i', strtotime($booking->start_date)) . " - " . date('H:i', strtotime($booking->end_date)) . ': #' . $booking->id . ' ' . $customerName,
                    'start' => $booking->start_date,
                    'end' => $booking->end_date,
                    'classNames' => ['confirmed'],
                    'url' => url('user/booking-details/' . $booking->id),
                    'other' => [
                        'id'=> $booking->id,
                        'spaceId'=> $booking->object_id,
                        'startDate'=> $booking->start_date,
                        'endDate'=> $booking->end_date,
                    ]
                ];
            }
        }

        $blocks = SpaceBlockTime::where('bravo_space_id', $id)->get();
        if ($blocks != null) { 
            foreach ($blocks as $block) {
                $events[] = [
                    'title' => date('H:i', strtotime($block->from)) . " - " . date('H:i', strtotime($block->to)) . ': Blocked',
                    'start' => $block->from,
                    'end' => $block->to,
                    'classNames' => ['blocked'],
                ];
            }
        }

        return response()->json($events);
    }


    public function calendcalendarAppointmentsarEvents()
    {
        $events = [];

        $userID = Auth::id();

        $id = $_GET['id'];

        if ($id != null) {

            $bookings = Booking::where('object_model', 'space')->where(function ($query) use ($userID) {
                $query->where('customer_id', $userID)->orWhere('vendor_id', $userID);
            })->whereIn('status', [
                Constants::BOOKING_STATUS_BOOKED,
                Constants::BOOKING_STATUS_CHECKED_IN,
                Constants::BOOKING_STATUS_CHECKED_OUT,
                Constants::BOOKING_STATUS_COMPLETED
            ])->where('object_id', $id)->get();
        } else {
            $bookings = Booking::where('object_model', 'space')->where(function ($query) use ($userID) {
                $query->where('customer_id', $userID)->orWhere('vendor_id', $userID);
            })->whereIn('status', [
                Constants::BOOKING_STATUS_BOOKED,
                Constants::BOOKING_STATUS_CHECKED_IN,
                Constants::BOOKING_STATUS_CHECKED_OUT,
                Constants::BOOKING_STATUS_COMPLETED
            ])->get();
        }


        if ($bookings != null) {
            foreach ($bookings as $booking) {
                $events[] = [
                    'title' => date('H:i', strtotime($booking->start_date)) . " - " . date('H:i', strtotime($booking->end_date)) . ': #' . $booking->id,
                    'start' => $booking->start_date,
                    'end' => $booking->end_date,
                    'classNames' => ['processing'],
                    'url' => url('user/booking-details/' . $booking->id),
                    'other' => [
                        'id'=> $booking->id,
                        'spaceId'=> $booking->object_id,
                        'startDate'=> $booking->start_date,
                        'endDate'=> $booking->end_date,
                    ]
                ];
            }
        }

        return response()->json($events);
    }

    public function availableDates()
    {
        $startMain = date('Y-m-d') . " 00:00:00";
        $id = $_GET['id'];
        $availabilities = [];
        $start = (isset($_GET['start']) ? $_GET['start'] : null);
        $end = (isset($_GET['end']) ? $_GET['end'] : null);
        if ($start != null && $end != null) {
            $start = date('Y-m-d', strtotime($start));
            $end = date('Y-m-d', strtotime($end));
            $dates = Constants::getDatesFromRange($start, $end);
            if ($dates != null) {
                foreach ($dates as $date) {
                    $startDateMain = $date . " 00:00:00";
                    $toDateMain = $date . " 23:59:59";
                    if ($startDateMain >= $startMain) {
                        $datesNotAvailable = [];

                        //echo $startDateMain . " - " . $toDateMain . PHP_EOL;

                        //get bookings between
                        $bookingBetween = Booking::whereRaw("`status` != 'draft' and `object_model` = 'space' and `object_id` = " . $id . " and ( (`start_date` BETWEEN '" . $startDateMain . "' and '" . $toDateMain . "') OR (`end_date` BETWEEN '" . $startDateMain . "' and '" . $toDateMain . "') OR ('$startDateMain' BETWEEN `start_date` and `end_date`) OR ('$toDateMain' BETWEEN `start_date` and `end_date`) )")->orderBy('start_date')->get();
                        if ($bookingBetween != null && count($bookingBetween) > 0) {
                            foreach ($bookingBetween as $bookingRow) { {
                                    $s = $bookingRow->start_date;
                                    $e = $bookingRow->end_date;

                                    $npStart = date('H:i', strtotime($s));
                                    if ($startDateMain >= $s) {
                                        $npStart = '00:00';
                                    }

                                    $npEnd = date('H:i', strtotime($e));
                                    if ($e >= $toDateMain) {
                                        $npEnd = '23:59';
                                    }

                                    //$datesNotAvailable[] = $npStart . " - " . $npEnd;
                                    $availabilities[] = [
                                        'title' => "Booked For MyOffice Client " . $npStart . " - " . $npEnd,
                                        'start' => $date . " " . $npStart . ":00",
                                        'end' => $date . " " . $npEnd . ":59",
                                    ];
                                }
                            }
                        }

                        $blockedBetween = SpaceBlockTime::whereRaw("`bravo_space_id` = " . $id . " and ( (`from` BETWEEN '" . $startDateMain . "' and '" . $toDateMain . "') OR (`to` BETWEEN '" . $startDateMain . "' and '" . $toDateMain . "') OR ('$startDateMain' BETWEEN `from` and `to`) OR ('$toDateMain' BETWEEN `from` and `to`) )")->get();
                        if ($blockedBetween != null && count($blockedBetween) > 0) {
                            foreach ($blockedBetween as $blockedRow) { {
                                    $s = $blockedRow->from;
                                    $e = $blockedRow->to;

                                    $npStart = date('H:i', strtotime($s));
                                    if ($startDateMain >= $s) {
                                        $npStart = '00:00';
                                    }

                                    $npEnd = date('H:i', strtotime($e));
                                    if ($e >= $toDateMain) {
                                        $npEnd = '23:59';
                                    }

                                    //$datesNotAvailable[] = $npStart . " - " . $npEnd;
                                    $availabilities[] = [
                                        'title' => "Unavailable " . $npStart . " - " . $npEnd,
                                        'start' => $date . " " . $npStart . ":00",
                                        'end' => $date . " " . $npEnd . ":59",
                                    ];
                                }
                            }
                        }

                        // print_r($datesNotAvailable);

                    }
                }
            }
        }
        return response()->json($availabilities);
    }

    public function index(Request $request)
    {
        $this->checkPermission('space_create');

        $q = $this->spaceClass::query();

        if ($request->query('s')) {
            $q->where('title', 'like', '%' . $request->query('s') . '%');
        }

        if (!$this->hasPermission('space_manage_others')) {
            $q->where('create_user', $this->currentUser()->id);
        }

        $q->orderBy('bravo_spaces.id', 'desc');

        $rows = $q->paginate(15);

        $current_month = strtotime(date('Y-m-01', time()));

        if ($request->query('month')) {
            $date = date_create_from_format('m-Y', $request->query('month'));
            if (!$date) {
                $current_month = time();
            } else {
                $current_month = $date->getTimestamp();
            }
        }
        $breadcrumbs = [
            [
                'name' => __('Spaces'),
                'url' => route('space.vendor.index')
            ],
            [
                'name' => __('Availability'),
                'class' => 'active'
            ],
        ];
        $page_title = __('Spaces Availability');

        return view($this->indexView, compact('rows', 'breadcrumbs', 'current_month', 'page_title', 'request'));
    }

    public function verifySelectedTimes()
    {
        $response = [
            'status' => 'error',
            'message' => 'Failed to check availability',
            'price' => 0,
            'start_time' => null,
            'end_time' => null
        ];

        $id = isset($_POST['id']) ? trim($_POST['id']) : null;
        $bookingId = isset($_POST['bookingId']) ? trim($_POST['bookingId']) : null;

        if ($id != null) {
            $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : null;
            $end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : null;
            $start_ampm = isset($_POST['start_ampm']) ? trim($_POST['start_ampm']) : null;
            $end_ampm = isset($_POST['end_ampm']) ? trim($_POST['end_ampm']) : null;
            $startHour = isset($_POST['start_hour']) ? trim($_POST['start_hour']) : null;
            $endHour = isset($_POST['end_hour']) ? trim($_POST['end_hour']) : null;

            $start_dateTemp = explode('/', $start_date);
            $start_dateTemp = $start_dateTemp[2] . "-" . $start_dateTemp[0] . "-" . $start_dateTemp[1];
            $end_dateTemp = explode('/', $end_date);
            $end_dateTemp = $end_dateTemp[2] . "-" . $end_dateTemp[0] . "-" . $end_dateTemp[1];

            $start_dateTemp = $start_dateTemp . " " . $startHour . ":00";
            $end_dateTemp = $end_dateTemp . " " . $endHour . ":00";

            $totalHoursTemp = CodeHelper::getHoursBetweenDates($start_dateTemp, $end_dateTemp);

            $response['total_hours'] = $totalHoursTemp;

            $space = Space::where('id', $id)->first();
            if ($space != null) {

                $process = true;

                if ($bookingId != null) {
                    $booking = Booking::where('id', $bookingId)->first();
                    if ($booking != null) {

                        if ($booking->start_date == $start_dateTemp && $booking->end_date == $end_dateTemp) {
                            $process = false;
                            $response['status'] = 'success';
                            $response['message'] = 'No Changes';

                            $response['price'] = $booking->total_before_fees;
                            $response['priceFormatted'] = CodeHelper::formatPrice($booking->total_before_fees);
                        }
                    } else {
                        $process = false;
                        $response['status'] = 'error';
                        $response['message'] = 'Booking not found';
                    }
                } else {
                    $bookingId = -1;
                }

                $response['space_title'] = $space->title;

                if ($process) {

                    if ($start_date != null && $end_date != null) {

                        $start_hour_state = date("A", strtotime($startHour));
                        $end_hour_state = date("A", strtotime($endHour));

                        //if ($start_hour_state == $start_ampm && $end_hour_state == $end_ampm) {
                        if (true) {

                            $start_date = explode('/', $start_date);
                            $start_date = $start_date[2] . "-" . $start_date[0] . "-" . $start_date[1];
                            $end_date = explode('/', $end_date);
                            $end_date = $end_date[2] . "-" . $end_date[0] . "-" . $end_date[1];

                            $startDateMain = $start_date . " 00:00:01";
                            $toDateMain = $end_date . " 23:59:59";

                            if ($startHour != null && $endHour != null) {

                                if ($space->long_term_rental == 1) {
                                    // $startHour = $space->available_from;
                                    // $endHour = $space->available_to;
                                }

                                //$startDate = $start_date . " " . $startHour . ":01 " . $start_ampm;
                                $startDate = $start_date . " " . $startHour . ":01 ";
                                $startDate = date('Y-m-d H:i:s', strtotime($startDate));

                                //$toDate = $end_date . " " . $endHour . ":00 " . $end_ampm;
                                $toDate = $end_date . " " . $endHour . ":00 ";
                                $toDate = date('Y-m-d H:i:s', (strtotime($toDate) - 0));

                                $nowDateTime = date('Y-m-d H:i:s');

                                $zipcode = $space->zip;
                                $city = $space->city;
                                $state = $space->state;
                                $country = $space->country;
                                $postalcodedata = PostalCodesAndTimeZone::Where('province_abbr', $state)->orWhere('postalcode', $zipcode)->orWhere('city', strtoupper($city))->first();
                                if (!empty($postalcodedata)) {
                                    $timezonecode = $postalcodedata->timezone;
                                    $timezonedata = Timezones_Reference::where('id', $timezonecode)->first();
                                    $timezone = $timezonedata->php_time_zones;
                                } else {
                                    $timezone = "Canada/Eastern";
                                }
                                $nowDateTime = $systemdate = \Carbon\Carbon::now()->tz($timezone);

                                //$nowDateTime=\Carbon\Carbon::now()->tz($timezone);
                                // $nowDateTime = date('Y-m-d H:i:s');

                                $response['requestInfo'] = [
                                    'startDate' => $startDate,
                                    'toDate' => $toDate,
                                    'nowDateTime' => $nowDateTime
                                ];

                                if (strtotime($startDate) >= strtotime($nowDateTime)) {

                                    if (strtotime($toDate) > strtotime($startDate)) {

                                        $response['start_time'] = $startDate;
                                        $response['end_time'] = $toDate;

                                        $startHourOnly = date('H', strtotime($startDate));
                                        $endHourOnly = date('H', strtotime($toDate));

                                        if ($start_date < $end_date) {
                                            $differenceHours = 24;
                                        } else {
                                            //$differenceHours = ($endHourExploded[0] * 1) - ($startHourExploded[0] * 1);
                                            $differenceHours = ($endHourOnly) - ($startHourOnly);
                                            $response['difference_hours'] = $differenceHours;
                                        }

                                        if ($space->min_hour_stays == null) {
                                            $space->min_hour_stays = 0;
                                        }

                                        if ($differenceHours >= $space->min_hour_stays) {

                                            $toDate = date('Y-m-d H:i:s', (strtotime($toDate) - 1));

                                            $response['date_range'] = $startDateMain . " -> " . $toDateMain;
                                            $response['bookings'] = [];
                                            $bookingBetween = Booking::whereRaw("(`status` != 'complete' OR `status` != 'paid') and `object_model` = 'space' and `id` != " . $bookingId . " and `object_id` = " . $id . " and ( (`start_date` BETWEEN '" . $startDateMain . "' and '" . $toDateMain . "') OR (`end_date` BETWEEN '" . $startDateMain . "' and '" . $toDateMain . "') OR ('$startDateMain' BETWEEN `start_date` and `end_date`) OR ('$toDateMain' BETWEEN `start_date` and `end_date`) )")->orderBy('start_date')->get();
                                            if ($bookingBetween != null && count($bookingBetween) > 0) {
                                                foreach ($bookingBetween as $bookingBet) {
                                                    $bookInfo = date('d M H:i', strtotime($bookingBet->start_date)) . " - " . date('d M H:i', strtotime($bookingBet->end_date));
                                                    $response['bookings'][] = $bookInfo;
                                                }


                                                if ($startHour != null && $endHour != null) {
                                                    if ($toDate > $startDate) {
                                                        $response['date_range_nxt'] = $startDate . " -> " . $toDate;

                                                        $bookingBetween = Booking::whereRaw("(`status` != 'complete' OR `status` != 'paid') and `object_model` = 'space' and `id` != " . $bookingId . " and `object_id` = " . $id . "
                                                and ( (`start_date` BETWEEN '" . $startDate . "' and '" . $toDate . "')
                                                OR (`end_date` BETWEEN '" . $startDate . "' and '" . $toDate . "')
                                                OR ('$startDate' BETWEEN `start_date` and `end_date`)  
                                                OR ('$toDate' BETWEEN `start_date` and `end_date`) )")->get();

                                                        if ($bookingBetween != null && count($bookingBetween) > 0) {
                                                            $response['status'] = 'error';
                                                            $response['message'] = 'Timings not available';
                                                        } else {
                                                            $response['status'] = 'success';
                                                            $response['step'] = '0x0';
                                                            $response['message'] = 'Successfully checked the availability';
                                                        }
                                                    } else {
                                                        $response['status'] = 'error';
                                                        $response['message'] = 'To date should be greater than from date';
                                                    }
                                                } else {
                                                    $response['status'] = 'pending';
                                                    $response['step'] = '0x1';
                                                }
                                            } else {
                                                if ($startHour != null && $endHour != null) {
                                                    $response['status'] = 'success';
                                                    $response['message'] = 'Successfully checked the availability';
                                                    $response['step'] = '0x2';
                                                } else {
                                                    $response['status'] = 'pending';
                                                }
                                            }

                                            //check if hour are out then decided
                                            if ($space->available_from != null && $space->available_to != null) {
                                                $availableFrom = $space->available_from;
                                                $availableTo = $space->available_to;
                                                /* var_dump($availableFrom." -> ".$startHour);
                                             var_dump($availableTo." -> ".$endHour);
                                             var_dump(($startHour < $availableFrom));
                                             var_dump(($endHour > $availableTo));die;*/
                                                if (($startHour < $availableFrom) || ($startHour > $availableTo) || ($endHour < $availableFrom) || ($endHour > $availableTo)) {
                                                    $response['status'] = 'error';
                                                    $response['message'] = 'Space is only available from ' . $availableFrom . "-" . $availableTo;
                                                }
                                            }


                                            //block dates
                                            $response['date_range_block'] = $startDateMain . " -> " . $toDateMain;

                                            $blockedBetween = SpaceBlockTime::whereRaw("`bravo_space_id` = " . $id . " and ( (`from` BETWEEN '" . $startDateMain . "' and '" . $toDateMain . "') OR (`to` BETWEEN '" . $startDateMain . "' and '" . $toDateMain . "') OR ('$startDateMain' BETWEEN `from` and `to`) OR ('$toDateMain' BETWEEN `from` and `to`) )")->get();
                                            if ($blockedBetween != null && count($blockedBetween) > 0) {
                                                foreach ($blockedBetween as $blockBetween) {
                                                    $bookInfo = date('d M H:i', strtotime($blockBetween->from)) . " - " . date('d M H:i', strtotime($blockBetween->to));
                                                    $response['bookings'][] = $bookInfo;
                                                }
                                            }

                                            if ($response['status'] == 'success') {
                                                //check in blocked dates
                                                if ($startHour != null && $endHour != null) {
                                                    $response['date_range_block_nxt'] = $startDate . " -> " . $toDate;
                                                    $blockedBetween = SpaceBlockTime::whereRaw("`bravo_space_id` = " . $id . " and ( (`from` BETWEEN '" . $startDate . "' and '" . $toDate . "') OR (`to` BETWEEN '" . $startDate . "' and '" . $toDate . "') OR ('$startDate' BETWEEN `from` and `to`) OR ('$toDate' BETWEEN `from` and `to`) )")->get();
                                                    if ($blockedBetween != null && count($blockedBetween) > 0) {
                                                        $response['status'] = 'error';
                                                        $response['message'] = 'Timings not available';
                                                    }
                                                }
                                            }
                                        } else {
                                            $response['status'] = 'error';
                                            $response['message'] = 'Minimum Stay should be equal or greater than ' . $space->min_hour_stays . ' hours.';
                                        }
                                    } else {
                                        $response['status'] = 'error';
                                        $response['message'] = 'To date should be greater than from date.';
                                    }
                                } else {
                                    $response['status'] = 'error';
                                    $response['message'] = 'Start Date should not be less than now.';
                                }
                            } else {
                                $response['status'] = 'pending';
                                $response['step'] = '0x3';
                            }
                        } else {
                            $response['status'] = 'error';
                            $response['message'] = 'Time State is incorrect';
                        }
                    }

                    $priceInfo = CodeHelper::getSpacePrice($space, $response['start_time'], $response['end_time']);

                    $response['priceInfo'] = $priceInfo;
                    $response['price'] = $priceInfo['price'];
                    $response['priceFormatted'] = CodeHelper::formatPrice($response['price']);
                }
            }
        }

        return response()->json($response);
    }

    public function loadDates(Request $request)
    {
        $rules = [
            'id' => 'required',
            'start' => 'required',
            'end' => 'required',
        ];
        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $space = $this->spaceClass::find($request->query('id'));
        if (empty($space)) {
            return $this->sendError(__('Space not found'));
        }
        $is_single = $request->query('for_single');
        $query = $this->spaceDateClass::query();
        $query->where('target_id', $request->query('id'));
        $query->where('start_date', '>=', date('Y-m-d H:i:s', strtotime($request->query('start'))));
        $query->where('end_date', '<=', date('Y-m-d H:i:s', strtotime($request->query('end'))));
        $rows = $query->take(100)->get();
        $allDates = [];
        $period = periodDate($request->input('start'), $request->input('end'));
        foreach ($period as $dt) {
            $i = $dt->getTimestamp();
            $date = [
                'id' => rand(0, 999),
                'active' => 0,
                'price' => (!empty($space->sale_price) and $space->sale_price > 0 and $space->sale_price < $space->price) ? $space->sale_price : $space->price,
                'is_instant' => $space->is_instant,
                'is_default' => true,
                'textColor' => '#2791fe'
            ];
            if (!$is_single) {
                $date['price_html'] = format_money_main($date['price']);
            } else {
                $date['price_html'] = format_money($date['price']);
            }
            $date['title'] = $date['event'] = $date['price_html'];
            $date['start'] = $date['end'] = date('Y-m-d', $i);
            if ($space->default_state) {
                $date['active'] = 1;
            } else {
                $date['title'] = $date['event'] = __('Blocked');
                $date['backgroundColor'] = 'orange';
                $date['borderColor'] = '#fe2727';
                $date['classNames'] = ['blocked-event'];
                $date['textColor'] = '#fe2727';
            }
            $allDates[date('Y-m-d', $i)] = $date;
        }
        /*if(!empty($rows))
        {
            foreach ($rows as $row)
            {
                $row->start = date('Y-m-d',strtotime($row->start_date));
                $row->end = date('Y-m-d',strtotime($row->start_date));
                $row->textColor = '#2791fe';
                $price = $row->price;
                if(empty($price)){
                    $price = (!empty($space->sale_price) and $space->sale_price > 0 and $space->sale_price < $space->price) ? $space->sale_price : $space->price;
                }
	            if(!$is_single){
		            $row->title = $row->event = format_money_main($price);
	            }else{
		            $row->title = $row->event = format_money($price);

	            }
                $row->price = $price;
                if(!$row->active)
                {
                    $row->title = $row->event = __('Blocked');
                    $row->backgroundColor = '#fe2727';
                    $row->classNames = ['blocked-event'];
                    $row->textColor = '#fe2727';
                    $row->active = 0;
                }else{
                    $row->classNames = ['active-event'];
                    $row->active = 1;
                    if($row->is_instant){
                        $row->title = '<i class="fa fa-bolt"></i> '.$row->title;
                    }
                }
                $allDates[date('Y-m-d',strtotime($row->start_date))] = $row->toArray();
            }
        }
        $bookings = $this->bookingClass::getBookingInRanges($space->id,$space->type,$request->query('start'),$request->query('end'));
        if(!empty($bookings))
        {
            foreach ($bookings as $booking){
                $period = periodDate($booking->start_date,$booking->end_date);
                foreach ($period as $dt){
                    $i = $dt->getTimestamp();
                    if(isset($allDates[date('Y-m-d',$i)])){
                        $allDates[date('Y-m-d',$i)]['active'] = 0;
                        $allDates[date('Y-m-d',$i)]['event'] = __('Full Book');
                        $allDates[date('Y-m-d',$i)]['title'] = __('Full Book');
                        $allDates[date('Y-m-d',$i)]['classNames'] = ['full-book-event'];
                    }
                }
            }
        }
	    if(!empty($space->ical_import_url)){
		    $startDate = $request->query('start');
		    $endDate = $request->query('end');
		    $timezone = setting_item('site_timezone',config('app.timezone'));
		    try {
			    $icalevents   =  new Ical($space->ical_import_url,[
				    'defaultTimeZone'=>$timezone
			    ]);
			    $eventRange  = $icalevents->eventsFromRange($startDate,$endDate);
			    if(!empty($eventRange)){
				    foreach ($eventRange as $item=>$value){
					    if(!empty($eventStart = $value->dtstart_array[2]) and !empty($eventEnd = $value->dtend_array[2])){
						    for($i = $eventStart; $i <= $eventEnd; $i+= DAY_IN_SECONDS){
							    if(isset($allDates[date('Y-m-d',$i)])){
								    $allDates[date('Y-m-d',$i)]['active'] = 0;
								    $allDates[date('Y-m-d',$i)]['event'] = __('Full Book');
								    $allDates[date('Y-m-d',$i)]['title'] = __('Full Book');
								    $allDates[date('Y-m-d',$i)]['classNames'] = ['full-book-event'];
							    }
						    }
					    }
				    }
			    }
		    }catch (\Exception $exception){
			    return $this->sendError($exception->getMessage());
		    }
	    }*/
        $data = array_values($allDates);
        return response()->json($data);
    }

    public function store(Request $request)
    {

        $request->validate([
            'target_id' => 'required',
            'start_date' => 'required',
            'end_date' => 'required'
        ]);

        $space = $this->spaceClass::find($request->input('target_id'));
        $target_id = $request->input('target_id');

        if (empty($space)) {
            return $this->sendError(__('Space not found'));
        }

        if (!$this->hasPermission('space_manage_others')) {
            if ($space->create_user != Auth::id()) {
                return $this->sendError("You do not have permission to access it");
            }
        }

        $postData = $request->input();
        //        for($i = strtotime($request->input('start_date')); $i <= strtotime($request->input('end_date')); $i+= DAY_IN_SECONDS)
        //        {
        $period = periodDate($request->input('start_date'), $request->input('end_date'));
        foreach ($period as $dt) {
            $date = $this->spaceDateClass::where('start_date', $dt->format('Y-m-d'))->where('target_id', $target_id)->first();

            if (empty($date)) {
                $date = new $this->spaceDateClass();
                $date->target_id = $target_id;
            }
            $postData['start_date'] = $dt->format('Y-m-d H:i:s');
            $postData['end_date'] = $dt->format('Y-m-d H:i:s');


            $date->fillByAttr([
                'start_date', 'end_date', 'price',
                //                'max_guests','min_guests',
                'is_instant', 'active',
                //                'enable_person','person_types'
            ], $postData);

            $date->save();
        }

        return $this->sendSuccess([], __("Update Success"));
    }
}