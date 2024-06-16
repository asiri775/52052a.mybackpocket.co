<?php

namespace Modules\User\Controllers;

use App\Helpers\CodeHelper;
use App\Helpers\Constants;
use App\Http\Controllers\Controller;
use App\Models\CreditCoupons;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use DataTables;
use Modules\Coupon\Models\CouponBookings;
use Modules\Review\Models\Review;
use Modules\Space\Models\Space;

class ReportController extends Controller
{

    public function summary()
    {
        $userId = auth()->user()->id;
        $type = $_GET['type'] ?? 'all';
        $startDate = CodeHelper::getFirstOldDayOfVendor($userId);
        $endDate = date(Constants::PHP_DATE_FORMAT);
        switch ($type) {
            case '1m':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 months'));
                break;
            case '6m':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-6 months'));
                break;
            case '1y':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 years'));
                break;
            default:
                $endDate = date(Constants::PHP_DATE_FORMAT, strtotime("+10 years"));
                break;
        }

        $grossVal = Booking::where('vendor_id', $userId)->where('start_date', '>=', $startDate)
            ->where('start_date', '<=', $endDate)->whereIn('status', array_keys(Constants::NON_CANCELLED_BOOKING_STATUES))->sum('payable_amount');
        if ($grossVal == null) {
            $grossVal = 0;
        }
        $siteFee = Booking::where('vendor_id', $userId)->where('start_date', '>=', $startDate)
            ->where('start_date', '<=', $endDate)->whereIn('status', array_keys(Constants::NON_CANCELLED_BOOKING_STATUES))->sum('admin_amount');
        if ($siteFee == null) {
            $siteFee = 0;
        }

        $completedBookings = Booking::where('vendor_id', $userId)->where('start_date', '>=', $startDate)
            ->where('start_date', '<=', $endDate)->whereIn('status', array_keys(Constants::NON_CANCELLED_BOOKING_STATUES))
            ->selectRaw('SUM(payable_amount) as totalAmount, COUNT(*) total')->first();

        $cancelledBookings = Booking::where('vendor_id', $userId)->where('start_date', '>=', $startDate)
            ->where('start_date', '<=', $endDate)->where('status', Constants::BOOKING_STATUS_CANCELLED)
            ->selectRaw('SUM(payable_amount) as totalAmount, COUNT(*) total')->first();

        $newClients = Booking::where('vendor_id', $userId)->where('start_date', '>=', $startDate)
            ->where('start_date', '<=', $endDate)->whereIn('status', array_keys(Constants::NON_CANCELLED_BOOKING_STATUES))
            ->where('object_model', 'space')->selectRaw('count(*) as totalBooked')->groupBy('customer_id')
            ->having('totalBooked', '<', 2)->get()->count();
        if ($newClients == null) {
            $newClients = 0;
        }

        $repeatClients = Booking::where('vendor_id', $userId)->where('start_date', '>=', $startDate)
            ->where('start_date', '<=', $endDate)->whereIn('status', array_keys(Constants::NON_CANCELLED_BOOKING_STATUES))
            ->where('object_model', 'space')->selectRaw('count(*) as totalBooked')->groupBy('customer_id')
            ->having('totalBooked', '>', 1)->get()->count();
        if ($repeatClients == null) {
            $repeatClients = 0;
        }

        $totalClients = $newClients + $repeatClients;

        $newClientsPer = $repeatClientsPer = 0;
        if ($newClients > 0 && $totalClients > 0) {
            $newClientsPer = round((($newClients * 100) / $totalClients), 2);
        }
        if ($repeatClients > 0 && $totalClients > 0) {
            $repeatClientsPer = round((($repeatClients * 100) / $totalClients), 2);
        }

        $vacancy = Space::where('create_user', $userId)->where('total_bookings', '<=', 0)->count();
        if ($vacancy == null) {
            $vacancy = 0;
        }

        $vacancyText = '<tr><td colspan="3">No Data Found</td></tr>';

        if ($vacancy > 0) {
            $spaces = Space::where('create_user', $userId)
                ->where('total_bookings', '<=', 0)
                ->orderBy('created_at', 'ASC')
                ->limit(5)->get();
            if ($spaces !== null) {
                $vacancyText = '';
                foreach ($spaces as $space) {
                    $vacData = CodeHelper::findVacanciesOfSpace($space, $startDate);
                    $vacancyText .= '<tr> 
                    <td style="text-align:left;max-width:100px;overflow:hidden;"><a target="_blank" href="' . route('space.vendor.edit', [$space->id]) . '">' . $space->title . '</a></td>
                    <td>' . $vacData['vacant'] . '</td>
                    <td>' . $vacData['percentage'] . '%</td>
                    </tr>';
                }
            }
            if ($vacancy > 5) {
                $vacancyText .= '<tr><td colspan="3"><a target="_blank" href="' . route('user.reports.vacant') . '" class="btn btn-primary btn-sm">View All</a></td></tr>';
            }
        }

        $html = '<div class="row item-table">
            <div class="col-sm-12 p-3 m-t-15 m-b-10">
                <div class="card">
                    <table class="table text-nowrap table-borderless">
                        <thead>
                            <tr class="no-style">
                                <th style="width:50%">Earnings</th>
                                <th style="width:35%;text-align: center;"></th>
                                <th style="width:15%;text-align: right;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Gross Revenue</td>
                                <td class="text-center"></td>
                                <td class="text-right">' . CodeHelper::printAmount($grossVal) . '</td>
                            </tr>
                            <tr class="border-bottom">
                                <td>Less: Site Fees</td>
                                <td class="text-center"></td>
                                <td class="text-right">' . CodeHelper::printAmount($siteFee) . '</td>
                            </tr>
                            <tr>
                                <td class="bold">Net Earnings</td>
                                <td class="text-center"></td>
                                <td class="text-right bold">' . CodeHelper::printAmount($grossVal - $siteFee) . '</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card">
                    <table class="table text-nowrap table-borderless">
                        <thead>
                            <tr class="no-style">
                                <th style="width:50%">Bookings</th>
                                <th style="width:35%;text-align: center;">QTY</th>
                                <th style="width:15%;text-align: right;">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Completed</td>
                                <td class="text-center">' . CodeHelper::printNumber($completedBookings['total']) . '</td>
                                <td class="text-right">' . CodeHelper::printAmount($completedBookings['totalAmount']) . '</td>
                            </tr>
                            <tr>
                                <td>Cancelled</td>
                                <td class="text-center">' . CodeHelper::printNumber($cancelledBookings['total']) . '</td>
                                <td class="text-right">' . CodeHelper::printAmount($cancelledBookings['totalAmount']) . '</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card">
                    <table class="table text-nowrap table-borderless">
                        <thead>
                            <tr class="no-style">
                                <th style="width:50%">Vacancy</th>
                                <th style="width:35%;text-align: center;"># of Days</th>
                                <th style="width:15%;text-align: right;">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            ' . $vacancyText . '
                        </tbody>
                    </table>
                </div>
                <div class="card">
                    <table class="table text-nowrap table-borderless">
                        <!--h3 class="tr-title p-l-10">Clients</h3-->
                        <thead>
                            <tr class="no-style">
                                <th style="width:50%">Clients</th>
                                <th style="width:35%;text-align: center;">QTY</th>
                                <th style="width:15%;text-align: right;">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>New Clients</td>
                                <td class="text-center">' . CodeHelper::printNumber($newClients) . '</td>
                                <td class="text-right">' . CodeHelper::printNumber($newClientsPer) . '%</td>
                            </tr>
                            <tr>
                                <td>Repeat Clients</td>
                                <td class="text-center">' . CodeHelper::printNumber($repeatClients) . '</td>
                                <td class="text-right">' . CodeHelper::printNumber($repeatClientsPer) . '%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>';
        return $html;
    }

    public function promoCodes()
    {
        $userId = auth()->user()->id;
        $type = $_POST['type'] ?? 'all';
        $startDate = CodeHelper::getFirstOldDayOfVendor($userId);
        $endDate = date(Constants::PHP_DATE_FORMAT);
        switch ($type) {
            case '1m':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 months'));
                break;
            case '6m':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-6 months'));
                break;
            case '1y':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 years'));
                break;
            default:
                $endDate = date(Constants::PHP_DATE_FORMAT, strtotime("+10 years"));
                break;
        }

        // $bookingIds = Booking::where('vendor_id', $userId)->select('id')->pluck('id')->toArray();
        // if ($bookingIds == null) {
        //     $bookingIds = [-1];
        // }

        $query = CouponBookings::where('object_model', 'space')
            ->whereIn('booking_status', array_keys(Constants::NON_CANCELLED_BOOKING_STATUES))
            ->where('created_at', '>=', $startDate)->where('created_at', '<=', $endDate)
            ->orderBy('created_at', 'DESC');

        return DataTables::eloquent($query)
            ->addColumn('checkboxes', function ($model) {
                $select = '<input type="checkbox" name="checkbox[]" value="' . $model->id . '">';
                return $select;
            })
            ->addColumn('coupon_amount', function ($model) {
                return CodeHelper::printAmount($model->coupon_amount);
            })
            ->rawColumns(['checkboxes'])
            ->make(true);
    }

    public function saleBySpace()
    {
        $userId = auth()->user()->id;
        $type = $_POST['type'] ?? 'all';
        $startDate = CodeHelper::getFirstOldDayOfVendor($userId);
        $endDate = date(Constants::PHP_DATE_FORMAT);
        switch ($type) {
            case '1m':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 months'));
                break;
            case '6m':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-6 months'));
                break;
            case '1y':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 years'));
                break;
            default:
                $endDate = date(Constants::PHP_DATE_FORMAT, strtotime("+10 years"));
                break;
        }

        $spaceIds = Booking::where('vendor_id', $userId)
            ->where('start_date', '>=', $startDate)->where('start_date', '<=', $endDate)
            ->select('object_id')
            ->whereIn('status', array_keys(Constants::NON_CANCELLED_BOOKING_STATUES))
            ->pluck('object_id')->toArray();
        if ($spaceIds == null) {
            $spaceIds = [-1];
        }

        $query = Space::whereIn('id', $spaceIds);

        return DataTables::eloquent($query)
            ->addColumn('title', function ($model) {
                return '<a style="float:left;" target="_blank" href="' . route('space.vendor.edit', [$model->id]) . '">' . $model->title . '</a>';
            })
            ->addColumn('checkboxes', function ($model) {
                $select = '<input type="checkbox" name="checkbox[]" value="' . $model->id . '">';
                return $select;
            })
            ->addColumn('bookings', function ($model) use ($startDate, $endDate) {
                $bookings = Booking::where('object_id', $model->id)
                    ->where('start_date', '>=', $startDate)->where('start_date', '<=', $endDate)
                    ->count();
                if ($bookings == null) {
                    $bookings = 0;
                }
                return CodeHelper::printNumber($bookings);
            })
            ->addColumn('earnings', function ($model) use ($startDate, $endDate) {
                $bookings = Booking::where('object_id', $model->id)
                    ->where('start_date', '>=', $startDate)->where('start_date', '<=', $endDate)
                    ->sum('host_amount');
                if ($bookings == null) {
                    $bookings = 0;
                }
                return CodeHelper::printAmount($bookings);
            })
            ->rawColumns(['checkboxes', 'title'])
            ->make(true);
    }

    public function reviews()
    {
        $user = auth()->user();
        $userId = $user->id;
        $type = $_POST['type'] ?? 'all';
        $startDate = CodeHelper::getFirstOldDayOfVendor($userId);
        $endDate = date(Constants::PHP_DATE_FORMAT);
        switch ($type) {
            case '1m':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 months'));
                break;
            case '6m':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-6 months'));
                break;
            case '1y':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 years'));
                break;
            default:
                $endDate = date(Constants::PHP_DATE_FORMAT, strtotime("+10 years"));
                break;
        }


        $query = Review::query()->where(function ($query) use ($userId) {
            $query->where('review_to', $userId)
                ->orWhere('create_user', $userId);
        })->where('created_at', '>=', $startDate)->where('created_at', '<=', $endDate);

        return DataTables::eloquent($query)
            ->addColumn('checkboxes', function ($model) {
                $select = '<input type="checkbox" name="checkbox[]" value="' . $model->id . '">';
                return $select;
            })
            ->addColumn('idLink', function ($model) {
                return '<a target="_blank" href="' . route('user.single.booking.detail', [$model->reference_id]) . '">#' . $model->id . '</a>';
            })
            ->addColumn('title', function ($model) {
                return $model->title;
            })
            ->addColumn('content', function ($model) {
                return $model->content;
            })
            ->addColumn('rating', function ($model) {
                return $model->rate_number;
            })
            ->addColumn('date', function ($model) {
                return CodeHelper::formatDateTime($model->created_at);
            })
            ->addColumn('booking', function ($model) {
                return '<a target="_blank" href="' . route('user.single.booking.detail', [$model->reference_id]) . '">#' . $model->reference_id . '</a>';
            })
            ->addColumn('reviewByUser', function ($model) use ($user) {
                if ($model->create_user == $user->id) {
                    return 'You';
                }
                $reviewUser = User::where('id', $model->create_user)->first();
                if ($reviewUser != null) {
                    return '<a target="_blank" href="' . route('user.profile', [$reviewUser->user_name]) . '" class="d-flex align-items-center"> <span
                    class="avatar avatar-sm me-2 avatar-rounded"> <img
                        src="' . $reviewUser->getAvatarUrl() . '"
                        alt="img"></span>' . $reviewUser->getDisplayName() . '</a>';
                }
            })
            ->addColumn('status', function ($model) {
                return '<span class="badge badge-' . $model->status . '">' . $model->status . '</span>';
            })
            ->rawColumns(['checkboxes', 'status', 'reviewByUser', 'actions', 'idLink', 'booking', 'totalBookings', 'earnings', 'title'])
            ->make(true);
    }

    public function topClients()
    {
        $user = auth()->user();
        $userId = $user->id;
        $type = $_POST['type'] ?? 'all';
        $startDate = CodeHelper::getFirstOldDayOfVendor($userId);
        $endDate = date(Constants::PHP_DATE_FORMAT);
        switch ($type) {
            case '1m':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 months'));
                break;
            case '6m':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-6 months'));
                break;
            case '1y':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 years'));
                break;
            default:
                $endDate = date(Constants::PHP_DATE_FORMAT, strtotime("+10 years"));
                break;
        }

        $repeatClientIds = Booking::where('vendor_id', $userId)->where('start_date', '>=', $startDate)
            ->where('start_date', '<=', $endDate)->whereIn('status', array_keys(Constants::NON_CANCELLED_BOOKING_STATUES))
            ->where('object_model', 'space')->groupBy('customer_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(15)
            ->pluck('customer_id')->toArray();
        if ($repeatClientIds == null) {
            $repeatClientIds = [-1];
        }

        $query = User::query()->whereIn('id', $repeatClientIds)
            ->orderByRaw(DB::raw("FIELD(id, " . implode(',', $repeatClientIds) . ")"));

        return DataTables::eloquent($query)
            ->addColumn('checkboxes', function ($model) {
                $select = '<input type="checkbox" name="checkbox[]" value="' . $model->id . '">';
                return $select;
            })
            ->addColumn('clientName', function ($client) {
                $title = $client->name . ' (#' . $client->id . ')';
                return '<a target="_blank" href="' . route('user.profile.publicProfile', $client->id) . '" class="d-flex align-items-center fw-semibold"> <span class="avatar avatar-sm me-2 avatar-rounded"> <img src="' . $client->getAvatarUrl() . '" alt="img"></span>' . clean($title) . '</a>';
            })
            ->addColumn('lastBooking', function ($client) use ($userId) {
                $lastBooking = Booking::orderBy('id', 'DESC')->where('vendor_id', '=', $userId)
                    ->where('customer_id', $client->id)->orderBy('id', 'DESC')->first();
                if ($lastBooking != null) {
                    return '<a target="_blank" href="' . route('user.single.booking.detail', $lastBooking->id) . '">#' . $lastBooking->id . ': ' . date('M d, Y', strtotime($lastBooking->start_date)) . '</a>';
                }
                return '-';
            })
            ->addColumn('bookings', function ($client) use ($userId) {
                $totalBookings = Booking::orderBy('id', 'DESC')->where('vendor_id', '=', $userId)
                    ->where('customer_id', $client->id)->count();
                if ($totalBookings == null) {
                    $totalBookings = 0;
                }
                return $totalBookings;
            })
            ->addColumn('revenue', function ($client) use ($userId) {
                $totalRevenue = Booking::orderBy('id', 'DESC')->where('vendor_id', '=', $userId)
                    ->where('customer_id', $client->id)->sum('host_amount');
                if ($totalRevenue == null) {
                    $totalRevenue = 0;
                }
                return CodeHelper::printAmount($totalRevenue);
            })
            ->rawColumns(['clientName', 'lastBooking'])
            ->make(true);
    }
    public function revenueAnalytics()
    {
        $user = auth()->user();
        $userId = $user->id;
        $type = $_GET['type'] ?? 'all';

        $startDate = CodeHelper::getFirstOldDayOfVendor($userId);
        $endDate = date(Constants::PHP_DATE_FORMAT);

        $startDateFormat = 'Y-01-01 00:00:00';
        $endDateFormat = 'Y-12-31 23:59:59';

        $dateRanges = ['-5 years', '-4 years', '-3 years', '-2 years', '-1 year', '-0 year'];
        $chartType = 'year';

        switch ($type) {
            case '1m':
                $dateRanges = ['-28 days', '-21 days', '-14 days', '-7 days'];
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 months'));
                $startDateFormat = 'Y-m-d 00:00:00';
                $endDateFormat = 'Y-m-d 23:59:59';
                $chartType = 'category';
                break;
            case '6m':
                $dateRanges = ['-5 months', '-4 months', '-3 months', '-2 months', '-1 month', '-0 month'];
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-6 months'));
                $startDateFormat = 'Y-m-01 00:00:00';
                $endDateFormat = 'Y-m-t 23:59:59';
                $chartType = 'month';
                break;
            case '1y':
                $dateRanges = ['-11 months', '-10 months', '-9 months', '-8 months', '-7 months', '-6 months', '-5 months', '-4 months', '-3 months', '-2 months', '-1 month', '-0 month'];
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 years'));
                $startDateFormat = 'Y-m-01 00:00:00';
                $endDateFormat = 'Y-m-t 23:59:59';
                $chartType = 'month';
                break;
            default:
                // $endDate = date(Constants::PHP_DATE_FORMAT, strtotime("+10 years"));
                break;
        }

        $data = [];

        $totalData = Booking::where('vendor_id', $userId)
            ->where('start_date', '>=', $startDate)->where('start_date', '<=', $endDate)
            ->whereIn('status', array_keys(Constants::NON_CANCELLED_BOOKING_STATUES))
            ->selectRaw('COUNT(*) as totalBookings, SUM(`host_amount`) as hostTotal')->first()->toArray();

        $vacancyData = CodeHelper::vacanciesOfVendor($userId, $startDate, $endDate);

        $data['totalBookings'] = $totalData['totalBookings'];
        $data['earnings'] = CodeHelper::printAmount($totalData['hostTotal']);
        $data['vacancy'] = $vacancyData['vacant'];

        $chartData = [
            'dates' => [],
            'bookings' => [],
            'earnings' => [],
            'vacancy' => [],
            'categories' => [],
            'type' => $chartType
        ];

        $data['dateRanges'] = $dateRanges;
        $data['dates'] = [
            'start' => $startDate,
            'end' => $endDate
        ];

        foreach ($dateRanges as $dateRange) {
            $startDate = date($startDateFormat, strtotime($dateRange));
            $endDate = date($endDateFormat, strtotime($dateRange));

            if ($type === '1m') {
                $startDateTime = strtotime("+7 days", strtotime($startDate));
                $endDate = date($endDateFormat, $startDateTime);
                $startDate = strtotime("+1 day", strtotime($startDate));
                $startDate = date($startDateFormat, $startDate);
            }

            if (strtotime($endDate) > time()) {
                $endDate = date($endDateFormat);
            }

            $totalData = Booking::where('vendor_id', $userId)
                ->where('start_date', '>=', $startDate)->where('start_date', '<=', $endDate)
                ->whereIn('status', array_keys(Constants::NON_CANCELLED_BOOKING_STATUES))
                ->selectRaw('COUNT(*) as totalBookings, SUM(`host_amount`) as hostTotal')->first()->toArray();

            $vacancyData = CodeHelper::vacanciesOfVendor($userId, $startDate, $endDate);

            $chartData['dates'][] = $startDate . ' - ' . $endDate;
            $chartData['bookings'][] = $totalData['totalBookings'];
            $chartData['earnings'][] = $totalData['hostTotal'];
            $chartData['vacancy'][] = $vacancyData['vacant'];

            switch ($chartType) {
                case 'category':
                    $chartData['categories'][] = date('Y-m-d', strtotime($startDate)) . " - " . date('Y-m-d', strtotime($endDate));
                    break;
                case 'month':
                    $chartData['categories'][] = date('M', strtotime($startDate));
                    break;
                case 'year':
                    $chartData['categories'][] = date('Y', strtotime($startDate));
                    break;
            }
        }

        $data['chart'] = $chartData;

        return response()->json($data);
    }
    public function customSatisfcation()
    {
        $user = auth()->user();
        $userId = $user->id;
        $type = $_GET['type'] ?? 'all';
        $totalRatings = 0;
        $startDate = CodeHelper::getFirstOldDayOfVendor($userId);
        $endDate = date(Constants::PHP_DATE_FORMAT);
        switch ($type) {
            case '1m':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 months'));
                break;
            case '6m':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-6 months'));
                break;
            case '1y':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 years'));
                break;
            default:
                $endDate = date(Constants::PHP_DATE_FORMAT, strtotime("+10 years"));
                break;
        }
        $spaceIds = Booking::where('vendor_id', $userId)
            ->where('start_date', '>=', $startDate)->where('start_date', '<=', $endDate)
            ->select('object_id')->pluck('object_id')->toArray();
        if ($spaceIds == null) {
            $spaceIds = [-1];
        }
        $data = CodeHelper::getRatingsBySpaces($spaceIds);
        foreach ($data as $item) {
            $totalRatings += $item['totalRatings'];
        }
        return response()->json([
            'data' => $data,
            'totalRatings' => $totalRatings
        ]);
    }

    public function bookingAnalytics()
    {
        $user = auth()->user();
        $userId = $user->id;
        $type = $_GET['type'] ?? 'all';
        $startDate = CodeHelper::getFirstOldDayOfVendor($userId);
        $endDate = date(Constants::PHP_DATE_FORMAT);
        switch ($type) {
            case '1m':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 months'));
                break;
            case '6m':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-6 months'));
                break;
            case '1y':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 years'));
                break;
            default:
                $endDate = date(Constants::PHP_DATE_FORMAT, strtotime("+10 years"));
                break;
        }
        $spaceIds = Space::where('create_user', $userId)
            ->where('created_at', '>=', $startDate)->where('created_at', '<=', $endDate)
            ->select('id')->pluck('id')->toArray();
        if ($spaceIds == null) {
            $spaceIds = [-1];
        }
        $data = CodeHelper::getAnalyticsBySpaces($spaceIds);
        return response()->json($data);
    }

    public function alerts()
    {
        $user = auth()->user();
        $userId = $user->id;
        $type = $_GET['type'] ?? 'all';
        $startDate = CodeHelper::getFirstOldDayOfVendor($userId);
        $endDate = date(Constants::PHP_DATE_FORMAT);
        switch ($type) {
            case '1m':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 months'));
                break;
            case '6m':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-6 months'));
                break;
            case '1y':
                $startDate = date(Constants::PHP_DATE_FORMAT, strtotime('-1 years'));
                break;
            default:
                $endDate = date(Constants::PHP_DATE_FORMAT, strtotime("+10 years"));
                break;
        }
    }

    public function overview()
    {
        $user = auth()->user();
        $userId = $user->id;

        $startDateO = CodeHelper::getFirstOldDayOfVendor($userId);

        $from = $_GET['from'] ?? date('Y-m-d', strtotime($startDateO));
        $to = $_GET['to'] ?? date('Y-m-d', strtotime("+10 years"));

        if (strtotime($from) < strtotime($startDateO)) {
            $from = $startDateO;
        }

        $startDate = date(Constants::PHP_DATE_FORMAT, strtotime($from));
        $endDate = date(Constants::PHP_DATE_FORMAT, strtotime($to));

        $newClients = Booking::where('vendor_id', $userId)->where('start_date', '>=', $startDate)
            ->where('start_date', '<=', $endDate)->whereIn('status', array_keys(Constants::NON_CANCELLED_BOOKING_STATUES))
            ->where('object_model', 'space')->selectRaw('count(*) as totalBooked')->groupBy('customer_id')
            ->having('totalBooked', '<', 2)->get()->count();
        if ($newClients == null) {
            $newClients = 0;
        }

        $repeatClients = Booking::where('vendor_id', $userId)->where('start_date', '>=', $startDate)
            ->where('start_date', '<=', $endDate)->whereIn('status', array_keys(Constants::NON_CANCELLED_BOOKING_STATUES))
            ->where('object_model', 'space')->selectRaw('count(*) as totalBooked')->groupBy('customer_id')
            ->having('totalBooked', '>', 1)->get()->count();
        if ($repeatClients == null) {
            $repeatClients = 0;
        }

        $totalClients = $newClients + $repeatClients;

        $newClientsPer = $repeatClientsPer = 0;
        if ($newClients > 0 && $totalClients > 0) {
            $newClientsPer = round((($newClients * 100) / $totalClients), 2);
        }
        if ($repeatClients > 0 && $totalClients > 0) {
            $repeatClientsPer = round((($repeatClients * 100) / $totalClients), 2);
        }

        $bookings = Booking::where('vendor_id', $userId)->where('start_date', '>=', $startDate)
            ->where('start_date', '<=', $endDate)->whereIn('status', array_keys(Constants::NON_CANCELLED_BOOKING_STATUES))
            ->count();
        if ($bookings == null) {
            $bookings = 0;
        }

        $totalBookings = Booking::where('vendor_id', $userId)->whereIn('status', array_keys(Constants::NON_CANCELLED_BOOKING_STATUES))
            ->count();
        if ($totalBookings == null) {
            $totalBookings = 0;
        }

        $bookingsPer = 0;
        if ($totalBookings > 0 && $bookings > 0) {
            $bookingsPer = round($bookings * 100 / $totalBookings, 2);
        }

        $earnings = Booking::where('vendor_id', $userId)->where('start_date', '>=', $startDate)
            ->where('start_date', '<=', $endDate)->whereIn('status', array_keys(Constants::NON_CANCELLED_BOOKING_STATUES))
            ->sum('host_amount');
        if ($earnings == null) {
            $earnings = 0;
        }

        $totalEarnings = Booking::where('vendor_id', $userId)
            ->whereIn('status', array_keys(Constants::NON_CANCELLED_BOOKING_STATUES))
            ->sum('host_amount');
        if ($totalEarnings == null) {
            $totalEarnings = 0;
        }

        $earningsPer = 0;
        if ($totalEarnings > 0 && $earnings > 0) {
            $earningsPer = round($earnings * 100 / $totalEarnings, 2);
        }

        $vacancyData = CodeHelper::vacanciesOfVendor($userId, $startDate, $endDate);

        return response()->json([
            'newClients' => CodeHelper::printNumber($newClients),
            'newClientsPer' => CodeHelper::printNumber($newClientsPer) . "%",
            'repeatClients' => CodeHelper::printNumber($repeatClients),
            'repeatClientsPer' => CodeHelper::printNumber($repeatClientsPer) . "%",
            'vacancyData' => $vacancyData,
            'vacancy' => CodeHelper::printNumber($vacancyData['vacant']),
            'vacancyPercentage' => CodeHelper::printNumber($vacancyData['percentage']) . "%",
            'bookings' => CodeHelper::printNumber($bookings),
            'bookingsPercentage' => CodeHelper::printNumber($bookingsPer) . "%",
            'earnings' => CodeHelper::printAmount($earnings),
            'earningsPercentage' => CodeHelper::printAmount($earningsPer) . "%"
        ]);

    }

}
